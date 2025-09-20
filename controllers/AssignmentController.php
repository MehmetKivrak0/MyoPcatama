<?php

// Cache kontrolü - okul sunucusu için
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once '../config/db.php';
require_once '../models/Assignment.php';
require_once '../models/Student.php';
require_once '../models/Lab.php';
require_once '../models/Pc.php';

class AssignmentController {
    private $assignmentModel;
    private $studentModel;
    private $labModel;
    private $pcModel;
    private $database;

    public function __construct($database = null) {
        if ($database === null) {
            $this->database = Database::getInstance();
        } else {
            $this->database = $database;
        }

        $this->assignmentModel = new Assignment($this->database);
        $this->studentModel = new Student($this->database);
        $this->labModel = new Lab($this->database);
        $this->pcModel = new Pc($this->database);
    }

    /**
     * Atama sayfasını göster
     */
    public function showAssignmentPage() {
        try {
            // Tüm laboratuvarları getir
            $labs = $this->labModel->getAll();

            // Tüm öğrencileri getir
            $students = $this->assignmentModel->getAllStudents();

            // Varsayılan laboratuvar seçiliyse, o laboratuvardaki PC'leri ve atamaları getir
            $selectedComputerId = $_GET['computer_id'] ?? null;
            $pcs = [];
            $assignments = [];

            if ($selectedComputerId) {
                $assignments = $this->assignmentModel->getPCAssignmentsByLab($selectedComputerId);
                $pcs = $assignments; // PC'ler zaten atama bilgilerini içeriyor
            }

            // Atama istatistiklerini getir
            $stats = $selectedComputerId ? $this->assignmentModel->getAssignmentStats($selectedComputerId) : null;

            include '../views/assign.php';
        } catch (Exception $e) {
            error_log("Atama sayfası gösterilirken hata: " . $e->getMessage());
            $this->showError("Atama sayfası yüklenirken bir hata oluştu.");
        }
    }

    /**
     * Öğrenci ataması yap
     */
    public function assignStudent() {
        // Tüm hata raporlamayı kapat ve output buffer'ı temizle
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Geçersiz istek metodu");
            }

            $studentId = $_POST['student_id'] ?? null;
            $pcId = $_POST['pc_id'] ?? null;
            $computerId = $_POST['computer_id'] ?? null;

            error_log("Assign student - studentId: $studentId, pcId: $pcId, computerId: $computerId");

            if (!$studentId || !$pcId || !$computerId) {
                throw new Exception("Gerekli parametreler eksik - studentId: $studentId, pcId: $pcId, computerId: $computerId");
            }

            // PC'deki mevcut öğrenci sayısını kontrol et
            $currentStudentCount = $this->assignmentModel->getPCStudentCount($pcId);
            
            // Maksimum öğrenci sayısını veritabanından al
            $maxStudentsPerPC = $this->labModel->getMaxStudentsPerPC($computerId);

            if ($currentStudentCount >= $maxStudentsPerPC) {
                throw new Exception("PC $pcId'ye maksimum $maxStudentsPerPC öğrenci atanabilir. $currentStudentCount öğrenci atamaya çalışıyorsunuz.");
            }

            // Öğrencinin bu laboratuvarda zaten atanıp atanmadığını kontrol et
            $existingAssignmentInLab = $this->assignmentModel->isStudentAssignedToLab($studentId, $computerId);
            if ($existingAssignmentInLab) {
                // Hangi PC'ye atanmış olduğunu bul
                $existingPcNumber = $existingAssignmentInLab % 100;
                throw new Exception("Bu öğrenci zaten bu laboratuvarda PC{$existingPcNumber}'ye atanmış. Aynı laboratuvarda bir öğrenci sadece bir PC'ye atanabilir.");
            }

