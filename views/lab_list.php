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
</head>
<body>
    <!-- Top Header Bar -->
    <div class="top-header-bar" style="background: linear-gradient(135deg, #1e3a8a 0%, #0ea5e9 100%); border-bottom: 1px solid #e5e7eb;">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Logo and Title -->
                <div class="col-md-3">
                    <div class="logo-section d-flex align-items-center">
                        <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" class="header-logo">
                        <div class="logo-text">
                            <div class="brand-name">MyOPC</div>
                            <div class="brand-subtitle">Laboratuvar Listesi</div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Section -->
                <div class="col-md-6">
                    <div class="header-stats d-flex justify-content-between">
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
                <div class="col-md-3">
                    <div class="header-actions d-flex justify-content-end align-items-center">
                        <a href="dashboard.php" class="header-btn dashboard-btn">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                        <a href="../logout.php" class="header-btn logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            Çıkış Yap
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
