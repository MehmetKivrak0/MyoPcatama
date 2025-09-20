<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: iambatman.php');
    exit;
}

require_once 'config/db.php';

$db = Database::getInstance();

echo "<h2>Tüm Atamaları Silme İşlemi</h2>";

try {
    // Önce mevcut atamaları göster
    $assignments = $db->fetchAll('SELECT COUNT(*) as total FROM myopc_assignments');
    $totalAssignments = $assignments[0]['total'];
    
    echo "<p>Mevcut atama sayısı: <strong>$totalAssignments</strong></p>";
    
    if ($totalAssignments > 0) {
        // Tüm atamaları sil
        $result = $db->execute('DELETE FROM myopc_assignments');
        
        if ($result) {
            echo "<p style='color: green;'>✅ Tüm atamalar başarıyla silindi!</p>";
            
            // Silme sonrası kontrol
            $remainingAssignments = $db->fetchAll('SELECT COUNT(*) as total FROM myopc_assignments');
            $remainingCount = $remainingAssignments[0]['total'];
            
            echo "<p>Kalan atama sayısı: <strong>$remainingCount</strong></p>";
            
            if ($remainingCount == 0) {
                echo "<p style='color: green;'>✅ Veritabanı tamamen temizlendi!</p>";
            } else {
                echo "<p style='color: red;'>❌ Bazı atamalar silinemedi!</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Atamalar silinirken hata oluştu!</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Silinecek atama bulunamadı.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='views/assign.php'>Atama Sayfasına Dön</a></p>";
?>
