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
    $db = Database::getInstance();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Tüm laboratuvarları getir
            $labs = $db->fetchAll("SELECT * FROM myopc_lab_computers ORDER BY lab_name ASC");
            echo json_encode([
                'type' => 'success',
                'data' => $labs,
                'message' => 'Laboratuvarlar başarıyla getirildi'
            ]);
            break;
            
        case 'POST':
            // Yeni laboratuvar ekle
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['lab_name']) || !isset($input['pc_count'])) {
                throw new Exception('Gerekli alanlar eksik');
            }
            
            $sql = "INSERT INTO myopc_lab_computers (lab_name, pc_count, user_type, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
            $result = $db->execute($sql, [
                $input['lab_name'],
                $input['pc_count'],
                $input['user_type'] ?? 'admin',
                $input['created_by'] ?? 'System'
            ]);
            
            if ($result) {
                echo json_encode([
                    'type' => 'success',
                    'data' => ['id' => $db->lastInsertId()],
                    'message' => 'Laboratuvar başarıyla eklendi'
                ]);
            } else {
                throw new Exception('Laboratuvar eklenirken hata oluştu');
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
