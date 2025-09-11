<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$pcNumber = intval($input['pc_number'] ?? 0);

if ($pcNumber <= 0) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    require_once '../config/db.php';
    require_once '../models/Lab.php';
    
    $db = Database::getInstance();
    $labModel = new Lab($db);
    
    $exists = $labModel->checkPcNumberExists($pcNumber);
    
    echo json_encode(['exists' => $exists]);
} catch (Exception $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}
?>

