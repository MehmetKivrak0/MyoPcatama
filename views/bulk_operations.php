<?php
// Hata raporlamayı kapat (production için)
error_reporting(0);
ini_set('display_errors', 0);

// Türkçe karakter yardımcısını dahil et
require_once '../utils/TurkishCharacterHelper.php';

// Ortam tespiti ile dinamik bağlantı
require_once '../includes/environment_detector.php';
$environment = detectEnvironment();

if ($environment['isSchoolServer']) {
    // School server (xrlab.mcbu.edu.tr) için
    $host = 'localhost'; // Okul sunucusunda genellikle localhost
    $username = 'xrlab_user'; // Gerçek kullanıcı adı
    $password = 'xrlab_password'; // Gerçek şifre
    $database = 'xrlab_myopc'; // Gerçek veritabanı adı
} else {
    // Local development
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'myopc';
}

try {
    $mysqli = new mysqli($host, $username, $password, $database);
    
    if ($mysqli->connect_error) {
        throw new Exception("Bağlantı hatası: " . $mysqli->connect_error);
    }
    
    // UTF-8 karakter desteği
    $mysqli->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("❌ Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Yılları getir
$years_query = "SELECT DISTINCT academic_year as year FROM myopc_students ORDER BY academic_year DESC";
$years_result = $mysqli->query($years_query);
$years = [];
if ($years_result) {
    while ($row = $years_result->fetch_assoc()) {
        $years[] = $row;
    }
}

// AJAX istekleri için
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hata raporlamayı kapat (JSON çıktısı için)
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Çıktı tamponunu temizle
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'bulk_delete':
                // FormData'dan gelen array formatını kontrol et
                if (isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
                    $student_ids = $_POST['student_ids'];
                } elseif (isset($_POST['student_ids']) && is_string($_POST['student_ids'])) {
                    $student_ids = json_decode($_POST['student_ids'], true);
                } else {
                    $student_ids = [];
                }
                
                if (empty($student_ids) || !is_array($student_ids)) {
                    echo json_encode(['success' => false, 'message' => 'Silinecek öğrenci seçilmedi']);
                    exit;
                }
                
                // Güvenli silme işlemi
                $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
                $delete_query = "DELETE FROM myopc_students WHERE student_id IN ($placeholders)";
                
                $stmt = $mysqli->prepare($delete_query);
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Prepare hatası: ' . $mysqli->error]);
                    exit;
                }
                
                // Parametre tiplerini oluştur (hepsi integer)
                $types = str_repeat('i', count($student_ids));
                $stmt->bind_param($types, ...$student_ids);
                
                if ($stmt->execute()) {
                    $deleted_count = $stmt->affected_rows;
                    echo json_encode([
                        'success' => true, 
                        'message' => "{$deleted_count} öğrenci başarıyla silindi",
                        'deleted_count' => $deleted_count
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Silme hatası: ' . $stmt->error]);
                }
                $stmt->close();
                break;
                
            case 'bulk_update_year':
                $new_year = $_POST['new_year'] ?? '';
                
                // FormData'dan gelen array formatını kontrol et
                if (isset($_POST['student_ids']) && is_array($_POST['student_ids'])) {
                    $student_ids = $_POST['student_ids'];
                } elseif (isset($_POST['student_ids']) && is_string($_POST['student_ids'])) {
                    $student_ids = json_decode($_POST['student_ids'], true);
                } else {
                    $student_ids = [];
                }
                
                if (empty($student_ids) || !is_array($student_ids) || empty($new_year)) {
                    echo json_encode(['success' => false, 'message' => 'Gerekli bilgiler eksik']);
                    exit;
                }
                
                // Güvenli güncelleme işlemi
                $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
                $update_query = "UPDATE myopc_students SET academic_year = ?, updated_at = NOW() WHERE student_id IN ($placeholders)";
                
                $stmt = $mysqli->prepare($update_query);
                if (!$stmt) {
                    echo json_encode(['success' => false, 'message' => 'Prepare hatası: ' . $mysqli->error]);
                    exit;
                }
                
                // Parametreleri hazırla (yeni yıl + tüm ID'ler)
                $params = [intval($new_year)];
                $params = array_merge($params, $student_ids);
                
                // Parametre tiplerini oluştur (1 integer + N integer)
                $types = 'i' . str_repeat('i', count($student_ids));
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $updated_count = $stmt->affected_rows;
                    echo json_encode([
                        'success' => true, 
                        'message' => "{$updated_count} öğrencinin akademik yılı güncellendi",
                        'updated_count' => $updated_count
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Güncelleme hatası: ' . $stmt->error]);
                }
                $stmt->close();
                break;
                
            case 'get_students':
                $year_filter = $_POST['year_filter'] ?? '';
                $search = $_POST['search'] ?? '';
                $page = intval($_POST['page'] ?? 1);
                $limit = 50; // Toplu işlemler için daha fazla
                $offset = ($page - 1) * $limit;
                
                // WHERE koşullarını oluştur
                $where_conditions = [];
                $params = [];
                $types = '';
                
                if (!empty($year_filter)) {
                    $where_conditions[] = "academic_year = ?";
                    $params[] = intval($year_filter);
                    $types .= 'i';
                }
                
                if (!empty($search)) {
                    $where_conditions[] = "(full_name LIKE ? OR sdt_nmbr LIKE ?)";
                    $search_param = '%' . $search . '%';
                    $params[] = $search_param;
                    $params[] = $search_param;
                    $types .= 'ss';
                }
                
                $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
                
                // Öğrencileri getir
                $students_query = "SELECT * FROM myopc_students {$where_clause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                $types .= 'ii';
                
                $stmt = $mysqli->prepare($students_query);
                if ($stmt && !empty($params)) {
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $students = [];
                    while ($row = $result->fetch_assoc()) {
                        $students[] = $row;
                    }
                    $stmt->close();
                } else {
                    $students = [];
                }
                
                // Toplam sayıyı getir
                $count_query = "SELECT COUNT(*) as total FROM myopc_students {$where_clause}";
                $count_stmt = $mysqli->prepare($count_query);
                if ($count_stmt && !empty($where_conditions)) {
                    $count_params = array_slice($params, 0, -2); // LIMIT ve OFFSET'i çıkar
                    $count_types = substr($types, 0, -2); // 'ii' çıkar
                    $count_stmt->bind_param($count_types, ...$count_params);
                }
                
                if ($count_stmt) {
                    if (!empty($where_conditions)) {
                        $count_stmt->execute();
                    }
                    $count_result = $count_stmt->get_result();
                    $total = $count_result->fetch_assoc()['total'];
                    $count_stmt->close();
                } else {
                    $total = 0;
                }
                
                echo json_encode([
                    'success' => true,
                    'students' => $students,
                    'total' => $total,
                    'page' => $page,
                    'total_pages' => ceil($total / $limit)
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
        }
    } catch (Exception $e) {
        // Hata logla
        error_log("Bulk operations hatası: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()
        ]);
    } catch (Error $e) {
        // Fatal error'ları yakala
        error_log("Bulk operations fatal hatası: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Sistem hatası oluştu'
        ]);
    }
    exit;
}

// Sayfa yüklendiğinde öğrenci listesi
$page = intval($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;
$year_filter = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

// WHERE koşullarını oluştur
$where_conditions = [];
$params = [];
$types = '';

if (!empty($year_filter)) {
    $where_conditions[] = "academic_year = " . intval($year_filter);
}

if (!empty($search)) {
    $search_escaped = $mysqli->real_escape_string($search);
    $where_conditions[] = "(full_name LIKE '%{$search_escaped}%' OR sdt_nmbr LIKE '%{$search_escaped}%')";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Öğrencileri getir
$students_query = "SELECT * FROM myopc_students {$where_clause} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
$students_result = $mysqli->query($students_query);
$students = [];
if ($students_result) {
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Toplam sayıyı getir
$total_query = "SELECT COUNT(*) as total FROM myopc_students {$where_clause}";
$total_result = $mysqli->query($total_query);
$total = 0;
if ($total_result) {
    $row = $total_result->fetch_assoc();
    $total = $row['total'];
}
$total_pages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toplu İşlemler - MyOPC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../assets/image/logo/myologo.png" type="image/x-icon" />

    
    <!-- Mevcut CSS Files -->
    <link href="../assets/css/navbar.css" rel="stylesheet">
    
    <!-- Main Dashboard CSS -->
    <link href="../assets/css/dashboard-clean.css" rel="stylesheet">
    
    <style>
        /* Navbar fixed position düzeltmesi */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #4a90a4 100%) !important;
            box-shadow: 0 4px 20px rgba(30, 58, 95, 0.3);
        }
        
        /* Body padding */
        body {
            padding-top: 80px; /* Navbar yüksekliği kadar boşluk */
        }
        
        .container-fluid {
            margin-top: 20px;
        }
        
        /* Dashboard tarzı stats card */
        .stats-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #4a90a4 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(30, 58, 95, 0.3);
        }
        
        /* Filter section dashboard tarzı */
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        /* ========================================
           BULK OPERATIONS MOBILE RESPONSIVE IMPROVEMENTS
           ======================================== */
        
        /* Navbar Mobile Improvements */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem 0;
            }
            
            .navbar .container-fluid {
                padding-left: 0.8rem;
                padding-right: 0.8rem;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .navbar-brand img {
                width: 30px !important;
                height: 30px !important;
            }
            
            .navbar-toggler {
                border: none;
                padding: 0.25rem 0.5rem;
                font-size: 1.1rem;
            }
            
            .navbar-toggler:focus {
                box-shadow: none;
            }
            
            .navbar-collapse {
                background: rgba(30, 58, 138, 0.95);
                border-radius: 10px;
                margin-top: 0.5rem;
                padding: 1rem;
                backdrop-filter: blur(10px);
            }
            
            .navbar-nav .nav-link {
                padding: 0.6rem 1rem;
                margin: 0.2rem 0;
                border-radius: 8px;
                transition: all 0.3s ease;
            }
            
            .navbar-nav .nav-link:hover {
                background: rgba(255, 255, 255, 0.1);
                transform: translateX(5px);
            }
            
            .navbar-nav .nav-link i {
                width: 20px;
                text-align: center;
            }
            
            body {
                padding-top: 70px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar {
                padding: 0.4rem 0;
            }
            
            .navbar .container-fluid {
                padding-left: 0.6rem;
                padding-right: 0.6rem;
            }
            
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-brand img {
                width: 28px !important;
                height: 28px !important;
            }
            
            .navbar-toggler {
                font-size: 1rem;
            }
            
            .navbar-collapse {
                margin-top: 0.4rem;
                padding: 0.8rem;
            }
            
            .navbar-nav .nav-link {
                padding: 0.5rem 0.8rem;
                font-size: 0.9rem;
            }
            
            body {
                padding-top: 65px;
            }
        }
        
        /* Main Content Mobile Adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-top: 1rem;
                padding: 0 0.5rem;
            }
            
            .stats-card {
                padding: 1.2rem;
                margin-bottom: 1.5rem;
            }
            
            .stats-card h2 {
                font-size: 1.4rem;
            }
            
            .stats-card p {
                font-size: 0.9rem;
            }
            
            .filter-section {
                padding: 1.2rem;
                margin-bottom: 1.5rem;
            }
            
            .filter-section .form-label {
                font-size: 0.95rem;
            }
            
            .filter-section .form-control, .filter-section .form-select {
                padding: 0.6rem 0.8rem;
                font-size: 0.95rem;
            }
            
            .filter-section .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                margin-top: 0.8rem;
                padding: 0 0.3rem;
            }
            
            .stats-card {
                padding: 1rem;
                margin-bottom: 1.2rem;
            }
            
            .stats-card h2 {
                font-size: 1.2rem;
            }
            
            .stats-card p {
                font-size: 0.85rem;
            }
            
            .filter-section {
                padding: 1rem;
                margin-bottom: 1.2rem;
            }
            
            .filter-section .form-label {
                font-size: 0.9rem;
            }
            
            .filter-section .form-control, .filter-section .form-select {
                padding: 0.5rem 0.7rem;
                font-size: 0.9rem;
            }
            
            .filter-section .btn {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
            
            .filter-section .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem !important;
            }
            
            .filter-section .d-flex.gap-2 .btn {
                width: 100%;
            }
        }
        
        /* Table Mobile Improvements */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .table th, .table td {
                padding: 0.5rem 0.3rem;
            }
            
            .table th:first-child, .table td:first-child {
                width: 40px;
            }
        }
        
        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 0.4rem 0.2rem;
            }
            
            .badge {
                font-size: 0.7rem;
            }
        }
        
        /* Pagination Mobile */
        @media (max-width: 768px) {
            .pagination {
                font-size: 0.9rem;
            }
            
            .page-link {
                padding: 0.5rem 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .pagination {
                font-size: 0.8rem;
            }
            
            .page-link {
                padding: 0.4rem 0.6rem;
            }
        }
        
        /* Modal Mobile */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 1rem;
            }
            
            .modal-content {
                border-radius: 10px;
            }
            
            .modal-header, .modal-body, .modal-footer {
                padding: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .modal-header, .modal-body, .modal-footer {
                padding: 0.8rem;
            }
            
            .modal-title {
                font-size: 1.1rem;
            }
        }
        
        /* Toast Mobile */
        @media (max-width: 768px) {
            .toast-container {
                bottom: 1rem;
                right: 1rem;
                left: 1rem;
            }
            
            .toast {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .toast-container {
                bottom: 0.5rem;
                right: 0.5rem;
                left: 0.5rem;
            }
            
            .toast {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1e3a8a 0%, #0ea5e9 100%); border-bottom: 1px solid #e5e7eb;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" style="width: 35px; height: auto; margin-right: 10px;">
                <div class="brand-text">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #fff;">MyoPC</div>
                    <div class="d-none d-md-block" style="font-size: 0.9rem; opacity: 0.9;">Toplu İşlemler</div>
                    <div class="d-block d-md-none" style="font-size: 0.8rem; opacity: 0.9;">Toplu İşlem</div>
                </div>
            </a>
            
            <!-- Hamburger Menu Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Collapsible Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link d-flex align-items-center" href="dashboard.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        <span class="d-none d-sm-inline">Dashboard'a Dön</span>
                        <span class="d-inline d-sm-none">Dashboard</span>
                    </a>
                    <a class="nav-link d-flex align-items-center" href="student_management.php">
                        <i class="fas fa-users me-1"></i>
                        <span class="d-none d-sm-inline">Öğrenci Yönetimi</span>
                        <span class="d-inline d-sm-none">Öğrenciler</span>
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
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h2 class="mb-0"><?php echo $total; ?></h2>
                            <p class="mb-0">Toplam Öğrenci</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h2 class="mb-0"><?php echo count($years); ?></h2>
                            <p class="mb-0">Aktif Yıl</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h2 class="mb-0" id="selectedCount">0</h2>
                            <p class="mb-0">Seçili Öğrenci</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h2 class="mb-0"><?php echo $total_pages; ?></h2>
                            <p class="mb-0">Toplam Sayfa</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtreler -->
            <div class="filter-section">
                        <form method="GET" id="filterForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Yıl Filtresi</label>
                                    <select class="form-select" name="year" id="yearFilter" onchange="submitFilter()">
                                        <option value="">Tüm Yıllar</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?php echo $year['year']; ?>" <?php echo $year_filter == $year['year'] ? 'selected' : ''; ?>><?php echo $year['year']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Arama</label>
                                    <input type="text" class="form-control" name="search" id="searchInput" placeholder="Öğrenci adı veya numarası..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="debounceSearch()">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" id="filterBtn">
                                            <i class="fas fa-search me-1"></i>Filtrele
                                        </button>
                                        <a href="bulk_operations.php" class="btn btn-outline-secondary" id="clearBtn">
                                            <i class="fas fa-times me-1"></i>Temizle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
            </div>

            <!-- Toplu İşlem Butonları -->
            <div class="filter-section">
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-danger" id="bulkDeleteBtn" disabled>
                        <i class="fas fa-trash me-1"></i>Seçilenleri Sil
                    </button>
                    <button class="btn btn-warning" id="bulkUpdateYearBtn" disabled>
                        <i class="fas fa-edit me-1"></i>Yıl Güncelle
                    </button>
                    <button class="btn btn-info" id="selectAllBtn">
                        <i class="fas fa-check-square me-1"></i>Tümünü Seç
                    </button>
                    <button class="btn btn-secondary" id="deselectAllBtn">
                        <i class="fas fa-square me-1"></i>Seçimi Kaldır
                    </button>
                </div>
            </div>

            <!-- Öğrenci Listesi -->
            <div class="card" style="border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); border: 1px solid #e1e5e9;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Öğrenci Listesi</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" id="refreshBtn">
                                <i class="fas fa-sync-alt me-1"></i>Yenile
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="studentsContainer">
                            <?php if (empty($students)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Öğrenci bulunamadı</h5>
                                    <p class="text-muted">Filtreleri değiştirerek tekrar deneyin</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                                </th>
                                                <th>Öğrenci No</th>
                                                <th>Ad Soyad</th>
                                                <th>Akademik Yıl</th>
                                                <th>Kayıt Tarihi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student_data): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input student-checkbox" value="<?php echo $student_data['student_id']; ?>">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student_data['sdt_nmbr']); ?></td>
                                                    <td><?php echo htmlspecialchars($student_data['full_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $student_data['academic_year']; ?></span>
                                                    </td>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($student_data['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sayfalama -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Sayfa navigasyonu" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplu Silme Onay Modal -->
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Toplu Silme Onayı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Seçilen <span id="deleteCount">0</span> öğrenciyi silmek istediğinizden emin misiniz?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Bu işlem geri alınamaz!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="confirmBulkDelete">
                        <i class="fas fa-trash me-1"></i>Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toplu Yıl Güncelleme Modal -->
    <div class="modal fade" id="bulkUpdateYearModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Toplu Yıl Güncelleme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Seçilen <span id="updateCount">0</span> öğrencinin akademik yılını güncellemek istiyorsunuz.</p>
                    <div class="mb-3">
                        <label class="form-label">Yeni Akademik Yıl</label>
                        <select class="form-select" id="newYearSelect">
                            <option value="">Yıl Seçin</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year['year']; ?>"><?php echo $year['year']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-warning" id="confirmBulkUpdateYear">
                        <i class="fas fa-edit me-1"></i>Güncelle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toast" class="toast" role="alert">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">Bildirim</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global değişkenler
        let selectedStudents = new Set();
        let currentPage = <?php echo $page; ?>;
        let currentYearFilter = '<?php echo $year_filter; ?>';
        let currentSearch = '<?php echo $search; ?>';

        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Bulk operations page loaded');
            initializeEventListeners();
            updateSelectedCount();
            initializeMobileNavbar();
        });
        
        // Mobile navbar functionality
        function initializeMobileNavbar() {
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            const navLinks = document.querySelectorAll('.nav-link');
            
            // Manual navbar toggle functionality
            if (navbarToggler && navbarCollapse) {
                navbarToggler.addEventListener('click', function() {
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
                    const isClickInsideNav = navbarCollapse.contains(event.target) || navbarToggler.contains(event.target);
                    
                    if (!isClickInsideNav && navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Close navbar when clicking on nav links
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    });
                });
            }
            
            // Touch-friendly improvements
            const buttons = document.querySelectorAll('.btn, .nav-link, .form-check-input');
            buttons.forEach(button => {
                button.style.minHeight = '44px';
                button.style.touchAction = 'manipulation';
            });
        }

        function initializeEventListeners() {
            
            // Checkbox olayları
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.student-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                        if (this.checked) {
                            selectedStudents.add(checkbox.value);
                        } else {
                            selectedStudents.delete(checkbox.value);
                        }
                    });
                    updateSelectedCount();
                });
            }

            // Student checkbox'ları için event delegation kullan
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('student-checkbox')) {
                    if (e.target.checked) {
                        selectedStudents.add(e.target.value);
                    } else {
                        selectedStudents.delete(e.target.value);
                    }
                    updateSelectedCount();
                }
            });

            // Toplu işlem butonları - null kontrolü ile
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', function() {
                    if (selectedStudents.size === 0) {
                        showToast('Lütfen silinecek öğrenci seçin', 'error');
                        return;
                    }
                    showBulkDeleteModal();
                });
            }

            const bulkUpdateYearBtn = document.getElementById('bulkUpdateYearBtn');
            if (bulkUpdateYearBtn) {
                bulkUpdateYearBtn.addEventListener('click', function() {
                    if (selectedStudents.size === 0) {
                        showToast('Lütfen güncellenecek öğrenci seçin', 'error');
                        return;
                    }
                    showBulkUpdateYearModal();
                });
            }

            const selectAllBtn = document.getElementById('selectAllBtn');
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    const checkboxes = document.querySelectorAll('.student-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = true;
                        selectedStudents.add(checkbox.value);
                    });
                    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = true;
                    }
                    updateSelectedCount();
                });
            }

            const deselectAllBtn = document.getElementById('deselectAllBtn');
            if (deselectAllBtn) {
                deselectAllBtn.addEventListener('click', function() {
                    const checkboxes = document.querySelectorAll('.student-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = false;
                    }
                    selectedStudents.clear();
                    updateSelectedCount();
                });
            }

            // Onay modal butonları
            const confirmBulkDelete = document.getElementById('confirmBulkDelete');
            if (confirmBulkDelete) {
                confirmBulkDelete.addEventListener('click', function() {
                    bulkDelete();
                });
            }

            const confirmBulkUpdateYear = document.getElementById('confirmBulkUpdateYear');
            if (confirmBulkUpdateYear) {
                confirmBulkUpdateYear.addEventListener('click', function() {
                    bulkUpdateYear();
                });
            }

            // Yenile butonu
            const refreshBtn = document.getElementById('refreshBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    location.reload();
                });
            }
        }

        function updateSelectedCount() {
            const count = selectedStudents.size;
            document.getElementById('selectedCount').textContent = count;
            
            // Buton durumlarını güncelle
            document.getElementById('bulkDeleteBtn').disabled = count === 0;
            document.getElementById('bulkUpdateYearBtn').disabled = count === 0;
        }

        function showBulkDeleteModal() {
            document.getElementById('deleteCount').textContent = selectedStudents.size;
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();
        }

        function showBulkUpdateYearModal() {
            document.getElementById('updateCount').textContent = selectedStudents.size;
            const modal = new bootstrap.Modal(document.getElementById('bulkUpdateYearModal'));
            modal.show();
        }

        function bulkDelete() {
            const formData = new FormData();
            formData.append('action', 'bulk_delete');
            // Array'i doğru formatta gönder
            Array.from(selectedStudents).forEach(id => {
                formData.append('student_ids[]', id);
            });

            fetch('bulk_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Response'u text olarak oku
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse hatası:', text);
                        throw new Error('Sunucudan geçersiz yanıt alındı: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    selectedStudents.clear();
                    updateSelectedCount();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Bulk delete hatası:', error);
                showToast('Bir hata oluştu: ' + error.message, 'error');
            });
        }

        function bulkUpdateYear() {
            const newYear = document.getElementById('newYearSelect').value;
            if (!newYear) {
                showToast('Lütfen yeni akademik yıl seçin', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'bulk_update_year');
            // Array'i doğru formatta gönder
            Array.from(selectedStudents).forEach(id => {
                formData.append('student_ids[]', id);
            });
            formData.append('new_year', newYear);

            fetch('bulk_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Response'u text olarak oku
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse hatası:', text);
                        throw new Error('Sunucudan geçersiz yanıt alındı: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    selectedStudents.clear();
                    updateSelectedCount();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Bulk update year hatası:', error);
                showToast('Bir hata oluştu: ' + error.message, 'error');
            });
        }

        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastBody = toast.querySelector('.toast-body');
            const toastHeader = toast.querySelector('.toast-header');
            
            // Mesajı ayarla
            toastBody.textContent = message;
            
            // Tip'e göre ikon ve renk ayarla
            const icon = toastHeader.querySelector('i');
            if (type === 'success') {
                icon.className = 'fas fa-check-circle me-2 text-success';
            } else if (type === 'error') {
                icon.className = 'fas fa-exclamation-triangle me-2 text-danger';
            } else if (type === 'warning') {
                icon.className = 'fas fa-exclamation-circle me-2 text-warning';
            } else {
                icon.className = 'fas fa-info-circle me-2 text-info';
            }
            
            // Toast'u göster
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }

        // Filtreleme fonksiyonları
        function submitFilter() {
            document.getElementById('filterForm').submit();
        }

        let searchTimeout;
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500); // 500ms bekle
        }
    </script>
</body>
</html>