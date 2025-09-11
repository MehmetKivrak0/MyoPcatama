<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: iambatman.php');
    exit;
}

$username = $_SESSION['full_name'] ?? 'Kullanıcı';
$message = '';
$messageType = '';

// Laboratuvarları getir
require_once '../controllers/LabController.php';
$labController = new LabController();
$labsResult = $labController->getAllLabs();

$labs = [];
if ($labsResult['type'] === 'success') {
    $labs = $labsResult['data'];
} else {
    $message = $labsResult['message'];
    $messageType = 'error';
}

// AJAX istekleri için
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'delete':
            $labId = intval($_GET['id'] ?? 0);
            if ($labId > 0) {
                $result = $labController->deleteLab($labId);
                echo json_encode($result);
            } else {
                echo json_encode(['type' => 'error', 'message' => 'Geçersiz laboratuvar ID']);
            }
            exit;
            
        case 'get_details':
            $labId = intval($_GET['id'] ?? 0);
            if ($labId > 0) {
                $result = $labController->getLabDetails($labId);
                echo json_encode($result);
            } else {
                echo json_encode(['type' => 'error', 'message' => 'Geçersiz laboratuvar ID']);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratuvarlar - MyoPc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="css/lab_list.css" rel="stylesheet">
</head>
<body>
    <a href="dashboard.php" class="back-btn" title="Dashboard'a Dön">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <a href="add_lab.php" class="add-lab-btn" title="Yeni Laboratuvar Ekle">
        <i class="fas fa-plus"></i>
    </a>
    
    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="page-title">
                        <i class="fas fa-building me-3"></i>Laboratuvarlar
                    </h1>
                    <p class="page-subtitle">Mevcut laboratuvarları yönetin ve yeni laboratuvarlar oluşturun</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($labs)): ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h3>Henüz laboratuvar bulunmuyor</h3>
                <p>İlk laboratuvarınızı oluşturmak için sağ alt köşedeki + butonuna tıklayın.</p>
                <a href="add_lab.php" class="btn btn-primary btn-lg mt-3">
                    <i class="fas fa-plus me-2"></i>İlk Laboratuvarı Oluştur
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($labs as $lab): ?>
                    <div class="col-12 mb-4">
                        <div class="lab-card-horizontal">
                            <div class="row align-items-center">
                                <!-- Sol taraf: Laboratuvar bilgileri -->
                                <div class="col-md-4">
                                    <div class="lab-header-horizontal">
                                        <h3 class="lab-title-horizontal">
                                            <?php 
                                            // Kullanıcı tipini göster (örn: "Bil_Mekanik-PC50" -> "Mekanik")
                                            echo htmlspecialchars($lab['user_type'] ?? 'Bilinmeyen');
                                            ?>
                                        </h3>
                                        <p class="lab-description-horizontal">
                                            <?php 
                                            $labName = $lab['lab_name'] ?? 'Bilinmeyen';
                                            // PC kısmını bul (örn: "Bil_Mekanik-PC50" -> "PC50")
                                            if (preg_match('/PC\d+/', $labName, $matches)) {
                                                echo htmlspecialchars($matches[0]);
                                            } else {
                                                echo 'PC Numarası Yok';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Orta: İstatistikler -->
                                <div class="col-md-4">
                                    <div class="lab-stats-horizontal">
                                        <div class="stat-item-horizontal">
                                            <div class="stat-number-horizontal"><?php echo $lab['pc_count']; ?></div>
                                            <div class="stat-label-horizontal">Toplam PC</div>
                                        </div>
                                        <div class="stat-item-horizontal">
                                            <div class="stat-number-horizontal"><?php echo $lab['available_pc_count']; ?></div>
                                            <div class="stat-label-horizontal">Müsait PC</div>
                                        </div>
                                        <div class="stat-item-horizontal">
                                            <div class="stat-number-horizontal"><?php echo $lab['pc_count'] - $lab['available_pc_count']; ?></div>
                                            <div class="stat-label-horizontal">Atanmış PC</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Sağ taraf: Butonlar -->
                                <div class="col-md-4">
                                    <div class="lab-actions-horizontal">
                                        <div class="d-flex justify-content-end flex-wrap gap-2">
                                            <button class="btn btn-outline-primary btn-sm" 
                                                    onclick="viewLabDetails(<?php echo $lab['computer_id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>Detaylar
                                            </button>
                                            <button class="btn btn-outline-success btn-sm" 
                                                    onclick="managePCs(<?php echo $lab['computer_id']; ?>)">
                                                <i class="fas fa-desktop me-1"></i>Dashboard'a Git
                                            </button>
                                            <button class="btn btn-outline-warning btn-sm" 
                                                    onclick="editLab(<?php echo $lab['computer_id']; ?>)">
                                                <i class="fas fa-cogs me-1"></i>PC Yönet
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteLab(<?php echo $lab['computer_id']; ?>, '<?php echo htmlspecialchars($lab['user_type'] ?? 'Bilinmeyen'); ?>')">
                                                <i class="fas fa-trash me-1"></i>Sil
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Laboratuvar Detay Modal -->
    <div class="modal fade" id="labDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-building me-2"></i>Laboratuvar Detayları
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="labDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Laboratuvar detaylarını göster
        function viewLabDetails(labId) {
            const modal = new bootstrap.Modal(document.getElementById('labDetailsModal'));
            const content = document.getElementById('labDetailsContent');
            
            content.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Yükleniyor...</span></div></div>';
            
            modal.show();
            
            fetch('?action=get_details&id=' + labId)
                .then(response => response.json())
                .then(data => {
                    if (data.type === 'success') {
                        const lab = data.data.lab;
                        const pcCount = data.data.pc_count;
                        const availablePcCount = data.data.available_pc_count;
                        
                        const labName = lab.user_type || 'Bilinmeyen';
                        const createdDate = lab.created_at ? new Date(lab.created_at).toLocaleDateString('tr-TR') : 'Bilinmeyen';
                        const updatedDate = lab.updated_at ? new Date(lab.updated_at).toLocaleDateString('tr-TR') : 'Bilinmeyen';
                        
                        content.innerHTML = '<div class="row">' +
                            '<div class="col-md-6">' +
                                '<h6><i class="fas fa-info-circle me-2"></i>Laboratuvar Bilgileri</h6>' +
                                '<ul class="list-unstyled">' +
                                    '<li><strong>Laboratuvar Adı:</strong> ' + labName + '</li>' +
                                    '<li><strong>Oluşturulma Tarihi:</strong> ' + createdDate + '</li>' +
                                    '<li><strong>Güncellenme Tarihi:</strong> ' + updatedDate + '</li>' +
                                '</ul>' +
                            '</div>' +
                            '<div class="col-md-6">' +
                                '<h6><i class="fas fa-chart-bar me-2"></i>İstatistikler</h6>' +
                                '<div class="row text-center">' +
                                    '<div class="col-4">' +
                                        '<div class="stat-number text-primary">' + pcCount + '</div>' +
                                        '<div class="stat-label">Toplam PC</div>' +
                                    '</div>' +
                                    '<div class="col-4">' +
                                        '<div class="stat-number text-success">' + availablePcCount + '</div>' +
                                        '<div class="stat-label">Müsait PC</div>' +
                                    '</div>' +
                                    '<div class="col-4">' +
                                        '<div class="stat-number text-warning">' + (pcCount - availablePcCount) + '</div>' +
                                        '<div class="stat-label">Atanmış PC</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    } else {
                        content.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Bir hata oluştu: ' + error.message + '</div>';
                });
        }
        
        // PC'leri yönet
        function managePCs(labId) {
            window.location.href = 'dashboard.php?lab_id=' + labId;
        }
        
        // Laboratuvarı düzenle (PC yönetimi)
        function editLab(labId) {
            // Dashboard'a yönlendir ve PC yönetim modunu aç
            window.location.href = 'dashboard.php?lab_id=' + labId + '&pc_management=true';
        }
        
        // Laboratuvarı sil
        function deleteLab(labId, labName) {
            if (confirm('"' + labName + '" laboratuvarını ve tüm PC\'lerini silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
                fetch('?action=delete&id=' + labId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.type === 'success') {
                            showToast('success', data.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showToast('error', data.message);
                        }
                    })
                    .catch(error => {
                        showToast('error', 'Bir hata oluştu: ' + error.message);
                    });
            }
        }
        
        // Toast bildirimi göster
        function showToast(type, message) {
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            
            const toast = document.createElement('div');
            toast.className = 'toast show';
            const iconClass = type === 'success' ? 'check-circle text-success' : 'exclamation-triangle text-danger';
            toast.innerHTML = '<div class="toast-header">' +
                '<i class="fas fa-' + iconClass + ' me-2"></i>' +
                '<strong class="me-auto">Sistem Bildirimi</strong>' +
                '<button type="button" class="btn-close" data-bs-dismiss="toast"></button>' +
                '</div>' +
                '<div class="toast-body">' + message + '</div>';
            
            toastContainer.appendChild(toast);
            document.body.appendChild(toastContainer);
            
            setTimeout(() => {
                toastContainer.remove();
            }, 5000);
        }
    </script>
</body>
</html>