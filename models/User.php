<?php

/**
 * Kullanıcı modeli
 * User model
 */
class User {
    public $user_id;
    public $full_name;
    public $password_hash;
    public $created_at;
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Yeni kullanıcı oluştur
     * Create new user
     */
    public function create($full_name, $password) {
        // Kullanıcı adı kontrolü
        if ($this->userExists($full_name)) {
            return false;
        }
        
        // Şifreyi hash'le
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Kullanıcıyı veritabanına ekle
        $sql = "INSERT INTO myopc_users (full_name, password_hash, created_at) VALUES (?, ?, NOW())";
        $result = $this->db->execute($sql, [$full_name, $hashedPassword]);
        
        if ($result > 0) {
            $this->user_id = $this->db->lastInsertId();
            $this->full_name = $full_name;
            $this->password_hash = $hashedPassword;
            return true;
        }
        
        return false;
    }
    
    /**
     * Kullanıcı girişi
     * User login
     */
    public function login($full_name, $password) {
        $sql = "SELECT user_id, full_name, password_hash FROM myopc_users WHERE full_name = ?";
        $user = $this->db->fetchOne($sql, [$full_name]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $this->user_id = $user['user_id'];
            $this->full_name = $user['full_name'];
            $this->password_hash = $user['password_hash'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Kullanıcı adı var mı kontrol et
     * Check if username exists
     */
    public function userExists($full_name) {
        $sql = "SELECT COUNT(*) as count FROM myopc_users WHERE full_name = ?";
        $result = $this->db->fetchOne($sql, [$full_name]);
        return $result['count'] > 0;
    }
    
    /**
     * Kullanıcıyı ID'ye göre getir
     * Get user by ID
     */
    public function getById($id) {
        $sql = "SELECT user_id, full_name, created_at FROM myopc_users WHERE user_id = ?";
        $user = $this->db->fetchOne($sql, [$id]);
        
        if ($user) {
            $this->user_id = $user['user_id'];
            $this->full_name = $user['full_name'];
            $this->created_at = $user['created_at'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Tüm kullanıcıları getir
     * Get all users
     */
    public function getAll() {
        $sql = "SELECT user_id, full_name, created_at FROM myopc_users ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }
}
?>