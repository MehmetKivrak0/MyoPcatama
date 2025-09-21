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
    
    $pcCount = intval($_POST['pc_count'] ?? 0);
    $userType = trim($_POST['user_type'] ?? '');
    
    if ($pcCount < 1 || $pcCount > 100) {
        $message = 'PC sayısı 1-100 arasında olmalıdır.';
        $messageType = 'error';
    } elseif (empty($userType)) {
        $message = 'Kullanıcı tipi seçilmelidir.';
        $messageType = 'error';
    } else {
        $labController = new LabController();
        // Laboratuvar adını otomatik oluştur
        $cleanType = preg_replace('/[^a-zA-ZğüşıöçĞÜŞİÖÇ]/', '', $userType);
        $labName = 'Lab_' . $cleanType;
        
        $result = $labController->createLabWithPcs($labName, $pcCount, $userType);
        
        $message = $result['message'];
        $messageType = $result['type'];
        
        if ($result['type'] === 'success') {
            // Başarılı olursa dashboard'a yönlendir
            header('Location: dashboard.php');
            exit;
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
    <link rel="icon" href="../assets/image/logo/myologo.png" type="image/x-icon" />

    
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
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
        }
        
        .btn-secondary:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        /* Form Actions Styling */
        .form-actions {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .form-actions .btn {
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0.5px;
        }
        
        .form-actions .btn i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }
        
        .form-actions .btn span {
            white-space: nowrap;
        }
        
        /* Button Loading State */
        .btn.loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn.loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid #ffffff;
            border-radius: 50%;
            animation: button-spin 1s linear infinite;
        }
        
        @keyframes button-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Button Ripple Effect */
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:active::before {
            width: 300px;
            height: 300px;
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
        
        /* ========================================
           ADD LAB MOBILE RESPONSIVE IMPROVEMENTS
           ======================================== */
        
        /* Navbar Mobile Improvements */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem 0;
            }
            
            .navbar .container-fluid {
                padding-left: 0.8rem;
                padding-right: 0.8rem;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .navbar-brand img {
                width: 30px !important;
                height: 30px !important;
            }
            
            .navbar-toggler {
                border: none;
                padding: 0.25rem 0.5rem;
                font-size: 1.1rem;
            }
            
            .navbar-toggler:focus {
                box-shadow: none;
            }
            
            .navbar-collapse {
                background: rgba(30, 58, 138, 0.95);
                border-radius: 10px;
                margin-top: 0.5rem;
                padding: 1rem;
                backdrop-filter: blur(10px);
            }
            
            .navbar-nav .nav-link {
                padding: 0.6rem 1rem;
                margin: 0.2rem 0;
                border-radius: 8px;
                transition: all 0.3s ease;
            }
            
            .navbar-nav .nav-link:hover {
                background: rgba(255, 255, 255, 0.1);
                transform: translateX(5px);
            }
            
            .navbar-nav .nav-link i {
                width: 20px;
                text-align: center;
            }
            
            body {
                padding-top: 70px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar {
                padding: 0.4rem 0;
            }
            
            .navbar .container-fluid {
                padding-left: 0.6rem;
                padding-right: 0.6rem;
            }
            
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-brand img {
                width: 28px !important;
                height: 28px !important;
            }
            
            .navbar-toggler {
                font-size: 1rem;
            }
            
            .navbar-collapse {
                margin-top: 0.4rem;
                padding: 0.8rem;
            }
            
            .navbar-nav .nav-link {
                padding: 0.5rem 0.8rem;
                font-size: 0.9rem;
            }
            
            body {
                padding-top: 65px;
            }
        }
        
        /* Main Content Mobile Adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-top: 1rem;
                padding: 0 0.5rem;
            }
            
            .stats-card {
                padding: 1.2rem;
                margin-bottom: 1.5rem;
            }
            
            .stats-card h2 {
                font-size: 1.4rem;
            }
            
            .stats-card p {
                font-size: 0.9rem;
            }
            
            .form-card {
                padding: 1.5rem;
            }
            
            .form-group {
                margin-bottom: 1.2rem;
            }
            
            .form-label {
                font-size: 0.95rem;
            }
            
            .form-control, .form-select {
                padding: 0.6rem 0.8rem;
                font-size: 0.95rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.6rem 1.5rem;
                font-size: 1rem;
            }
            
            .form-actions {
                margin-top: 1.5rem;
                padding-top: 1.2rem;
            }
            
            .form-actions .btn {
                min-height: 45px;
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                margin-top: 0.8rem;
                padding: 0 0.3rem;
            }
            
            .stats-card {
                padding: 1rem;
                margin-bottom: 1.2rem;
            }
            
            .stats-card h2 {
                font-size: 1.2rem;
            }
            
            .stats-card p {
                font-size: 0.85rem;
            }
            
            .form-card {
                padding: 1.2rem;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-control, .form-select {
                padding: 0.5rem 0.7rem;
                font-size: 0.9rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.5rem 1.2rem;
                font-size: 0.9rem;
            }
            
            .form-actions {
                margin-top: 1.2rem;
                padding-top: 1rem;
            }
            
            .form-actions .btn {
                min-height: 44px;
                font-size: 0.9rem;
                padding: 0.6rem 1rem;
            }
            
            .form-actions .btn i {
                font-size: 1rem;
                margin-right: 0.4rem;
            }
            
            .pc-preview {
                padding: 0.8rem;
            }
            
            .pc-preview h6 {
                font-size: 0.9rem;
            }
            
            .pc-preview .pc-item {
                font-size: 0.8rem;
                padding: 0.4rem;
                margin: 0.2rem;
            }
        }
        
        /* Alert Mobile Improvements */
        @media (max-width: 768px) {
            .alert {
                padding: 0.8rem 1.2rem;
                margin-bottom: 1.2rem;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .alert {
                padding: 0.6rem 1rem;
                margin-bottom: 1rem;
                font-size: 0.85rem;
            }
        }

        /* ========================================
           CUSTOM CONFIRMATION MODAL STYLES
           ======================================== */
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .modal-container {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            animation: slideIn 0.4s ease-out;
            border: 2px solid #34495e;
            position: relative;
        }
        
        .modal-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
            animation: shimmer 2s infinite;
        }
        
        .modal-header {
            padding: 2rem 2rem 1rem 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-title {
            font-family: 'Exo 2', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: #ffffff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .modal-body {
            padding: 2rem;
            text-align: center;
        }
        
        .modal-question {
            font-size: 1.2rem;
            color: #ecf0f1;
            margin-bottom: 2rem;
            font-weight: 500;
            line-height: 1.5;
        }
        
        .modal-details {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #bdc3c7;
            font-size: 1rem;
        }
        
        .detail-value {
            font-weight: 700;
            color: #3498db;
            font-size: 1.1rem;
            background: rgba(52, 152, 219, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .modal-footer {
            padding: 1.5rem 2rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .modal-footer .btn {
            min-width: 120px;
            height: 50px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
            background: linear-gradient(135deg, #229954 0%, #1e8449 100%);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* Modal Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-50px) scale(0.9);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }
        
        /* Modal Ripple Effect */
        .modal-footer .btn::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .modal-footer .btn:active::before {
            width: 300px;
            height: 300px;
        }
        
        /* Mobile Responsive Modal */
        @media (max-width: 768px) {
            .modal-container {
                width: 95%;
                margin: 1rem;
            }
            
            .modal-header {
                padding: 1.5rem 1.5rem 1rem 1.5rem;
            }
            
            .modal-title {
                font-size: 1.5rem;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .modal-question {
                font-size: 1.1rem;
            }
            
            .modal-details {
                padding: 1.2rem;
            }
            
            .detail-item {
                padding: 0.6rem 0;
            }
            
            .detail-label {
                font-size: 0.9rem;
            }
            
            .detail-value {
                font-size: 1rem;
                padding: 0.2rem 0.6rem;
            }
            
            .modal-footer {
                padding: 1rem 1.5rem 1.5rem 1.5rem;
                flex-direction: column;
            }
            
            .modal-footer .btn {
                width: 100%;
                min-width: auto;
            }
        }
        
        @media (max-width: 576px) {
            .modal-container {
                width: 98%;
                margin: 0.5rem;
            }
            
            .modal-header {
                padding: 1.2rem 1rem 0.8rem 1rem;
            }
            
            .modal-title {
                font-size: 1.3rem;
            }
            
            .modal-body {
                padding: 1.2rem;
            }
            
            .modal-question {
                font-size: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .modal-details {
                padding: 1rem;
            }
            
            .detail-item {
                padding: 0.5rem 0;
            }
            
            .detail-label {
                font-size: 0.85rem;
            }
            
            .detail-value {
                font-size: 0.9rem;
                padding: 0.2rem 0.5rem;
            }
            
            .modal-footer {
                padding: 0.8rem 1rem 1.2rem 1rem;
            }
            
            .modal-footer .btn {
                height: 45px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1e3a8a 0%, #0ea5e9 100%); border-bottom: 1px solid #e5e7eb;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="../assets/image/logo/xrlogo.ico" alt="MyOPC" style="width: 35px; height: auto; margin-right: 10px;">
                <div class="brand-text">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #fff;">Pc-Atama</div>
                    <div class="d-none d-md-block" style="font-size: 0.9rem; opacity: 0.9;">Laboratuvar Ekle</div>
                    <div class="d-block d-md-none" style="font-size: 0.8rem; opacity: 0.9;">Lab Ekle</div>
                </div>
            </a>
            
            <!-- Hamburger Menu Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Collapsible Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link d-flex align-items-center" href="dashboard.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        <span class="d-none d-sm-inline">Dashboard'a Dön</span>
                        <span class="d-inline d-sm-none">Dashboard</span>
                    </a>
                    <a class="nav-link d-flex align-items-center" href="lab_list.php">
                        <i class="fas fa-building me-1"></i>
                        <span class="d-none d-sm-inline">Laboratuvarlar</span>
                        <span class="d-inline d-sm-none">Lablar</span>
                    </a>
                    <a class="nav-link d-flex align-items-center" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        <span class="d-none d-sm-inline">Çıkış Yap</span>
                        <span class="d-inline d-sm-none">Çıkış</span>
                    </a>
                </div>
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
                                            <label for="user_type" class="form-label">
                                                <i class="fas fa-user-tag"></i>Laboratuvar Adı Giriniz
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="user_type" 
                                                   name="user_type" 
                                                   value="<?php echo htmlspecialchars($_POST['user_type'] ?? ''); ?>"
                                                   placeholder="Örn: Mekanik, Veri/Analistliği, Bilgisayar Progrmacılığı" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-info-circle"></i>Format Önizleme
                                            </label>
                                            <div class="form-control" style="background-color: #f8f9fa; font-weight: bold; color: #667eea;" id="formatPreview">
                                                Laboratuvar Adı  ve PC sayısını girin
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
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
                                
                                
                                
                                
                                <!-- PC Önizleme -->
                                <div class="pc-preview" id="pcPreview" style="display: none;">
                                    <h6><i class="fas fa-eye me-2"></i>PC Önizleme</h6>
                                    <div id="pcList"></div>
                                </div>
                                
                                <div class="form-actions mt-4">
                                    <div class="row g-3">
                                        <div class="col-12 col-sm-6">
                                            <a href="dashboard.php" class="btn btn-secondary w-100">
                                                <i class="fas fa-arrow-left me-2"></i>
                                                <span>Geri Dön</span>
                                            </a>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-plus me-2"></i>
                                                <span>Laboratuvar Oluştur</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="confirmationModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">LOCALHOST WEB SİTESİNİN MESAJI</h3>
            </div>
            <div class="modal-body">
                <p class="modal-question">Laboratuvar oluşturmak istediğinizden emin misiniz?</p>
                <div class="modal-details">
                    <div class="detail-item">
                        <span class="detail-label">Format:</span>
                        <span class="detail-value" id="modalFormat">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Kullanıcı Tipi:</span>
                        <span class="detail-value" id="modalUserType">-</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">PC Sayısı:</span>
                        <span class="detail-value" id="modalPcCount">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" id="cancelBtn">
                    <i class="fas fa-times"></i>
                    İptal
                </button>
                <button type="button" class="btn btn-confirm" id="confirmBtn">
                    <i class="fas fa-check"></i>
                    Onayla
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile navbar functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Add lab page loaded');
            
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            const navLinks = document.querySelectorAll('.nav-link');
            
            // Manual navbar toggle functionality
            if (navbarToggler && navbarCollapse) {
                navbarToggler.addEventListener('click', function() {
                    const isExpanded = navbarCollapse.classList.contains('show');
                    
                    if (isExpanded) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    } else {
                        navbarCollapse.classList.add('show');
                        navbarToggler.setAttribute('aria-expanded', 'true');
                    }
                });
                
                // Close navbar when clicking outside
                document.addEventListener('click', function(event) {
                    const isClickInsideNav = navbarCollapse.contains(event.target) || navbarToggler.contains(event.target);
                    
                    if (!isClickInsideNav && navbarCollapse.classList.contains('show')) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Close navbar when clicking on nav links
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.setAttribute('aria-expanded', 'false');
                    });
                });
            }
            
            // Touch-friendly improvements
            const buttons = document.querySelectorAll('.btn, .nav-link');
            buttons.forEach(button => {
                button.style.minHeight = '44px';
                button.style.touchAction = 'manipulation';
            });
        });
    </script>
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
                const prefix = `Lab_${cleanType}`;
                const fullFormat = `${prefix}`;
                
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
                const prefix = `Lab_${cleanType}`;
                
                // İlk 10 PC'yi göster
                const showCount = Math.min(pcCount, 10);
                for (let i = 1; i <= showCount; i++) {
                    const pcItem = document.createElement('span');
                    pcItem.className = 'pc-item';
                    pcItem.textContent = `${prefix}`;
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
        
        
        // Laboratuvar adı otomatik oluşturulduğu için bu event listener kaldırıldı
        
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
        
        // Custom Modal Functions
        function showConfirmationModal(format, userType, pcCount) {
            const modal = document.getElementById('confirmationModal');
            const modalFormat = document.getElementById('modalFormat');
            const modalUserType = document.getElementById('modalUserType');
            const modalPcCount = document.getElementById('modalPcCount');
            
            // Update modal content
            modalFormat.textContent = format;
            modalUserType.textContent = userType;
            modalPcCount.textContent = pcCount;
            
            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Return a promise that resolves with the user's choice
            return new Promise((resolve) => {
                const confirmBtn = document.getElementById('confirmBtn');
                const cancelBtn = document.getElementById('cancelBtn');
                
                const handleConfirm = () => {
                    hideConfirmationModal();
                    resolve(true);
                };
                
                const handleCancel = () => {
                    hideConfirmationModal();
                    resolve(false);
                };
                
                // Remove existing event listeners
                confirmBtn.replaceWith(confirmBtn.cloneNode(true));
                cancelBtn.replaceWith(cancelBtn.cloneNode(true));
                
                // Add new event listeners
                document.getElementById('confirmBtn').addEventListener('click', handleConfirm);
                document.getElementById('cancelBtn').addEventListener('click', handleCancel);
                
                // Close on overlay click
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        handleCancel();
                    }
                });
                
                // Close on Escape key
                const handleEscape = (e) => {
                    if (e.key === 'Escape') {
                        handleCancel();
                        document.removeEventListener('keydown', handleEscape);
                    }
                };
                document.addEventListener('keydown', handleEscape);
            });
        }
        
        function hideConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Form validasyonu
        document.getElementById('labForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const pcCount = parseInt(document.getElementById('pc_count').value);
            const userType = document.getElementById('user_type').value;
            const submitBtn = document.querySelector('button[type="submit"]');
            
            if (pcCount < 1 || pcCount > 100) {
                alert('PC sayısı 1-100 arasında olmalıdır.');
                return;
            }
            
            if (!userType) {
                alert('Kullanıcı tipi seçilmelidir.');
                return;
            }
            
            // Format oluştur (boşlukları kaldır, küçük harfe çevirme)
            const cleanType = userType.replace(/\s+/g, '').replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ]/g, '');
            const prefix = `Lab_${cleanType}`;
            const format = `${prefix}`;
            
            // Show custom confirmation modal
            const confirmed = await showConfirmationModal(format, userType, pcCount);
            
            if (confirmed) {
                // Loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                // Submit the form
                this.submit();
            }
        });
        
        // Button click effects
        document.querySelectorAll('.form-actions .btn').forEach(button => {
            button.addEventListener('click', function(e) {
                // Ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