            // Öğrenci ataması yap (çoklu atama destekli)
            $result = $this->assignmentModel->assignStudent($studentId, $pcId, $computerId);

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Öğrenci başarıyla atandı'
                ]);
            } else {
                throw new Exception("Öğrenci ataması yapılamadı");
            }
        } catch (Exception $e) {
            error_log("Öğrenci ataması yapılırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Öğrenci atamasını kaldır
     */
    public function unassignStudent() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Geçersiz istek metodu");
            }

            $studentId = $_POST['student_id'] ?? null;
            $computerId = $_POST['computer_id'] ?? null;

            if (!$studentId || !$computerId) {
                throw new Exception("Gerekli parametreler eksik");
            }

            // Öğrenci atamasını kaldır
            $result = $this->assignmentModel->unassignStudent($studentId, $computerId);

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Öğrenci ataması kaldırıldı'
                ]);
            } else {
                throw new Exception("Öğrenci ataması kaldırılamadı");
            }
        } catch (Exception $e) {
            error_log("Öğrenci ataması kaldırılırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Laboratuvar değiştirildiğinde AJAX isteği
     */
    public function getLabData() {
        // Tüm hata raporlamayı kapat ve output buffer'ı temizle
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            $computerId = $_GET['computer_id'] ?? null;

            if (!$computerId) {
                throw new Exception("Computer ID gerekli");
            }

            // PC'leri ve atamaları getir (aynı veri)
            $assignments = $this->assignmentModel->getPCAssignmentsByLab($computerId);
            $pcs = $assignments;

            // İstatistikleri getir
            $stats = $this->assignmentModel->getAssignmentStats($computerId);

            $this->sendJsonResponse([
                'success' => true,
                'data' => [
                    'pcs' => $pcs,
                    'assignments' => $assignments,
                    'stats' => $stats
                ]
            ]);
        } catch (Exception $e) {
            error_log("Laboratuvar verileri alınırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (Error $e) {
            error_log("Laboratuvar verileri alınırken PHP hatası: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Sunucu hatası: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toplu atama yap
     */
    public function bulkAssign() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            error_log("🚀 === bulkAssign BAŞLADI ===");

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Geçersiz istek metodu");
            }

            $assignmentsJson = $_POST['assignments'] ?? '';
            $computerId = $_POST['computer_id'] ?? null;

            error_log("📋 Gelen computerId: $computerId, Type: " . gettype($computerId));
            error_log("📋 Gelen assignmentsJson: $assignmentsJson");

            if (!$computerId || empty($assignmentsJson)) {
                throw new Exception("Gerekli parametreler eksik - computerId: $computerId, assignmentsJson: " . (empty($assignmentsJson) ? 'BOŞ' : 'DOLU'));
            }

            // JSON'u decode et
            $assignments = json_decode($assignmentsJson, true);
            if (!$assignments || !is_array($assignments)) {
                error_log("❌ JSON decode hatası: " . json_last_error_msg());
                throw new Exception("Geçersiz atama verisi");
            }

            error_log("📋 Decode edilen assignments: " . print_r($assignments, true));
            error_log("📋 Atama sayısı: " . count($assignments));

            // Her atamayı tek tek yap
            $successCount = 0;
            $errorCount = 0;
            
            // Maksimum öğrenci sayısını veritabanından al
            $maxStudentsPerPC = $this->labModel->getMaxStudentsPerPC($computerId);

            // PC'ye göre grupla ve sınır kontrolü yap
            $pcStudentCounts = [];
            foreach ($assignments as $assignment) {
                $pcId = (int)($assignment['pc_id'] ?? 0);
                if ($pcId) {
                    if (!isset($pcStudentCounts[$pcId])) {
                        $pcStudentCounts[$pcId] = $this->assignmentModel->getPCStudentCount($pcId);
                    }
                    $pcStudentCounts[$pcId]++;
                }
            }

            // Sınır aşımı kontrolü
            foreach ($pcStudentCounts as $pcId => $totalCount) {
                if ($totalCount > $maxStudentsPerPC) {
                    throw new Exception("PC $pcId'ye maksimum $maxStudentsPerPC öğrenci atanabilir. $totalCount öğrenci atamaya çalışıyorsunuz.");
                }
            }

            foreach ($assignments as $index => $assignment) {
                error_log("📋 İşlenen atama $index: " . print_r($assignment, true));

                $studentId = (int)($assignment['student_id'] ?? 0);
                $pcId = (int)($assignment['pc_id'] ?? 0); // PC ID'si integer'a çevir

                error_log("📋 Dönüştürülen değerler - studentId: $studentId, pcId: $pcId");

                if (!$studentId || !$pcId) {
                    error_log("❌ Geçersiz atama verisi: studentId=$studentId, pcId=$pcId");
                    $errorCount++;
                    continue;
                }

                // PC ID'si ve laboratuvar ID'si ile atama yap
                error_log("📋 assignStudent çağrılıyor - studentId: $studentId, pcId: $pcId, computerId: $computerId");
                $result = $this->assignmentModel->assignStudent($studentId, $pcId, $computerId);

                if ($result) {
                    error_log("✅ Atama başarılı - studentId: $studentId, pcId: $pcId");
                    $successCount++;
                } else {
                    error_log("❌ Atama başarısız - studentId: $studentId, pcId: $pcId");
                    $errorCount++;
                }
            }

            error_log("📋 Atama sonuçları - Başarılı: $successCount, Başarısız: $errorCount");

            if ($successCount > 0) {
                error_log("✅ Toplu atama tamamlandı - $successCount başarılı");
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => "$successCount öğrenci başarıyla atandı" . ($errorCount > 0 ? ", $errorCount atama başarısız" : "")
                ]);
            } else {
                error_log("❌ Hiçbir atama yapılamadı");
                throw new Exception("Hiçbir atama yapılamadı");
            }
        } catch (Exception $e) {
            error_log("Toplu atama yapılırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * PC'den tüm öğrencileri kaldır
     */
    public function clearPCStudents() {
        try {
            error_reporting(0);
            ini_set('display_errors', 0);
            ob_clean();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Geçersiz istek metodu");
            }

            $pcNumber = $_POST['pc_id'] ?? null;
            $computerId = $_POST['computer_id'] ?? null;

            if (!$pcNumber || !$computerId) {
                throw new Exception("Gerekli parametreler eksik");
            }

            // PC ID'sini hesapla (computer_id * 100 + pc_number)
            $pcId = $computerId * 100 + $pcNumber;

            // Tüm öğrencileri kaldır
            $result = $this->assignmentModel->unassignAllStudentsFromPC($pcId);

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'PC\'deki tüm öğrenciler kaldırıldı'
                ]);
            } else {
                throw new Exception("Öğrenciler kaldırılamadı");
            }
        } catch (Exception $e) {
            error_log("PC öğrencileri kaldırılırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Atama geçmişini getir
     */
    public function getAssignmentHistory() {
        try {
            $studentId = $_GET['student_id'] ?? null;
            $pcId = $_GET['pc_id'] ?? null;
            $computerId = $_GET['computer_id'] ?? null;

            $history = $this->assignmentModel->getAssignmentHistory($studentId, $pcId, $computerId);

            $this->sendJsonResponse([
                'success' => true,
                'data' => $history
            ]);
        } catch (Exception $e) {
            error_log("Atama geçmişi alınırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Atama durumunu kontrol et
     */
    public function checkAssignmentStatus() {
        try {
            $studentId = $_GET['student_id'] ?? null;
            $computerId = $_GET['computer_id'] ?? null;

            if (!$studentId || !$computerId) {
                throw new Exception("Gerekli parametreler eksik");
            }

            $pcId = $this->assignmentModel->isStudentAssigned($studentId, $computerId);

            $this->sendJsonResponse([
                'success' => true,
                'assigned' => $pcId !== false,
                'pc_id' => $pcId
            ]);
        } catch (Exception $e) {
            error_log("Atama durumu kontrol edilirken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * JSON yanıt gönder
     */
    private function sendJsonResponse($data) {
        // Tüm output buffer'ları temizle
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Hata raporlamayı kapat
        error_reporting(0);
        ini_set('display_errors', 0);

        // JSON header'ı gönder
        header('Content-Type: application/json; charset=utf-8');

        // JSON encode et
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            // JSON encode hatası
            $errorData = [
                'success' => false,
                'message' => 'JSON encode hatası: ' . json_last_error_msg()
            ];
            echo json_encode($errorData);
        } else {
            echo $json;
        }

        exit;
    }

    /**
     * PC sayısını güncelle
     */
    public function updatePCCount() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Sadece POST metodu kabul edilir'
                ]);
                return;
            }

            $computerId = $_POST['computer_id'] ?? null;
            $pcCount = $_POST['pc_count'] ?? null;

            if (!$computerId || !$pcCount) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Gerekli parametreler eksik'
                ]);
                return;
            }

            // PC sayısını güncelle (lab yapısı da güncellenir)
            $result = $this->labModel->updatePCCount($computerId, $pcCount);

            if ($result['success']) {
                // Güncellenmiş lab verilerini de döndür
                $labData = $this->labModel->getById($computerId);
                $assignments = $this->assignmentModel->getPCAssignmentsByLab($computerId);
                $stats = $this->assignmentModel->getAssignmentStats($computerId);

                $this->sendJsonResponse([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'lab' => $labData,
                        'pcs' => $assignments,
                        'stats' => $stats
                    ]
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (Exception $e) {
            error_log("PC sayısı güncelleme hatası: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Sunucu hatası: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Laboratuvar bilgilerini getir
     */
    public function getLabInfo() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            $computerId = $_GET['computer_id'] ?? null;

            if (!$computerId) {
                throw new Exception("Computer ID gerekli");
            }

            // Laboratuvar bilgilerini getir
            $lab = $this->labModel->getById($computerId);

            if (!$lab) {
                throw new Exception("Laboratuvar bulunamadı");
            }

            $this->sendJsonResponse([
                'success' => true,
                'lab_name' => $lab['lab_name'],
                'pc_count' => $lab['pc_count'],
                'computer_id' => $lab['computer_id']
            ]);

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Atama için öğrenci verilerini getir
     */
    public function getStudentsForAssignment() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            $computerId = $_GET['computer_id'] ?? null;
            $pcId = $_GET['pc_id'] ?? null;
            $maxStudentsPerCard = $_GET['max_students'] ?? 5;
            
            // Filtreleme parametreleri
            $search = $_GET['search'] ?? '';
            $year = $_GET['year'] ?? '';
            $department = $_GET['department'] ?? '';

            if (!$computerId || !$pcId) {
                throw new Exception("Gerekli parametreler eksik");
            }

            // Öğrencileri filtreleme ile getir
            require_once '../models/Student.php';
            $db = \Database::getInstance();
            $studentModel = new \Student($db);
            $allStudents = $studentModel->getStudentsPaginated(0, 1000, $year, $search, $department);

            // Her öğrencinin atama durumunu kontrol et
            $studentsWithAssignmentStatus = [];
            foreach ($allStudents as $student) {
                // Bu laboratuvarda atanmış mı kontrol et
                $isAssignedToCurrentLab = $this->assignmentModel->isStudentAssignedToLabBoolean($student['student_id'], $computerId);
                
                // Bu PC'ye atanmış mı kontrol et
                $isAssignedToCurrentPC = $this->assignmentModel->isStudentAssignedToPC($student['student_id'], $pcId);
                
                // Öğrenci atanabilir mi kontrol et:
                // Sadece bu laboratuvarda atanmamış olmalı (diğer lablar etkilemez)
                $canBeAssigned = !$isAssignedToCurrentLab;
                
                // Debug log
                error_log("DEBUG STUDENT FILTER - Öğrenci: {$student['full_name']} (ID: {$student['student_id']}) - Lab: $computerId, PC: $pcId - CurrentLab: " . ($isAssignedToCurrentLab ? 'YES' : 'NO') . " - CurrentPC: " . ($isAssignedToCurrentPC ? 'YES' : 'NO') . " - CanAssign: " . ($canBeAssigned ? 'YES' : 'NO'));
                
                $studentsWithAssignmentStatus[] = [
                    'student_id' => $student['student_id'],
                    'full_name' => $student['full_name'],
                    'sdt_nmbr' => $student['sdt_nmbr'],
                    'academic_year' => $student['academic_year'],
                    'is_assigned_to_current_lab' => $isAssignedToCurrentLab !== false,
                    'is_assigned_to_current_pc' => $isAssignedToCurrentPC,
                    'can_be_assigned' => $canBeAssigned
                ];
            }

            // Maksimum öğrenci sayısını veritabanından al
            $maxStudentsPerPC = $this->labModel->getMaxStudentsPerPC($computerId);
            
            $this->sendJsonResponse([
                'success' => true,
                'students' => $studentsWithAssignmentStatus,
                'maxStudentsPerCard' => $maxStudentsPerPC,
                'maxStudentsPerPC' => $maxStudentsPerPC,
                'totalStudents' => count($studentsWithAssignmentStatus)
            ]);

        } catch (Exception $e) {
            error_log("Atama için öğrenci verileri alınırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Laboratuvar PC'lerini getir (güncelleme için)
     */
    public function getLabPCs($labId = null) {
        try {
            // Eğer parametre olarak labId gelmemişse GET'ten al
            $computerId = $labId ?? $_GET['lab_id'] ?? $_GET['computer_id'] ?? null;
            
            if (!$computerId) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Laboratuvar ID gerekli - labId: ' . $labId . ', GET lab_id: ' . ($_GET['lab_id'] ?? 'yok')
                ]);
                return;
            }

            // Laboratuvar bilgilerini al
            $labInfo = $this->assignmentModel->getLabInfo($computerId);
            if (!$labInfo) {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Laboratuvar bulunamadı - ID: ' . $computerId
                ]);
                return;
            }

            // PC'leri ve atamalarını al
            $pcs = $this->assignmentModel->getLabPCsWithAssignments($computerId);
            
            // Maksimum öğrenci sayısını al
            $maxStudentsPerPC = $this->labModel->getMaxStudentsPerPC($computerId);

            $this->sendJsonResponse([
                'success' => true,
                'pcs' => $pcs,
                'lab_info' => $labInfo,
                'maxStudentsPerPC' => $maxStudentsPerPC
            ]);

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * PC'deki öğrencileri getir
     */
    public function getPCStudents() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            $pcId = $_GET['pc_id'] ?? null;

            if (!$pcId) {
                throw new Exception("PC ID gerekli");
            }

            $students = $this->assignmentModel->getStudentsByPC($pcId);

            $this->sendJsonResponse([
                'success' => true,
                'students' => $students
            ]);

        } catch (Exception $e) {
            error_log("PC öğrencileri alınırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Boş PC'leri getir (transfer için)
     */
    public function getAvailablePCs() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            $computerId = $_GET['computer_id'] ?? null;
            $excludePc = $_GET['exclude_pc'] ?? null;

            if (!$computerId) {
                throw new Exception("Laboratuvar ID gerekli");
            }

            $pcs = $this->assignmentModel->getAvailablePCs($computerId, $excludePc);

            $this->sendJsonResponse([
                'success' => true,
                'pcs' => $pcs
            ]);

        } catch (Exception $e) {
            error_log("Boş PC'ler alınırken hata: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Öğrenci transfer et
     */
    public function transferStudent() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ob_clean();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Geçersiz istek metodu");
            }

            $studentId = $_POST['student_id'] ?? null;
            $newPcId = $_POST['new_pc_id'] ?? null;
            $computerId = $_POST['computer_id'] ?? null;

            if (!$studentId || !$newPcId || !$computerId) {
                throw new Exception("Gerekli parametreler eksik");
            }

            $result = $this->assignmentModel->transferStudent($studentId, $newPcId, $computerId);

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Öğrenci başarıyla transfer edildi'
                ]);
            } else {
                throw new Exception("Transfer işlemi başarısız");
            }

        } catch (Exception $e) {
            error_log("Öğrenci transfer hatası: " . $e->getMessage());
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Toplam atama sayısını getir
     */
    public function getAssignmentCount() {
        try {
            $count = $this->assignmentModel->getAssignmentCount();
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

    /**
     * Geçersiz atamaları temizle
     */
    public function cleanInvalidAssignments() {
        // Tüm output buffer'ları temizle
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Hata raporlamayı kapat
        error_reporting(0);
        ini_set('display_errors', 0);

        try {
            error_log("cleanInvalidAssignments başlatıldı");

            $result = $this->assignmentModel->cleanInvalidAssignments();

            error_log("cleanInvalidAssignments sonucu: " . ($result ? 'true' : 'false'));

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Geçersiz atamalar temizlendi'
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Atamalar temizlenemedi'
                ]);
            }
        } catch (Exception $e) {
            error_log("Atamalar temizlenirken hata: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ]);
        } catch (Error $e) {
            error_log("Atamalar temizlenirken PHP hatası: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'PHP Hatası: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Atama verilerini doğrula ve düzelt
     */
    public function validateAndFixAssignments() {
        // Tüm output buffer'ları temizle
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Hata raporlamayı kapat
        error_reporting(0);
        ini_set('display_errors', 0);

        try {
            error_log("validateAndFixAssignments başlatıldı");

            $result = $this->assignmentModel->validateAndFixAssignments();

            error_log("validateAndFixAssignments sonucu: " . ($result ? 'true' : 'false'));

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Atama verileri doğrulandı ve düzeltildi'
                ]);
            } else {
                $this->sendJsonResponse([
                    'success' => false,
                    'message' => 'Atama verileri düzeltilemedi'
                ]);
            }
        } catch (Exception $e) {
            error_log("Atama verileri düzeltilirken hata: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Hata: ' . $e->getMessage()
            ]);
        } catch (Error $e) {
            error_log("Atama verileri düzeltilirken PHP hatası: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'PHP Hatası: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * PC detaylarını getir
     */
    public function getPCDetails() {
        try {
            $pcId = $_POST['pc_id'] ?? null;

            if (!$pcId) {
                throw new Exception("PC ID gerekli");
            }

            // PC bilgilerini myopc_assignments tablosundan getir
            $pcInfo = $this->assignmentModel->getPCInfoFromAssignments($pcId);
            if (!$pcInfo) {
                throw new Exception("PC bulunamadı");
            }

            // Laboratuvar bilgilerini getir
            $lab = $this->labModel->getById($pcInfo['lab_id']);

            // PC'ye atanmış öğrencileri getir
            $students = $this->assignmentModel->getPCStudents($pcId);

            $this->sendJsonResponse([
                'success' => true,
                'pc' => $pcInfo,
                'lab' => $lab,
                'students' => $students
            ]);

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * PC'den öğrenci kaldır
     */
    public function removeStudentFromPC() {
        try {
            $assignmentId = $_POST['assignment_id'] ?? null;

            if (!$assignmentId) {
                throw new Exception("Atama ID gerekli");
            }

            $result = $this->assignmentModel->removeAssignment($assignmentId);

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => 'Öğrenci PC\'den kaldırıldı'
                ]);
            } else {
                throw new Exception("Öğrenci kaldırılamadı");
            }

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Laboratuvar için maksimum öğrenci sayısını getir
     */
    public function getLabMaxStudents() {
        try {
            $computerId = $_GET['computer_id'] ?? null;

            if (!$computerId) {
                throw new Exception("Laboratuvar ID gerekli");
            }

            $maxStudents = $this->labModel->getMaxStudentsPerPC($computerId);

            $this->sendJsonResponse([
                'success' => true,
                'maxStudentsPerPC' => $maxStudents
            ]);

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Maksimum öğrenci sayısını güncelle
     */
    public function updateMaxStudents() {
        try {
            $computerId = $_POST['computer_id'] ?? null;
            $maxStudentsPerPC = $_POST['max_students_per_pc'] ?? 4;

            if (!$computerId) {
                throw new Exception("Laboratuvar ID gerekli");
            }

            // Önce mevcut PC'lerdeki öğrenci sayılarını kontrol et
            $currentAssignments = $this->assignmentModel->getPCAssignmentsByLab($computerId);
            $exceededPCs = [];

            foreach ($currentAssignments as $pc) {
                if ($pc['student_count'] > $maxStudentsPerPC) {
                    $exceededPCs[] = [
                        'pc_number' => $pc['pc_number'],
                        'current_students' => $pc['student_count'],
                        'max_allowed' => $maxStudentsPerPC
                    ];
                }
            }

            // Eğer sınırı aşan PC'ler varsa hata döndür
            if (!empty($exceededPCs)) {
                $errorMessage = "Maksimum öğrenci sayısı azaltılamaz! Aşağıdaki PC'lerde mevcut öğrenci sayısı yeni sınırdan fazla:\n\n";
                foreach ($exceededPCs as $pc) {
                    $errorMessage .= "• PC{$pc['pc_number']}: {$pc['current_students']} öğrenci (maksimum: {$pc['max_allowed']})\n";
                }
                $errorMessage .= "\nLütfen önce bu PC'lerden öğrenci kaldırın, sonra tekrar deneyin.";

                $this->sendJsonResponse([
                    'success' => false,
                    'message' => $errorMessage,
                    'exceeded_pcs' => $exceededPCs
                ]);
                return;
            }

            // Maksimum öğrenci sayısını güncelle
            $result = $this->labModel->updateMaxStudentsPerPC($computerId, $maxStudentsPerPC);

            if ($result) {
                $this->sendJsonResponse([
                    'success' => true,
                    'message' => "Maksimum öğrenci sayısı başarıyla güncellendi! (PC başına: {$maxStudentsPerPC} öğrenci)"
                ]);
            } else {
                throw new Exception("Maksimum öğrenci sayısı güncellenemedi");
            }

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Hata sayfası göster
     */
    private function showError($message) {
        echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
    }

    /**
     * Tüm atamaları Excel formatında dışa aktar
     */
    public function exportAssignments() {
        try {
            // Output buffering başlat ve mevcut çıktıyı temizle
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            // PhpSpreadsheet kütüphanesini yükle
            require_once '../vendor/autoload.php';

            // Tüm atamaları getir
            $assignments = $this->assignmentModel->getAllAssignmentsForExport();
            
            // Debug: Atama sayısını kontrol et
            error_log("Export - Atama sayısı: " . count($assignments));
            
            // Eğer atama yoksa boş bir Excel dosyası oluştur
            if (empty($assignments)) {
                $assignments = [
                    [
                        'assignment_id' => 'N/A',
                        'full_name' => 'Veri Bulunamadı',
                        'sdt_nmbr' => 'N/A',
                        'academic_year' => 'N/A',
                        'lab_name' => 'N/A',
                        'pc_number' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
            }

            // Yeni spreadsheet oluştur
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Atamalar');

            // Başlık satırı - Template formatına uygun
            $headers = [
                'A' => 'Öğrenci No',
                'B' => 'Ad',
                'C' => 'Soyad',
                'D' => 'Akademik Yıl',
                'E' => 'Bölüm',
                'F' => 'Sınıf Durumu',
                'G' => 'Laboratuvar Adı',
                'H' => 'PC Numarası',
                'I' => 'Atama Tarihi'
            ];

            // Başlıkları yaz
            foreach ($headers as $col => $header) {
                $sheet->setCellValue($col . '1', $header);
            }

            // Başlık stilini ayarla
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '366092']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];

            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

            // Verileri yaz - Template formatına uygun
            $row = 2;
            foreach ($assignments as $assignment) {
                // Ad ve soyadı ayır
                $nameParts = explode(' ', $assignment['full_name'], 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
                
                $sheet->setCellValue('A' . $row, $assignment['sdt_nmbr']); // Öğrenci No
                $sheet->setCellValue('B' . $row, $firstName); // Ad
                $sheet->setCellValue('C' . $row, $lastName); // Soyad
                $sheet->setCellValue('D' . $row, $assignment['academic_year']); // Akademik Yıl
                $sheet->setCellValue('E' . $row, $assignment['department'] ?? 'N/A'); // Bölüm
                $sheet->setCellValue('F' . $row, $assignment['class_level'] ?? 'N/A'); // Sınıf Durumu
                $sheet->setCellValue('G' . $row, $assignment['lab_name']); // Laboratuvar Adı
                $pcNumber = $assignment['pc_number'] ?? 0;
                $sheet->setCellValue('H' . $row, 'PC' . str_pad($pcNumber, 2, '0', STR_PAD_LEFT)); // PC Numarası
                $sheet->setCellValue('I' . $row, $assignment['created_at'] ?? date('Y-m-d H:i:s')); // Atama Tarihi
                $row++;
            }

            // Sütun genişliklerini ayarla - Template formatına uygun
            $sheet->getColumnDimension('A')->setWidth(12); // Öğrenci No
            $sheet->getColumnDimension('B')->setWidth(15); // Ad
            $sheet->getColumnDimension('C')->setWidth(15); // Soyad
            $sheet->getColumnDimension('D')->setWidth(12); // Akademik Yıl
            $sheet->getColumnDimension('E')->setWidth(25); // Bölüm
            $sheet->getColumnDimension('F')->setWidth(15); // Sınıf Durumu
            $sheet->getColumnDimension('G')->setWidth(20); // Laboratuvar Adı
            $sheet->getColumnDimension('H')->setWidth(12); // PC Numarası
            $sheet->getColumnDimension('I')->setWidth(18); // Atama Tarihi

            // Veri satırları için stil
            $dataStyle = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ];

            if ($row > 2) {
                $sheet->getStyle('A2:I' . ($row - 1))->applyFromArray($dataStyle);
            }

            // Excel dosyasını oluştur
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            // Output buffer'ı temizle
            ob_clean();

            // HTTP headers ayarla
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="atamalar_' . date('Y-m-d_H-i-s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Expires: 0');

            // Dosyayı çıktıla
            $writer->save('php://output');
            ob_end_flush();
            exit;

        } catch (Exception $e) {
            // Output buffer'ı temizle
            if (ob_get_level()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Dışa aktarma hatası: ' . $e->getMessage()
            ]);
            ob_end_flush();
        }
    }

    /**
     * Belirli bir laboratuvarın atamalarını Excel formatında dışa aktar
     */
    public function exportLabAssignments() {
        try {
            // Output buffering başlat ve mevcut çıktıyı temizle
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            $computerId = $_GET['computer_id'] ?? null;

            if (!$computerId) {
                throw new Exception('Laboratuvar ID gerekli');
            }

            // PhpSpreadsheet kütüphanesini yükle
            require_once '../vendor/autoload.php';

            // Veritabanı bağlantısını kontrol et
            if (!$this->database) {
                throw new Exception('Veritabanı bağlantısı kurulamadı');
            }
            
            // Laboratuvar atamalarını getir
            $assignments = $this->assignmentModel->getLabAssignmentsForExport($computerId);
            error_log("Lab Export - Computer ID: $computerId, Atama sayısı: " . count($assignments));
            
            $labInfo = $this->labModel->getById($computerId);
            error_log("Lab Export - Lab Info: " . json_encode($labInfo));
            
            // Eğer atama yoksa boş dosya oluştur
            if (empty($assignments)) {
                $assignments = [
                    [
                        'assignment_id' => 'N/A',
                        'full_name' => 'Veri Bulunamadı',
                        'sdt_nmbr' => 'N/A',
                        'academic_year' => 'N/A',
                        'pc_number' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
            }

            // Yeni spreadsheet oluştur
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($labInfo['lab_name'] ?? 'Laboratuvar Atamaları');

            // Başlık satırı - Template formatına uygun
            $headers = [
                'A' => 'Öğrenci No',
                'B' => 'Ad',
                'C' => 'Soyad',
                'D' => 'Akademik Yıl',
                'E' => 'Bölüm',
                'F' => 'Sınıf Durumu',
                'G' => 'PC Numarası',
                'H' => 'Atama Tarihi'
            ];

            // Başlıkları yaz
            foreach ($headers as $col => $header) {
                $sheet->setCellValue($col . '1', $header);
            }

            // Başlık stilini ayarla
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '28a745']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];

            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

            // Verileri yaz - Template formatına uygun
            $row = 2;
            foreach ($assignments as $assignment) {
                // Ad ve soyadı ayır
                $nameParts = explode(' ', $assignment['full_name'], 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
                
                $sheet->setCellValue('A' . $row, $assignment['sdt_nmbr']); // Öğrenci No
                $sheet->setCellValue('B' . $row, $firstName); // Ad
                $sheet->setCellValue('C' . $row, $lastName); // Soyad
                $sheet->setCellValue('D' . $row, $assignment['academic_year']); // Akademik Yıl
                $sheet->setCellValue('E' . $row, $assignment['department'] ?? 'N/A'); // Bölüm
                $sheet->setCellValue('F' . $row, $assignment['class_level'] ?? 'N/A'); // Sınıf Durumu
                $pcNumber = $assignment['pc_number'] ?? 0;
                $sheet->setCellValue('G' . $row, 'PC' . str_pad($pcNumber, 2, '0', STR_PAD_LEFT)); // PC Numarası
                $sheet->setCellValue('H' . $row, $assignment['created_at'] ?? date('Y-m-d H:i:s')); // Atama Tarihi
                $row++;
            }

            // Sütun genişliklerini ayarla - Template formatına uygun
            $sheet->getColumnDimension('A')->setWidth(12); // Öğrenci No
            $sheet->getColumnDimension('B')->setWidth(15); // Ad
            $sheet->getColumnDimension('C')->setWidth(15); // Soyad
            $sheet->getColumnDimension('D')->setWidth(12); // Akademik Yıl
            $sheet->getColumnDimension('E')->setWidth(25); // Bölüm
            $sheet->getColumnDimension('F')->setWidth(15); // Sınıf Durumu
            $sheet->getColumnDimension('G')->setWidth(12); // PC Numarası
            $sheet->getColumnDimension('H')->setWidth(18); // Atama Tarihi

            // Veri satırları için stil
            $dataStyle = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ];

            if ($row > 2) {
                $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($dataStyle);
            }

            // Excel dosyasını oluştur
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            // Output buffer'ı temizle
            ob_clean();

            // HTTP headers ayarla
            $labName = $labInfo['lab_name'] ?? 'Laboratuvar';
            $safeLabName = preg_replace('/[^a-zA-Z0-9]/', '_', $labName);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $safeLabName . '_atamalar_' . date('Y-m-d_H-i-s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Expires: 0');

            // Dosyayı çıktıla
            $writer->save('php://output');
            ob_end_flush();
            exit;

        } catch (Exception $e) {
            // Output buffer'ı temizle
            if (ob_get_level()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Laboratuvar dışa aktarma hatası: ' . $e->getMessage()
            ]);
            ob_end_flush();
        }
    }


    /**
     * Atama istatistiklerini Excel formatında dışa aktar
     */
    public function exportAssignmentStats() {
        try {
            // Output buffering başlat ve mevcut çıktıyı temizle
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            // PhpSpreadsheet kütüphanesini yükle
            require_once '../vendor/autoload.php';

            // Veritabanı bağlantısını kontrol et
            if (!$this->database) {
                throw new Exception('Veritabanı bağlantısı kurulamadı');
            }
            
            // İstatistikleri getir
            $stats = $this->assignmentModel->getAssignmentStatsForExport();
            
            // Eğer istatistik yoksa varsayılan değerler
            if (empty($stats) || !is_array($stats)) {
                $stats = [
                    'total_assignments' => 0,
                    'total_students' => 0,
                    'total_labs' => 0,
                    'total_pcs' => 0,
                    'occupied_pcs' => 0,
                    'available_pcs' => 0,
                    'lab_details' => []
                ];
            }

            // Yeni spreadsheet oluştur
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            // 1. Sayfa: Genel İstatistikler
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Genel İstatistikler');

            // Genel istatistikler
            $sheet1->setCellValue('A1', 'ATAMA İSTATİSTİKLERİ');
            $sheet1->setCellValue('A2', 'Rapor Tarihi: ' . date('d.m.Y H:i'));
            $sheet1->setCellValue('A4', 'Toplam Atama Sayısı:');
            $sheet1->setCellValue('B4', $stats['total_assignments']);
            $sheet1->setCellValue('A5', 'Toplam Öğrenci Sayısı:');
            $sheet1->setCellValue('B5', $stats['total_students']);
            $sheet1->setCellValue('A6', 'Toplam PC Sayısı:');
            $sheet1->setCellValue('B6', $stats['total_pcs']);
            $sheet1->setCellValue('A7', 'Toplam Laboratuvar Sayısı:');
            $sheet1->setCellValue('B7', $stats['total_labs']);
            $sheet1->setCellValue('A8', 'Dolu PC Sayısı:');
            $sheet1->setCellValue('B8', $stats['occupied_pcs']);
            $sheet1->setCellValue('A9', 'Boş PC Sayısı:');
            $sheet1->setCellValue('B9', $stats['available_pcs']);

            // 2. Sayfa: Laboratuvar Detayları
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Laboratuvar Detayları');

            $headers = ['A' => 'Laboratuvar Adı', 'B' => 'PC Sayısı', 'C' => 'Dolu PC', 'D' => 'Boş PC', 'E' => 'Atama Sayısı'];
            foreach ($headers as $col => $header) {
                $sheet2->setCellValue($col . '1', $header);
            }

            $row = 2;
            foreach ($stats['lab_details'] as $lab) {
                $sheet2->setCellValue('A' . $row, $lab['lab_name']);
                $sheet2->setCellValue('B' . $row, $lab['total_pcs']);
                $sheet2->setCellValue('C' . $row, $lab['occupied_pcs']);
                $sheet2->setCellValue('D' . $row, $lab['available_pcs']);
                $sheet2->setCellValue('E' . $row, $lab['assignment_count']);
                $row++;
            }

            // Stil ayarları
            $titleStyle = [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet1->getStyle('A1')->applyFromArray($titleStyle);

            // Excel dosyasını oluştur
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            // Output buffer'ı temizle
            ob_clean();

            // HTTP headers ayarla
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="atama_istatistikleri_' . date('Y-m-d_H-i-s') . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Expires: 0');

            // Dosyayı çıktıla
            $writer->save('php://output');
            ob_end_flush();
            exit;

        } catch (Exception $e) {
            // Output buffer'ı temizle
            if (ob_get_level()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'İstatistik dışa aktarma hatası: ' . $e->getMessage()
            ]);
            ob_end_flush();
        }
    }
}

// Controller'ı başlat (doğrudan çağrıldığında)
if (basename($_SERVER['PHP_SELF']) === 'AssignmentController.php') {
    $controller = new AssignmentController();

    $action = $_GET['action'] ?? $_POST['action'] ?? 'show_assignment_page';

    switch ($action) {
        case 'assign_student':
            $controller->assignStudent();
            break;
        case 'unassign_student':
            $controller->unassignStudent();
            break;
        case 'clear_pc_students':
            $controller->clearPCStudents();
            break;
        case 'get_lab_data':
            $controller->getLabData();
            break;
        case 'bulk_assign':
            $controller->bulkAssign();
            break;
        case 'get_assignment_history':
            $controller->getAssignmentHistory();
            break;
        case 'check_assignment_status':
            $controller->checkAssignmentStatus();
            break;
        case 'update_pc_count':
            $controller->updatePCCount();
            break;
        case 'get_lab_max_students':
            $controller->getLabMaxStudents();
            break;
        case 'update_max_students':
            $controller->updateMaxStudents();
            break;
        case 'get_assignment_count':
            $result = $controller->getAssignmentCount();
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
        case 'get_lab_info':
            $controller->getLabInfo();
            break;
        case 'get_students_for_assignment':
            $controller->getStudentsForAssignment();
            break;
        case 'clean_invalid_assignments':
            $controller->cleanInvalidAssignments();
            break;
        case 'validate_and_fix_assignments':
            $controller->validateAndFixAssignments();
            break;
        case 'show_assignment_page':
            $controller->showAssignmentPage();
            break;
        case 'get_lab_pcs':
            $controller->getLabPCs();
            break;
        case 'get_pc_students':
            $controller->getPCStudents();
            break;
        case 'get_available_pcs':
            $controller->getAvailablePCs();
            break;
        case 'transfer_student':
            $controller->transferStudent();
            break;
        case 'get_pc_details':
            $controller->getPCDetails();
            break;
        case 'remove_student_from_pc':
            $controller->removeStudentFromPC();
            break;
        case 'export_assignments':
            $controller->exportAssignments();
            break;
        case 'export_lab_assignments':
            $controller->exportLabAssignments();
            break;
        case 'export_assignment_stats':
            $controller->exportAssignmentStats();
            break;
        case 'get_lab_pcs':
            $labId = $_GET['lab_id'] ?? null;
            error_log("get_lab_pcs action - labId: " . $labId);
            if ($labId) {
                $controller->getLabPCs($labId);
            } else {
                error_log("get_lab_pcs action - labId boş");
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Laboratuvar ID gerekli'
                ]);
            }
            break;
        default:
            // Varsayılan olarak JSON response döndür
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz action: ' . $action
            ]);
            break;
    }
}
