<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS isteği için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Veritabanı bağlantısı
    require_once '../config/db.php';
    require_once '../models/Assignment.php';
    $db = Database::getInstance();
    $assignmentModel = new Assignment($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    
    // URL parametrelerini parse et
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'student-assignments' && isset($_GET['student_id'])) {
                // Belirli bir öğrencinin atamalarını getir
                $studentId = intval($_GET['student_id']);
                $assignments = $assignmentModel->getStudentAssignments($studentId);
                
                echo json_encode([
                    'type' => 'success',
                    'data' => $assignments,
                    'message' => 'Öğrenci atamaları başarıyla getirildi'
                ]);
            } else {
                // Tüm atamaları getir
                $assignments = $db->fetchAll("
                    SELECT 
                        a.*,
                        s.full_name,
                        s.sdt_nmbr,
                        l.lab_name,
                        (a.computer_id % 100) as pc_number,
                        FLOOR(a.computer_id / 100) as lab_id
                    FROM myopc_assignments a
                    LEFT JOIN myopc_students s ON a.student_id = s.student_id
                    LEFT JOIN myopc_lab_computers l ON FLOOR(a.computer_id / 100) = l.computer_id
                    ORDER BY a.created_at DESC
                ");
                
                echo json_encode([
                    'type' => 'success',
                    'data' => $assignments,
                    'message' => 'Atamalar başarıyla getirildi'
                ]);
            }
            break;
            
        case 'POST':
            // Yeni atama ekle (çoklu atama destekli)
            // Hem JSON hem de form verilerini destekle
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            if (!$input || !isset($input['student_id']) || !isset($input['pc_id']) || !isset($input['computer_id'])) {
                throw new Exception('Gerekli alanlar eksik: student_id, pc_id, computer_id');
            }
            
            // PC ID'sini hesapla
            $pcId = $input['pc_id'];
            $computerId = $input['computer_id'];
            
            // Eğer pcId 100'den küçükse, computerId ile çarp
            if ($pcId < 100) {
                $pcId = $computerId * 100 + $pcId;
            }
            
            // Öğrencinin bu laboratuvarda zaten atanıp atanmadığını kontrol et
            $existingAssignmentInLab = $assignmentModel->isStudentAssignedToLab($input['student_id'], $computerId);
            if ($existingAssignmentInLab) {
                // Hangi PC'ye atanmış olduğunu bul
                $existingPcNumber = $existingAssignmentInLab % 100;
                throw new Exception("Bu öğrenci zaten bu laboratuvarda PC{$existingPcNumber}'ye atanmış. Aynı laboratuvarda bir öğrenci sadece bir PC'ye atanabilir.");
            }
            
            // Debug: Atama öncesi kontrol
            error_log("DEBUG API - Öğrenci ID: {$input['student_id']}, Lab ID: $computerId, PC ID: $pcId");
            error_log("DEBUG API - Mevcut atama kontrolü: " . ($existingAssignmentInLab ? 'VAR' : 'YOK'));
            
            $sql = "INSERT INTO myopc_assignments (student_id, computer_id, created_at, updated_at, created_by) VALUES (?, ?, NOW(), NOW(), ?)";
            $result = $db->execute($sql, [
                $input['student_id'],
                $pcId,
                $input['created_by'] ?? 'System'
            ]);
            
            if ($result) {
                $assignmentId = $db->lastInsertId();
                error_log("DEBUG API - Atama başarılı: Assignment ID: $assignmentId, Student ID: {$input['student_id']}, PC ID: $pcId");
                
                echo json_encode([
                    'type' => 'success',
                    'data' => ['id' => $assignmentId],
                    'message' => 'Öğrenci başarıyla atandı'
                ]);
            } else {
                error_log("DEBUG API - Atama başarısız: Student ID: {$input['student_id']}, PC ID: $pcId");
                throw new Exception('Atama eklenirken hata oluştu');
            }
            break;
            
        case 'DELETE':
            // Atama kaldır
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['student_id'])) {
                throw new Exception('Öğrenci ID gerekli');
            }
            
            $studentId = $input['student_id'];
            $labId = isset($input['lab_id']) ? $input['lab_id'] : null;
            $pcId = isset($input['pc_id']) ? $input['pc_id'] : null;
            
            if ($pcId) {
                // Belirli bir PC'den kaldır
                $result = $assignmentModel->unassignStudentFromPC($studentId, $pcId);
            } elseif ($labId) {
                // Belirli bir laboratuvardan kaldır
                $result = $assignmentModel->unassignStudentFromLab($studentId, $labId);
            } else {
                // Tüm atamaları kaldır
                $result = $assignmentModel->unassignStudent($studentId);
            }
            
            if ($result) {
                echo json_encode([
                    'type' => 'success',
                    'message' => 'Atama başarıyla kaldırıldı'
                ]);
            } else {
                throw new Exception('Atama kaldırılırken hata oluştu');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'type' => 'error',
                'message' => 'Method not allowed'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'type' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
