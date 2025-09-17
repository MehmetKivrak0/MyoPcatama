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

// Form işleme
if ($_POST) {
    require_once '../controllers/LabController.php';
    
    $labName = trim($_POST['lab_name'] ?? '');
    $pcCount = intval($_POST['pc_count'] ?? 0);
    $userType = trim($_POST['user_type'] ?? '');
    
    if (empty($labName)) {
        $message = 'Laboratuvar adı boş olamaz.';
        $messageType = 'error';
    } elseif ($pcCount < 1 || $pcCount > 100) {
        $message = 'PC sayısı 1-100 arasında olmalıdır.';
        $messageType = 'error';
    } elseif (empty($userType)) {
        $message = 'Kullanıcı tipi seçilmelidir.';
        $messageType = 'error';
    } else {
        $labController = new LabController();
        $result = $labController->createLabWithPcs($labName, $pcCount, $userType);
        
        $message = $result['message'];
        $messageType = $result['type'];
        
        if ($result['type'] === 'success') {
            // Başarılı olursa formu temizle
            $_POST = [];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratuvar Ekle - MyoPc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Mevcut CSS Files -->
    <link href="../assets/css/navbar.css" rel="stylesheet">
    <style>
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
        
        /* Dashboard tarzı stats card */
        .stats-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #4a90a4 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(30, 58, 95, 0.3);
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e5e9;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            margin-right: 0.5rem;
            color: #667eea;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .pc-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .pc-preview h6 {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .pc-preview .pc-item {
            background: white;
            border-radius: 5px;
            padding: 0.5rem;
            margin: 0.25rem;
            display: inline-block;
            font-size: 0.9rem;
            color: #666;
        }
        
        .back-btn {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1e3a8a 0%, #0ea5e9 100%); border-bottom: 1px solid #e5e7eb;">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" style="width: 35px; height: auto; margin-right: 10px;">
                <div class="brand-text">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #fff;">MyoPC</div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Laboratuvar Ekle</div>
                </div>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard'a Dön
                </a>
                <a class="nav-link" href="lab_list.php">
                    <i class="fas fa-building me-1"></i>Laboratuvarlar
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Çıkış Yap
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container-fluid px-4">
            <!-- İstatistikler -->
            <div class="stats-card">
                <div class="row">
                    <div class="col-md-12">
                        <div class="text-center">
                            <h2><i class="fas fa-building me-2"></i>Yeni Laboratuvar Ekle</h2>
                            <p class="mb-0">Laboratuvar adı ve PC sayısını belirleyerek yeni bir laboratuvar oluşturun</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                        
                        <div class="form-card">
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>">
                                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" id="labForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="lab_name" class="form-label">
                                                <i class="fas fa-building"></i>Laboratuvar Adı
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="lab_name" 
                                                   name="lab_name" 
                                                   value="<?php echo htmlspecialchars($_POST['lab_name'] ?? ''); ?>"
                                                   placeholder="Örn: Bilgisayar Laboratuvarı 1" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="pc_count" class="form-label">
                                                <i class="fas fa-desktop"></i>PC Sayısı
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="pc_count" 
                                                   name="pc_count" 
                                                   value="<?php echo htmlspecialchars($_POST['pc_count'] ?? ''); ?>"
                                                   min="1" 
                                                   max="100" 
                                                   placeholder="Örn: 50" 
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="user_type" class="form-label">
                                                <i class="fas fa-user-tag"></i>Kullanıcı Tipi
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="user_type" 
                                                   name="user_type" 
                                                   value="<?php echo htmlspecialchars($_POST['user_type'] ?? ''); ?>"
                                                   placeholder="Örn: admin, öğretmen, öğrenci" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-info-circle"></i>Format Önizleme
                                            </label>
                                            <div class="form-control" style="background-color: #f8f9fa; font-weight: bold; color: #667eea;" id="formatPreview">
                                                Kullanıcı tipi ve PC sayısını girin
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <!-- PC Önizleme -->
                                <div class="pc-preview" id="pcPreview" style="display: none;">
                                    <h6><i class="fas fa-eye me-2"></i>PC Önizleme</h6>
                                    <div id="pcList"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Geri Dön
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Laboratuvar Oluştur
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // PC numarası kontrolü
        function checkPcNumber(pcNumber) {
            return fetch('check_pc_number.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({pc_number: pcNumber})
            })
            .then(response => response.json())
            .then(data => data.exists);
        }
        
        // Format önizleme güncelle
        async function updateFormatPreview() {
            const userType = document.getElementById('user_type').value.trim();
            const pcCount = parseInt(document.getElementById('pc_count').value);
            const formatPreview = document.getElementById('formatPreview');
            
            if (userType && pcCount > 0) {
                // Kullanıcı tipini temizle ve formatla (boşlukları kaldır, küçük harfe çevirme)
                const cleanType = userType.replace(/\s+/g, '').replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ]/g, '');
                const prefix = `Bil_${cleanType}`;
                const fullFormat = `${prefix}-PC${pcCount}`;
                
                // PC numarası kontrolü yap
                try {
                    const exists = await checkPcNumber(pcCount);
                    if (exists) {
                        formatPreview.innerHTML = `${fullFormat} <span class="text-danger">⚠️ Bu PC numarası zaten kullanılıyor!</span>`;
                        formatPreview.className = 'text-danger';
                    } else {
                        formatPreview.textContent = fullFormat;
                        formatPreview.className = 'text-success';
                    }
                } catch (error) {
                    formatPreview.textContent = fullFormat;
                    formatPreview.className = 'text-muted';
                }
            } else {
                formatPreview.textContent = 'Kullanıcı tipi ve PC sayısını girin';
                formatPreview.className = 'text-muted';
            }
        }
        
        // PC sayısı değiştiğinde önizleme göster
        document.getElementById('pc_count').addEventListener('input', function() {
            const pcCount = parseInt(this.value);
            const userType = document.getElementById('user_type').value.trim();
            const preview = document.getElementById('pcPreview');
            const pcList = document.getElementById('pcList');
            
            updateFormatPreview();
            
            if (pcCount > 0 && pcCount <= 100 && userType) {
                preview.style.display = 'block';
                pcList.innerHTML = '';
                
                // Kullanıcı tipini temizle ve formatla (boşlukları kaldır, küçük harfe çevirme)
                const cleanType = userType.replace(/\s+/g, '').replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ]/g, '');
                const prefix = `Bil_${cleanType}`;
                
                // İlk 10 PC'yi göster
                const showCount = Math.min(pcCount, 10);
                for (let i = 1; i <= showCount; i++) {
                    const pcItem = document.createElement('span');
                    pcItem.className = 'pc-item';
                    pcItem.textContent = `${prefix}-PC${i}`;
                    pcList.appendChild(pcItem);
                }
                
                if (pcCount > 10) {
                    const moreItem = document.createElement('span');
                    moreItem.className = 'pc-item';
                    moreItem.textContent = `... ve ${pcCount - 10} PC daha`;
                    pcList.appendChild(moreItem);
                }
            } else {
                preview.style.display = 'none';
            }
        });
        
        
        // Laboratuvar adı değiştiğinde önizleme güncelle
        document.getElementById('lab_name').addEventListener('input', function() {
            // Boşlukları kaldır
            this.value = this.value.replace(/\s+/g, '');
            
            const pcCount = parseInt(document.getElementById('pc_count').value);
            if (pcCount > 0) {
                document.getElementById('pc_count').dispatchEvent(new Event('input'));
            }
        });
        
        // Kullanıcı tipi input'unda boşlukları kaldır
        document.getElementById('user_type').addEventListener('input', function() {
            // Boşlukları kaldır
            this.value = this.value.replace(/\s+/g, '');
            
            updateFormatPreview();
            const pcCount = parseInt(document.getElementById('pc_count').value);
            if (pcCount > 0) {
                document.getElementById('pc_count').dispatchEvent(new Event('input'));
            }
        });
        
        // Form validasyonu
        document.getElementById('labForm').addEventListener('submit', function(e) {
            const labName = document.getElementById('lab_name').value.trim();
            const pcCount = parseInt(document.getElementById('pc_count').value);
            const userType = document.getElementById('user_type').value;
            
            if (!labName) {
                alert('Laboratuvar adı boş olamaz.');
                e.preventDefault();
                return;
            }
            
            if (pcCount < 1 || pcCount > 100) {
                alert('PC sayısı 1-100 arasında olmalıdır.');
                e.preventDefault();
                return;
            }
            
            if (!userType) {
                alert('Kullanıcı tipi seçilmelidir.');
                e.preventDefault();
                return;
            }
            
            // Format oluştur (boşlukları kaldır, küçük harfe çevirme)
            const cleanType = userType.replace(/\s+/g, '').replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ]/g, '');
            const prefix = `Bil_${cleanType}`;
            const format = `${prefix}-PC${pcCount}`;
            
            // Onay mesajı
            if (!confirm(`${labName} laboratuvarını oluşturmak istediğinizden emin misiniz?\n\nFormat: ${format}\nKullanıcı Tipi: ${userType}\nPC Sayısı: ${pcCount}`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
