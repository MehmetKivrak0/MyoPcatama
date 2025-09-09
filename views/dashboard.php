<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: iambatman.php');
    exit;
}

$username = $_SESSION['full_name'] ?? 'Kullanıcı';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MyoPc </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" class="img-fluid" style="width: 35px; height: auto;">
                <div class="brand-text">
                    <div class="text-blue">MyoPC</div>
                    <div class="text-white"> Atama Sistemi</div>
                </div>
            </a>
            
            <!-- Mini Stats in Navbar -->
            <div class="navbar-stats d-none d-lg-flex">
                <div class="navbar-stat-item">
                    <i class="fas fa-users"></i>
                    <span class="stat-number">0</span>
                    <span class="stat-label">Öğrenci</span>
                </div>
                <div class="navbar-stat-item">
                    <i class="fas fa-desktop"></i>
                    <span class="stat-number">0</span>
                    <span class="stat-label">Bilgisayar</span>
                </div>
                <div class="navbar-stat-item">
                    <i class="fas fa-building"></i>
                    <span class="stat-number">0</span>
                    <span class="stat-label">Laboratuvar</span>
                </div>
                <div class="navbar-stat-item">
                    <i class="fas fa-tasks"></i>
                    <span class="stat-number">0</span>
                    <span class="stat-label">Atama</span>
                </div>
            </div>
            
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-file-csv me-1"></i>CSV Import
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Çıkış Yap
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Action Buttons -->
        <div class="top-actions mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="left-actions">
                    <button class="btn btn-outline-primary me-2">
                        <i class="fas fa-users me-1"></i>Öğrenciler
                    </button>
                    <button class="btn btn-outline-success">
                        <i class="fas fa-desktop me-1"></i>Laboratuvarlar
                    </button>
                </div>
                <div class="right-actions">
                    <button class="btn btn-outline-info" onclick="exportAssignments()">
                        <i class="fas fa-download me-1"></i>Atamaları Dışa Aktar
                    </button>
                </div>
            </div>
        </div>

            
        <!-- Laboratuvar Seçimi ve Grid Yapısı -->
        <div class="grid-section">
            <div class="grid-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="grid-stats mt-3">
                    <span class="stat-item">
                        <i class="fas fa-building text-info"></i>
                        <span class="stat-number" id="selectedLab">-</span>
                        <span class="stat-label">Laboratuvar</span>
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-desktop text-primary"></i>
                        <span class="stat-number" id="totalPcs">54</span>
                        <span class="stat-label">Toplam</span>
                    </span>
                    <span class="stat-item clickable-stat" onclick="filterByStatus('assigned')" data-status="assigned">
                        <i class="fas fa-user-check text-success"></i>
                        <span class="stat-number" id="assignedPcs">0</span>
                        <span class="stat-label">Atanmış</span>
                    </span>
                    <span class="stat-item clickable-stat" onclick="filterByStatus('available')" data-status="available">
                        <i class="fas fa-user-times text-warning"></i>
                        <span class="stat-number" id="availablePcs">54</span>
                        <span class="stat-label">Boş</span>
                    </span>
                </div>
                
                <!-- Yıl Filtreleme -->
                <div class="year-stats mt-3">
                    <span class="stat-item clickable-year" onclick="filterByYear('')" data-year="">
                        <i class="fas fa-globe text-primary"></i>
                        <span class="stat-number">Tümü</span>
                        <span class="stat-label">Yıllar</span>
                    </span>
                    <?php
                    // Veritabanından yılları çek
                    require_once '../config/db.php';
                    require_once '../models/Student.php';
                    
                    try {
                        $db = Database::getInstance();
                        $studentModel = new Student($db);
                        $years = $studentModel->getAvailableYears();
                        
                        foreach ($years as $yearData) {
                            $year = $yearData['year'];
                            echo "<span class=\"stat-item clickable-year\" onclick=\"filterByYear('{$year}')\" data-year=\"{$year}\">";
                            echo "<i class=\"fas fa-calendar text-success\"></i>";
                            echo "<span class=\"stat-number\">{$year}</span>";
                            echo "<span class=\"stat-label\">Yılı</span>";
                            echo "</span>";
                        }
                    } catch (Exception $e) {
                        // Hata durumunda varsayılan yılları göster
                        $defaultYears = [2024, 2023, 2022, 2021];
                        foreach ($defaultYears as $year) {
                            echo "<span class=\"stat-item clickable-year\" onclick=\"filterByYear('{$year}')\" data-year=\"{$year}\">";
                            echo "<i class=\"fas fa-calendar text-success\"></i>";
                            echo "<span class=\"stat-number\">{$year}</span>";
                            echo "<span class=\"stat-label\">Yılı</span>";
                            echo "</span>";
                        }
                    }
                    ?>
                </div>
                    <div class="lab-selector">
                        <label for="labSelect" class="form-label me-2">Laboratuvar:</label>
                        <select class="form-select lab-select" id="labSelect" onchange="changeLab()">
                            <option value="">Laboratuvar seçin...</option>
                            <option value="lab1" data-rows="14" data-cols="4" data-total="54">Lab 1 - 54 PC (14x4)</option>
                            <option value="lab2" data-rows="12" data-cols="4" data-total="48">Lab 2 - 48 PC (12x4)</option>
                            <option value="lab3" data-rows="14" data-cols="4" data-total="56">Lab 3 - 56 PC (14x4)</option>
                            <option value="lab4" data-rows="13" data-cols="4" data-total="50">Lab 4 - 50 PC (13x4)</option>
                            <option value="lab5" data-rows="11" data-cols="4" data-total="42">Lab 5 - 42 PC (11x4)</option>
                        </select>
                    </div>
                        </div>
                
            </div>
            
            <div class="pc-grid" id="pcGrid">
                <!-- 54 adet bilgisayar kartı buraya gelecek -->
                <!-- 4 sütun x 14 satır = 54 adet -->
            </div>
            </div>
        </div>

    <!-- Assignment Modal -->
    <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignmentModalLabel">
                        <i class="fas fa-tasks me-2"></i>Bilgisayar Atama Sistemi Detayları
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="info-title">
                                    <i class="fas fa-info-circle me-2"></i>Sistem Bilgileri
                                </h6>
                                <ul class="info-list">
                                    <li><strong>Toplam Bilgisayar:</strong> 54 adet</li>
                                    <li><strong>Grid Yapısı:</strong> 4 sütun x 14 satır</li>
                                    <li><strong>Atama Durumu:</strong> <span id="modalAssignedCount">0</span> atanmış, <span id="modalAvailableCount">54</span> boş</li>
                                    <li><strong>Son Güncelleme:</strong> <span id="lastUpdate">-</span></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="info-title">
                                    <i class="fas fa-cogs me-2"></i>Hızlı İşlemler
                                </h6>
                                <div class="quick-actions">
                                    <button class="btn btn-outline-primary btn-sm mb-2" onclick="selectRandomPC()">
                                        <i class="fas fa-random me-1"></i>Rastgele PC Seç
                                    </button>
                                    <button class="btn btn-outline-success btn-sm mb-2" onclick="showAvailablePCs()">
                                        <i class="fas fa-list me-1"></i>Boş PC'leri Göster
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm mb-2" onclick="showAssignedPCs()">
                                        <i class="fas fa-user-check me-1"></i>Atanmış PC'leri Göster
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="info-section">
                                <h6 class="info-title">
                                    <i class="fas fa-chart-pie me-2"></i>Atama İstatistikleri
                                </h6>
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <div class="stat-icon bg-primary">
                                            <i class="fas fa-desktop"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-number" id="modalTotalPcs">54</div>
                                            <div class="stat-label">Toplam PC</div>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon bg-success">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-number" id="modalAssignedPcs">0</div>
                                            <div class="stat-label">Atanmış</div>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon bg-warning">
                                            <i class="fas fa-user-times"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-number" id="modalAvailablePcs">54</div>
                                            <div class="stat-label">Boş</div>
                                        </div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-icon bg-info">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-number" id="modalUsagePercent">0%</div>
                                            <div class="stat-label">Kullanım Oranı</div>
                            </div>
                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-primary" onclick="refreshAssignments()">
                        <i class="fas fa-sync-alt me-1"></i>Yenile
                    </button>
                </div>
            </div>
    </div>
