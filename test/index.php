<?php
// Test klasörü ana sayfası
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sistemi - Öğrenci Atama Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-bug me-2"></i>Test Sistemi</h4>
                    </div>
                    <div class="card-body">
                        <p class="lead">Öğrenci Atama Sistemi Test Dosyaları</p>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Bu klasör, sistem testlerini gerçekleştiren API dosyalarını içerir.
                        </div>
                        
                        <h5>Mevcut Test Dosyaları:</h5>
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-database me-2"></i>
                                    <strong>test-database.php</strong>
                                    <br><small class="text-muted">Veritabanı testleri</small>
                                </div>
                                <span class="badge bg-primary">API</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-server me-2"></i>
                                    <strong>test-server.php</strong>
                                    <br><small class="text-muted">Sunucu testleri</small>
                                </div>
                                <span class="badge bg-primary">API</span>
                            </li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Dikkat:</strong> Bu dosyalar doğrudan erişim için değil, sistem içi testler için tasarlanmıştır.
                        </div>
                        
                        <div class="text-center">
                            <a href="../views/dashboard.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Dashboard'a Dön
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
