<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS isteği için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/db.php';
require_once '../models/Student.php';

$db = Database::getInstance();
$studentModel = new Student($db);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_years':
            $years = $studentModel->getAvailableYears();
            echo json_encode([
                'success' => true,
                'years' => $years
            ]);
            break;
            
        case 'get_departments':
            $departments = $studentModel->getAvailableDepartments();
            echo json_encode([
                'success' => true,
                'departments' => $departments
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Geçersiz aksiyon'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>
