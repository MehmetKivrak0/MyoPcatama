<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS isteği için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit();
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek formatı']);
    exit();
}

$action = $input['action'];

try {
    // Veritabanı bağlantısını test et
    require_once '../config/db.php';
    $db = Database::getInstance();
    
    switch ($action) {
        case 'test_connection':
            testDatabaseConnection($db);
            break;
            
        case 'test_tables':
            testTableExistence($db);
            break;
            
        case 'test_integrity':
            testDataIntegrity($db);
            break;
            
        case 'test_performance':
            testDatabasePerformance($db);
            break;
            
        case 'test_backup':
            testDatabaseBackup($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz test aksiyonu']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Test hatası: ' . $e->getMessage()]);
}

function testDatabaseConnection($db) {
    try {
        // Basit bir sorgu çalıştır
        $result = $db->fetchOne("SELECT 1 as test");
        
        if ($result && $result['test'] == 1) {
            echo json_encode([
                'success' => true, 
                'message' => 'Veritabanı bağlantısı başarılı',
                'connection_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı testi başarısız']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    }
}

function testTableExistence($db) {
    $requiredTables = [
        'myopc_students',
        'myopc_lab_computers', 
        'myopc_assignments'
    ];
    
    $existingTables = [];
    $missingTables = [];
    
    try {
        // Mevcut tabloları kontrol et
        $tables = $db->fetchAll("SHOW TABLES");
        $tableNames = [];
        foreach ($tables as $table) {
            $tableNames[] = array_values($table)[0];
        }
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $tableNames)) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            echo json_encode([
                'success' => true,
                'message' => 'Tüm gerekli tablolar mevcut',
                'tables' => $existingTables
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Bazı tablolar eksik',
                'existing_tables' => $existingTables,
                'missing_tables' => $missingTables
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Tablo kontrol hatası: ' . $e->getMessage()]);
    }
}

function testDataIntegrity($db) {
    $integrityIssues = [];
    
    try {
        // Foreign key kontrolü
        $foreignKeyChecks = [
            "SELECT COUNT(*) as count FROM myopc_assignments a 
             LEFT JOIN myopc_students s ON a.student_id = s.student_id 
             WHERE s.student_id IS NULL" => "myopc_assignments.student_id"
        ];
        
        foreach ($foreignKeyChecks as $query => $description) {
            $result = $db->fetchOne($query);
            if ($result['count'] > 0) {
                $integrityIssues[] = "Orphaned records found in $description";
            }
        }
        
        // Duplicate kontrolü
        $duplicateChecks = [
            "SELECT student_id, computer_id, COUNT(*) as count FROM myopc_assignments 
             GROUP BY student_id, computer_id HAVING count > 1" => "Duplicate assignments"
        ];
        
        foreach ($duplicateChecks as $query => $description) {
            $result = $db->fetchAll($query);
            if (!empty($result)) {
                $integrityIssues[] = $description . " (" . count($result) . " found)";
            }
        }
        
        if (empty($integrityIssues)) {
            echo json_encode([
                'success' => true,
                'message' => 'Veri bütünlüğü kontrolü başarılı',
                'checks_performed' => count($foreignKeyChecks) + count($duplicateChecks)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Veri bütünlüğü sorunları bulundu',
                'issues' => $integrityIssues
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Veri bütünlüğü kontrol hatası: ' . $e->getMessage()]);
    }
}

function testDatabasePerformance($db) {
    $queries = [
        "SELECT COUNT(*) FROM myopc_students",
        "SELECT COUNT(*) FROM myopc_lab_computers", 
        "SELECT COUNT(*) FROM myopc_assignments",
        "SELECT s.full_name, l.lab_name, a.computer_id 
         FROM myopc_assignments a 
         JOIN myopc_students s ON a.student_id = s.student_id 
         JOIN myopc_lab_computers l ON FLOOR(a.computer_id / 100) = l.computer_id 
         LIMIT 10"
    ];
    
    $queryTimes = [];
    $totalTime = 0;
    
    try {
        foreach ($queries as $index => $query) {
            $startTime = microtime(true);
            $db->fetchAll($query);
            $endTime = microtime(true);
            
            $queryTime = ($endTime - $startTime) * 1000; // ms cinsinden
            $queryTimes[] = $queryTime;
            $totalTime += $queryTime;
        }
        
        $avgQueryTime = $totalTime / count($queries);
        $maxQueryTime = max($queryTimes);
        
        // Performans kriterleri
        $performanceGood = $avgQueryTime < 100 && $maxQueryTime < 500; // ms
        
        echo json_encode([
            'success' => $performanceGood,
            'message' => $performanceGood ? 'Performans testi başarılı' : 'Performans sorunları tespit edildi',
            'avg_query_time' => round($avgQueryTime, 2),
            'max_query_time' => round($maxQueryTime, 2),
            'total_queries' => count($queries),
            'query_times' => array_map('round', $queryTimes, [2])
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Performans test hatası: ' . $e->getMessage()]);
    }
}

function testDatabaseBackup($db) {
    try {
        // Backup dizinini kontrol et
        $backupDir = '../backups/';
        $backupExists = is_dir($backupDir);
        
        if (!$backupExists) {
            echo json_encode([
                'success' => false,
                'message' => 'Backup dizini bulunamadı',
                'backup_dir' => $backupDir
            ]);
            return;
        }
        
        // Son backup dosyasını kontrol et
        $backupFiles = glob($backupDir . '*.sql');
        $latestBackup = null;
        $latestBackupTime = 0;
        
        foreach ($backupFiles as $file) {
            $fileTime = filemtime($file);
            if ($fileTime > $latestBackupTime) {
                $latestBackup = $file;
                $latestBackupTime = $fileTime;
            }
        }
        
        $hoursSinceBackup = $latestBackup ? (time() - $latestBackupTime) / 3600 : null;
        $backupRecent = $hoursSinceBackup !== null && $hoursSinceBackup < 24; // 24 saat içinde
        
        echo json_encode([
            'success' => $backupRecent,
            'message' => $backupRecent ? 'Son backup güncel' : 'Backup güncel değil',
            'backup_dir' => $backupDir,
            'backup_files_count' => count($backupFiles),
            'latest_backup' => $latestBackup ? basename($latestBackup) : null,
            'hours_since_backup' => $hoursSinceBackup ? round($hoursSinceBackup, 1) : null
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Backup kontrol hatası: ' . $e->getMessage()]);
    }
}
?>
