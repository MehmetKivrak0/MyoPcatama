<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['type' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$labId = intval($input['lab_id'] ?? 0);
$count = intval($input['count'] ?? 0);

if ($labId <= 0) {
    echo json_encode(['type' => 'error', 'message' => 'Geçersiz laboratuvar ID']);
    exit;
}

try {
    require_once '../config/db.php';
    require_once '../controllers/LabController.php';
    
    $labController = new LabController();
    
    switch ($action) {
        case 'add':
            if ($count < 1 || $count > 100) {
                echo json_encode(['type' => 'error', 'message' => 'PC sayısı 1-100 arasında olmalıdır.']);
                exit;
            }
            $result = $labController->addPCToLab($labId, $count);
            break;
            
        case 'remove':
            if ($count < 1) {
                echo json_encode(['type' => 'error', 'message' => 'PC sayısı 1\'den küçük olamaz.']);
                exit;
            }
            $result = $labController->removePCFromLab($labId, $count);
            break;
            
        case 'update':
            if ($count < 0 || $count > 1000) {
                echo json_encode(['type' => 'error', 'message' => 'PC sayısı 0-1000 arasında olmalıdır.']);
                exit;
            }
            $result = $labController->updateLabPCCount($labId, $count);
            break;
            
        default:
            echo json_encode(['type' => 'error', 'message' => 'Geçersiz işlem.']);
            exit;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['type' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
}
?>