</div>

<!-- Toast Bildirim Sistemi -->
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

<!-- Assignment Form Modal -->
    <div class="modal fade" id="assignmentFormModal" tabindex="-1" aria-labelledby="assignmentFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignmentFormModalLabel">
                        <i class="fas fa-tasks me-2"></i>Bilgisayar Atama Sistemi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="assignment-form-container">
                        <div class="assignment-header">
                            <h4 class="assignment-title">
                                <i class="fas fa-tasks me-2"></i>Bilgisayar Atama Sistemi
                            </h4>
                            <p class="assignment-subtitle">Öğrencileri bilgisayarlara atayın - Detaylar için başlığa tıklayın</p>
                        </div>
                        
                        <div class="assignment-controls">
                            <div class="row g-4">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group">
                                        <label for="labSelectModal" class="form-label">
                                            <i class="fas fa-building me-1"></i>Laboratuvar Seçin
                                        </label>
                                        <select class="form-select form-select-lg" id="labSelectModal" onchange="changeLabModal()">
                                            <option value="">Laboratuvar seçin...</option>
                                            <option value="lab1" data-rows="14" data-cols="4" data-total="54">Lab 1 - 54 PC (14x4)</option>
                                            <option value="lab2" data-rows="12" data-cols="4" data-total="48">Lab 2 - 48 PC (12x4)</option>
                                            <option value="lab3" data-rows="14" data-cols="4" data-total="56">Lab 3 - 56 PC (14x4)</option>
                                            <option value="lab4" data-rows="13" data-cols="4" data-total="50">Lab 4 - 50 PC (13x4)</option>
                                            <option value="lab5" data-rows="11" data-cols="4" data-total="42">Lab 5 - 42 PC (11x4)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group">
                                        <label for="studentSelectModal" class="form-label">
                                            <i class="fas fa-user me-1"></i>Öğrenci Seçin
                                        </label>
                                        <select class="form-select form-select-lg" id="studentSelectModal">
                                            <option value="">Öğrenci seçin...</option>
                                            <!-- Öğrenci listesi buraya gelecek -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group">
                                        <label for="pcSelectModal" class="form-label">
                                            <i class="fas fa-desktop me-1"></i>Bilgisayar Seçin
                                        </label>
                                        <select class="form-select form-select-lg" id="pcSelectModal">
                                            <option value="">Bilgisayar seçin...</option>
                                            <!-- Bilgisayar listesi buraya gelecek -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button class="btn btn-primary btn-lg" id="assignBtnModal">
                                                <i class="fas fa-link me-2"></i>Atama Yap
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
