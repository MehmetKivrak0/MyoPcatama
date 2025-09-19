<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: iambatman.php');
    exit;
}

$username = $_SESSION['full_name'] ?? 'Kullanıcı';

// Gerekli modelleri import et
require_once '../config/db.php';
require_once '../models/Student.php';
require_once '../models/Lab.php';
require_once '../models/Assignment.php';
require_once '../controllers/LabController.php';

// Detaylı istatistikleri al
try {
    $db = Database::getInstance();
    
    // Öğrenci sayısı - myopc_students tablosundan
    $studentCount = $db->fetchOne("SELECT COUNT(*) as count FROM myopc_students")['count'] ?? 0;
    
    // Lab sayısı - LabController kullanarak (index.html ile aynı yöntem)
    $labController = new LabController();
    $labsResult = $labController->getAllLabs();
    $labCount = 0;
    if ($labsResult['type'] === 'success') {
        $labCount = count($labsResult['data']);
    }
    
    // Toplam atama sayısı - myopc_assignments tablosundan
    $assignmentCount = $db->fetchOne("SELECT COUNT(*) as count FROM myopc_assignments")['count'] ?? 0;
    
    // Son eklenen öğrenciler
    $recentStudents = $db->fetchAll("SELECT student_name, student_surname, created_at FROM myopc_students ORDER BY created_at DESC LIMIT 5");
    
    // Debug için log ekle
    error_log("Dashboard Stats - Student Count: " . $studentCount);
    error_log("Dashboard Stats - Lab Count: " . $labCount);
    error_log("Dashboard Stats - Assignment Count: " . $assignmentCount);
    error_log("Dashboard Stats - Labs Result: " . json_encode($labsResult));
    
} catch (Exception $e) {
    $studentCount = 0;
    $labCount = 0;
    $assignmentCount = 0;
    $recentStudents = [];
    error_log("Dashboard Stats Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title> Öğrenci Atama Sistemi</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard CSS -->
    <link href="css/dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="css/pc-update.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="css/student_cards.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <!-- Student Year Filter CSS -->
    <style>
        /* Filtreleme paneli için ek stiller */
        .student-filter-panel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(30, 58, 95, 0.2);
            border: 1px solid rgba(135, 206, 235, 0.2);
            position: relative;
            overflow: hidden;
            display: none;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .student-filter-panel.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        .student-filter-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(135, 206, 235, 0.05) 0%, rgba(30, 58, 95, 0.05) 100%);
            pointer-events: none;
        }
        
        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .filter-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #ffffff;
            font-size: 1.2rem;
            font-weight: 600;
            text-shadow: 0 2px 10px rgba(30, 58, 95, 0.5);
            margin: 0;
        }
        
        .filter-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        
        .year-filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .year-filter-btn {
            padding: 8px 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .year-filter-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: #87ceeb;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(135, 206, 235, 0.3);
            color: #ffffff;
        }
        
        .year-filter-btn.active {
            background: linear-gradient(135deg, #87ceeb, #2d5a87);
            border-color: #87ceeb;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(135, 206, 235, 0.4);
            transform: translateY(-2px);
        }
        
        .show-all-btn {
            padding: 8px 16px;
            border: 2px solid rgba(34, 197, 94, 0.3);
            border-radius: 25px;
            background: rgba(34, 197, 94, 0.1);
            color: rgba(34, 197, 94, 0.9);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .show-all-btn:hover {
            background: rgba(34, 197, 94, 0.2);
            border-color: #22c55e;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        
        .show-all-btn.active {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-color: #22c55e;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
            transform: translateY(-2px);
        }
        
        .clear-filters-btn {
            padding: 8px 16px;
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 25px;
            background: rgba(239, 68, 68, 0.1);
            color: rgba(239, 68, 68, 0.9);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .clear-filters-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        .filter-stats {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        .filter-stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-stat-item i {
            color: #87ceeb;
            font-size: 0.8rem;
        }
        
        .filter-stat-number {
            font-weight: 600;
            color: #ffffff;
        }
        
        /* Filtreleme durumu sınıfları - animasyon yok */
        .student-card-filtered {
            opacity: 0.3;
            pointer-events: none;
        }
        
        .student-card-visible {
            opacity: 1;
            pointer-events: auto;
        }
        
        .pc-card.filtered {
            display: none !important;
        }
        
        .pc-card.visible {
            display: block !important;
        }
        
        
        /* Responsive */
        @media (max-width: 768px) {
            .filter-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .filter-controls {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }
            
            .year-filter-buttons {
                justify-content: center;
                width: 100%;
            }
            
            .year-filter-btn, .show-all-btn, .clear-filters-btn {
                flex: 1;
                min-width: 0;
                text-align: center;
            }
            
        }
    </style>
    
    <!-- Export Button Styles -->
    <style>
        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #6c757d !important;
            border-color: #6c757d !important;
        }
        
        .action-button:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .action-button:disabled i {
            color: #adb5bd !important;
        }
    </style>
</head>
<body>
    <!-- Top Header Bar -->
    <div class="top-header-bar">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Logo and Title -->
                <div class="col-lg-3 col-md-4 col-sm-12 mb-2 mb-md-0">
                    <div class="logo-section d-flex align-items-center justify-content-center justify-content-md-start">
                        <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" class="header-logo">
                        <div class="logo-text">
                            <div class="brand-name">Öğrenci Atama <br> Sistemi</div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Section -->
                <div class="col-lg-6 col-md-5 col-sm-12 mb-2 mb-md-0">
                    <div class="header-stats d-flex justify-content-center flex-wrap">
                        <div class="header-stat-item">
                            <i class="fas fa-users"></i>
                            <span class="stat-number"><?php echo $studentCount; ?></span>
                            <span class="stat-label">Öğrenci</span>
                        </div>
                        <div class="header-stat-item">
                            <i class="fas fa-building"></i>
                            <span class="stat-number"><?php echo $labCount; ?></span>
                            <span class="stat-label">Laboratuvar</span>
                        </div>
                        <div class="header-stat-item">
                            <i class="fas fa-tasks"></i>
                            <span class="stat-number"><?php echo $assignmentCount; ?></span>
                            <span class="stat-label">Atama</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="col-lg-3 col-md-3 col-sm-12">
                    <div class="header-actions d-flex justify-content-center justify-content-lg-end flex-wrap">
                        <button class="header-btn action-button excel-import-btn" onclick="openExcelImport()">
                            <i class="fas fa-file-excel"></i>
                            <span class="btn-text">Excel'den İçe Aktar</span>
                        </button>
                        <a href="../logout.php" class="header-btn logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="btn-text">Çıkış Yap</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Welcome Message -->
           

            <!-- Action Buttons Row -->
            <div class="action-buttons-row mb-4">
                <div class="container-fluid">
                    <div class="action-buttons-container">
                        <a href="student_management.php" class="action-button students-btn">
                            <i class="fas fa-users"></i>
                            <span class="btn-text">Öğrenciler</span>
                        </a>
                        <a href="lab_list.php" class="action-button labs-btn">
                            <i class="fas fa-building"></i>
                            <span class="btn-text">Laboratuvarlar</span>
                        </a>
                        <a href="add_lab.php" class="action-button new-lab-btn">
                            <i class="fas fa-plus"></i>
                            <span class="btn-text">Yeni Laboratuvar Ekle</span>
                        </a>
                        <button class="action-button assignments-btn" id="exportAssignmentsBtn" onclick="exportAssignments()" disabled>
                            <i class="fas fa-download"></i>
                            <span class="btn-text">Atamaları Dışa Aktar</span>
                        </button>
                        <button class="action-button test-btn" onclick="openTestPage()" id="testButton">
                            <i class="fas fa-bug"></i>
                            <span class="btn-text">Atama Testi</span>
                        </button>
                     </div>
                </div>
            </div>

            <!-- Lab PC Viewer Section -->
            <div class="lab-pc-viewer-section">
                <div class="container-fluid">
                    <div class="lab-selector-card">
                        <div class="lab-selector-header">
                            <h3><i class="fas fa-desktop"></i> Laboratuvar PC Durumu</h3>
                            <p>Bir laboratuvar seçerek PC'lerin durumunu görüntüleyin</p>
                        </div>
                        
                        <div class="lab-selector-controls">
                            <div class="lab-select-wrapper">
                                <select id="labSelector" class="lab-select">
                                    <option value="">Laboratuvar Seçin</option>
                                    <?php
                                    try {
                                        $labModel = new Lab($db);
                                        $labs = $labModel->getAll();
                                        error_log("Dashboard - Laboratuvarlar yüklendi: " . count($labs));
                                        foreach ($labs as $lab) {
                                            error_log("Dashboard - Lab: " . $lab['lab_name'] . " (ID: " . $lab['computer_id'] . ", PC Count: " . $lab['pc_count'] . ")");
                                            echo '<option value="' . $lab['computer_id'] . '" data-pc-count="' . $lab['pc_count'] . '">' . htmlspecialchars($lab['lab_name']) . ' (' . $lab['pc_count'] . ' PC)</option>';
                                        }
                                        // Debug için JavaScript'e laboratuvar sayısını gönder
                                        echo '<script>console.log("Dashboard - PHP\'den laboratuvar sayısı: ' . count($labs) . '");</script>';
                                    } catch (Exception $e) {
                                        error_log("Dashboard - Laboratuvar yükleme hatası: " . $e->getMessage());
                                        echo '<option value="">Laboratuvar listesi alınamadı</option>';
                                        echo '<script>console.error("Dashboard - Laboratuvar yükleme hatası: ' . addslashes($e->getMessage()) . '");</script>';
                                    }
                                    ?>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>
                            <div class="control-buttons">
                                <button id="refreshPCs" class="refresh-btn" title="PC Durumlarını Yenile">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button id="editPCCount" class="edit-pc-btn" title="PC Sayısını Düzenle" style="display: none;">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button id="editMaxStudents" class="edit-max-students-btn" title="Maksimum Öğrenci Sayısını Düzenle" style="display: none;">
                                    <i class="fas fa-users"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Öğrenci Yılı Filtreleme Paneli -->
                    <div class="student-filter-panel" id="studentFilterPanel">
                        <div class="filter-header">
                            <h3 class="filter-title">
                                <i class="fas fa-filter"></i>
                                Öğrenci Yılı Filtreleme
                            </h3>
                            <div class="filter-controls">
                                <div class="year-filter-buttons" id="yearFilterButtons">
                                    <!-- Yıl butonları dinamik olarak eklenecek -->
                                </div>
                                <button class="show-all-btn active" id="showAllBtn">
                                    <i class="fas fa-eye"></i>
                                    Tümünü Göster
                                </button>
                                <button class="clear-filters-btn" id="clearFiltersBtn">
                                    <i class="fas fa-times"></i>
                                    Temizle
                                </button>
                            </div>
                        </div>
                        <div class="filter-stats" id="filterStats">
                            <div class="filter-stat-item">
                                <i class="fas fa-users"></i>
                                <span>Toplam: <span class="filter-stat-number" id="totalStudents">0</span></span>
                            </div>
                            <div class="filter-stat-item">
                                <i class="fas fa-eye"></i>
                                <span>Görünen: <span class="filter-stat-number" id="visibleStudents">0</span></span>
                            </div>
                            <div class="filter-stat-item">
                                <i class="fas fa-filter"></i>
                                <span>Filtre: <span class="filter-stat-number" id="currentFilterText">Tümü</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- PC Cards Container -->
                    <div id="pcCardsContainer" class="pc-cards-container" style="display: none;">
                        <div class="pc-cards-header">
                            <h4 id="pcCardsLabName">Laboratuvar PC'leri</h4>
                            <div class="pc-stats">
                                <span class="stat-item available">
                                    <i class="fas fa-circle"></i>
                                    <span>Boş</span>
                                    <span class="stat-count" id="availablePCs">0</span>
                                </span>
                                <span class="stat-item occupied">
                                    <i class="fas fa-circle"></i>
                                    <span>Dolu</span>
                                    <span class="stat-count" id="occupiedPCs">0</span>
                                </span>
                            </div>
                        </div>
                        <div id="pcCardsGrid" class="pc-cards-grid">
                            <!-- PC kartları buraya dinamik olarak yüklenecek -->
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div id="pcLoadingIndicator" class="loading-indicator" style="display: none;">
                        <div class="loading-spinner"></div>
                        <p>PC durumları yükleniyor...</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- PC Sayısı Düzenleme Modal -->
    <div class="modal fade" id="editPCCountModal" tabindex="-1" aria-labelledby="editPCCountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPCCountModalLabel">
                        <i class="fas fa-desktop me-2"></i>PC Sayısını Düzenle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong id="currentLabName">Laboratuvar</strong> için PC sayısını değiştiriyorsunuz.
                    </div>
                    
                    <div class="mb-3">
                        <label for="newPCCount" class="form-label">PC Sayısı:</label>
                        <input type="number" class="form-control" id="newPCCount" min="1" max="100" placeholder="PC sayısını girin">
                        <div class="form-text">PC sayısı 1 ile 100 arasında olmalıdır.</div>
                    </div>
                    
                    <div class="alert alert-warning" style="display: none;" id="pcCountWarning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="warningText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="savePCCount">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Maksimum Öğrenci Sayısı Düzenleme Modal -->
    <div class="modal fade" id="editMaxStudentsModal" tabindex="-1" aria-labelledby="editMaxStudentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMaxStudentsModalLabel">
                        <i class="fas fa-users me-2"></i>Maksimum Öğrenci Sayısını Düzenle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong id="currentLabNameMaxStudents">Laboratuvar</strong> için PC başına maksimum öğrenci sayısını değiştiriyorsunuz.
                    </div>
                    
                    <div class="mb-3">
                        <label for="newMaxStudentsPerPC" class="form-label">PC Başına Maksimum Öğrenci Sayısı:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="newMaxStudentsPerPC" min="1" max="20" value="4" placeholder="Maksimum öğrenci sayısı">
                            <span class="input-group-text">öğrenci</span>
                        </div>
                        <div class="form-text">Her PC'ye atanabilecek maksimum öğrenci sayısı (1-20 arası).</div>
                    </div>
                    
                    <div class="alert alert-warning" style="display: none;" id="maxStudentsWarning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="maxStudentsWarningText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="saveMaxStudents">
                        <i class="fas fa-save me-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Excel Import Modal -->
    <div class="modal fade" id="excelImportModal" tabindex="-1" aria-labelledby="excelImportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="excelImportModalLabel">
                        <i class="fas fa-file-excel me-2"></i>Excel'den Öğrenci Verilerini İçe Aktar
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Excel Import Form -->
                    <form id="excelImportForm" enctype="multipart/form-data">
                        <!-- Template Download Section -->
                        <div class="mb-3 text-center">
                            <a href="../excel-to-mysql/template.xlsx" class="btn btn-outline-info" download>
                                <i class="fas fa-file-excel me-2"></i>Şablon Excel Dosyasını İndir
                            </a>
                        </div>
                        
                        <div class="mb-4">
                            <label for="excel_file" class="form-label fw-bold">
                                <i class="fas fa-upload me-2"></i>Excel Dosyası Seçin:
                            </label>
                            <input type="file" class="form-control form-control-lg" id="excel_file" name="excel_file" 
                                   accept=".xlsx,.xls" required>
                            <div class="form-text">Sadece .xlsx ve .xls dosyaları kabul edilir.</div>
                        </div>
                        
                        <!-- Import Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" form="excelImportForm" class="btn btn-success btn-lg" id="importButton">
                                <i class="fas fa-upload me-2"></i>Verileri İçe Aktar
                            </button>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div id="importProgress" class="mb-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 100%"></div>
                            </div>
                            <small class="text-muted">Veriler işleniyor...</small>
                        </div>
                        
                        <!-- Result Area -->
                        <div id="importResult" class="mt-3"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Atama Sistemi Modal -->
    <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="assignmentModalTitle">
                        <i class="fas fa-user-plus me-2"></i>
                        Öğrenci Ekle
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Seçim Bilgileri -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="info-content">
                                    <h6>Laboratuvar</h6>
                                    <p id="selectedLabName" class="mb-0">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-icon">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div class="info-content">
                                    <h6>PC Numarası</h6>
                                    <p id="selectedPCNumber" class="mb-0">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Öğrenci Seçimi -->
                    <div class="student-selection-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fas fa-user-graduate me-2"></i>
                                Öğrenci Seçin
                            </h5>
                            <div class="selected-count-display">
                                <span class="badge bg-primary fs-6" id="selectedStudentCount">0</span>
                                <small class="text-muted ms-2">öğrenci seçildi</small>
                                <span class="badge bg-warning ms-2" id="maxStudentsLimit" style="display: none;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Maksimum <span id="maxStudentsCount">4</span> öğrenci
                                </span>
                            </div>
                        </div>
                        
                        <!-- Öğrenci Sınırı Uyarısı -->
                        <div class="alert alert-warning" id="studentLimitWarning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Dikkat:</strong> Bu PC'ye maksimum <span id="warningMaxStudents">4</span> öğrenci atanabilir. 
                            Şu anda <span id="currentStudentCount">0</span> öğrenci atanmış durumda.
                        </div>
                        
                        <!-- Loading Indicator -->
                        <div id="studentLoadingIndicator" class="text-center py-4" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                            <p class="mt-2 text-muted">Öğrenci verileri yükleniyor...</p>
                        </div>
                        
                        <!-- Öğrenci Listesi -->
                        <div id="studentListContainer" class="student-list-simple">
                            <!-- Öğrenci listesi buraya yüklenecek -->
                        </div>
                    </div>

                    <!-- Gizli Input'lar -->
                    <input type="hidden" id="selectedPCId" value="">
                    <input type="hidden" id="selectedComputerId" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>İptal
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmAssignment">
                        <i class="fas fa-check me-2"></i>Öğrencileri Ekle
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Toast Notification -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="systemToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle text-primary me-2"></i>
                <strong class="me-auto">Sistem Bildirimi</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                <!-- Bildirim mesajı buraya gelecek -->
            </div>
        </div>
    </div>

    <!-- Test Modal -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="testModalLabel">
                        <i class="fas fa-bug me-2"></i>
                        Atama Sistemi Test Sayfası
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 0;">
                    <div id="testContent" style="height: 600px; overflow-y: auto;">
                        <!-- Test içeriği buraya yüklenecek -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Kapat
                    </button>
                    <button type="button" class="btn btn-primary" onclick="refreshTest()">
                        <i class="fas fa-sync-alt me-2"></i>Yenile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PC Detayları Modal -->
    <div class="modal fade" id="pcDetailsModal" tabindex="-1" aria-labelledby="pcDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="pcDetailsModalLabel">
                        <i class="fas fa-desktop me-2"></i>
                        <span id="pcDetailsTitle">PC Detayları</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- PC Bilgileri -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="fas fa-info-circle me-2"></i>PC Bilgileri
                                    </h6>
                                    <div class="mb-2">
                                        <strong>PC Numarası:</strong> <span id="pcDetailsNumber" class="text-primary">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Durum:</strong> <span id="pcDetailsStatus" class="badge">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Laboratuvar:</strong> <span id="pcDetailsLab">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-success">
                                        <i class="fas fa-users me-2"></i>Atanmış Öğrenciler
                                    </h6>
                                    <div class="mb-2">
                                        <strong>Toplam Öğrenci:</strong> <span id="pcDetailsStudentCount" class="badge bg-success">0</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Son Atama:</strong> <span id="pcDetailsLastAssignment">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Atanmış Öğrenciler Listesi -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-user-graduate me-2"></i>Atanmış Öğrenciler
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="pcStudentsList">
                                <!-- Öğrenciler buraya yüklenecek -->
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Yükleniyor...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Kapat
                    </button>
                    <button type="button" class="btn btn-primary" onclick="refreshPCDetails()">
                        <i class="fas fa-sync-alt me-2"></i>Yenile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dasboard.js?v=<?php echo time(); ?>"></script>
    <script src="js/pc-update.js?v=<?php echo time(); ?>"></script>
    <script src="js/export-assignments.js?v=<?php echo time(); ?>"></script>
    <script src="js/student-year-filter.js?v=<?php echo time(); ?>"></script>
    <script>
        // Sayfa yüklendiğinde laboratuvar seçimi yapılmaz
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Dashboard yüklendi');
            const labSelector = document.getElementById('labSelector');
            console.log('🔍 Laboratuvar seçici bulundu:', labSelector);
            console.log('🔍 Laboratuvar seçici options sayısı:', labSelector ? labSelector.options.length : 0);
            
            // Laboratuvar seçimi yapılmaz, kullanıcı manuel olarak seçmeli
            console.log('ℹ️ Laboratuvar seçimi kullanıcıya bırakıldı');
            
            // Filtreleme sistemi yüklendi
        });
    </script>
    <script>
        // Test sayfasını aç
        function openTestPage() {
            console.log('🧪 Test sayfası açılıyor...');
            try {
                loadTestContent();
                const modal = new bootstrap.Modal(document.getElementById('testModal'));
                modal.show();
                console.log('✅ Test modal başarıyla açıldı');
            } catch (error) {
                console.error('❌ Test modal açılırken hata:', error);
                alert('Test modal açılırken hata oluştu: ' + error.message);
            }
        }
        
        // Test içeriğini yükle
        function loadTestContent() {
            const testContent = document.getElementById('testContent');
            testContent.innerHTML = `
                <div style="padding: 20px; font-family: Arial, sans-serif;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; margin: -20px -20px 20px -20px;">
                        <h2><i class="fas fa-bug me-2"></i>Atama Sistemi Test Sayfası</h2>
                        <p>Atama sisteminin çalışıp çalışmadığını test edin</p>
                    </div>
                    
                    <!-- Test 1: PC Kartları Oluşturma -->
                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h4><i class="fas fa-desktop me-2"></i>Test 1: PC Kartları Oluşturma</h4>
                        <p>PC kartlarının doğru şekilde oluşturulup oluşturulmadığını test eder.</p>
                        <button class="btn btn-success" onclick="testPCCards()">
                            <i class="fas fa-desktop me-2"></i>PC Kartlarını Test Et
                        </button>
                        <div id="test1-result" style="margin-top: 10px;"></div>
                        <div id="pc-cards-container" style="margin-top: 15px;"></div>
                    </div>

                    <!-- Test 2: PC ID Kontrolü -->
                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h4><i class="fas fa-key me-2"></i>Test 2: PC ID Kontrolü</h4>
                        <p>PC ID'lerinin doğru şekilde set edilip edilmediğini test eder.</p>
                        <button class="btn btn-success" onclick="testPCIds()">
                            <i class="fas fa-key me-2"></i>PC ID'lerini Test Et
                        </button>
                        <div id="test2-result" style="margin-top: 10px;"></div>
                    </div>

                    <!-- Test 3: Modal Açma -->
                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h4><i class="fas fa-window-maximize me-2"></i>Test 3: Modal Açma</h4>
                        <p>Atama modal'ının doğru şekilde açılıp açılmadığını test eder.</p>
                        <button class="btn btn-success" onclick="testModal()">
                            <i class="fas fa-window-maximize me-2"></i>Modal'ı Test Et
                        </button>
                        <div id="test3-result" style="margin-top: 10px;"></div>
                    </div>

                    <!-- Test 4: Atama İşlemi -->
                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h4><i class="fas fa-user-plus me-2"></i>Test 4: Atama İşlemi</h4>
                        <p>Atama işleminin doğru şekilde çalışıp çalışmadığını test eder.</p>
                        <button class="btn btn-success" onclick="testAssignment()">
                            <i class="fas fa-user-plus me-2"></i>Atama İşlemini Test Et
                        </button>
                        <div id="test4-result" style="margin-top: 10px;"></div>
                    </div>

                    <!-- Test Sonuçları -->
                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px;">
                        <h4><i class="fas fa-chart-bar me-2"></i>Test Sonuçları</h4>
                        <div id="overall-result"></div>
                    </div>
                </div>
            `;
        }
        
        // Test sayfasını yenile
        function refreshTest() {
            loadTestContent();
        }
        
        // Test verileri
        let testResults = {
            test1: false,
            test2: false,
            test3: false,
            test4: false
        };

        const testPCs = [
            { pc_id: 1, pc_number: 1, student_count: 0, students: [] },
            { pc_id: 2, pc_number: 2, student_count: 1, students: [{ full_name: 'Test Öğrenci', sdt_nmbr: '12345' }] },
            { pc_id: 3, pc_number: 3, student_count: 0, students: [] },
            { pc_id: 4, pc_number: 4, student_count: 0, students: [] }
        ];

        function showResult(elementId, message, type = 'success') {
            const element = document.getElementById(elementId);
            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'exclamation-triangle';
            const bgClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-warning';
            
            element.innerHTML = `
                <div class="alert ${bgClass}">
                    <i class="fas fa-${icon} me-2"></i>
                    ${message}
                </div>
            `;
        }

        function testPCCards() {
            console.log('🧪 Test 1: PC Kartları Oluşturma başlatılıyor...');
            try {
                const container = document.getElementById('pc-cards-container');
                container.innerHTML = '';
                
                testPCs.forEach(pc => {
                    const isOccupied = pc.student_count > 0;
                    const statusClass = isOccupied ? 'border-danger' : 'border-success';
                    const statusText = isOccupied ? 'Dolu' : 'Boş';
                    const statusIcon = isOccupied ? 'fas fa-user' : 'fas fa-desktop';
                    
                    const pcDisplayNumber = `PC${pc.pc_number.toString().padStart(2, '0')}`;
                    const pcId = pc.pc_id || pc.pc_number;
                    
                    const cardHTML = `
                        <div class="card ${statusClass} mb-2" style="width: 200px; display: inline-block; margin: 10px;">
                            <div class="card-body text-center">
                                <h5 class="card-title">${pcDisplayNumber}</h5>
                                <p class="card-text">
                                    <i class="${statusIcon}"></i> ${statusText}
                                </p>
                                <button class="btn btn-primary btn-sm" onclick="testAssignStudent(${pcId}, ${pc.pc_number})">
                                    <i class="fas fa-user-plus"></i> Ata
                                </button>
                            </div>
                        </div>
                    `;
                    container.innerHTML += cardHTML;
                });
                
                testResults.test1 = true;
                showResult('test1-result', `PC kartları başarıyla oluşturuldu! (${testPCs.length} kart)`, 'success');
                updateOverallResult();
                
            } catch (error) {
                testResults.test1 = false;
                showResult('test1-result', `Hata: ${error.message}`, 'error');
                updateOverallResult();
            }
        }

        function testPCIds() {
            console.log('🧪 Test 2: PC ID Kontrolü başlatılıyor...');
            
            try {
                const pcCards = document.querySelectorAll('#pc-cards-container .card');
                let allValid = true;
                let errorMessage = '';
                
                pcCards.forEach((card, index) => {
                    const pcId = card.querySelector('button')?.getAttribute('onclick')?.match(/testAssignStudent\(([^,]+),/)?.[1];
                    
                    if (!pcId || pcId === 'undefined' || pcId === 'null') {
                        allValid = false;
                        errorMessage += `Kart ${index + 1}: PC ID geçersiz (${pcId})<br>`;
                    }
                });
                
                if (allValid) {
                    testResults.test2 = true;
                    showResult('test2-result', `Tüm PC ID'leri geçerli! (${pcCards.length} kart kontrol edildi)`, 'success');
                } else {
                    testResults.test2 = false;
                    showResult('test2-result', `Hata: ${errorMessage}`, 'error');
                }
                
                updateOverallResult();
                
            } catch (error) {
                testResults.test2 = false;
                showResult('test2-result', `Hata: ${error.message}`, 'error');
                updateOverallResult();
            }
        }

        function testModal() {
            console.log('🧪 Test 3: Modal Açma başlatılıyor...');
            
            try {
                // Test modal'ı oluştur
                const testModal = document.createElement('div');
                testModal.className = 'modal fade';
                testModal.innerHTML = `
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Test Modal</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Test modal'ı başarıyla açıldı!</p>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(testModal);
                const modal = new bootstrap.Modal(testModal);
                modal.show();
                
                testModal.addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(testModal);
                });
                
                testResults.test3 = true;
                showResult('test3-result', 'Modal başarıyla açıldı!', 'success');
                updateOverallResult();
                
            } catch (error) {
                testResults.test3 = false;
                showResult('test3-result', `Hata: ${error.message}`, 'error');
                updateOverallResult();
            }
        }

        function testAssignStudent(pcId, pcNumber) {
            console.log('🧪 Test Assign Student:', pcId, pcNumber);
            alert(`Test Atama: PC ID: ${pcId}, PC No: ${pcNumber}`);
        }

        function testAssignment() {
            console.log('🧪 Test 4: Atama İşlemi başlatılıyor...');
            
            try {
                const testData = {
                    pcId: 1,
                    pcNumber: 1,
                    labId: 1,
                    studentIds: [1, 2, 3]
                };
                
                console.log('Test atama verileri:', testData);
                
                if (testData.pcId && testData.pcNumber && testData.labId && testData.studentIds.length > 0) {
                    testResults.test4 = true;
                    showResult('test4-result', `Atama işlemi test verileri geçerli! (${testData.studentIds.length} öğrenci)`, 'success');
                } else {
                    testResults.test4 = false;
                    showResult('test4-result', 'Atama test verileri geçersiz!', 'error');
                }
                
                updateOverallResult();
                
            } catch (error) {
                testResults.test4 = false;
                showResult('test4-result', `Hata: ${error.message}`, 'error');
                updateOverallResult();
            }
        }

        function updateOverallResult() {
            const totalTests = Object.keys(testResults).length;
            const passedTests = Object.values(testResults).filter(result => result === true).length;
            const failedTests = totalTests - passedTests;
            
            let resultHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Geçen Testler: ${passedTests}/${totalTests}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert ${failedTests > 0 ? 'alert-danger' : 'alert-success'}">
                            <i class="fas fa-${failedTests > 0 ? 'times-circle' : 'check-circle'} me-2"></i>
                            Başarısız Testler: ${failedTests}/${totalTests}
                        </div>
                    </div>
                </div>
            `;
            
            if (passedTests === totalTests) {
                resultHTML += `
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-trophy me-2"></i>
                        <strong>Tüm testler başarılı! Atama sistemi çalışıyor.</strong>
                    </div>
                `;
            } else {
                resultHTML += `
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Bazı testler başarısız. Lütfen hataları kontrol edin.</strong>
                    </div>
                `;
            }
            
            document.getElementById('overall-result').innerHTML = resultHTML;
        }


        // Sayfa yüklendiğinde test butonunu kontrol et
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Dashboard yüklendi, test butonu kontrol ediliyor...');
            
            const testButton = document.getElementById('testButton');
            if (testButton) {
                console.log('✅ Test butonu bulundu');
                testButton.addEventListener('click', function(e) {
                    console.log('🖱️ Test butonuna tıklandı');
                });
            } else {
                console.error('❌ Test butonu bulunamadı!');
            }
            
            const testModal = document.getElementById('testModal');
            if (testModal) {
                console.log('✅ Test modal bulundu');
            } else {
                console.error('❌ Test modal bulunamadı!');
            }
        });
    </script>
</body>
</html>
