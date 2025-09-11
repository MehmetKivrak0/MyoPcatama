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
                    <a href="lab_list.php" class="btn btn-outline-success me-2">
                        <i class="fas fa-building me-1"></i>Laboratuvarlar
                    </a>
                    <a href="add_lab.php" class="btn btn-outline-info">
                        <i class="fas fa-plus me-1"></i>Yeni Laboratuvar
                    </a>
                </div>
                <div class="right-actions">
                    <button class="btn btn-outline-info" onclick="exportAssignments()">
                        <i class="fas fa-download me-1"></i>Atamaları Dışa Aktar
                    </button>
                </div>
            </div>
        </div>

            
        <!-- Ultra Modern Dashboard Header -->
        <div class="dashboard-header">
            <div class="container-fluid px-4">
                <!-- Üst Bölüm: Laboratuvar Seçimi -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="lab-selection-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="lab-info-section">
                                    <div class="lab-icon-wrapper">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="lab-details">
                                        <h2 class="lab-title" id="selectedLabName" onclick="openAssignmentModal()" style="cursor: pointer;">Laboratuvar Seçin</h2>
                                    </div>
                                </div>
                                <div class="lab-selector-wrapper">
                                    <div class="d-flex gap-2">
                                        <select class="form-select lab-selector" id="labSelect" onchange="changeLab()">
                                            <option value="">Laboratuvar seçin...</option>
                                            <?php
                                            // Veritabanından laboratuvarları çek
                                            require_once '../controllers/LabController.php';
                                            
                                            try {
                                                $labController = new LabController();
                                                $labsResult = $labController->getAllLabs();
                                                
                                                if ($labsResult['type'] === 'success') {
                                                    foreach ($labsResult['data'] as $lab) {
                                                        $rows = ceil($lab['pc_count'] / 4);
                                                        $labName = $lab['lab_name'] ?? 'Bilinmeyen';
                                                        $userType = $lab['user_type'] ?? 'Bilinmeyen';
                                                        $pcNumber = preg_match('/PC\d+/', $labName, $matches) ? $matches[0] : 'PC?';
                                                        echo "<option value=\"{$lab['computer_id']}\" data-rows=\"{$rows}\" data-cols=\"4\" data-total=\"{$lab['pc_count']}\" data-fullname=\"" . htmlspecialchars($labName) . "\">";
                                                        echo htmlspecialchars($userType) . " - {$lab['pc_count']} PC ({$rows}x4)";
                                                        echo "</option>";
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                echo '<option value="lab1" data-rows="14" data-cols="4" data-total="54" data-fullname="Bil_Mekanik-PC50">PC50 - 54 PC (14x4)</option>';
                                                echo '<option value="lab2" data-rows="12" data-cols="4" data-total="48" data-fullname="Bil_ögr-PC48">PC48 - 48 PC (12x4)</option>';
                                                echo '<option value="lab3" data-rows="14" data-cols="4" data-total="56" data-fullname="Bil_admin-PC56">PC56 - 56 PC (14x4)</option>';
                                            }
                                            ?>
                                        </select>
                                        <!-- PC Düzenleme Butonu -->
                                        <button class="btn btn-outline-primary btn-sm" id="pcEditButton" onclick="editPCCount()" style="display: none;" title="PC Sayısını Düzenle">
                                            <i class="fas fa-edit me-1"></i>PC Düzenle
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alt Bölüm: İstatistikler ve Filtreler -->
                <div class="row">
                    <!-- İstatistik Kartları -->
                    <div class="col-lg-8 col-md-12 mb-4">
                        <div class="stats-container">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="stat-card total-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-desktop"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3 class="stat-number" id="totalPcs">0</h3>
                                            <p class="stat-label">Toplam PC</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card assigned-card clickable-stat" onclick="filterByStatus('assigned')" data-status="assigned">
                                        <div class="stat-icon">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3 class="stat-number" id="assignedPcs">0</h3>
                                            <p class="stat-label">Atanmış</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card available-card clickable-stat" onclick="filterByStatus('available')" data-status="available">
                                        <div class="stat-icon">
                                            <i class="fas fa-user-times"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3 class="stat-number" id="availablePcs">0</h3>
                                            <p class="stat-label">Boş</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtreler -->
                    <div class="col-lg-4 col-md-12 mb-4">
                        <div class="filters-card">
                            
                        
                            <div class="year-filters">
                                <h6 class="filter-section-title">Yıl Seçimi</h6>
                                <div class="year-buttons">
                                    <button class="year-btn active" onclick="filterByYear('')" data-year="">
                                        <i class="fas fa-globe"></i>
                                        <span>Tümü</span>
                                    </button>
                                    <?php
                                    try {
                                        $db = Database::getInstance();
                                        $studentModel = new Student($db);
                                        $years = $studentModel->getAvailableYears();
                                        
                                        foreach ($years as $yearData) {
                                            $year = $yearData['year'];
                                            echo "<button class=\"year-btn\" onclick=\"filterByYear('{$year}')\" data-year=\"{$year}\">";
                                            echo "<i class=\"fas fa-calendar\"></i>";
                                            echo "<span>{$year}</span>";
                                            echo "</button>";
                                        }
                                    } catch (Exception $e) {
                                        $defaultYears = [2024, 2023, 2022, 2021];
                                        foreach ($defaultYears as $year) {
                                            echo "<button class=\"year-btn\" onclick=\"filterByYear('{$year}')\" data-year=\"{$year}\">";
                                            echo "<i class=\"fas fa-calendar\"></i>";
                                            echo "<span>{$year}</span>";
                                            echo "</button>";
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                            <?php
                                            // Modal için de aynı laboratuvar listesini kullan
                                            if (isset($labController)) {
                                                $labsResult = $labController->getAllLabs();
                                                if ($labsResult['type'] === 'success') {
                                                    foreach ($labsResult['data'] as $lab) {
                                                        $rows = ceil($lab['pc_count'] / 4);
                                                        $userType = $lab['user_type'] ?? 'Bilinmeyen';
                                                        echo "<option value=\"{$lab['computer_id']}\" data-rows=\"{$rows}\" data-cols=\"4\" data-total=\"{$lab['pc_count']}\">";
                                                        echo htmlspecialchars($userType) . " - {$lab['pc_count']} PC ({$rows}x4)";
                                                        echo "</option>";
                                                    }
                                                }
                                            }
                                            ?>
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


    <!-- PC Sayısını Düzenleme Modal -->
    <div class="modal fade" id="editPCCountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>PC Sayısını Düzenle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newPCCount" class="form-label">Yeni PC Sayısı</label>
                        <input type="number" class="form-control" id="newPCCount" min="0" max="1000" value="0">
                        <div class="form-text">Mevcut PC sayısı: <span id="currentPCCountEdit">0</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-info" onclick="confirmEditPCCount()">
                        <i class="fas fa-save me-1"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
