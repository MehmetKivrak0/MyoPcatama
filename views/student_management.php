<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: iambatman.php');
    exit;
}

// Türkçe karakter yardımcısını dahil et
require_once '../utils/TurkishCharacterHelper.php';

$username = $_SESSION['full_name'] ?? 'Kullanıcı';

// Gerekli modelleri import et
require_once '../config/db.php';
require_once '../models/Student.php';

$db = Database::getInstance();
$studentModel = new Student($db);

// AJAX istekleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Error handling için output buffering başlat
    ob_start();
    
    try {
        header('Content-Type: application/json');
        
        $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_student':
            $id = $_POST['id'] ?? 0;
            $student = $studentModel->getStudentById($id);
            if ($student) {
                echo json_encode(['success' => true, 'student' => $student]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Öğrenci bulunamadı']);
            }
            exit;
            
        case 'add':
            $studentData = [
                'sdt_nmbr' => trim($_POST['sdt_nmbr'] ?? ''),
                'full_name' => trim($_POST['full_name'] ?? ''),
                'academic_year' => intval($_POST['academic_year'] ?? date('Y')),
                'department' => trim($_POST['department'] ?? ''),
                'class_level' => trim($_POST['class_level'] ?? '')
            ];
            
            // Validasyon
            $errors = [];
            if (empty($studentData['sdt_nmbr'])) $errors[] = 'Öğrenci numarası gereklidir!';
            if (empty($studentData['full_name'])) $errors[] = 'Öğrenci adı gereklidir!';
            if ($studentData['academic_year'] < 2000 || $studentData['academic_year'] > 2030) $errors[] = 'Geçerli bir akademik yıl seçiniz!';
            if ($studentModel->studentNumberExists($studentData['sdt_nmbr'])) $errors[] = 'Bu öğrenci numarası zaten kullanılıyor!';
            
            if (empty($errors)) {
                $result = $studentModel->addStudent($studentData);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
            }
            exit;
            
        case 'update':
            $id = $_POST['student_id'] ?? 0;
            $studentData = [
                'sdt_nmbr' => trim($_POST['sdt_nmbr'] ?? ''),
                'full_name' => trim($_POST['full_name'] ?? ''),
                'academic_year' => intval($_POST['academic_year'] ?? date('Y')),
                'department' => trim($_POST['department'] ?? ''),
                'class_level' => trim($_POST['class_level'] ?? '')
            ];
            
            // Validasyon
            $errors = [];
            if (empty($studentData['sdt_nmbr'])) $errors[] = 'Öğrenci numarası gereklidir!';
            if (empty($studentData['full_name'])) $errors[] = 'Öğrenci adı gereklidir!';
            if ($studentData['academic_year'] < 2000 || $studentData['academic_year'] > 2030) $errors[] = 'Geçerli bir akademik yıl seçiniz!';
            if ($studentModel->studentNumberExists($studentData['sdt_nmbr'], $id)) $errors[] = 'Bu öğrenci numarası zaten kullanılıyor!';
            
            if (empty($errors)) {
                $result = $studentModel->updateStudent($id, $studentData);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
            }
            exit;
            
        case 'delete_student':
            $id = $_POST['id'] ?? 0;
            $result = $studentModel->deleteStudent($id);
            echo json_encode($result);
            exit;
            
    }
    
    } catch (Exception $e) {
        // Hata durumunda buffer'ı temizle ve JSON error döndür
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Sunucu hatası: ' . $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        ]);
        exit;
    } finally {
        // Buffer'ı temizle
        if (ob_get_level()) {
            ob_end_flush();
        }
    }
}

// Sayfalama parametreleri
$page = $_GET['page'] ?? 1;
$limit = 20; // Sayfa başına öğrenci sayısı
$offset = ($page - 1) * $limit;

// Filtreleme parametreleri
$year_filter = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$class_filter = $_GET['class'] ?? '';

