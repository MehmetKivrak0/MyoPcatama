<?php

/**
 * Laboratuvar modeli
 * Lab model
 */
class Lab {
    public $id;
    public $name;
    public $description;
    public $created_at;
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Yeni laboratuvar oluştur
     * Create new lab
     */
    public function create($name, $description = '') {
        $sql = "INSERT INTO myopc_labs (name, description, created_at) VALUES (?, ?, NOW())";
        $result = $this->db->execute($sql, [$name, $description]);
        
        if ($result > 0) {
            $this->id = $this->db->lastInsertId();
            $this->name = $name;
            $this->description = $description;
            return true;
        }
        
        return false;
    }
    
    /**
     * Laboratuvarı ID'ye göre getir
     * Get lab by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM myopc_labs WHERE id = ?";
        $lab = $this->db->fetchOne($sql, [$id]);
        
        if ($lab) {
            $this->id = $lab['id'];
            $this->name = $lab['name'];
            $this->description = $lab['description'];
            $this->created_at = $lab['created_at'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Tüm laboratuvarları getir
     * Get all labs
     */
    public function getAll() {
        $sql = "SELECT * FROM myopc_labs ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Laboratuvarı güncelle
     * Update lab
     */
    public function update($id, $name, $description = '') {
        $sql = "UPDATE myopc_labs SET name = ?, description = ? WHERE id = ?";
        $result = $this->db->execute($sql, [$name, $description, $id]);
        return $result > 0;
    }
    
    /**
     * Laboratuvarı sil
     * Delete lab
     */
    public function delete($id) {
        $sql = "DELETE FROM myopc_labs WHERE id = ?";
        $result = $this->db->execute($sql, [$id]);
        return $result > 0;
    }
    
    /**
     * Laboratuvardaki bilgisayar sayısını getir
     * Get computer count in lab
     */
    public function getComputerCount($lab_id) {
        $sql = "SELECT COUNT(*) as count FROM myopc_computers WHERE lab_id = ?";
        $result = $this->db->fetchOne($sql, [$lab_id]);
        return $result['count'];
    }
    
    /**
     * Laboratuvardaki müsait bilgisayar sayısını getir
     * Get available computer count in lab
     */
    public function getAvailableComputerCount($lab_id) {
        $sql = "SELECT COUNT(*) as count FROM myopc_computers WHERE lab_id = ? AND status = 'available'";
        $result = $this->db->fetchOne($sql, [$lab_id]);
        return $result['count'];
    }
}
?>

