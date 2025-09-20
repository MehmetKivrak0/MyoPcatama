<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: iambatman.php');
    exit;
}

// Türkçe karakter yardımcısını dahil et
require_once '../utils/TurkishCharacterHelper.php';

// Gerekli modelleri import et
require_once '../config/db.php';
require_once '../models/Student.php';

$db = Database::getInstance();
$studentModel = new Student($db);

// Düzenleme modu kontrolü
$edit_mode = false;
$student_data = null;
$student_id = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $student_id = intval($_GET['edit']);
    $student_data = $studentModel->getStudentById($student_id);
    
    if (!$student_data) {
        $_SESSION['error_message'] = 'Öğrenci bulunamadı!';
        header('Location: student_management.php');
        exit;
    }
}

$username = $_SESSION['full_name'] ?? 'Kullanıcı';
$years = $studentModel->getAvailableYears();

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        // Form verilerini temizle
        $form_data = [
            'sdt_nmbr' => trim($_POST['sdt_nmbr'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'academic_year' => intval($_POST['academic_year'] ?? date('Y')),
            'department' => trim($_POST['department'] ?? ''),
            'class_level' => trim($_POST['class_level'] ?? '')
        ];
        
        // Validasyon
        $errors = [];
        
        if (empty($form_data['sdt_nmbr'])) {
            $errors[] = 'Öğrenci numarası gereklidir!';
        }
        
        if (empty($form_data['full_name'])) {
            $errors[] = 'Öğrenci adı gereklidir!';
        }
        
        if ($form_data['academic_year'] < 2000 || $form_data['academic_year'] > 2030) {
            $errors[] = 'Geçerli bir akademik yıl seçiniz!';
        }
        
        // Öğrenci numarası kontrolü
        if ($studentModel->studentNumberExists($form_data['sdt_nmbr'], $edit_mode ? $student_id : null)) {
            $errors[] = 'Bu öğrenci numarası zaten kullanılıyor!';
        }
        
        if (empty($errors)) {
            if ($edit_mode) {
                // Güncelleme
                $result = $studentModel->updateStudent($student_id, $form_data);
                if ($result['success']) {
                    $_SESSION['success_message'] = 'Öğrenci başarıyla güncellendi!';
                    header('Location: student_management.php');
                    exit;
                } else {
                    $errors[] = 'Güncelleme hatası: ' . $result['error'];
                }
            } else {
                // Ekleme
                $result = $studentModel->addStudent($form_data);
                if ($result['success']) {
                    $_SESSION['success_message'] = 'Öğrenci başarıyla eklendi!';
                    header('Location: student_management.php');
                    exit;
                } else {
                    $errors[] = 'Ekleme hatası: ' . $result['error'];
                }
            }
        }
        
    } catch (Exception $e) {
        $errors[] = 'Sunucu hatası: ' . $e->getMessage();
    }
}

// Hata ve başarı mesajlarını al
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Öğrenci Düzenle' : 'Öğrenci Ekle'; ?> - MyoPc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
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
        
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e8ecf0;
        }
        
        .form-title {
            color: #1e3a5f;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .required {
            color: #e53e3e;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1e3a8a 0%, #0ea5e9 100%); border-bottom: 1px solid #e5e7eb;">
        <div class="container-fluid">
            <a class="navbar-brand" href="student_management.php">
                <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" class="img-fluid" style="width: 35px; height: auto;">
                <div class="brand-text">
                    <div class="text-blue">MyoPC</div>
                    <div class="text-white"><?php echo $edit_mode ? 'Öğrenci Düzenle' : 'Öğrenci Ekle'; ?></div>
                </div>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="student_management.php">
                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Çıkış Yap
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    <div class="form-container">
                        <h2 class="form-title">
                            <i class="fas <?php echo $edit_mode ? 'fa-edit' : 'fa-user-plus'; ?> me-2"></i>
                            <?php echo $edit_mode ? 'Öğrenci Düzenle' : 'Yeni Öğrenci Ekle'; ?>
                        </h2>
                        
                        <!-- Hata Mesajları -->
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Başarı Mesajları -->
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Form Hataları -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Form -->
                        <form method="POST" id="studentForm">
                            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'add'; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sdt_nmbr" class="form-label">
                                        Öğrenci Numarası <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="sdt_nmbr" 
                                           name="sdt_nmbr" 
                                           value="<?php echo htmlspecialchars($student_data['sdt_nmbr'] ?? ''); ?>"
                                           placeholder="Örn: 2024001"
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="academic_year" class="form-label">
                                        Akademik Yıl <span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="academic_year" name="academic_year" required>
                                        <option value="">Yıl Seçiniz</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?php echo $year['year']; ?>" 
                                                    <?php echo (($student_data['academic_year'] ?? '') == $year['year']) ? 'selected' : ''; ?>>
                                                <?php echo $year['year']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="full_name" class="form-label">
                                    Öğrenci Adı Soyadı <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       value="<?php echo htmlspecialchars($student_data['full_name'] ?? ''); ?>"
                                       placeholder="Örn: Ahmet Yılmaz"
                                       required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">
                                        Bölüm
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="department" 
                                           name="department" 
                                           value="<?php echo htmlspecialchars($student_data['department'] ?? ''); ?>"
                                           placeholder="Örn: Bilgisayar Programcılığı">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="class_level" class="form-label">
                                        Sınıf Durumu
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="class_level" 
                                           name="class_level" 
                                           value="<?php echo htmlspecialchars($student_data['class_level'] ?? ''); ?>"
                                           placeholder="Örn: 1. Sınıf">
                                </div>
                            </div>
                            
                            <div class="d-flex gap-3 justify-content-end">
                                <a href="student_management.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>İptal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas <?php echo $edit_mode ? 'fa-save' : 'fa-plus'; ?> me-1"></i>
                                    <?php echo $edit_mode ? 'Güncelle' : 'Ekle'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validasyonu
        document.getElementById('studentForm').addEventListener('submit', function(e) {
            const sdt_nmbr = document.getElementById('sdt_nmbr').value.trim();
            const full_name = document.getElementById('full_name').value.trim();
            const academic_year = document.getElementById('academic_year').value;
            
            if (!sdt_nmbr || !full_name || !academic_year) {
                e.preventDefault();
                alert('Lütfen tüm alanları doldurunuz!');
                return false;
            }
            
            // Öğrenci numarası format kontrolü
            if (!/^\d+$/.test(sdt_nmbr)) {
                e.preventDefault();
                alert('Öğrenci numarası sadece rakam içermelidir!');
                return false;
            }
            
            // Ad soyad format kontrolü
            if (full_name.length < 2) {
                e.preventDefault();
                alert('Ad soyad en az 2 karakter olmalıdır!');
                return false;
            }
        });
        
        // Otomatik büyük harf dönüşümü
        document.getElementById('full_name').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
        
        // Sayfa yüklendiğinde focus
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('sdt_nmbr').focus();
        });
    </script>
</body>
</html>
