<?php
session_start();

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

if ($_POST) {
    require_once '../controllers/AuthController.php';
    
    $password = $_POST['password'] ?? '';
    
    // Sabit kullanıcı adı ile giriş
    $full_name = 'Xrlab-Yönetici';
    
    $auth = new AuthController();
    $result = $auth->login($full_name, $password);
    
    if ($result['type'] === 'success') {
        header('Location: dashboard.php');
        exit;
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
    <title>Giriş - MyOPC</title>
    <link rel="stylesheet" href="../assets/css/iambatman.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Sol taraf - Branding -->
    <div class="branding-section">
        <div class="logo-container">
            <div class="logo">
                <div class="logo-bars">
                    <div class="logo-bar"></div>
                    <div class="logo-bar"></div>
                    <div class="logo-bar"></div>
                </div>
            </div>
            <div class="brand-name">MyOPC</div>
        </div>
        
        <h1 class="tagline">Building the Future...</h1>
        <p class="description">
            Bilgisayar laboratuvarı yönetim sisteminiz. Öğrencileri bilgisayarlara atayın, 
            laboratuvarları yönetin ve sistem durumunu takip edin.
        </p>
        
        <div class="pagination">
            <div class="pagination-dot active"></div>
            <div class="pagination-dot"></div>
            <div class="pagination-dot"></div>
        </div>
    </div>

    <!-- Sağ taraf - Login Form -->
    <div class="login-section">
        <div class="login-card">
            <div class="form-header">
                <div class="form-subtitle">ADMIN PANEL</div>
                <h2 class="form-title">Şifre ile Giriş</h2>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password" class="form-label">Şifre</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Şifrenizi girin" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">GİRİŞ YAP</button>
                
                
            </form>
        </div>
    </div>

</body>
</html>
