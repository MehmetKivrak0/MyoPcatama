<?php

class Assignment {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Tüm öğrencileri getir
     */
    public function getAllStudents() {
        try {
            $sql = "SELECT * FROM myopc_students ORDER BY full_name ASC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Öğrenci listesi alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Belirli bir laboratuvardaki öğrencileri getir
     */
    public function getStudentsByLab($computerId) {
        try {
            $sql = "
                SELECT s.*, a.computer_id as pc_id, a.created_at as assignment_date 
                FROM myopc_students s 
                LEFT JOIN myopc_assignments a ON s.student_id = a.student_id 
                WHERE a.computer_id IS NULL OR (a.computer_id BETWEEN ? AND ?)
                ORDER BY s.full_name ASC
            ";
            $pcIdStart = $computerId * 100 + 1;
            $pcIdEnd = $computerId * 100 + 999;
            return $this->db->fetchAll($sql, [$pcIdStart, $pcIdEnd]);
        } catch (Exception $e) {
            error_log("Laboratuvar öğrencileri alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Belirli bir laboratuvardaki PC atamalarını getir
     */
    public function getPCAssignmentsByLab($computerId) {
        try {
            // Laboratuvarı bul
            $labSql = "SELECT * FROM myopc_lab_computers WHERE computer_id = ?";
            $lab = $this->db->fetchOne($labSql, [$computerId]);
            
            if (!$lab) {
                error_log("Laboratuvar bulunamadı: computer_id = " . $computerId);
                return [];
            }
            
            // pc_count kontrolü
            if (!isset($lab['pc_count']) || $lab['pc_count'] <= 0) {
                error_log("Geçersiz pc_count: " . ($lab['pc_count'] ?? 'null'));
                return [];
            }
            
            // Bu laboratuvardaki PC'leri oluştur (pc_count kadar)
            $pcs = [];
            for ($i = 1; $i <= $lab['pc_count']; $i++) {
                $pcId = $computerId * 100 + $i; // Benzersiz PC ID oluştur
                $pcNumber = $i; // Sadece PC numarası
                
                // Bu PC'ye atanmış tüm öğrencileri getir
                $assignmentSql = "SELECT s.*, a.created_at as assignment_date 
                                 FROM myopc_assignments a 
                                 INNER JOIN myopc_students s ON a.student_id = s.student_id 
                                 WHERE a.computer_id = ?
                                 ORDER BY a.created_at ASC";
                $assignments = $this->db->fetchAll($assignmentSql, [$pcId]);
                
                
                // İlk öğrenciyi geriye uyumluluk için sakla
                $firstAssignment = !empty($assignments) ? $assignments[0] : null;
                
                $pcs[] = [
                    'pc_id' => $pcId,
                    'pc_number' => $pcNumber,
                    'students' => $assignments, // Tüm öğrenciler
                    'student_count' => count($assignments),
                    // Geriye uyumluluk için ilk öğrenci bilgileri
                    'student_id' => $firstAssignment ? $firstAssignment['student_id'] : null,
                    'full_name' => $firstAssignment ? $firstAssignment['full_name'] : null,
                    'sdt_nmbr' => $firstAssignment ? $firstAssignment['sdt_nmbr'] : null,
                    'academic_year' => $firstAssignment ? $firstAssignment['academic_year'] : null,
                    'assignment_date' => $firstAssignment ? $firstAssignment['assignment_date'] : null
                ];
            }
            
            return $pcs;
        } catch (Exception $e) {
            error_log("PC atamaları alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Belirli bir PC'ye atanmış öğrencileri getir
     */
    public function getStudentsByPC($pcId) {
        try {
            $sql = "
                SELECT s.*, a.created_at as assignment_date, FLOOR(a.computer_id / 100) as lab_id 
                FROM myopc_students s 
                INNER JOIN myopc_assignments a ON s.student_id = a.student_id 
                WHERE a.computer_id = ?
                ORDER BY s.full_name ASC
            ";
            return $this->db->fetchAll($sql, [$pcId]);
        } catch (Exception $e) {
            error_log("PC öğrencileri alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Öğrenci ataması yap
     */
    public function assignStudent($studentId, $pcId, $computerId = null) {
        try {
            error_log("🚀 === assignStudent BAŞLADI ===");
            error_log("📋 Gelen parametreler - studentId: $studentId, pcId: $pcId, computerId: $computerId");
            
            // PC ID'si zaten doğru formatta geliyorsa (101, 102, 103...) direkt kullan
            // Eğer sadece PC numarası geliyorsa (1, 2, 3...) computer_id ile çarp
            $finalPcId = $pcId;
            
            // Eğer pcId 100'den küçükse ve computerId varsa, hesapla
            if ($computerId && $pcId < 100) {
                $finalPcId = $computerId * 100 + $pcId;
                error_log("📋 PC ID hesaplandı: $finalPcId (computerId: $computerId * 100 + pcId: $pcId)");
            } else {
                error_log("📋 PC ID direkt kullanılıyor: $finalPcId");
            }
            
            // Önce mevcut atamayı kontrol et
            $checkSql = "SELECT assignment_id FROM myopc_assignments WHERE student_id = ?";
            $existingAssignment = $this->db->fetchOne($checkSql, [$studentId]);
            
            error_log("📋 Mevcut atama kontrolü - studentId: $studentId, existingAssignment: " . ($existingAssignment ? 'VAR' : 'YOK'));
            
            if ($existingAssignment) {
                // Mevcut atamayı güncelle
                error_log("📋 Mevcut atama güncelleniyor - assignment_id: " . $existingAssignment['assignment_id']);
                $updateSql = "UPDATE myopc_assignments SET computer_id = ?, updated_at = NOW() WHERE assignment_id = ?";
                $result = $this->db->execute($updateSql, [$finalPcId, $existingAssignment['assignment_id']]);
                error_log("📋 Güncelleme sonucu: " . ($result ? 'BAŞARILI' : 'BAŞARISIZ'));
            } else {
                // Yeni atama oluştur
                error_log("📋 Yeni atama oluşturuluyor - studentId: $studentId, finalPcId: $finalPcId");
                $insertSql = "INSERT INTO myopc_assignments (student_id, computer_id, created_at, updated_at, created_by) VALUES (?, ?, NOW(), NOW() + INTERVAL 1 MINUTE, 'System')";
                $result = $this->db->execute($insertSql, [$studentId, $finalPcId]);
                error_log("📋 Ekleme sonucu: " . ($result ? 'BAŞARILI' : 'BAŞARISIZ'));
            }
            
            error_log("📋 assignStudent sonucu: " . ($result ? 'BAŞARILI' : 'BAŞARISIZ'));
            return $result;
        } catch (Exception $e) {
            error_log("Öğrenci ataması yapılırken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Öğrenci atamasını kaldır
     */
    public function unassignStudent($studentId, $computerId) {
        try {
            $sql = "DELETE FROM myopc_assignments WHERE student_id = ?";
            return $this->db->execute($sql, [$studentId]);
        } catch (Exception $e) {
            error_log("Öğrenci ataması kaldırılırken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * PC'den tüm öğrenci atamalarını kaldır
     */
    public function unassignAllStudentsFromPC($pcId) {
        try {
            $sql = "DELETE FROM myopc_assignments WHERE computer_id = ?";
            return $this->db->execute($sql, [$pcId]);
        } catch (Exception $e) {
            error_log("PC'den tüm atamalar kaldırılırken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Laboratuvardaki atama istatistiklerini getir
     */
    public function getAssignmentStats($computerId) {
        try {
            // Toplam öğrenci sayısı
            $totalStudentsSql = "SELECT COUNT(*) as count FROM myopc_students";
            $totalStudents = $this->db->fetchOne($totalStudentsSql)['count'];
            
            // Bu laboratuvarda atanmış öğrenci sayısı (PC ID'leri computer_id * 100 + PC numarası formatında)
            $assignedStudentsSql = "
                SELECT COUNT(DISTINCT student_id) as count 
                FROM myopc_assignments 
                WHERE computer_id BETWEEN ? AND ?
            ";
            $pcIdStart = $computerId * 100 + 1;
            $pcIdEnd = $computerId * 100 + 999; // Maksimum PC sayısı için geniş aralık
            $assignedStudents = $this->db->fetchOne($assignedStudentsSql, [$pcIdStart, $pcIdEnd])['count'];
            
            // Kullanılan PC sayısı
            $usedPcsSql = "
                SELECT COUNT(DISTINCT computer_id) as count 
                FROM myopc_assignments 
                WHERE computer_id BETWEEN ? AND ?
            ";
            $usedPcs = $this->db->fetchOne($usedPcsSql, [$pcIdStart, $pcIdEnd])['count'];
            
            return [
                'total_students' => $totalStudents,
                'assigned_students' => $assignedStudents,
                'used_pcs' => $usedPcs
            ];
        } catch (Exception $e) {
            error_log("Atama istatistikleri alınırken hata: " . $e->getMessage());
            return [
                'total_students' => 0,
                'assigned_students' => 0,
                'used_pcs' => 0
            ];
        }
    }
    
    /**
     * Öğrenci atama geçmişini getir
     */
    public function getAssignmentHistory($studentId = null, $pcId = null, $labId = null) {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($studentId) {
                $whereConditions[] = "a.student_id = ?";
                $params[] = $studentId;
            }
            
            if ($pcId) {
                $whereConditions[] = "a.computer_id = ?";
                $params[] = $pcId;
            }
            
            if ($labId) {
                $whereConditions[] = "a.computer_id BETWEEN ? AND ?";
                $pcIdStart = $labId * 100 + 1;
                $pcIdEnd = $labId * 100 + 999;
                $params[] = $pcIdStart;
                $params[] = $pcIdEnd;
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $sql = "
                SELECT 
                    s.full_name,
                    s.sdt_nmbr,
                    CONCAT('PC', (a.computer_id % 100)) as pc_number,
                    FLOOR(a.computer_id / 100) as lab_id,
                    a.created_at as assignment_date
                FROM myopc_assignments a
                INNER JOIN myopc_students s ON a.student_id = s.student_id
                {$whereClause}
                ORDER BY a.created_at DESC
            ";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log("Atama geçmişi alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Toplu öğrenci ataması yap
     */
    public function bulkAssignStudents($assignments) {
        try {
            $this->db->beginTransaction();
            
            foreach ($assignments as $assignment) {
                $this->assignStudent(
                    $assignment['student_id'],
                    $assignment['pc_id'],
                    $assignment['computer_id']
                );
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Toplu atama yapılırken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atama durumunu kontrol et
     */
    public function isStudentAssigned($studentId, $computerId) {
        try {
            // PC ID'leri computer_id * 100 + PC numarası formatında olduğu için aralık sorgusu yapıyoruz
            $sql = "
                SELECT computer_id FROM myopc_assignments 
                WHERE student_id = ? AND computer_id BETWEEN ? AND ?
            ";
            $pcIdStart = $computerId * 100 + 1;
            $pcIdEnd = $computerId * 100 + 999;
            $result = $this->db->fetchOne($sql, [$studentId, $pcIdStart, $pcIdEnd]);
            return $result ? $result['computer_id'] : false;
        } catch (Exception $e) {
            error_log("Atama durumu kontrol edilirken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * PC'nin dolu olup olmadığını kontrol et
     */
    public function isPCOccupied($pcId, $computerId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM myopc_assignments WHERE computer_id = ?";
            $result = $this->db->fetchOne($sql, [$pcId]);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("PC durumu kontrol edilirken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Öğrenci transfer et
     */
    public function transferStudent($studentId, $newPcId, $computerId) {
        try {
            // Mevcut atamayı bul
            $currentAssignment = $this->db->fetchOne(
                "SELECT assignment_id, computer_id FROM myopc_assignments WHERE student_id = ?", 
                [$studentId]
            );
            
            if (!$currentAssignment) {
                throw new Exception("Öğrenci ataması bulunamadı");
            }
            
            // Yeni PC ID'sini hesapla
            $finalNewPcId = $newPcId;
            if ($computerId && $newPcId < 100) {
                $finalNewPcId = $computerId * 100 + $newPcId;
            }
            
            // Atamayı güncelle
            $updateSql = "UPDATE myopc_assignments SET computer_id = ?, updated_at = NOW() WHERE assignment_id = ?";
            $result = $this->db->execute($updateSql, [$finalNewPcId, $currentAssignment['assignment_id']]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Transfer hatası: " . $e->getMessage());
            return false;
        }
    }
    
    
    /**
     * Boş PC'leri getir (transfer için)
     */
    public function getAvailablePCs($computerId, $excludePc = null) {
        try {
            // Laboratuvarın tüm PC'lerini getir
            $labModel = new Lab($this->db);
            $lab = $labModel->getById($computerId);
            
            if (!$lab) {
                return [];
            }
            
            $availablePCs = [];
            $pcCount = $lab['pc_count'];
            
            for ($i = 1; $i <= $pcCount; $i++) {
                $pcId = $computerId * 100 + $i;
                
                // Exclude edilecek PC'yi atla
                if ($excludePc && $pcId == $excludePc) {
                    continue;
                }
                
                // PC'nin dolu olup olmadığını kontrol et
                $isOccupied = $this->isPCOccupied($pcId, $computerId);
                
                if (!$isOccupied) {
                    $availablePCs[] = [
                        'pc_id' => $pcId,
                        'pc_number' => $i,
                        'computer_id' => $computerId
                    ];
                }
            }
            
            return $availablePCs;
        } catch (Exception $e) {
            error_log("Boş PC'ler alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Toplam atama sayısını getir
     */
    public function getAssignmentCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM myopc_assignments";
            $result = $this->db->fetchOne($sql);
            return $result ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log("Atama sayısı alınırken hata: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Geçersiz atamaları temizle (computer_id = 0 olanlar)
     */
    public function cleanInvalidAssignments() {
        try {
            $sql = "DELETE FROM myopc_assignments WHERE computer_id = 0 OR computer_id IS NULL";
            $result = $this->db->execute($sql);
            
            if ($result) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Geçersiz atamalar temizlenirken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atama verilerini doğrula ve düzelt
     */
    public function validateAndFixAssignments() {
        try {
            // Geçersiz atamaları temizle
            $this->cleanInvalidAssignments();
            
            // Mevcut laboratuvarları kontrol et
            $labsSql = "SELECT computer_id FROM myopc_lab_computers";
            $labs = $this->db->fetchAll($labsSql);
            $validLabIds = array_column($labs, 'computer_id');
            
            // Geçersiz laboratuvar ID'li atamaları temizle
            if (!empty($validLabIds)) {
                $placeholders = str_repeat('?,', count($validLabIds) - 1) . '?';
                $sql = "DELETE FROM myopc_assignments WHERE computer_id NOT IN ($placeholders)";
                $this->db->execute($sql, $validLabIds);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Atama verileri doğrulanırken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * PC'ye atanmış öğrencileri getir
     */
    public function getPCStudents($pcId) {
        try {
            $sql = "SELECT 
                        a.assignment_id,
                        a.student_id,
                        a.created_at as assigned_at,
                        s.full_name,
                        s.sdt_nmbr,
                        s.academic_year
                    FROM myopc_assignments a
                    JOIN myopc_students s ON a.student_id = s.student_id
                    WHERE a.computer_id = ?
                    ORDER BY a.created_at DESC";
            
            return $this->db->fetchAll($sql, [$pcId]);
        } catch (Exception $e) {
            error_log("PC öğrencileri getirilirken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Atamayı kaldır
     */
    public function removeAssignment($assignmentId) {
        try {
            // Önce atamanın var olup olmadığını kontrol et
            $checkSql = "SELECT assignment_id FROM myopc_assignments WHERE assignment_id = ?";
            $assignment = $this->db->fetchOne($checkSql, [$assignmentId]);
            
            if (!$assignment) {
                error_log("Atama bulunamadı: assignment_id = " . $assignmentId);
                return false;
            }
            
            // Atamayı sil
            $sql = "DELETE FROM myopc_assignments WHERE assignment_id = ?";
            $result = $this->db->execute($sql, [$assignmentId]);
            
            error_log("Atama silindi: assignment_id = " . $assignmentId . ", result = " . $result);
            return $result > 0;
        } catch (Exception $e) {
            error_log("Atama kaldırılırken hata: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * PC'deki öğrenci sayısını getir
     */
    public function getPCStudentCount($pcId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM myopc_assignments WHERE computer_id = ?";
            $result = $this->db->fetchOne($sql, [$pcId]);
            return $result ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            error_log("PC öğrenci sayısı alınırken hata: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * PC bilgilerini myopc_assignments tablosundan getir
     */
    public function getPCInfoFromAssignments($pcId) {
        try {
            // PC ID'sinden laboratuvar ID'sini hesapla
            $labId = floor($pcId / 100);
            $pcNumber = $pcId % 100;
            
            $sql = "SELECT 
                        a.computer_id as pc_id,
                        ? as pc_number,
                        ? as lab_id,
                        lc.lab_name,
                        COUNT(a.assignment_id) as student_count,
                        MAX(a.created_at) as last_assignment
                    FROM myopc_assignments a
                    LEFT JOIN myopc_lab_computers lc ON FLOOR(a.computer_id / 100) = lc.computer_id
                    WHERE a.computer_id = ?
                    GROUP BY a.computer_id, lc.lab_name";
            
            $result = $this->db->fetchOne($sql, [$pcNumber, $labId, $pcId]);
            
            if ($result) {
                // PC durumunu belirle
                $result['status'] = $result['student_count'] > 0 ? 'occupied' : 'available';
                $result['name'] = $result['pc_number'];
                $result['number'] = $result['pc_number'];
            } else {
                // Eğer atama yoksa, laboratuvar bilgilerini al
                $labSql = "SELECT lab_name FROM myopc_lab_computers WHERE computer_id = ?";
                $labInfo = $this->db->fetchOne($labSql, [$labId]);
                
                $result = [
                    'pc_id' => $pcId,
                    'pc_number' => $pcNumber,
                    'lab_id' => $labId,
                    'lab_name' => $labInfo ? $labInfo['lab_name'] : 'Bilinmiyor',
                    'student_count' => 0,
                    'last_assignment' => null,
                    'status' => 'available',
                    'name' => $pcNumber,
                    'number' => $pcNumber
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("PC bilgileri getirilirken hata: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Tüm atamaları export için getir
     */
    public function getAllAssignmentsForExport() {
        try {
            // Veritabanı bağlantısını kontrol et
            if (!$this->db) {
                error_log("Export - Veritabanı bağlantısı yok");
                return [];
            }
            
            $sql = "
                SELECT 
                    a.assignment_id,
                    s.full_name,
                    s.sdt_nmbr,
                    s.academic_year,
                    l.lab_name,
                    (a.computer_id % 100) as pc_number,
                    a.created_at
                FROM myopc_assignments a
                JOIN myopc_students s ON a.student_id = s.student_id
                JOIN myopc_lab_computers l ON FLOOR(a.computer_id / 100) = l.computer_id
                WHERE a.computer_id IS NOT NULL
                ORDER BY l.lab_name, pc_number, s.full_name
            ";
            
            $result = $this->db->fetchAll($sql);
            error_log("Export - SQL sonucu: " . (is_array($result) ? count($result) : 'null') . " kayıt");
            return $result ?: [];
        } catch (Exception $e) {
            error_log("Export atamaları alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Belirli bir laboratuvarın atamalarını export için getir
     */
    public function getLabAssignmentsForExport($computerId) {
        try {
            // Veritabanı bağlantısını kontrol et
            if (!$this->db) {
                error_log("Lab Export - Veritabanı bağlantısı yok");
                return [];
            }
            
            $sql = "
                SELECT 
                    a.assignment_id,
                    s.full_name,
                    s.sdt_nmbr,
                    s.academic_year,
                    (a.computer_id % 100) as pc_number,
                    a.created_at
                FROM myopc_assignments a
                JOIN myopc_students s ON a.student_id = s.student_id
                WHERE FLOOR(a.computer_id / 100) = ? 
                AND a.computer_id IS NOT NULL
                ORDER BY pc_number, s.full_name
            ";
            
            $result = $this->db->fetchAll($sql, [$computerId]);
            error_log("Lab Export - Computer ID: $computerId, Sonuç: " . (is_array($result) ? count($result) : 'null') . " kayıt");
            error_log("Lab Export - SQL: " . $sql);
            error_log("Lab Export - Parameters: " . json_encode([$computerId]));
            if (!empty($result)) {
                error_log("Lab Export - İlk kayıt: " . json_encode($result[0]));
            }
            return $result ?: [];
        } catch (Exception $e) {
            error_log("Laboratuvar export atamaları alınırken hata: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Atama istatistiklerini export için getir
     */
    public function getAssignmentStatsForExport() {
        try {
            // Veritabanı bağlantısını kontrol et
            if (!$this->db) {
                error_log("Stats Export - Veritabanı bağlantısı yok");
                return [
                    'total_assignments' => 0,
                    'total_students' => 0,
                    'total_labs' => 0,
                    'total_pcs' => 0,
                    'occupied_pcs' => 0,
                    'available_pcs' => 0,
                    'lab_details' => []
                ];
            }
            
            // Genel istatistikler
            $totalAssignments = $this->db->fetchOne("SELECT COUNT(*) as count FROM myopc_assignments")['count'] ?? 0;
            $totalStudents = $this->db->fetchOne("SELECT COUNT(*) as count FROM myopc_students")['count'] ?? 0;
            $totalLabs = $this->db->fetchOne("SELECT COUNT(*) as count FROM myopc_lab_computers")['count'] ?? 0;
            
            // PC istatistikleri
            $pcStats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total_pcs,
                    SUM(CASE WHEN student_count > 0 THEN 1 ELSE 0 END) as occupied_pcs,
                    SUM(CASE WHEN student_count = 0 THEN 1 ELSE 0 END) as available_pcs
                FROM (
                    SELECT 
                        FLOOR(computer_id / 100) as lab_id,
                        (computer_id % 100) as pc_number,
                        COUNT(*) as student_count
                    FROM myopc_assignments 
                    GROUP BY FLOOR(computer_id / 100), (computer_id % 100)
                ) pc_counts
            ");
            
            // Laboratuvar detayları
            $labDetails = $this->db->fetchAll("
                SELECT 
                    l.lab_name,
                    l.pc_count as total_pcs,
                    COALESCE(occupied.occupied_pcs, 0) as occupied_pcs,
                    (l.pc_count - COALESCE(occupied.occupied_pcs, 0)) as available_pcs,
                    COALESCE(assignment_counts.assignment_count, 0) as assignment_count
                FROM myopc_lab_computers l
                LEFT JOIN (
                    SELECT 
                        FLOOR(computer_id / 100) as lab_id,
                        COUNT(DISTINCT CONCAT(FLOOR(computer_id / 100), '-', (computer_id % 100))) as occupied_pcs
                    FROM myopc_assignments 
                    GROUP BY FLOOR(computer_id / 100)
                ) occupied ON l.computer_id = occupied.lab_id
                LEFT JOIN (
                    SELECT 
                        FLOOR(computer_id / 100) as lab_id,
                        COUNT(*) as assignment_count
                    FROM myopc_assignments 
                    GROUP BY FLOOR(computer_id / 100)
                ) assignment_counts ON l.computer_id = assignment_counts.lab_id
                ORDER BY l.lab_name
            ");
            
            return [
                'total_assignments' => $totalAssignments,
                'total_students' => $totalStudents,
                'total_labs' => $totalLabs,
                'total_pcs' => $pcStats['total_pcs'] ?? 0,
                'occupied_pcs' => $pcStats['occupied_pcs'] ?? 0,
                'available_pcs' => $pcStats['available_pcs'] ?? 0,
                'lab_details' => $labDetails
            ];
        } catch (Exception $e) {
            error_log("Export istatistikleri alınırken hata: " . $e->getMessage());
            return [
                'total_assignments' => 0,
                'total_students' => 0,
                'total_labs' => 0,
                'total_pcs' => 0,
                'occupied_pcs' => 0,
                'available_pcs' => 0,
                'lab_details' => []
            ];
        }
    }

    /**
     * Laboratuvar bilgilerini getir
     * Get lab information
     */
    public function getLabInfo($labId) {
        try {
            $sql = "SELECT computer_id, lab_name, pc_count, user_type, created_at, updated_at, created_by 
                    FROM myopc_lab_computers 
                    WHERE computer_id = ?";
            $result = $this->db->fetchOne($sql, [$labId]);
            return $result;
        } catch (Exception $e) {
            error_log("getLabInfo hatası: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Laboratuvar PC'lerini ve atamalarını getir
     * Get lab PCs with assignments
     */
    public function getLabPCsWithAssignments($labId) {
        try {
            $pcs = [];
            
            // Laboratuvar bilgilerini al
            $labInfo = $this->getLabInfo($labId);
            if (!$labInfo) {
                return [];
            }
            
            $pcCount = $labInfo['pc_count'];
            
            // Her PC için veri oluştur
            for ($i = 1; $i <= $pcCount; $i++) {
                $pcId = $labId * 100 + $i;
                $pcNumber = $i;
                
                // Bu PC'ye atanmış öğrencileri getir
                $sql = "SELECT s.full_name, s.academic_year, s.sdt_nmbr
                        FROM myopc_assignments a
                        JOIN myopc_students s ON a.student_id = s.student_id
                        WHERE a.computer_id = ?";
                $students = $this->db->fetchAll($sql, [$pcId]);
                
                $pcs[] = [
                    'pc_id' => $pcId,
                    'pc_number' => $pcNumber,
                    'students' => $students,
                    'is_occupied' => !empty($students)
                ];
            }
            
            return $pcs;
            
        } catch (Exception $e) {
            error_log("getLabPCsWithAssignments hatası: " . $e->getMessage());
            return [];
        }
    }
}
