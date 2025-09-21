<?php
session_start();

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

// Mevcut kullanıcıları getir
require_once '../config/db.php';
$users = [];
try {
    $db = Database::getInstance();
    $users = $db->fetchAll("SELECT user_id, full_name FROM myopc_users ORDER BY full_name ASC");
} catch (Exception $e) {
    error_log("Kullanıcı listesi alınamadı: " . $e->getMessage());
}

if ($_POST) {
    require_once '../controllers/AuthController.php';
    
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    
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
    <link rel="stylesheet" href="../assets/css/photo-fix.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../assets/image/logo/myologo.png" type="image/x-icon" />

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
                    <label for="full_name" class="form-label">Kullanıcı Seçin</label>
                    <div class="input-container">
                        <select id="full_name" name="full_name" class="form-control" required>
                            <option value="">Kullanıcı seçin...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                        <?php echo (isset($full_name) && $full_name === $user['full_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
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

    <style>
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
        
        select.form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background-color: white;
            cursor: pointer;
        }
        
        select.form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
    </style>

</body>
</html>
