<?php

/**
 * Öğrenci modeli
 * Student model
 */
class Student {
    public $id;
    public $student_number;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $created_at;
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Yeni öğrenci oluştur
     * Create new student
     */
    public function create($student_number, $first_name, $last_name, $email = '', $phone = '') {
        // Öğrenci numarası kontrolü
        if ($this->studentNumberExists($student_number)) {
            return false;
        }
        
        $sql = "INSERT INTO myopc_students (student_number, first_name, last_name, email, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $result = $this->db->execute($sql, [$student_number, $first_name, $last_name, $email, $phone]);
        
        if ($result > 0) {
            $this->id = $this->db->lastInsertId();
            $this->student_number = $student_number;
            $this->first_name = $first_name;
            $this->last_name = $last_name;
            $this->email = $email;
            $this->phone = $phone;
            return true;
        }
        
        return false;
    }
    
    /**
     * Öğrenci numarası var mı kontrol et
     * Check if student number exists
     */
    public function studentNumberExists($student_number) {
        $sql = "SELECT COUNT(*) as count FROM myopc_students WHERE student_number = ?";
        $result = $this->db->fetchOne($sql, [$student_number]);
        return $result['count'] > 0;
    }
    
    /**
     * Öğrenciyi ID'ye göre getir
     * Get student by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM myopc_students WHERE id = ?";
        $student = $this->db->fetchOne($sql, [$id]);
        
        if ($student) {
            $this->id = $student['id'];
            $this->student_number = $student['student_number'];
            $this->first_name = $student['first_name'];
            $this->last_name = $student['last_name'];
            $this->email = $student['email'];
            $this->phone = $student['phone'];
            $this->created_at = $student['created_at'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Öğrenciyi numaraya göre getir
     * Get student by student number
     */
    public function getByStudentNumber($student_number) {
        $sql = "SELECT * FROM myopc_students WHERE student_number = ?";
        $student = $this->db->fetchOne($sql, [$student_number]);
        
        if ($student) {
            $this->id = $student['id'];
            $this->student_number = $student['student_number'];
            $this->first_name = $student['first_name'];
            $this->last_name = $student['last_name'];
            $this->email = $student['email'];
            $this->phone = $student['phone'];
            $this->created_at = $student['created_at'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Tüm öğrencileri getir
     * Get all students
     */
    public function getAll() {
        $sql = "SELECT * FROM myopc_students ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Mevcut yılları getir (created_at'ten)
     * Get available years from created_at
     */
    public function getAvailableYears() {
        $sql = "SELECT DISTINCT YEAR(created_at) as year FROM myopc_students ORDER BY year DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Belirli yıla göre öğrencileri getir
     * Get students by year
     */
    public function getByYear($year) {
        $sql = "SELECT * FROM myopc_students WHERE YEAR(created_at) = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$year]);
    }
    
    /**
     * Öğrenciyi güncelle
     * Update student
     */
    public function update($id, $student_number, $first_name, $last_name, $email = '', $phone = '') {
        $sql = "UPDATE myopc_students SET student_number = ?, first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
        $result = $this->db->execute($sql, [$student_number, $first_name, $last_name, $email, $phone, $id]);
        return $result > 0;
    }
    
    /**
     * Öğrenciyi sil
     * Delete student
     */
    public function delete($id) {
        $sql = "DELETE FROM myopc_students WHERE id = ?";
        $result = $this->db->execute($sql, [$id]);
        return $result > 0;
    }
}
?>

