<?php
require_once '../config/db.php';
require_once '../models/User.php';
require_once '../utils/Helpers.php';

/**
 * Giriş/çıkış işlemleri - Login/logout operations (session tabanlı)
 */

class AuthController {
    
    /**
     * Kullanıcı kaydı
     * User registration
     */
    public function register($full_name, $password) {
        if(empty($full_name) || empty($password)) {
            return Helpers::errorMessage('Ad soyad ve şifre gereklidir.');
        }
        
        if(strlen($full_name) < 3) {
            return Helpers::errorMessage('Ad soyad en az 3 karakter olmalıdır.');
        }
        
        if(strlen($password) < 6) {
            return Helpers::errorMessage('Şifre en az 6 karakter olmalıdır.');
        }
        
        $database = Database::getInstance();
        
        $user = new User($database);
        
        if($user->create($full_name, $password)) {
            return Helpers::successMessage('Kayıt başarılı! Şimdi giriş yapabilirsiniz.');
        } else {
            return Helpers::errorMessage('Bu ad soyad zaten kullanılıyor veya kayıt sırasında hata oluştu.');
        }
    }
    
    /**
     * Kullanıcı girişi
     * User login
     */
    public function login($full_name, $password) {
        if(empty($full_name) || empty($password)) {
            return Helpers::errorMessage('Ad soyad ve şifre gereklidir.');
        }
        
        $database = Database::getInstance();
        
        $user = new User($database);
        
        if($user->login($full_name, $password)) {
            session_start();
            $_SESSION['user_id'] = $user->user_id;
            $_SESSION['full_name'] = $user->full_name;
            $_SESSION['logged_in'] = true;
            
            return Helpers::successMessage('Giriş başarılı.');
        } else {
            return Helpers::errorMessage('Ad soyad veya şifre hatalı.');
        }
    }
    
    /**
     * Kullanıcı çıkışı
     * User logout
     */
    public function logout() {
        session_start();
        session_destroy();
        
        return Helpers::successMessage('Çıkış yapıldı.');
    }
    
    /**
     * Oturum kontrolü
     * Session check
     */
    public function checkAuth() {
        session_start();
        
        if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Giriş sayfasına yönlendir
     * Redirect to login page
     */
    public function redirectToLogin() {
        header('Location: views/login.php');
        exit;
    }
    
    /**
     * Ana sayfaya yönlendir
     * Redirect to dashboard
     */
    public function redirectToDashboard() {
        header('Location: views/dashboard.php');
        exit;
    }
}
?>
