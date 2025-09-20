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
        // Tüm laboratuvarları getir - user_type'a göre sırala
        $sql = "SELECT computer_id, lab_name, pc_count, user_type, created_at, updated_at, created_by 
                FROM myopc_lab_computers 
                ORDER BY user_type ASC, created_at DESC";
        error_log("Lab::getAll() - SQL: " . $sql);
        $result = $this->db->fetchAll($sql);
        error_log("Lab::getAll() - Result count: " . count($result));
        return $result;
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
     * NOT: Bu method artık optimize edildi - sadece assigned count'tan hesaplanıyor
     */
    public function getAvailableComputerCount($lab_id) {
        try {
            $totalPcs = $this->getComputerCount($lab_id);
            $assignedPcs = $this->getAssignedComputerCount($lab_id);
            return $totalPcs - $assignedPcs;
        } catch (Exception $e) {
            error_log("Müsait PC sayısı hesaplanırken hata: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Laboratuvardaki atanmış bilgisayar sayısını getir
     * Get assigned computer count in lab
     */
    public function getAssignedComputerCount($lab_id) {
        try {
            // Toplam PC sayısını al
            $totalPcs = $this->getComputerCount($lab_id);
            
            // Atanmış PC sayısını hesapla
            $assignedPcs = 0;
            for ($i = 1; $i <= $totalPcs; $i++) {
                $pcId = $lab_id * 100 + $i;
                $sql = "SELECT COUNT(*) as count FROM myopc_assignments WHERE computer_id = ?";
                $result = $this->db->fetchOne($sql, [$pcId]);
                if ($result && $result['count'] > 0) {
                    $assignedPcs++;
                }
            }
            
            return $assignedPcs;
        } catch (Exception $e) {
            error_log("Atanmış PC sayısı hesaplanırken hata: " . $e->getMessage());
            return 0;
        }
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
        
        try {
            // Mevcut PC sayısını al
            $currentLab = $this->getById($lab_id);
            if (!$currentLab) {
                return ['success' => false, 'message' => 'Laboratuvar bulunamadı.'];
            }
            
            $old_count = $currentLab['pc_count'];
            
            // Eğer yeni sayı eski sayıdan küçükse, fazla PC'lerde öğrenci ataması var mı kontrol et
            if ($new_count < $old_count) {
                $occupiedPCs = $this->checkOccupiedPCs($lab_id, $new_count, $old_count);
                if (!empty($occupiedPCs)) {
                    $pcNumbers = 'PC' . implode(', PC', $occupiedPCs);
                    $pcCount = count($occupiedPCs);
                    $pcText = $pcCount == 1 ? 'PC' : 'PC\'ler';
                    return [
                        'success' => false, 
                        'message' => "PC sayısını azaltamazsınız! Aşağıdaki {$pcText}de öğrenci ataması bulunuyor: {$pcNumbers}. Lütfen önce bu PC'lerdeki öğrenci atamalarını kaldırın.",
                        'occupied_pcs' => $occupiedPCs
                    ];
                }
            }
            
            // Transaction başlat
            $this->db->beginTransaction();
            
            // Eğer yeni sayı eski sayıdan küçükse, fazla PC'lerdeki atamaları temizle
            if ($new_count < $old_count) {
                $this->clearExcessPCAssignments($lab_id, $new_count, $old_count);
            }
            
            // PC sayısını güncelle
            $sql = "UPDATE myopc_lab_computers SET pc_count = ?, updated_at = NOW() WHERE computer_id = ?";
            $result = $this->db->execute($sql, [$new_count, $lab_id]);
            
            if ($result <= 0) {
                throw new Exception('PC sayısı güncellenirken bir hata oluştu.');
            }
            
            // Transaction'ı commit et
            $this->db->commit();
            
            return ['success' => true, 'message' => "PC sayısı {$old_count}'dan {$new_count}'a güncellendi. Lab yapısı yenilendi."];
            
        } catch (Exception $e) {
            // Hata durumunda rollback yap
            $this->db->rollback();
            error_log("PC sayısı güncelleme hatası: " . $e->getMessage());
            return ['success' => false, 'message' => 'PC sayısı güncellenirken bir hata oluştu: ' . $e->getMessage()];
        }
    }
    
    /**
     * Fazla PC'lerde öğrenci ataması olup olmadığını kontrol et
     * Check if there are student assignments in excess PCs
     */
    private function checkOccupiedPCs($lab_id, $new_count, $old_count) {
        $occupiedPCs = [];
        
        // Yeni sayıdan fazla olan PC'lerde öğrenci ataması var mı kontrol et
        for ($i = $new_count + 1; $i <= $old_count; $i++) {
            $pcId = $lab_id * 100 + $i;
            $sql = "SELECT COUNT(*) as count FROM myopc_assignments WHERE computer_id = ?";
            $result = $this->db->fetchOne($sql, [$pcId]);
            
            if ($result && $result['count'] > 0) {
                $occupiedPCs[] = $i;
            }
        }
        
        return $occupiedPCs;
    }
    
    /**
     * Fazla PC'lerdeki atamaları temizle
     * Clear assignments from excess PCs
     */
    private function clearExcessPCAssignments($lab_id, $new_count, $old_count) {
        // Yeni sayıdan fazla olan PC'lerdeki atamaları temizle
        for ($i = $new_count + 1; $i <= $old_count; $i++) {
            $pcId = $lab_id * 100 + $i;
            $sql = "DELETE FROM myopc_assignments WHERE computer_id = ?";
            $this->db->execute($sql, [$pcId]);
        }
    }
    
    /**
     * Belirli kullanıcı tipine göre laboratuvarları getir
     * Get labs by user type
     */
    public function getByUserType($userType) {
        $sql = "SELECT computer_id, lab_name, pc_count, user_type, created_at, updated_at, created_by 
                FROM myopc_lab_computers 
                WHERE user_type = ? 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$userType]);
    }
    
    /**
     * Mevcut kullanıcı tiplerini getir
     * Get available user types
     */
    public function getUserTypes() {
        $sql = "SELECT DISTINCT user_type FROM myopc_lab_computers ORDER BY user_type ASC";
        $result = $this->db->fetchAll($sql);
        return array_column($result, 'user_type');
    }
    
    /**
     * Toplam laboratuvar sayısını getir
     * Get total lab count
     */
    public function getLabCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM myopc_lab_computers";
            $result = $this->db->fetchOne($sql);
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Laboratuvar için maksimum öğrenci sayısını getir
     */
    public function getMaxStudentsPerPC($labId) {
        try {
            $sql = "SELECT max_students_per_pc FROM myopc_lab_computers WHERE computer_id = ?";
            $result = $this->db->fetchOne($sql, [$labId]);
            return $result ? (int)$result['max_students_per_pc'] : 4; // Varsayılan 4
        } catch (Exception $e) {
            error_log("getMaxStudentsPerPC hatası: " . $e->getMessage());
            return 4; // Varsayılan değer
        }
    }
    
    /**
     * Laboratuvar için maksimum öğrenci sayısını güncelle
     */
    public function updateMaxStudentsPerPC($labId, $maxStudents) {
        try {
            $sql = "UPDATE myopc_lab_computers SET max_students_per_pc = ? WHERE computer_id = ?";
            $result = $this->db->execute($sql, [$maxStudents, $labId]);
            return $result;
        } catch (Exception $e) {
            error_log("updateMaxStudentsPerPC hatası: " . $e->getMessage());
            return false;
        }
    }
}
?>