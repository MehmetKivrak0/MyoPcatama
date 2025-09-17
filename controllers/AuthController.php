<?php

require_once '../config/db.php';

class AuthController {
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            throw new Exception("Veritabanı bağlantısı kurulamadı: " . $e->getMessage());
        }
    }
    
    /**
     * Kullanıcı giriş kontrolü
     */
    public function login($full_name, $password) {
        try {
            // Giriş parametrelerini kontrol et
            if (empty($full_name) || empty($password)) {
                return [
                    'type' => 'error',
                    'message' => 'Kullanıcı adı ve şifre boş olamaz!'
                ];
            }
            
            // Veritabanından kullanıcı bilgilerini getir
            $sql = "SELECT user_id, full_name, password_hash, created_at FROM myopc_users WHERE full_name = ? LIMIT 1";
            $user = $this->db->fetchOne($sql, [$full_name]);
            
            if (!$user) {
                // Güvenlik için aynı sürede yanıt ver
                $this->simulatePasswordCheck();
                return [
                    'type' => 'error',
                    'message' => 'Kullanıcı adı veya şifre hatalı!'
                ];
            }
            
            // Şifre kontrolü
            $isPasswordValid = $this->verifyPassword($password, $user['password_hash'], $user['user_id']);
            
            if ($isPasswordValid) {
                // Başarılı giriş - session bilgilerini kaydet
                $this->createUserSession($user);
                
                // Giriş logunu kaydet
                $this->logUserLogin($user['user_id']);
                
                return [
                    'type' => 'success',
                    'message' => 'Giriş başarılı! Yönlendiriliyorsunuz...'
                ];
            } else {
                return [
                    'type' => 'error',
                    'message' => 'Kullanıcı adı veya şifre hatalı!'
                ];
            }
            
        } catch (Exception $e) {
            // Hata logunu kaydet
            error_log("AuthController Login Error: " . $e->getMessage());
            
            return [
                'type' => 'error',
                'message' => 'Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.'
            ];
        }
    }
    
    /**
     * Şifre doğrulama ve gerekirse güncelleme
     */
    private function verifyPassword($password, $storedHash, $userId) {
        // Önce password_verify ile hash kontrolü yap
        if (password_verify($password, $storedHash)) {
            return true;
        }
        
        // Eğer hash kontrolü başarısız olursa, düz metin kontrolü yap (eski sistemler için)
        if ($password === $storedHash) {
            // Plain text şifreyi hash'le ve güncelle
            $this->updatePasswordHash($password, $userId);
            return true;
        }
        
        return false;
    }
    
    /**
     * Plain text şifreyi hash'leyip güncelle
     */
    private function updatePasswordHash($password, $userId) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateSql = "UPDATE myopc_users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
            $this->db->execute($updateSql, [$hashedPassword, $userId]);
        } catch (Exception $e) {
            error_log("Password hash update error: " . $e->getMessage());
        }
    }
    
    /**
     * Kullanıcı session'ını oluştur
     */
    private function createUserSession($user) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['full_name'] = $user['full_name']; // dashboard.php için uyumluluk
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        $_SESSION['session_token'] = bin2hex(random_bytes(32)); // Güvenlik için session token
        
        // Session güvenliği için
        session_regenerate_id(true);
    }
    
    /**
     * Giriş logunu kaydet
     */
    private function logUserLogin($userId) {
        try {
            // Eğer login_logs tablosu varsa giriş logunu kaydet
            $logSql = "INSERT INTO myopc_login_logs (user_id, login_time, ip_address, user_agent) 
                       VALUES (?, NOW(), ?, ?)";
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $this->db->execute($logSql, [$userId, $ipAddress, $userAgent]);
        } catch (Exception $e) {
            // Log tablosu yoksa sessizce devam et
            error_log("Login log error: " . $e->getMessage());
        }
    }
    
    /**
     * Güvenlik için sahte şifre kontrolü (timing attack önleme)
     */
    private function simulatePasswordCheck() {
        // Gerçek şifre kontrolü ile aynı sürede işlem yap
        password_verify('dummy', '$2y$10$dummy.hash.to.simulate.timing');
    }
    
    /**
     * Kullanıcı çıkışı
     */
    public function logout() {
        try {
            // Çıkış logunu kaydet
            if (isset($_SESSION['user_id'])) {
                $this->logUserLogout($_SESSION['user_id']);
            }
            
            // Session'ı temizle
            $_SESSION = array();
            
            // Session cookie'sini sil
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // Session'ı yok et
            session_destroy();
            
            return [
                'type' => 'success',
                'message' => 'Güvenli çıkış yapıldı!'
            ];
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return [
                'type' => 'error',
                'message' => 'Çıkış işlemi sırasında hata oluştu.'
            ];
        }
    }
    
    /**
     * Kullanıcının giriş yapmış olup olmadığını kontrol et
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true && 
               isset($_SESSION['user_id']) && 
               isset($_SESSION['session_token']);
    }
    
    /**
     * Mevcut kullanıcı bilgilerini getir
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $sql = "SELECT user_id, full_name, created_at FROM myopc_users WHERE user_id = ? LIMIT 1";
            return $this->db->fetchOne($sql, [$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Şifre değiştirme
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Mevcut şifreyi kontrol et
            $sql = "SELECT password_hash FROM myopc_users WHERE user_id = ? LIMIT 1";
            $user = $this->db->fetchOne($sql, [$userId]);
            
            if (!$user || !$this->verifyPassword($currentPassword, $user['password_hash'], $userId)) {
                return [
                    'type' => 'error',
                    'message' => 'Mevcut şifre hatalı!'
                ];
            }
            
            // Yeni şifreyi hash'le ve güncelle
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE myopc_users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?";
            $this->db->execute($updateSql, [$hashedPassword, $userId]);
            
            return [
                'type' => 'success',
                'message' => 'Şifre başarıyla değiştirildi!'
            ];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return [
                'type' => 'error',
                'message' => 'Şifre değiştirme işlemi başarısız!'
            ];
        }
    }
    
    /**
     * Çıkış logunu kaydet
     */
    private function logUserLogout($userId) {
        try {
            $logSql = "INSERT INTO myopc_login_logs (user_id, logout_time, ip_address, user_agent) 
                       VALUES (?, NOW(), ?, ?)";
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $this->db->execute($logSql, [$userId, $ipAddress, $userAgent]);
        } catch (Exception $e) {
            error_log("Logout log error: " . $e->getMessage());
        }
    }
    
    /**
     * Session güvenlik kontrolü
     */
    public function validateSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Session timeout kontrolü (2 saat)
        if (isset($_SESSION['login_time'])) {
            $loginTime = strtotime($_SESSION['login_time']);
            $currentTime = time();
            $sessionTimeout = 2 * 60 * 60; // 2 saat
            
            if (($currentTime - $loginTime) > $sessionTimeout) {
                $this->logout();
                return false;
            }
        }
        
        return true;
    }
}

?>
