<?php

/**
 * Yardımcı fonksiyonlar sınıfı
 * Helper functions class
 */
class Helpers {
    
    /**
     * Başarı mesajı döndür
     * Return success message
     */
    public static function successMessage($message) {
        return [
            'type' => 'success',
            'message' => $message
        ];
    }
    
    /**
     * Hata mesajı döndür
     * Return error message
     */
    public static function errorMessage($message) {
        return [
            'type' => 'error',
            'message' => $message
        ];
    }
    
    /**
     * HTML çıktısını temizle
     * Sanitize HTML output
     */
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Şifreyi hash'le
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Şifreyi doğrula
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Mesajı göster
     * Display message
     */
    public static function displayMessage($message) {
        if (is_array($message) && isset($message['type']) && isset($message['message'])) {
            $class = $message['type'] === 'success' ? 'alert-success' : 'alert-danger';
            return "<div class='alert $class alert-dismissible fade show' role='alert'>
                        {$message['message']}
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
        }
        return '';
    }
}
?>

