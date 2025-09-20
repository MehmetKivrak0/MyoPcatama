<?php

/**
 * PC modeli
 * PC model
 */
class Pc {
    public $id;
    public $name;
    public $lab_id;
    public $status;
    public $assigned_student_id;
    public $created_at;
    public $updated_at;
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Yeni PC oluştur
     * Create new PC
     */
    public function create($name, $lab_id, $status = 'available', $assigned_student_id = null) {
        $sql = "INSERT INTO myopc_computers (name, lab_id, status, assigned_student_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW() + INTERVAL 1 MINUTE)";
        $result = $this->db->execute($sql, [$name, $lab_id, $status, $assigned_student_id]);
        
        if ($result > 0) {
            $this->id = $this->db->lastInsertId();
            $this->name = $name;
            $this->lab_id = $lab_id;
            $this->status = $status;
            $this->assigned_student_id = $assigned_student_id;
            return true;
        }
        
        return false;
    }
    
    /**
     * PC'yi ID'ye göre getir
     * Get PC by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM myopc_computers WHERE id = ?";
        $pc = $this->db->fetchOne($sql, [$id]);
        
        if ($pc) {
            $this->id = $pc['id'];
            $this->name = $pc['name'];
            $this->lab_id = $pc['lab_id'];
            $this->status = $pc['status'];
            $this->assigned_student_id = $pc['assigned_student_id'];
            $this->created_at = $pc['created_at'];
            $this->updated_at = $pc['updated_at'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Laboratuvardaki tüm PC'leri getir
     * Get all PCs in lab
     */
    public function getByLabId($lab_id) {
        // Bu metod artık Assignment modelindeki getPCAssignmentsByLab ile aynı işi yapıyor
        // Assignment modelini kullanmak daha mantıklı
        return [];
    }
    
    /**
     * Tüm PC'leri getir
     * Get all PCs
     */
    public function getAll() {
        $sql = "SELECT c.*, l.lab_name FROM myopc_computers c 
                LEFT JOIN myopc_lab_computers l ON c.lab_id = l.computer_id 
                ORDER BY l.lab_name ASC, c.name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Müsait PC'leri getir
     * Get available PCs
     */
    public function getAvailable() {
        $sql = "SELECT c.*, l.lab_name FROM myopc_computers c 
                LEFT JOIN myopc_lab_computers l ON c.lab_id = l.computer_id 
                WHERE c.status = 'available' 
                ORDER BY l.lab_name ASC, c.name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Atanmış PC'leri getir
     * Get assigned PCs
     */
    public function getAssigned() {
        $sql = "SELECT p.*, l.lab_name, s.full_name as student_name 
                FROM myopc_pcs p 
                LEFT JOIN myopc_labs l ON p.lab_id = l.lab_id 
                LEFT JOIN myopc_assignments a ON p.pc_id = a.pc_id
                LEFT JOIN myopc_students s ON a.student_id = s.student_id 
                WHERE a.assignment_id IS NOT NULL 
                ORDER BY l.lab_name ASC, p.pc_number ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * PC'yi güncelle
     * Update PC
     */
    public function update($id, $name, $status = null, $assigned_student_id = null) {
        $sql = "UPDATE myopc_computers SET name = ?, status = ?, assigned_student_id = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->execute($sql, [$name, $status, $assigned_student_id, $id]);
        return $result > 0;
    }
    
    /**
     * PC'yi sil
     * Delete PC
     */
    public function delete($id) {
        $sql = "DELETE FROM myopc_computers WHERE id = ?";
        $result = $this->db->execute($sql, [$id]);
        return $result > 0;
    }
    
    /**
     * Laboratuvardaki tüm PC'leri sil
     * Delete all PCs in lab
     */
    public function deleteByLabId($lab_id) {
        $sql = "DELETE FROM myopc_computers WHERE lab_id = ?";
        $result = $this->db->execute($sql, [$lab_id]);
        return $result;
    }
    
    /**
     * PC'yi öğrenciye ata
     * Assign PC to student
     */
    public function assignToStudent($pc_id, $student_id) {
        $sql = "UPDATE myopc_computers SET status = 'assigned', assigned_student_id = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->execute($sql, [$student_id, $pc_id]);
        return $result > 0;
    }
    
    /**
     * PC'den öğrenci atamasını kaldır
     * Unassign PC from student
     */
    public function unassignFromStudent($pc_id) {
        $sql = "UPDATE myopc_computers SET status = 'available', assigned_student_id = NULL, updated_at = NOW() WHERE id = ?";
        $result = $this->db->execute($sql, [$pc_id]);
        return $result > 0;
    }
    
    /**
     * PC durumunu güncelle
     * Update PC status
     */
    public function updateStatus($pc_id, $status) {
        $sql = "UPDATE myopc_computers SET status = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->execute($sql, [$status, $pc_id]);
        return $result > 0;
    }
    
    /**
     * Laboratuvardaki PC sayısını getir
     * Get PC count in lab
     */
    public function getCountByLabId($lab_id) {
        $sql = "SELECT COUNT(*) as count FROM myopc_computers WHERE lab_id = ?";
        $result = $this->db->fetchOne($sql, [$lab_id]);
        return $result['count'];
    }
    
    /**
     * Laboratuvardaki müsait PC sayısını getir
     * Get available PC count in lab
     */
    public function getAvailableCountByLabId($lab_id) {
        $sql = "SELECT COUNT(*) as count FROM myopc_computers WHERE lab_id = ? AND status = 'available'";
        $result = $this->db->fetchOne($sql, [$lab_id]);
        return $result['count'];
    }
}
?>

