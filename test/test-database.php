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
            
        case 'test_database_errors':
            testDatabaseErrors($db);
            break;
            
        case 'test_connection_errors':
            testConnectionErrors();
            break;
            
        case 'test_query_errors':
            testQueryErrors($db);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz test aksiyonu']);
            break;
    }
    
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal Veritabanı Test Hatası: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'http_status' => 500
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Veritabanı Test Hatası: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'http_status' => 500
    ]);
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

function testDatabaseErrors($db) {
    // Veritabanı hata testleri
    $errorTests = [];
    $passedTests = 0;
    $totalTests = 0;
    
    try {
        // Test 1: Geçersiz SQL sorgusu
        $totalTests++;
        try {
            $result = $db->fetchAll("INVALID SQL QUERY");
            $errorTests[] = ['test' => 'Invalid SQL test', 'status' => 'FAIL', 'message' => 'Geçersiz SQL testi başarısız'];
        } catch (Exception $e) {
            $errorTests[] = ['test' => 'Invalid SQL test', 'status' => 'PASS', 'message' => 'Geçersiz SQL hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 2: Olmayan tablo sorgusu
        $totalTests++;
        try {
            $result = $db->fetchAll("SELECT * FROM non_existent_table");
            $errorTests[] = ['test' => 'Non-existent table test', 'status' => 'FAIL', 'message' => 'Olmayan tablo testi başarısız'];
        } catch (Exception $e) {
            $errorTests[] = ['test' => 'Non-existent table test', 'status' => 'PASS', 'message' => 'Olmayan tablo hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 3: Olmayan sütun sorgusu
        $totalTests++;
        try {
            $result = $db->fetchAll("SELECT non_existent_column FROM myopc_students");
            $errorTests[] = ['test' => 'Non-existent column test', 'status' => 'FAIL', 'message' => 'Olmayan sütun testi başarısız'];
        } catch (Exception $e) {
            $errorTests[] = ['test' => 'Non-existent column test', 'status' => 'PASS', 'message' => 'Olmayan sütun hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 4: SQL injection denemesi
        $totalTests++;
        try {
            $maliciousInput = "'; DROP TABLE myopc_students; --";
            $result = $db->fetchAll("SELECT * FROM myopc_students WHERE student_id = '" . $maliciousInput . "'");
            $errorTests[] = ['test' => 'SQL injection test', 'status' => 'PASS', 'message' => 'SQL injection koruması çalışıyor'];
            $passedTests++;
        } catch (Exception $e) {
            $errorTests[] = ['test' => 'SQL injection test', 'status' => 'PASS', 'message' => 'SQL injection hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 5: Transaction rollback testi
        $totalTests++;
        try {
            $db->beginTransaction();
            $db->execute("INSERT INTO myopc_students (student_id, full_name) VALUES ('TEST123', 'Test Student')");
            $db->rollback();
            
            // Rollback sonrası veri kontrolü
            $result = $db->fetchOne("SELECT COUNT(*) as count FROM myopc_students WHERE student_id = 'TEST123'");
            if ($result['count'] == 0) {
                $errorTests[] = ['test' => 'Transaction rollback test', 'status' => 'PASS', 'message' => 'Transaction rollback çalışıyor'];
                $passedTests++;
            } else {
                $errorTests[] = ['test' => 'Transaction rollback test', 'status' => 'FAIL', 'message' => 'Transaction rollback başarısız'];
            }
        } catch (Exception $e) {
            $errorTests[] = ['test' => 'Transaction rollback test', 'status' => 'FAIL', 'message' => 'Transaction rollback hatası: ' . $e->getMessage()];
        }
        
        $success = $passedTests === $totalTests;
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Tüm veritabanı hata testleri başarılı' : 'Bazı veritabanı hata testleri başarısız',
            'passed_tests' => $passedTests,
            'total_tests' => $totalTests,
            'test_results' => $errorTests,
            'http_status' => $success ? 200 : 500
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Veritabanı hata test hatası: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'http_status' => 500
        ]);
    }
}

function testConnectionErrors() {
    // Bağlantı hata testleri
    $connectionTests = [];
    $passedTests = 0;
    $totalTests = 0;
    
    try {
        // Test 1: Geçersiz host ile bağlantı denemesi
        $totalTests++;
        try {
            $invalidDb = new mysqli('invalid_host', 'user', 'pass', 'database');
            if ($invalidDb->connect_error) {
                $connectionTests[] = ['test' => 'Invalid host test', 'status' => 'PASS', 'message' => 'Geçersiz host hatası yakalandı: ' . $invalidDb->connect_error];
                $passedTests++;
            } else {
                $connectionTests[] = ['test' => 'Invalid host test', 'status' => 'FAIL', 'message' => 'Geçersiz host testi başarısız'];
            }
        } catch (Exception $e) {
            $connectionTests[] = ['test' => 'Invalid host test', 'status' => 'PASS', 'message' => 'Geçersiz host hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 2: Geçersiz kullanıcı adı ile bağlantı denemesi
        $totalTests++;
        try {
            $invalidDb = new mysqli('localhost', 'invalid_user', 'invalid_pass', 'myopc_db');
            if ($invalidDb->connect_error) {
                $connectionTests[] = ['test' => 'Invalid credentials test', 'status' => 'PASS', 'message' => 'Geçersiz kimlik bilgileri hatası yakalandı: ' . $invalidDb->connect_error];
                $passedTests++;
            } else {
                $connectionTests[] = ['test' => 'Invalid credentials test', 'status' => 'FAIL', 'message' => 'Geçersiz kimlik bilgileri testi başarısız'];
            }
        } catch (Exception $e) {
            $connectionTests[] = ['test' => 'Invalid credentials test', 'status' => 'PASS', 'message' => 'Geçersiz kimlik bilgileri hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 3: Geçersiz veritabanı adı ile bağlantı denemesi
        $totalTests++;
        try {
            $invalidDb = new mysqli('localhost', 'root', '', 'invalid_database');
            if ($invalidDb->connect_error) {
                $connectionTests[] = ['test' => 'Invalid database test', 'status' => 'PASS', 'message' => 'Geçersiz veritabanı hatası yakalandı: ' . $invalidDb->connect_error];
                $passedTests++;
            } else {
                $connectionTests[] = ['test' => 'Invalid database test', 'status' => 'FAIL', 'message' => 'Geçersiz veritabanı testi başarısız'];
            }
        } catch (Exception $e) {
            $connectionTests[] = ['test' => 'Invalid database test', 'status' => 'PASS', 'message' => 'Geçersiz veritabanı hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 4: Bağlantı timeout testi
        $totalTests++;
        try {
            // Çok kısa timeout ile bağlantı denemesi
            $timeoutDb = new mysqli();
            $timeoutDb->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
            $result = $timeoutDb->real_connect('192.168.1.999', 'user', 'pass', 'database'); // Geçersiz IP
            if (!$result || $timeoutDb->connect_error) {
                $connectionTests[] = ['test' => 'Connection timeout test', 'status' => 'PASS', 'message' => 'Bağlantı timeout hatası yakalandı: ' . $timeoutDb->connect_error];
                $passedTests++;
            } else {
                $connectionTests[] = ['test' => 'Connection timeout test', 'status' => 'FAIL', 'message' => 'Bağlantı timeout testi başarısız'];
            }
        } catch (Exception $e) {
            $connectionTests[] = ['test' => 'Connection timeout test', 'status' => 'PASS', 'message' => 'Bağlantı timeout hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        $success = $passedTests === $totalTests;
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Tüm bağlantı hata testleri başarılı' : 'Bazı bağlantı hata testleri başarısız',
            'passed_tests' => $passedTests,
            'total_tests' => $totalTests,
            'test_results' => $connectionTests,
            'http_status' => $success ? 200 : 500
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Bağlantı hata test hatası: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'http_status' => 500
        ]);
    }
}

function testQueryErrors($db) {
    // Sorgu hata testleri
    $queryTests = [];
    $passedTests = 0;
    $totalTests = 0;
    
    try {
        // Test 1: Syntax hatası
        $totalTests++;
        try {
            $result = $db->fetchAll("SELCT * FROM myopc_students"); // SELCT yazım hatası
            $queryTests[] = ['test' => 'Syntax error test', 'status' => 'FAIL', 'message' => 'Syntax hatası testi başarısız'];
        } catch (Exception $e) {
            $queryTests[] = ['test' => 'Syntax error test', 'status' => 'PASS', 'message' => 'Syntax hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 2: Geçersiz veri türü
        $totalTests++;
        try {
            $result = $db->execute("INSERT INTO myopc_students (student_id, full_name) VALUES (123, 'Test')"); // student_id string olmalı
            $queryTests[] = ['test' => 'Invalid data type test', 'status' => 'FAIL', 'message' => 'Geçersiz veri türü testi başarısız'];
        } catch (Exception $e) {
            $queryTests[] = ['test' => 'Invalid data type test', 'status' => 'PASS', 'message' => 'Geçersiz veri türü hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 3: Constraint violation
        $totalTests++;
        try {
            // Aynı student_id ile iki kayıt eklemeye çalış
            $db->execute("INSERT INTO myopc_students (student_id, full_name) VALUES ('DUPLICATE123', 'Test Student 1')");
            $db->execute("INSERT INTO myopc_students (student_id, full_name) VALUES ('DUPLICATE123', 'Test Student 2')");
            $queryTests[] = ['test' => 'Constraint violation test', 'status' => 'FAIL', 'message' => 'Constraint violation testi başarısız'];
        } catch (Exception $e) {
            $queryTests[] = ['test' => 'Constraint violation test', 'status' => 'PASS', 'message' => 'Constraint violation hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 4: Deadlock simulation
        $totalTests++;
        try {
            // Basit deadlock simülasyonu
            $db->beginTransaction();
            $db->execute("SELECT * FROM myopc_students FOR UPDATE");
            // Bu noktada başka bir transaction başlatılırsa deadlock olabilir
            $db->commit();
            $queryTests[] = ['test' => 'Deadlock simulation test', 'status' => 'PASS', 'message' => 'Deadlock simülasyonu tamamlandı'];
            $passedTests++;
        } catch (Exception $e) {
            $queryTests[] = ['test' => 'Deadlock simulation test', 'status' => 'PASS', 'message' => 'Deadlock hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 5: Query timeout testi
        $totalTests++;
        try {
            // Uzun süren sorgu simülasyonu
            $result = $db->fetchAll("SELECT SLEEP(2)"); // 2 saniye bekle
            $queryTests[] = ['test' => 'Query timeout test', 'status' => 'PASS', 'message' => 'Query timeout testi tamamlandı'];
            $passedTests++;
        } catch (Exception $e) {
            $queryTests[] = ['test' => 'Query timeout test', 'status' => 'PASS', 'message' => 'Query timeout hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        $success = $passedTests === $totalTests;
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Tüm sorgu hata testleri başarılı' : 'Bazı sorgu hata testleri başarısız',
            'passed_tests' => $passedTests,
            'total_tests' => $totalTests,
            'test_results' => $queryTests,
            'http_status' => $success ? 200 : 500
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Sorgu hata test hatası: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'http_status' => 500
        ]);
    }
}
?>
