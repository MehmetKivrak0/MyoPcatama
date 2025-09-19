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
require_once '../controllers/LabController.php';

// Laboratuvar controller'ını başlat
$labController = new LabController();

// Tüm laboratuvarları getir
$labsResult = $labController->getAllLabs();
$labs = [];

if ($labsResult['type'] === 'success') {
    $labs = $labsResult['data'];
}

// AJAX istekleri için
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'delete_lab':
            $labId = $_POST['lab_id'] ?? null;
            if ($labId) {
                $result = $labController->deleteLab($labId);
                echo json_encode($result);
            } else {
                echo json_encode(['type' => 'error', 'message' => 'Laboratuvar ID gerekli']);
            }
            exit;
            
        case 'update_pc_count':
            $labId = $_POST['lab_id'] ?? null;
            $newCount = $_POST['pc_count'] ?? null;
            if ($labId && $newCount !== null) {
                $result = $labController->updateLabPCCount($labId, $newCount);
                echo json_encode($result);
            } else {
                echo json_encode(['type' => 'error', 'message' => 'Laboratuvar ID ve PC sayısı gerekli']);
            }
            exit;
            
        default:
            echo json_encode(['type' => 'error', 'message' => 'Geçersiz işlem']);
            exit;
    }
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
    <title>Laboratuvar Listesi - MyOPC Yönetim Sistemi</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Dashboard CSS -->
    <link href="css/dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Custom Lab List CSS -->
    <link href="css/lab_list.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <style>
        /* ========================================
           LAB LIST MOBILE RESPONSIVE IMPROVEMENTS
           ======================================== */
        
        /* Top Header Bar Mobile Improvements */
        @media (max-width: 768px) {
            .top-header-bar {
                padding: 1rem 0;
                min-height: auto;
            }
            
            .top-header-bar .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .top-header-bar .row {
                align-items: center;
                gap: 1rem;
            }
            
            /* Logo Section */
            .logo-section {
                margin-bottom: 0;
                justify-content: flex-start;
                order: 1;
            }
            
            .logo-section .header-logo {
                width: 40px !important;
                height: 40px !important;
            }
            
            .brand-name {
                font-size: 1.5rem !important;
                font-weight: 700;
                margin-bottom: 0.2rem;
            }
            
            .brand-subtitle {
                font-size: 0.9rem !important;
                opacity: 0.9;
                margin: 0;
            }
            
            /* Stats Section */
            .header-stats {
                flex-direction: row;
                gap: 0.6rem;
                margin-bottom: 0;
                justify-content: center;
                order: 2;
                flex: 1;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .header-stat-item {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
                min-width: 90px;
                justify-content: center;
                flex: 1;
                max-width: 110px;
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(15px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
            }
            
            .header-stat-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            }
            
            .header-stat-item i {
                font-size: 1.1rem;
                margin-bottom: 0.3rem;
                display: block;
                text-align: center;
            }
            
            .header-stat-item .stat-number {
                font-size: 1.3rem;
                font-weight: 700;
                display: block;
                margin-bottom: 0.2rem;
                text-align: center;
                color: #fff;
            }
            
            .header-stat-item .stat-label {
                font-size: 0.7rem;
                opacity: 0.9;
                text-align: center;
                font-weight: 500;
                line-height: 1.2;
            }
            
            /* Action Buttons */
            .header-actions {
                flex-direction: row;
                gap: 0.6rem;
                align-items: center;
                justify-content: flex-end;
                order: 3;
            }
            
            .header-btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.85rem;
                min-width: 110px;
                justify-content: center;
                border-radius: 10px;
                font-weight: 600;
                transition: all 0.3s ease;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            }
            
            .header-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            }
            
            .header-btn i {
                font-size: 0.9rem;
                margin-right: 0.4rem;
            }
            
            body {
                padding-top: 120px;
            }
        }
        
        @media (max-width: 576px) {
            .top-header-bar {
                padding: 0.8rem 0;
            }
            
            .top-header-bar .container-fluid {
                padding-left: 0.8rem;
                padding-right: 0.8rem;
            }
            
            .top-header-bar .row {
                gap: 0.8rem;
            }
            
            .logo-section {
                margin-bottom: 0;
            }
            
            .logo-section .header-logo {
                width: 35px !important;
                height: 35px !important;
            }
            
            .brand-name {
                font-size: 1.3rem !important;
            }
            
            .brand-subtitle {
                font-size: 0.8rem !important;
            }
            
            .header-stats {
                gap: 0.4rem;
                margin-bottom: 0;
                max-width: 350px;
            }
            
            .header-stat-item {
                padding: 0.5rem 0.6rem;
                font-size: 0.75rem;
                min-width: 75px;
                max-width: 95px;
                border-radius: 10px;
            }
            
            .header-stat-item i {
                font-size: 1rem;
                margin-bottom: 0.2rem;
            }
            
            .header-stat-item .stat-number {
                font-size: 1.2rem;
            }
            
            .header-stat-item .stat-label {
                font-size: 0.65rem;
            }
            
            .header-actions {
                gap: 0.5rem;
            }
            
            .header-btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
                min-width: 100px;
                border-radius: 8px;
            }
            
            .header-btn i {
                font-size: 0.85rem;
                margin-right: 0.3rem;
            }
            
            body {
                padding-top: 110px;
            }
        }
        
        @media (max-width: 400px) {
            .top-header-bar {
                padding: 0.6rem 0;
            }
            
            .top-header-bar .container-fluid {
                padding-left: 0.6rem;
                padding-right: 0.6rem;
            }
            
            .top-header-bar .row {
                gap: 0.6rem;
            }
            
            .logo-section {
                margin-bottom: 0;
            }
            
            .logo-section .header-logo {
                width: 32px !important;
                height: 32px !important;
            }
            
            .brand-name {
                font-size: 1.2rem !important;
            }
            
            .brand-subtitle {
                font-size: 0.75rem !important;
            }
            
            .header-stats {
                gap: 0.3rem;
                margin-bottom: 0;
                max-width: 300px;
            }
            
            .header-stat-item {
                padding: 0.4rem 0.5rem;
                font-size: 0.7rem;
                min-width: 65px;
                max-width: 80px;
                border-radius: 8px;
            }
            
            .header-stat-item i {
                font-size: 0.9rem;
                margin-bottom: 0.15rem;
            }
            
            .header-stat-item .stat-number {
                font-size: 1.1rem;
            }
            
            .header-stat-item .stat-label {
                font-size: 0.6rem;
            }
            
            .header-actions {
                gap: 0.4rem;
            }
            
            .header-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
                min-width: 90px;
                border-radius: 6px;
            }
            
            .header-btn i {
                font-size: 0.8rem;
                margin-right: 0.25rem;
            }
            
            body {
                padding-top: 100px;
            }
        }
        
        /* Main Content Mobile Adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0.5rem;
            }
            
            .container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .labs-section {
                margin-top: 1rem;
            }
            
            .glass-card {
                padding: 1rem;
                border-radius: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 0.8rem 0.3rem;
            }
            
            .container {
                padding-left: 0.3rem;
                padding-right: 0.3rem;
            }
            
            .labs-section {
                margin-top: 0.8rem;
            }
            
            .glass-card {
                padding: 0.8rem;
                border-radius: 12px;
            }
        }
        
        /* Table Mobile Improvements */
        @media (max-width: 768px) {
            .labs-table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .labs-table {
                min-width: 600px;
            }
            
            .labs-table th,
            .labs-table td {
                padding: 0.5rem 0.3rem;
                font-size: 0.8rem;
            }
            
            .labs-table th:first-child,
            .labs-table td:first-child {
                width: 40px;
            }
            
            .labs-table th:last-child,
            .labs-table td:last-child {
                width: 80px;
            }
        }
        
        @media (max-width: 576px) {
            .labs-table {
                min-width: 500px;
            }
            
            .labs-table th,
            .labs-table td {
                padding: 0.4rem 0.2rem;
                font-size: 0.75rem;
            }
            
            .lab-name {
                max-width: 120px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            
            .pc-count-control {
                display: flex;
                align-items: center;
                gap: 0.2rem;
            }
            
            .pc-count-input {
                width: 50px;
                font-size: 0.7rem;
                padding: 0.2rem;
            }
            
            .btn-icon {
                width: 24px;
                height: 24px;
                font-size: 0.7rem;
            }
        }
        
        /* Empty State Mobile */
        @media (max-width: 768px) {
            .empty-state {
                padding: 2rem 1rem;
                text-align: center;
            }
            
            .empty-icon i {
                font-size: 3rem;
            }
            
            .empty-title {
                font-size: 1.2rem;
                margin: 1rem 0 0.5rem 0;
            }
            
            .empty-subtitle {
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
            }
            
            .modern-btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .empty-state {
                padding: 1.5rem 0.8rem;
            }
            
            .empty-icon i {
                font-size: 2.5rem;
            }
            
            .empty-title {
                font-size: 1.1rem;
            }
            
            .empty-subtitle {
                font-size: 0.8rem;
            }
            
            .modern-btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.8rem;
            }
        }
        
        /* Alert Mobile Improvements */
        @media (max-width: 768px) {
            .modern-alert {
                margin: 0.5rem;
                border-radius: 8px;
            }
            
            .alert-content {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            
            .alert-content i {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .modern-alert {
                margin: 0.3rem;
                border-radius: 6px;
            }
            
            .alert-content {
                padding: 0.6rem;
                font-size: 0.8rem;
            }
            
            .alert-content i {
                font-size: 0.9rem;
            }
        }
        
        /* Loading Overlay Mobile */
        @media (max-width: 768px) {
            .loading-overlay {
                padding: 1rem;
            }
            
            .loading-spinner {
                font-size: 0.9rem;
            }
            
            .loading-spinner i {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .loading-overlay {
                padding: 0.8rem;
            }
            
            .loading-spinner {
                font-size: 0.8rem;
            }
            
            .loading-spinner i {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header Bar -->
    <div class="top-header-bar" style="background: linear-gradient(135deg, #1e3a8a 0%, #0ea5e9 100%); border-bottom: 1px solid #e5e7eb;">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Logo and Title -->
                <div class="col-12 col-md-3">
                    <div class="logo-section d-flex align-items-center">
                        <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" class="header-logo">
                        <div class="logo-text">
                            <div class="brand-name">MyOPC</div>
                            <div class="brand-subtitle d-none d-md-block">Laboratuvar Listesi</div>
                            <div class="brand-subtitle d-block d-md-none">Lab Listesi</div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Section -->
                <div class="col-12 col-md-6">
                    <div class="header-stats d-flex justify-content-center justify-content-md-between">
                        <div class="header-stat-item">
                            <i class="fas fa-building"></i>
                            <span class="stat-number"><?php echo count($labs); ?></span>
                            <span class="stat-label">Laboratuvar</span>
                        </div>
                        <div class="header-stat-item">
                            <i class="fas fa-desktop"></i>
                            <span class="stat-number"><?php echo array_sum(array_column($labs, 'pc_count')); ?></span>
                            <span class="stat-label">Toplam PC</span>
                        </div>
                        <div class="header-stat-item">
                            <i class="fas fa-tags"></i>
                            <span class="stat-number"><?php echo count(array_unique(array_column($labs, 'user_type'))); ?></span>
                            <span class="stat-label">Kullanıcı Tipi</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="col-12 col-md-3">
                    <div class="header-actions d-flex justify-content-center justify-content-md-end align-items-center">
                        <a href="dashboard.php" class="header-btn dashboard-btn">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="d-none d-sm-inline">Dashboard</span>
                            <span class="d-inline d-sm-none">Ana Sayfa</span>
                        </a>
                        <a href="../logout.php" class="header-btn logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="d-none d-sm-inline">Çıkış Yap</span>
                            <span class="d-inline d-sm-none">Çıkış</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Laboratuvar Listesi -->
            <div class="labs-section">
                <div class="glass-card">
                    
                    <?php if (!empty($labs)): ?>
                        <div class="labs-table-container">
                            <table class="labs-table">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll" class="modern-checkbox">
                                        </th>
                                        <th>ID</th>
                                        <th>Laboratuvar Adı</th>
                                        <th>PC Sayısı</th>
                                        <th>Kullanıcı Tipi</th>
                                        <th>Oluşturulma</th>
                                        <th>Güncellenme</th>
                                        <th>Oluşturan</th>
                                        <th width="200">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($labs as $lab): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="modern-checkbox lab-checkbox" value="<?php echo $lab['computer_id']; ?>">
                                            </td>
                                            <td>
                                                <div class="lab-id"><?php echo $lab['computer_id']; ?></div>
                                            </td>
                                            <td>
                                                <div class="lab-name"><?php echo htmlspecialchars($lab['lab_name']); ?></div>
                                            </td>
                                            <td>
                                                <div class="pc-count-control">
                                                    <input type="number" 
                                                           class="modern-input pc-count-input" 
                                                           value="<?php echo $lab['pc_count']; ?>" 
                                                           min="0" 
                                                           data-lab-id="<?php echo $lab['computer_id']; ?>">
                                                    <button class="btn-icon update-pc-btn" 
                                                            data-lab-id="<?php echo $lab['computer_id']; ?>"
                                                            title="PC sayısını güncelle">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $userTypeClass = 'user-type-default';
                                                switch(strtolower($lab['user_type'])) {
                                                    case 'prog':
                                                    case 'programming':
                                                        $userTypeClass = 'user-type-prog';
                                                        break;
                                                    case 'mekanik':
                                                    case 'mechanical':
                                                        $userTypeClass = 'user-type-mekanik';
                                                        break;
                                                    case 'admin':
                                                        $userTypeClass = 'user-type-admin';
                                                        break;
                                                }
                                                ?>
                                                <span class="user-type-badge <?php echo $userTypeClass; ?>">
                                                    <?php echo htmlspecialchars($lab['user_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <?php echo date('d.m.Y H:i', strtotime($lab['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <?php echo $lab['updated_at'] ? date('d.m.Y H:i', strtotime($lab['updated_at'])) : '-'; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="creator-info">
                                                    <?php echo htmlspecialchars($lab['created_by']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon delete-btn delete-lab-btn" 
                                                            data-lab-id="<?php echo $lab['computer_id']; ?>"
                                                            data-lab-name="<?php echo htmlspecialchars($lab['lab_name']); ?>"
                                                            title="Sil">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div class="empty-title">Henüz laboratuvar eklenmemiş</div>
                            <div class="empty-subtitle">Laboratuvar eklemek için dashboard sayfasını kullanın.</div>
                            <a href="dashboard.php" class="btn modern-btn primary-btn">
                                <i class="fas fa-plus"></i>
                                <span>Laboratuvar Ekle</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile responsive functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Lab list page loaded');
            
            // Mobile table scroll indicator
            const tableContainer = document.querySelector('.labs-table-container');
            if (tableContainer) {
                // Add scroll indicator for mobile
                if (window.innerWidth <= 768) {
                    tableContainer.style.position = 'relative';
                    
                    // Add scroll hint
                    const scrollHint = document.createElement('div');
                    scrollHint.className = 'scroll-hint';
                    scrollHint.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Kaydırarak tüm sütunları görebilirsiniz';
                    scrollHint.style.cssText = `
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        background: rgba(30, 58, 95, 0.9);
                        color: white;
                        padding: 0.3rem 0.6rem;
                        border-radius: 15px;
                        font-size: 0.7rem;
                        z-index: 10;
                        animation: fadeInOut 3s ease-in-out;
                    `;
                    
                    tableContainer.appendChild(scrollHint);
                    
                    // Remove hint after 3 seconds
                    setTimeout(() => {
                        if (scrollHint.parentNode) {
                            scrollHint.parentNode.removeChild(scrollHint);
                        }
                    }, 3000);
                }
            }
            
            // Touch-friendly button improvements
            const actionButtons = document.querySelectorAll('.btn-icon, .header-btn');
            actionButtons.forEach(button => {
                button.style.minHeight = '44px';
                button.style.minWidth = '44px';
                button.style.touchAction = 'manipulation';
            });
        });
        
        // Add CSS for scroll hint animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInOut {
                0% { opacity: 0; transform: translateY(-10px); }
                20% { opacity: 1; transform: translateY(0); }
                80% { opacity: 1; transform: translateY(0); }
                100% { opacity: 0; transform: translateY(-10px); }
            }
            
            .scroll-hint {
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            }
        `;
        document.head.appendChild(style);
        
        document.addEventListener('DOMContentLoaded', function() {
            // PC sayısı güncelleme
            document.querySelectorAll('.update-pc-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const labId = this.dataset.labId;
                    const input = document.querySelector(`input[data-lab-id="${labId}"]`);
                    const newCount = parseInt(input.value);
                    
                    if (isNaN(newCount) || newCount < 0) {
                        showAlert('error', 'Lütfen geçerli bir PC sayısı girin.');
                        return;
                    }
                    
                    if (confirm(`PC sayısını ${newCount} olarak güncellemek istediğinizden emin misiniz?`)) {
                        updatePCCount(labId, newCount);
                    }
                });
            });
            
            // Laboratuvar silme
            document.querySelectorAll('.delete-lab-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const labId = this.dataset.labId;
                    const labName = this.dataset.labName;
                    
                    if (confirm(`"${labName}" laboratuvarını silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!`)) {
                        deleteLab(labId);
                    }
                });
            });
            
            // Tümünü seç
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.lab-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }
        });
        
        function updatePCCount(labId, newCount) {
            showLoading();
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_pc_count&lab_id=${labId}&pc_count=${newCount}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.type === 'success') {
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('error', 'Bir hata oluştu.');
            });
        }
        
        function deleteLab(labId) {
            showLoading();
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_lab&lab_id=${labId}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.type === 'success') {
                    showAlert('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showAlert('error', 'Bir hata oluştu.');
            });
        }
        
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'modern-alert success' : 'modern-alert error';
            const alertHtml = `
                <div class="${alertClass}">
                    <div class="alert-content">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <span>${message}</span>
                        <button type="button" class="alert-close" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', alertHtml);
            
            // 4 saniye sonra otomatik kapat
            setTimeout(() => {
                const alert = document.querySelector('.modern-alert');
                if (alert) {
                    alert.remove();
                }
            }, 4000);
        }
        
        function showLoading() {
            const loadingHtml = `
                <div class="loading-overlay">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>İşleniyor...</span>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
        }
        
        function hideLoading() {
            const loading = document.querySelector('.loading-overlay');
            if (loading) {
                loading.remove();
            }
        }
    </script>
</body>
</html>
