<?php

/**
 * Laboratuvar modeli
 * Lab model
 */
class Lab {
    public $id;
    public $lab_name;
    public $pc_count;
    public $created_at;
    public $updated_at;
    public $created_by;
    public $user_type;
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * PC numarası kontrolü
     * Check if PC number already exists
     */
    public function checkPcNumberExists($pc_number) {
        $sql = "SELECT COUNT(*) as count FROM myopc_lab_computers WHERE lab_name LIKE ?";
        $result = $this->db->fetchOne($sql, ["%PC{$pc_number}"]);
        return $result['count'] > 0;
    }
    
    /**
     * Benzersiz PC numarası oluştur
     * Generate unique PC number
     */
    public function generateUniquePcNumber($user_type) {
        $base_name = "Bil_{$user_type}";
        $pc_number = 1;
        
        // PC numarasını bulana kadar döngü
        while ($this->checkPcNumberExists($pc_number)) {
            $pc_number++;
        }
        
        return "{$base_name}-PC{$pc_number}";
    }
    
    /**
     * Yeni laboratuvar oluştur
     * Create new lab
     */
    public function create($lab_name, $pc_count, $user_type = 'admin', $created_by = 'Xrlab-Yönetici') {
        // PC numarası kontrolü
        if (preg_match('/PC(\d+)/', $lab_name, $matches)) {
            $pc_number = $matches[1];
            if ($this->checkPcNumberExists($pc_number)) {
                return ['success' => false, 'message' => "PC{$pc_number} numarası zaten kullanılıyor. Lütfen farklı bir numara seçin."];
            }
        }
        
        $sql = "INSERT INTO myopc_lab_computers(lab_name, pc_count, user_type, created_by, created_at) VALUES (?, ?, ?, ?, NOW())";
        $result = $this->db->execute($sql, [$lab_name, $pc_count, $user_type, $created_by]);
        
        if ($result > 0) {
            $this->id = $this->db->lastInsertId();
            $this->lab_name = $lab_name;
            $this->pc_count = $pc_count;
            $this->user_type = $user_type;
            $this->created_by = $created_by;
            return ['success' => true, 'message' => 'Laboratuvar başarıyla oluşturuldu.'];
        }
        
        return ['success' => false, 'message' => 'Laboratuvar oluşturulurken bir hata oluştu.'];
    }
    
    /**
     * Laboratuvarı ID'ye göre getir
     * Get lab by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM myopc_lab_computers WHERE computer_id = ?";
        $lab = $this->db->fetchOne($sql, [$id]);
        
        if ($lab) {
            $this->id = $lab['computer_id'];
            $this->lab_name = $lab['lab_name'];
            $this->pc_count = $lab['pc_count'];
            $this->created_at = $lab['created_at'];
            $this->updated_at = $lab['updated_at'];
            $this->created_by = $lab['created_by'];
            $this->user_type = $lab['user_type'];
            return $lab; // Objeyi döndür
        }
        
        return false;
    }
    
    /**
     * Tüm laboratuvarları getir
     * Get all labs
     */
    public function getAll() {
        $sql = "SELECT * FROM myopc_lab_computers ORDER BY lab_name ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Laboratuvarı güncelle
     * Update lab
     */
    public function update($id, $lab_name, $pc_count, $user_type = 'admin', $created_by = 'Xrlab-Yönetici') {
        $sql = "UPDATE myopc_lab_computers SET lab_name = ?, pc_count = ?, user_type = ?, created_by = ?, updated_at = NOW() WHERE computer_id = ?";
        $result = $this->db->execute($sql, [$lab_name, $pc_count, $user_type, $created_by, $id]);
        return $result > 0;
    }
    
    /**
     * Laboratuvarı sil
     * Delete lab
     */
    public function delete($id) {
        $sql = "DELETE FROM myopc_lab_computers WHERE computer_id = ?";
        $result = $this->db->execute($sql, [$id]);
        return $result > 0;
    }
    
    /**
     * Laboratuvardaki bilgisayar sayısını getir
     * Get computer count in lab
     */
    public function getComputerCount($lab_id) {
        $sql = "SELECT pc_count FROM myopc_lab_computers WHERE computer_id = ?";
        $result = $this->db->fetchOne($sql, [$lab_id]);
        return $result ? $result['pc_count'] : 0;
    }
    
    /**
     * Laboratuvardaki müsait bilgisayar sayısını getir
     * Get available computer count in lab
     */
    public function getAvailableComputerCount($lab_id) {
        // Bu tablo yapısında müsait PC sayısı ayrı tutulmuyor
        // Şimdilik toplam PC sayısını döndürüyoruz
        return $this->getComputerCount($lab_id);
    }
    
    /**
     * Laboratuvara PC ekle
     * Add PC to lab
     */
    public function addPC($lab_id, $count = 1) {
        $sql = "UPDATE myopc_lab_computers SET pc_count = pc_count + ? WHERE computer_id = ?";
        $result = $this->db->execute($sql, [$count, $lab_id]);
        return $result > 0;
    }
    
    /**
     * Laboratuvardan PC çıkar
     * Remove PC from lab
     */
    public function removePC($lab_id, $count = 1) {
        // Önce mevcut PC sayısını kontrol et
        $currentCount = $this->getComputerCount($lab_id);
        if ($currentCount < $count) {
            return ['success' => false, 'message' => 'Çıkarılmak istenen PC sayısı mevcut PC sayısından fazla.'];
        }
        
        $sql = "UPDATE myopc_lab_computers SET pc_count = pc_count - ? WHERE computer_id = ?";
        $result = $this->db->execute($sql, [$count, $lab_id]);
        
        if ($result > 0) {
            return ['success' => true, 'message' => "{$count} adet PC başarıyla çıkarıldı."];
        } else {
            return ['success' => false, 'message' => 'PC çıkarılırken bir hata oluştu.'];
        }
    }
    
    /**
     * Laboratuvar PC sayısını güncelle
     * Update lab PC count
     */
    public function updatePCCount($lab_id, $new_count) {
        if ($new_count < 0) {
            return ['success' => false, 'message' => 'PC sayısı negatif olamaz.'];
        }
        
        $sql = "UPDATE myopc_lab_computers SET pc_count = ? WHERE computer_id = ?";
        $result = $this->db->execute($sql, [$new_count, $lab_id]);
        
        if ($result > 0) {
            return ['success' => true, 'message' => "PC sayısı {$new_count} olarak güncellendi."];
        } else {
            return ['success' => false, 'message' => 'PC sayısı güncellenirken bir hata oluştu.'];
        }
    }
}
?>