// Öğrenci listesini getir (sayfalama ile)
$students = $studentModel->getStudentsPaginated($offset, $limit, $year_filter, $search, $department_filter, $class_filter);
$total_students = $studentModel->getTotalStudents($year_filter, $search, $department_filter, $class_filter);
$total_pages = ceil($total_students / $limit);

$years = $studentModel->getAvailableYears();
$departments = $studentModel->getAvailableDepartments();
$classes = $studentModel->getAvailableClasses();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Yönetimi - MyoPc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Mevcut CSS Files -->
    <link href="../assets/css/navbar.css" rel="stylesheet">
    <link href="css/student_cards.css" rel="stylesheet">
    <style>
        /* Diğer Bileşenler */
        .stats-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #4a90a4 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(30, 58, 95, 0.3);
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-section h5 {
            color: #495057;
            font-weight: 600;
        }
        
        .filter-section .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .filter-section .form-control,
        .filter-section .form-select {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }
        
        .filter-section .form-control:focus,
        .filter-section .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .filter-section .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }
        
        .filter-section .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border: 1px solid #b8daff;
            color: #0c5460;
        }
        
        .alert-info .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }
        
        .alert-info .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
        }
        
        /* Dashboard ile tutarlı navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #4a90a4 100%) !important;
            box-shadow: 0 4px 20px rgba(30, 58, 95, 0.3);
        }
        
        body {
            padding-top: 80px;
            background: #f8f9fa;
        }
        
        .main-content {
            margin-top: 20px;
            min-height: calc(100vh - 100px);
        }
        
        /* Modal yatay scroll düzeltmesi */
        .modal-dialog {
            max-width: 90vw;
            margin: 1.75rem auto;
        }
        
        .modal-content {
            overflow-x: hidden;
        }
        
        .modal-body {
            overflow-x: hidden;
            padding: 1.5rem;
        }
        
        /* ========================================
           MOBILE RESPONSIVE IMPROVEMENTS
           ======================================== */
        
        /* Navbar Mobile Improvements */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem 0;
            }
            
            .navbar-brand {
                font-size: 0.9rem;
            }
            
            .navbar-brand img {
                width: 30px !important;
                height: 30px !important;
            }
            
            .navbar-toggler {
                border: none;
                padding: 0.25rem 0.5rem;
                font-size: 1rem;
            }
            
            .navbar-toggler:focus {
                box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
            }
            
            .navbar-collapse {
                background: rgba(30, 58, 95, 0.95);
                margin-top: 0.5rem;
                border-radius: 0.5rem;
                padding: 1rem;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            }
            
            .navbar-nav {
                text-align: center;
            }
            
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
                border-radius: 0.25rem;
                margin: 0.25rem 0;
                transition: all 0.3s ease;
            }
            
            .navbar-nav .nav-link:hover {
                background: rgba(255, 255, 255, 0.1);
            }
            
            .navbar-nav .nav-link i {
                font-size: 0.9rem;
                margin-right: 0.5rem;
            }
            
            body {
                padding-top: 70px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar {
                padding: 0.4rem 0;
            }
            
            .navbar-brand {
                font-size: 0.8rem;
            }
            
            .navbar-brand img {
                width: 25px !important;
                height: 25px !important;
            }
            
            .navbar-toggler {
                padding: 0.2rem 0.4rem;
                font-size: 0.9rem;
            }
            
            .navbar-collapse {
                margin-top: 0.4rem;
                padding: 0.8rem;
            }
            
            .navbar-nav .nav-link {
                padding: 0.6rem 0.8rem;
                font-size: 0.85rem;
                margin: 0.2rem 0;
            }
            
            .navbar-nav .nav-link i {
                font-size: 0.8rem;
                margin-right: 0.4rem;
            }
            
            body {
                padding-top: 65px;
            }
        }
        
        /* Stats Card Mobile Improvements */
        @media (max-width: 768px) {
            .stats-card {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .stats-card .row {
                margin: 0;
            }
            
            .stats-card .col-md-3 {
                margin-bottom: 1rem;
                padding: 0 0.5rem;
            }
            
            .stats-card .col-md-3:last-child {
                margin-bottom: 0;
            }
            
            .stats-card h2 {
                font-size: 1.8rem;
                margin-bottom: 0.25rem;
            }
            
            .stats-card p {
                font-size: 0.9rem;
                margin-bottom: 0;
            }
            
            .stats-card .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                margin: 0.25rem;
            }
            
            .stats-card .btn i {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .stats-card {
                padding: 0.8rem;
                margin-bottom: 1rem;
            }
            
            .stats-card .col-md-3 {
                margin-bottom: 0.8rem;
                padding: 0 0.25rem;
            }
            
            .stats-card h2 {
                font-size: 1.5rem;
            }
            
            .stats-card p {
                font-size: 0.8rem;
            }
            
            .stats-card .btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
                margin: 0.2rem;
            }
            
            .stats-card .btn i {
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 400px) {
            .stats-card {
                padding: 0.6rem;
            }
            
            .stats-card .col-md-3 {
                margin-bottom: 0.6rem;
            }
            
            .stats-card h2 {
                font-size: 1.3rem;
            }
            
            .stats-card p {
                font-size: 0.75rem;
            }
            
            .stats-card .btn {
                padding: 0.3rem 0.5rem;
                font-size: 0.7rem;
                margin: 0.1rem;
            }
        }
        
        /* Filter Section Mobile Improvements */
        @media (max-width: 768px) {
            .filter-section {
                padding: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .filter-section .row {
                margin: 0;
            }
            
            .filter-section .col-md-4 {
                margin-bottom: 1rem;
                padding: 0 0.5rem;
            }
            
            .filter-section .col-md-4:last-child {
                margin-bottom: 0;
            }
            
            .filter-section .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }
            
            .filter-section .form-control,
            .filter-section .form-select {
                font-size: 0.9rem;
            }
            
            .filter-section .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .filter-section {
                padding: 0.8rem;
                margin-bottom: 1rem;
            }
            
            .filter-section .col-md-4 {
                margin-bottom: 0.8rem;
                padding: 0 0.25rem;
            }
            
            .filter-section .form-label {
                font-size: 0.8rem;
            }
            
            .filter-section .form-control,
            .filter-section .form-select {
                font-size: 0.8rem;
                padding: 0.5rem 0.75rem;
            }
            
            .filter-section .btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }
        }
        
        /* Form elemanları için responsive düzenleme */
        @media (max-width: 768px) {
            .modal-dialog {
                max-width: 95vw;
                margin: 0.5rem auto;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .modal-body .row.g-3 {
                margin: 0;
            }
            
            .modal-body .col-12 {
                padding-left: 0;
                padding-right: 0;
                margin-bottom: 1rem;
            }
            
            .modal-body .form-control,
            .modal-body .form-select {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            
            .modal-body .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }
        }
        
        @media (max-width: 576px) {
            .modal-dialog {
                max-width: 98vw;
                margin: 0.25rem auto;
            }
            
            .modal-body {
                padding: 0.75rem;
            }
            
            .modal-header {
                padding: 0.75rem 1rem;
            }
            
            .modal-footer {
                padding: 0.75rem 1rem;
            }
        }
        
        /* Öğrenci Kartı Özel Stilleri */
        .student-card {
            height: 290px !important; /* Yeni bilgiler için daha yüksek */
        }
        
        .department-item {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
            border-left: 3px solid #2196f3 !important;
        }
        
        .department-item i {
            color: #2196f3 !important;
        }
        
        .class-item {
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%) !important;
            border-left: 3px solid #4caf50 !important;
        }
        
        .class-item i {
            color: #4caf50 !important;
        }
        
        .student-detail-item {
            margin-bottom: 6px;
        }
        
        .student-detail-item:last-child {
            margin-bottom: 0;
        }
        
        /* Sınıf ve Bölüm için özel vurgu */
        .department-item .student-detail-value,
        .class-item .student-detail-value {
            font-weight: 600;
            color: #1a202c;
        }
        
        .department-item .student-detail-label,
        .class-item .student-detail-label {
            font-weight: 700;
            color: #2d3748;
        }

        /* Main Content Mobile Adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-top: 15px;
                padding: 0 0.5rem;
            }
            
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            /* Tablet için kart yüksekliği */
            .student-card {
                height: 200px !important;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                margin-top: 10px;
                padding: 0 0.25rem;
            }
            
            .container-fluid {
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }
            
            /* Mobil için sınıf ve bölüm stilleri */
            .student-card {
                height: 180px !important; /* Mobil için uygun yükseklik */
            }
            
            .department-item,
            .class-item {
                padding: 4px 8px !important;
                margin-bottom: 4px !important;
            }
            
            .department-item .student-detail-label,
            .class-item .student-detail-label {
                font-size: 0.7rem !important;
            }
            
            .department-item .student-detail-value,
            .class-item .student-detail-value {
                font-size: 0.75rem !important;
            }
        }
        
        /* Pagination Mobile Improvements */
        @media (max-width: 768px) {
            .pagination-section {
                margin-top: 2rem;
            }
            
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .pagination .page-item {
                margin: 0.1rem;
            }
            
            .pagination .page-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .pagination .page-link i {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .pagination-section {
                margin-top: 1.5rem;
            }
            
            .pagination .page-link {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
            }
            
            .pagination .page-link i {
                font-size: 0.7rem;
            }
            
            .pagination .page-item .page-link span {
                display: none;
            }
        }
        
        @media (max-width: 400px) {
            .pagination .page-link {
                padding: 0.3rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .pagination .page-item .page-link {
                min-width: 35px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1e3a8a 0%, #0ea5e9 100%); border-bottom: 1px solid #e5e7eb;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" class="img-fluid me-2" style="width: 35px; height: auto;">
                <div class="brand-text d-none d-md-block">
                    <div class="text-white small">Öğrenci Atama <br>Sistemi</div>
                </div>
                <div class="brand-text d-block d-md-none">
                    <div class="text-blue fw-bold">MyoPC</div>
                    <div class="text-white small">Öğrenci</div>
                </div>
            </a>
            
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link d-flex align-items-center" href="dashboard.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        <span class="d-none d-sm-inline">Dashboard'a Dön</span>
                        <span class="d-inline d-sm-none">Dashboard</span>
                    </a>
                    <a class="nav-link d-flex align-items-center" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        <span class="d-none d-sm-inline">Çıkış Yap</span>
                        <span class="d-inline d-sm-none">Çıkış</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid px-4">
            <!-- İstatistikler -->
            <div class="stats-card">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <h2 class="mb-0"><?php echo number_format($total_students); ?></h2>
                            <p class="mb-0">Toplam Öğrenci</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <h2 class="mb-0"><?php echo count($years); ?></h2>
                            <p class="mb-0">Aktif Yıl</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <h2 class="mb-0"><?php echo ($year_filter || $department_filter || $class_filter || $search) ? number_format($total_students) : number_format($studentModel->getTotalStudents(date('Y'))); ?></h2>
                            <p class="mb-0"><?php echo ($year_filter || $department_filter || $class_filter || $search) ? 'Filtrelenmiş' : 'Bu Yıl'; ?></p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <div class="d-flex flex-column flex-md-row gap-2 justify-content-center">
                                <button class="btn btn-outline-light btn-sm" onclick="openAddStudentModal()">
                                    <i class="fas fa-plus me-1"></i>
                                    <span class="d-none d-sm-inline">Yeni Öğrenci</span>
                                    <span class="d-inline d-sm-none">Yeni</span>
                                </button>
                                <a href="bulk_operations.php" class="btn btn-outline-light btn-sm">
                                    <i class="fas fa-tasks me-1"></i>
                                    <span class="d-none d-sm-inline">Toplu İşlemler</span>
                                    <span class="d-inline d-sm-none">Toplu</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtreler -->
            <div class="filter-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filtreler
                    </h5>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                        <i class="fas fa-chevron-down me-1"></i>
                        <span class="d-none d-sm-inline">Filtreleri Gizle/Göster</span>
                        <span class="d-inline d-sm-none">Filtreler</span>
                    </button>
                </div>
                
                <div class="collapse show" id="filterCollapse">
                    <form method="GET" id="filterForm">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Yıl Filtresi
                                </label>
                                <select class="form-select" name="year" id="yearFilter" onchange="submitFilter()">
                                    <option value="">Tüm Yıllar</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year['year']; ?>" <?php echo $year_filter == $year['year'] ? 'selected' : ''; ?>><?php echo $year['year']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label">
                                    <i class="fas fa-building me-1"></i>Bölüm Filtresi
                                </label>
                                <select class="form-select" name="department" id="departmentFilter" onchange="submitFilter()">
                                    <option value="">Tüm Bölümler</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo htmlspecialchars($department['department']); ?>" <?php echo $department_filter == $department['department'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['department']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Sınıf Filtresi
                                </label>
                                <select class="form-select" name="class" id="classFilter" onchange="submitFilter()">
                                    <option value="">Tüm Sınıflar</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo htmlspecialchars($class['class_level']); ?>" <?php echo $class_filter == $class['class_level'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_level']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label">
                                    <i class="fas fa-search me-1"></i>Arama
                                </label>
                                <input type="text" class="form-control" name="search" id="searchInput" placeholder="Ad, soyad veya numara..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="debounceSearch()">
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-2 justify-content-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>
                                        <span class="d-none d-sm-inline">Filtrele</span>
                                        <span class="d-inline d-sm-none">Ara</span>
                                    </button>
                                    <a href="student_management.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>
                                        <span class="d-none d-sm-inline">Tüm Filtreleri Temizle</span>
                                        <span class="d-inline d-sm-none">Temizle</span>
                                    </a>
                                    <button type="button" class="btn btn-outline-info" onclick="resetFilters()">
                                        <i class="fas fa-undo me-1"></i>
                                        <span class="d-none d-sm-inline">Sıfırla</span>
                                        <span class="d-inline d-sm-none">Sıfırla</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aktif Filtreler -->
                        <?php if ($year_filter || $department_filter || $class_filter || $search): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div class="flex-grow-1">
                                        <strong>Aktif Filtreler:</strong>
                                        <?php
                                        $active_filters = [];
                                        if ($year_filter) $active_filters[] = "Yıl: {$year_filter}";
                                        if ($department_filter) $active_filters[] = "Bölüm: {$department_filter}";
                                        if ($class_filter) $active_filters[] = "Sınıf: {$class_filter}";
                                        if ($search) $active_filters[] = "Arama: {$search}";
                                        echo implode(' | ', $active_filters);
                                        ?>
                                    </div>
                                    <a href="student_management.php" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Öğrenci Listesi -->
            <div class="students-grid" id="studentsList">
                <?php foreach ($students as $student): ?>
                <div class="student-card" data-year="<?php echo $student['academic_year']; ?>" data-name="<?php echo strtolower($student['full_name']); ?>" data-number="<?php echo $student['sdt_nmbr']; ?>">
                    <!-- Kart Başlığı -->
                    <div class="student-header">
                        <h5 class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                        <div class="student-actions">
                            <button class="btn btn-outline-primary" onclick="editStudent(<?php echo $student['student_id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteStudent(<?php echo $student['student_id']; ?>)" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Kart İçeriği -->
                    <div class="student-info">
                        <div class="student-detail-item department-item">
                            <i class="fas fa-building"></i>
                            <span class="student-detail-label">Bölüm:</span>
                            <span class="student-detail-value"><?php echo htmlspecialchars($student['department'] ?? 'Belirtilmemiş'); ?></span>
                        </div>
                        <div class="student-detail-item class-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span class="student-detail-label">Sınıf:</span>
                            <span class="student-detail-value"><?php echo htmlspecialchars($student['class_level'] ?? 'Belirtilmemiş'); ?></span>
                        </div>
                       
                        <div class="student-detail-item">
                            <i class="fas fa-id-card"></i>
                            <span class="student-detail-label">Numara:</span>
                            <span class="student-detail-value"><?php echo htmlspecialchars($student['sdt_nmbr']); ?></span>
                        </div>
                        <div class="student-detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span class="student-detail-label">Yıl:</span>
                            <span class="student-detail-value"><?php echo $student['academic_year']; ?></span>
                        </div>
                        <div class="student-detail-item">
                            <i class="fas fa-clock"></i>
                            <span class="student-detail-labels">Eklenme:</span>
                            <span class="student-detail-values"><?php echo date('d.m.Y', strtotime($student['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Sayfalama -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-section mt-4">
                <nav aria-label="Öğrenci sayfalama">
                    <ul class="pagination justify-content-center">
                        <!-- Önceki sayfa -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&class=<?php echo urlencode($class_filter); ?>">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        </li>
                        
                        <!-- Sayfa numaraları -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&class=<?php echo urlencode($class_filter); ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&class=<?php echo urlencode($class_filter); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&class=<?php echo urlencode($class_filter); ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Sonraki sayfa -->
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&class=<?php echo urlencode($class_filter); ?>">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <!-- Sayfa bilgisi -->
                <div class="text-center mt-3">
                    <p class="text-muted">
                        Sayfa <?php echo $page; ?> / <?php echo $total_pages; ?> 
                        (Toplam <?php echo number_format($total_students); ?> öğrenci)
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Öğrenci Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="studentModalLabel">
                        <i class="fas fa-user-plus me-2"></i>
                        <span id="modalTitle">Yeni Öğrenci Ekle</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hata Mesajları -->
                    <div id="errorAlert" class="alert alert-danger d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="errorMessage"></span>
                    </div>
                    
                    <!-- Başarı Mesajları -->
                    <div id="successAlert" class="alert alert-success d-none" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="successMessage"></span>
                    </div>
                    
                    <!-- Form -->
                    <form id="studentForm">
                        <input type="hidden" id="studentId" name="student_id">
                        <input type="hidden" id="action" name="action" value="add">
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="modalSdtNmbr" class="form-label">
                                    Öğrenci Numarası <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="modalSdtNmbr" 
                                       name="sdt_nmbr" 
                                       placeholder="Örn: 2024001"
                                       required>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="modalAcademicYear" class="form-label">
                                    Akademik Yıl <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="modalAcademicYear" name="academic_year" required>
                                    <option value="">Yıl Seçiniz</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modalFullName" class="form-label">
                                Öğrenci Adı Soyadı <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="modalFullName" 
                                   name="full_name" 
                                   placeholder="Örn: Ahmet Yılmaz"
                                   required>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="modalDepartment" class="form-label">
                                    Bölüm
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="modalDepartment" 
                                       name="department" 
                                       placeholder="Örn: Bilgisayar Programcılığı">
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="modalClassLevel" class="form-label">
                                    Sınıf Durumu
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="modalClassLevel" 
                                       name="class_level" 
                                       placeholder="Örn: 1. Sınıf">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="button" class="btn btn-primary" id="saveStudentBtn">
                        <i class="fas fa-save me-1"></i>
                        <span id="saveButtonText">Ekle</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap navbar collapse functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing navbar functionality...');
            
            // Navbar toggle functionality
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            
            console.log('Navbar toggler found:', !!navbarToggler);
            console.log('Navbar collapse found:', !!navbarCollapse);
            
            if (navbarToggler && navbarCollapse) {
                // Manual toggle functionality
                navbarToggler.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Navbar toggler clicked');
                    
                    const isExpanded = navbarCollapse.classList.contains('show');
                    if (isExpanded) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    } else {
                        navbarCollapse.classList.add('show');
                        navbarToggler.setAttribute('aria-expanded', 'true');
                    }
                });
                
                // Close navbar when clicking outside
                document.addEventListener('click', function(event) {
                    if (!navbarToggler.contains(event.target) && !navbarCollapse.contains(event.target)) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Close navbar when clicking on nav links
                const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    });
                });
                
                console.log('Navbar functionality initialized successfully');
            } else {
                console.error('Navbar elements not found!');
            }
        });
    </script>
    <script>
        console.log('Student management page loaded');
        let isEditMode = false;
        
        // Kart sayısına göre grid düzenini ayarla
        function adjustGridLayout() {
            const studentsGrid = document.getElementById('studentsList');
            if (!studentsGrid) return;
            
            const studentCards = studentsGrid.querySelectorAll('.student-card');
            const cardCount = studentCards.length;
            
            console.log('Kart sayısı:', cardCount);
            
            // Mevcut sınıfları temizle
            studentsGrid.classList.remove('single-card', 'two-cards', 'three-cards', 'four-cards');
            
            // Kart sayısına göre sınıf ekle
            if (cardCount === 1) {
                studentsGrid.classList.add('single-card');
                console.log('Tek kart düzeni uygulandı');
            } else if (cardCount === 2) {
                studentsGrid.classList.add('two-cards');
                console.log('İki kart düzeni uygulandı');
            } else if (cardCount === 3) {
                studentsGrid.classList.add('three-cards');
                console.log('Üç kart düzeni uygulandı');
            } else if (cardCount >= 4) {
                studentsGrid.classList.add('four-cards');
                console.log('Dört kart düzeni uygulandı');
            }
        }
        
        // Sayfa yüklendiğinde grid düzenini ayarla
        document.addEventListener('DOMContentLoaded', function() {
            adjustGridLayout();
            loadYears();
            setupModalEventListeners();
        });
        
        function loadYears() {
            // Yılları modal dropdown'a yükle
            const yearSelect = document.getElementById('modalAcademicYear');
            const currentYear = new Date().getFullYear();
            
            // Mevcut yılları temizle (ilk option hariç)
            yearSelect.innerHTML = '<option value="">Yıl Seçiniz</option>';
            
            // Son 5 yıl ve gelecek 2 yıl ekle
            for (let year = currentYear + 2; year >= currentYear - 5; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            }
        }
        
        function setupModalEventListeners() {
            // Modal kaydet butonu
            document.getElementById('saveStudentBtn').addEventListener('click', function() {
                saveStudent();
            });
            
            // Form validasyonu
            document.getElementById('studentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                saveStudent();
            });
            
            // Ad soyad otomatik büyük harf
            document.getElementById('modalFullName').addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        }
        
        function saveStudent() {
            const formData = new FormData(document.getElementById('studentForm'));
            const action = formData.get('action');
            
            // Validasyon
            const sdt_nmbr = formData.get('sdt_nmbr').trim();
            const full_name = formData.get('full_name').trim();
            const academic_year = formData.get('academic_year');
            
            if (!sdt_nmbr || !full_name || !academic_year) {
                showError('Lütfen tüm alanları doldurunuz!');
                return;
            }
            
            if (!/^\d+$/.test(sdt_nmbr)) {
                showError('Öğrenci numarası sadece rakam içermelidir!');
                return;
            }
            
            if (full_name.length < 2) {
                showError('Ad soyad en az 2 karakter olmalıdır!');
                return;
            }
            
            // AJAX isteği
            fetch('student_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(action === 'add' ? 'Öğrenci başarıyla eklendi!' : 'Öğrenci başarıyla güncellendi!');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showError(data.error || 'İşlem başarısız!');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                showError('Bağlantı hatası: ' + error.message);
            });
        }
        
        // Sayfa tamamen yüklendiğinde tekrar kontrol et
        window.addEventListener('load', function() {
            setTimeout(adjustGridLayout, 100);
        });
        
        // Resize olayında da kontrol et
        window.addEventListener('resize', function() {
            setTimeout(adjustGridLayout, 100);
        });
        
        // Test function
        function testFunction() {
            console.log('Test function called!');
            alert('Test function works!');
        }
        
        
        
        
        function editStudent(id) {
            console.log('Edit student called with ID:', id);
            // Öğrenci verilerini getir ve modalı aç
            fetchStudentData(id);
        }
        
        function openAddStudentModal() {
            console.log('Opening add student modal');
            resetModal();
            document.getElementById('modalTitle').textContent = 'Yeni Öğrenci Ekle';
            document.getElementById('saveButtonText').textContent = 'Ekle';
            document.getElementById('action').value = 'add';
            document.getElementById('studentId').value = '';
            
            // Modalı aç
            const modal = new bootstrap.Modal(document.getElementById('studentModal'));
            modal.show();
        }
        
        function fetchStudentData(id) {
            console.log('Fetching student data for ID:', id);
            
            const formData = new FormData();
            formData.append('action', 'get_student');
            formData.append('id', id);
            
            fetch('student_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateModal(data.student);
                    document.getElementById('modalTitle').textContent = 'Öğrenci Düzenle';
                    document.getElementById('saveButtonText').textContent = 'Güncelle';
                    document.getElementById('action').value = 'update';
                    document.getElementById('studentId').value = id;
                    
                    // Modalı aç
                    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
                    modal.show();
                } else {
                    showError('Öğrenci verileri alınamadı: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error fetching student data:', error);
                showError('Bağlantı hatası: ' + error.message);
            });
        }
        
        function populateModal(student) {
            document.getElementById('modalSdtNmbr').value = student.sdt_nmbr || '';
            document.getElementById('modalFullName').value = student.full_name || '';
            document.getElementById('modalAcademicYear').value = student.academic_year || '';
            document.getElementById('modalDepartment').value = student.department || '';
            document.getElementById('modalClassLevel').value = student.class_level || '';
        }
        
        function resetModal() {
            document.getElementById('studentForm').reset();
            document.getElementById('errorAlert').classList.add('d-none');
            document.getElementById('successAlert').classList.add('d-none');
        }
        
        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorAlert').classList.remove('d-none');
            document.getElementById('successAlert').classList.add('d-none');
        }
        
        function showSuccess(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successAlert').classList.remove('d-none');
            document.getElementById('errorAlert').classList.add('d-none');
        }
        
        function deleteStudent(id) {
            console.log('Delete student called with ID:', id);
            if (confirm('Bu öğrenciyi silmek istediğinizden emin misiniz?')) {
                const formData = new FormData();
                formData.append('action', 'delete_student');
                formData.append('id', id);
                
                fetch('student_management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw delete response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Delete response:', data);
                        if (data.success) {
                            location.reload();
                        } else {
                            console.error('Delete error:', data.error);
                            if (data.debug) {
                                console.error('Debug info:', data.debug);
                            }
                            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
                        }
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError);
                        console.error('Response text:', text);
                        alert('Sunucu yanıtı geçersiz: ' + parseError.message);
                    }
                })
                .catch(error => {
                    console.error('Delete fetch error:', error);
                    alert('Bağlantı hatası: ' + error.message);
                });
            }
        }
        
        let searchTimeout;
        
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                submitFilter();
            }, 500);
        }
        
        function submitFilter() {
            document.getElementById('filterForm').submit();
        }
        
        function resetFilters() {
            // Tüm filtreleri temizle
            document.getElementById('yearFilter').value = '';
            document.getElementById('departmentFilter').value = '';
            document.getElementById('classFilter').value = '';
            document.getElementById('searchInput').value = '';
            
            // Formu gönder
            submitFilter();
        }
        
        function filterStudents() {
            // Bu fonksiyon artık kullanılmıyor, server-side filtreleme kullanılıyor
            submitFilter();
        }
        
        // Filtre değişikliklerini takip et
        document.addEventListener('DOMContentLoaded', function() {
            // Filtre collapse toggle
            const filterToggle = document.querySelector('[data-bs-target="#filterCollapse"]');
            const filterCollapse = document.getElementById('filterCollapse');
            
            if (filterToggle && filterCollapse) {
                filterToggle.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (filterCollapse.classList.contains('show')) {
                        icon.className = 'fas fa-chevron-right me-1';
                    } else {
                        icon.className = 'fas fa-chevron-down me-1';
                    }
                });
            }
            
            // Enter tuşu ile arama
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        submitFilter();
                    }
                });
            }
        });
        
    </script>
</body>
</html>
