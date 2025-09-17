<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Lab.php';
require_once __DIR__ . '/../models/Pc.php';

/**
 * Laboratuvar Controller
 * Lab management controller
 */
class LabController {
    private $db;
    private $labModel;
    private $pcModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->labModel = new Lab($this->db);
        $this->pcModel = new Pc($this->db);
    }
    
    /**
     * Yeni laboratuvar oluştur ve belirtilen sayıda PC ekle
     * Create new lab with specified number of PCs
     */
    public function createLabWithPcs($labName, $pcCount, $userType = 'admin') {
        try {
            // Kullanıcı tipini temizle ve formatla (boşlukları kaldır, küçük harfe çevirme)
            $cleanType = preg_replace('/\s+/', '', $userType); // Boşlukları kaldır
            $cleanType = preg_replace('/[^a-zA-ZğüşıöçĞÜŞİÖÇ]/', '', $cleanType); // Sadece harfleri al
            
            // Benzersiz PC numarası oluştur
            $format = $this->labModel->generateUniquePcNumber($cleanType);
            
            // Laboratuvar oluştur (bu tablo yapısında PC sayısı direkt kaydediliyor)
            $result = $this->labModel->create($format, $pcCount, $userType, 'Xrlab-Yönetici');
            
            if (!$result['success']) {
                return [
                    'type' => 'error',
                    'message' => $result['message']
                ];
            }
            
            $labId = $this->labModel->id;
            
            return [
                'type' => 'success',
                'message' => "Laboratuvar '{$format}' başarıyla oluşturuldu. {$pcCount} adet PC kapasitesi eklendi.",
                'data' => [
                    'lab_id' => $labId,
                    'lab_name' => $format,
                    'pc_count' => $pcCount,
                    'user_type' => $userType
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Tüm laboratuvarları getir
     * Get all labs
     */
    public function getAllLabs() {
        try {
            $labs = $this->labModel->getAll();
            
            // Her laboratuvar için PC sayısını ekle (zaten veritabanında var)
            foreach ($labs as &$lab) {
                // pc_count zaten veritabanından geliyor, sadece available_pc_count hesapla
                $lab['available_pc_count'] = $lab['pc_count']; // Bu tablo yapısında tüm PC'ler müsait
            }
            
            return [
                'type' => 'success',
                'data' => $labs
            ];
            
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Laboratuvarı sil
     * Delete lab
     */
    public function deleteLab($labId) {
        try {
            // Sadece laboratuvarı sil (PC'ler ayrı tabloda tutulmuyor)
            $labDeleted = $this->labModel->delete($labId);
            
            if (!$labDeleted) {
                throw new Exception('Laboratuvar silinemedi');
            }
            
            return [
                'type' => 'success',
                'message' => 'Laboratuvar başarıyla silindi.',
                'data' => [
                    'deleted_lab_id' => $labId
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Laboratuvara PC ekle
     * Add PC to lab
     */
    public function addPCToLab($labId, $count = 1) {
        try {
            $result = $this->labModel->addPC($labId, $count);
            
            if ($result) {
                return [
                    'type' => 'success',
                    'message' => "{$count} adet PC başarıyla eklendi."
                ];
            } else {
                return [
                    'type' => 'error',
                    'message' => 'PC eklenirken bir hata oluştu.'
                ];
            }
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Laboratuvardan PC çıkar
     * Remove PC from lab
     */
    public function removePCFromLab($labId, $count = 1) {
        try {
            $result = $this->labModel->removePC($labId, $count);
            
            if ($result['success']) {
                return [
                    'type' => 'success',
                    'message' => $result['message']
                ];
            } else {
                return [
                    'type' => 'error',
                    'message' => $result['message']
                ];
            }
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Laboratuvar PC sayısını güncelle
     * Update lab PC count
     */
    public function updateLabPCCount($labId, $newCount) {
        try {
            $result = $this->labModel->updatePCCount($labId, $newCount);
            
            if ($result['success']) {
                return [
                    'type' => 'success',
                    'message' => $result['message']
                ];
            } else {
                return [
                    'type' => 'error',
                    'message' => $result['message']
                ];
            }
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Laboratuvarı güncelle
     * Update lab
     */
    public function updateLab($labId, $name, $description = '') {
        try {
            $updated = $this->labModel->update($labId, $name, $description);
            
            if ($updated) {
                return [
                    'type' => 'success',
                    'message' => 'Laboratuvar başarıyla güncellendi.'
                ];
            } else {
                return [
                    'type' => 'error',
                    'message' => 'Laboratuvar güncellenemedi.'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Laboratuvar detaylarını getir
     * Get lab details
     */
    public function getLabDetails($labId) {
        try {
            $lab = $this->labModel->getById($labId);
            
            if (!$lab) {
                return [
                    'type' => 'error',
                    'message' => 'Laboratuvar bulunamadı.'
                ];
            }
            
            // Veritabanındaki gerçek verileri kullan (hesaplama yapmadan)
            $pcCount = $this->labModel->getComputerCount($labId);
            $assignedPcCount = 0; // Veritabanında assigned_pc_count kolonu yoksa
            $availablePcCount = $pcCount; // Toplam = Müsait (şimdilik)
            
            return [
                'type' => 'success',
                'data' => [
                    'lab' => $lab,
                    'pc_count' => $pcCount,
                    'assigned_pc_count' => $assignedPcCount,
                    'available_pc_count' => $availablePcCount
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kullanıcı tipine göre laboratuvarları getir
     * Get labs by user type
     */
    public function getLabsByUserType($userType) {
        try {
            $labs = $this->labModel->getByUserType($userType);
            
            // Her laboratuvar için PC sayısını ekle
            foreach ($labs as &$lab) {
                $lab['available_pc_count'] = $lab['pc_count'];
            }
            
            return [
                'type' => 'success',
                'data' => $labs
            ];
            
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Mevcut kullanıcı tiplerini getir
     * Get available user types
     */
    public function getUserTypes() {
        try {
            $userTypes = $this->labModel->getUserTypes();
            return [
                'type' => 'success',
                'data' => $userTypes
            ];
            
        } catch (Exception $e) {
            return [
                'type' => 'error',
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Toplam laboratuvar sayısını getir
     * Get total lab count
     */
    public function getLabCount() {
        try {
            $count = $this->labModel->getLabCount();
            return [
                'success' => true,
                'count' => $count
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ];
        }
    }
}

// Controller'ı başlat (doğrudan çağrıldığında)
if (basename($_SERVER['PHP_SELF']) === 'LabController.php') {
    $controller = new LabController();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_lab_count':
            $result = $controller->getLabCount();
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
        case 'get_all_labs':
            $result = $controller->getAllLabs();
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz action: ' . $action
            ]);
            break;
    }
}
?>
