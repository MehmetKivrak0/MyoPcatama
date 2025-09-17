<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Öğrenci sayısı - myopc_students tablosundan
    $studentCount = $db->fetchOne("SELECT COUNT(*) as count FROM myopc_students")['count'] ?? 0;
    
    // Lab sayısı - myopc_lab_computers tablosundan
    $labCount = $db->fetchOne("SELECT COUNT(*) as count FROM myopc_lab_computers")['count'] ?? 0;
    
    // Toplam atama sayısı - myopc_assignments tablosundan
    $assignmentCount = $db->fetchOne("SELECT COUNT(*) as count FROM myopc_assignments")['count'] ?? 0;
    
    // Header stats için basit veriler
    $headerStats = [
        'student_count' => (int)$studentCount,
        'lab_count' => (int)$labCount,
        'assignment_count' => (int)$assignmentCount,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $headerStats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>
