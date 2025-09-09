<?php
header('Content-Type: application/json');

try {
    require_once 'config/db.php';
    require_once 'models/Student.php';
    
    $db = Database::getInstance();
    $studentModel = new Student($db);
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_all_students':
            $students = $studentModel->getAll();
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);
            break;
            
        case 'get_students_by_year':
            $year = $_POST['year'] ?? '';
            if (empty($year)) {
                throw new Exception('Yıl parametresi gerekli');
            }
            
            $students = $studentModel->getByYear($year);
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);
            break;
            
        case 'get_available_years':
            $years = $studentModel->getAvailableYears();
            echo json_encode([
                'success' => true,
                'years' => $years
            ]);
            break;
            
        default:
            throw new Exception('Geçersiz işlem');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
