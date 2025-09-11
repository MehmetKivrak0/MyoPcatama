<?php
header('Content-Type: application/json');

require_once 'config/db.php';
require_once 'models/Student.php';

try {
    $db = Database::getInstance();
    $studentModel = new Student($db);
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'get_all_students') {
        $students = $studentModel->getAllStudents();
        
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
        
    } elseif ($action === 'get_students_by_year') {
        $year = $_POST['year'] ?? '';
        
        if (empty($year)) {
            echo json_encode([
                'success' => false,
                'message' => 'Yıl parametresi gerekli'
            ]);
            exit;
        }
        
        $students = $studentModel->getStudentsByYear($year);
        
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz işlem'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?>