<?php
session_start();

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: iambatman.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_POST) {
    require_once '../controllers/AuthController.php';
    
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $auth = new AuthController();
    $result = $auth->register($full_name, $password);
    
    if ($result['type'] === 'success') {
        $success_message = $result['message'];
        // 2 saniye sonra iambatman.php'ye yönlendir
        header('refresh:2;url=iambatman.php');
    } else {
        $error_message = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - MyOPC</title>
    <link rel="stylesheet" href="../assets/css/iambatman.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Sol taraf - Branding -->
    <div class="branding-section">
        <div class="logo-container">
            <div class="photos-container">
                <div class="photo-item">
                    <img src="../assets/image/logo//myologo.png" alt="Photo 1" class="photo-image">
                </div>
                <div class="photo-item">
                    <img src="../assets/image/logo/xrlab.png" alt="Photo 2" class="photo-images">
                </div>
            </div>
            <div class="brand-name">Pc-Atama </div>
        </div>
        
        <h1 class="tagline">Building the Future...</h1>
        <p class="description">
            Bilgisayar laboratuvarı yönetim sisteminiz. Öğrencileri bilgisayarlara atayın, 
            laboratuvarları yönetin ve sistem durumunu takip edin.
        </p>
        
        <div class="pagination">
            <div class="pagination-dot"></div>
            <div class="pagination-dot active"></div>
            <div class="pagination-dot"></div>
        </div>
    </div>

    <!-- Sağ taraf - Registration Form -->
    <div class="login-section">
        <div class="login-card">
            <div class="form-header">
                <div class="form-subtitle">ADMIN PANEL</div>
                <h2 class="form-title">Hesap Oluştur</h2>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name" class="form-label">Kullanıcı Adı</label>
                    <div class="input-container">
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               placeholder="Kullanıcı adınızı girin" required
                               value="<?php echo htmlspecialchars($full_name ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Şifre</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Şifrenizi girin" required>
                    </div>
                </div>
                
                
                <button type="submit" class="btn-primary">HESAP OLUŞTUR</button>
                
                <div class="form-footer">
                    <p>Zaten hesabınız var mı? <a href="iambatman.php" class="link">Giriş yapın</a></p>
                </div>
            </form>
        </div>
    </div>

    <style>
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            font-size: 14px;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .form-footer p {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }
        
        .link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .link:hover {
            text-decoration: underline;
        }
        
        .input-container {
            position: relative;
        }
    </style>

</body>
</html>
