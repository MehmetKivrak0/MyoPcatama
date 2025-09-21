<?php
// Kapsamlı test çalıştırıcısı - 500 hata testleri ve hata yönetimi
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

if (!$input || !isset($input['test_type'])) {
    echo json_encode(['success' => false, 'message' => 'Test türü belirtilmelidir']);
    exit();
}

$testType = $input['test_type'];
$allResults = [];
$overallSuccess = true;

try {
    switch ($testType) {
        case 'all_500_tests':
            runAll500ErrorTests($allResults, $overallSuccess);
            break;
            
        case 'all_error_handling_tests':
            runAllErrorHandlingTests($allResults, $overallSuccess);
            break;
            
        case 'comprehensive_test':
            runComprehensiveTests($allResults, $overallSuccess);
            break;
            
        case 'stress_test':
            runStressTests($allResults, $overallSuccess);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz test türü']);
            break;
    }
    
    // Genel sonuç
    echo json_encode([
        'success' => $overallSuccess,
        'message' => $overallSuccess ? 'Tüm testler başarılı' : 'Bazı testler başarısız',
        'total_tests' => count($allResults),
        'passed_tests' => count(array_filter($allResults, function($test) { return $test['success']; })),
        'failed_tests' => count(array_filter($allResults, function($test) { return !$test['success']; })),
        'test_results' => $allResults,
        'http_status' => $overallSuccess ? 200 : 500
    ]);
    
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test Runner Fatal Hatası: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'http_status' => 500
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test Runner Hatası: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'http_status' => 500
    ]);
}

function runAll500ErrorTests(&$allResults, &$overallSuccess) {
    // Sunucu 500 hata testleri
    $serverTests = [
        'test_500_error' => '500 Hata Testleri',
        'test_error_handling' => 'Hata Yönetimi Testleri',
        'simulate_server_error' => 'Sunucu Hata Simülasyonu'
    ];
    
    foreach ($serverTests as $action => $description) {
        $result = runTest('test-server.php', $action, ['error_type' => 'memory_limit']);
        $allResults[] = [
            'test_name' => $description,
            'test_file' => 'test-server.php',
            'action' => $action,
            'success' => $result['success'],
            'message' => $result['message'],
            'http_status' => $result['http_status'] ?? 200,
            'details' => $result
        ];
        
        if (!$result['success']) {
            $overallSuccess = false;
        }
    }
    
    // Veritabanı 500 hata testleri
    $dbTests = [
        'test_database_errors' => 'Veritabanı Hata Testleri',
        'test_connection_errors' => 'Bağlantı Hata Testleri',
        'test_query_errors' => 'Sorgu Hata Testleri'
    ];
    
    foreach ($dbTests as $action => $description) {
        $result = runTest('test-database.php', $action);
        $allResults[] = [
            'test_name' => $description,
            'test_file' => 'test-database.php',
            'action' => $action,
            'success' => $result['success'],
            'message' => $result['message'],
            'http_status' => $result['http_status'] ?? 200,
            'details' => $result
        ];
        
        if (!$result['success']) {
            $overallSuccess = false;
        }
    }
}

function runAllErrorHandlingTests(&$allResults, &$overallSuccess) {
    // Temel hata yönetimi testleri
    $basicTests = [
        'php_version' => 'PHP Versiyon Testi',
        'php_extensions' => 'PHP Extension Testleri',
        'server_resources' => 'Sunucu Kaynak Testleri'
    ];
    
    foreach ($basicTests as $action => $description) {
        $result = runTest('test-server.php', $action);
        $allResults[] = [
            'test_name' => $description,
            'test_file' => 'test-server.php',
            'action' => $action,
            'success' => $result['success'],
            'message' => $result['message'],
            'http_status' => $result['http_status'] ?? 200,
            'details' => $result
        ];
        
        if (!$result['success']) {
            $overallSuccess = false;
        }
    }
    
    // Veritabanı hata yönetimi testleri
    $dbTests = [
        'test_connection' => 'Veritabanı Bağlantı Testi',
        'test_tables' => 'Tablo Varlık Testi',
        'test_integrity' => 'Veri Bütünlük Testi',
        'test_performance' => 'Performans Testi',
        'test_backup' => 'Backup Testi'
    ];
    
    foreach ($dbTests as $action => $description) {
        $result = runTest('test-database.php', $action);
        $allResults[] = [
            'test_name' => $description,
            'test_file' => 'test-database.php',
            'action' => $action,
            'success' => $result['success'],
            'message' => $result['message'],
            'http_status' => $result['http_status'] ?? 200,
            'details' => $result
        ];
        
        if (!$result['success']) {
            $overallSuccess = false;
        }
    }
}

function runComprehensiveTests(&$allResults, &$overallSuccess) {
    // Tüm testleri çalıştır
    runAllErrorHandlingTests($allResults, $overallSuccess);
    runAll500ErrorTests($allResults, $overallSuccess);
    
    // Ek kapsamlı testler
    $additionalTests = [
        'memory_stress' => 'Bellek Stres Testi',
        'concurrent_requests' => 'Eşzamanlı İstek Testi',
        'error_recovery' => 'Hata Kurtarma Testi'
    ];
    
    foreach ($additionalTests as $testName => $description) {
        $result = runAdditionalTest($testName);
        $allResults[] = [
            'test_name' => $description,
            'test_file' => 'test-runner.php',
            'action' => $testName,
            'success' => $result['success'],
            'message' => $result['message'],
            'http_status' => $result['http_status'] ?? 200,
            'details' => $result
        ];
        
        if (!$result['success']) {
            $overallSuccess = false;
        }
    }
}

function runStressTests(&$allResults, &$overallSuccess) {
    // Stres testleri
    $stressTests = [
        'memory_limit' => 'Bellek Limit Stres Testi',
        'execution_time' => 'Çalışma Süresi Stres Testi',
        'concurrent_errors' => 'Eşzamanlı Hata Testi',
        'error_cascade' => 'Hata Zinciri Testi'
    ];
    
    foreach ($stressTests as $testName => $description) {
        $result = runStressTest($testName);
        $allResults[] = [
            'test_name' => $description,
            'test_file' => 'test-runner.php',
            'action' => $testName,
            'success' => $result['success'],
            'message' => $result['message'],
            'http_status' => $result['http_status'] ?? 200,
            'details' => $result
        ];
        
        if (!$result['success']) {
            $overallSuccess = false;
        }
    }
}

function runTest($testFile, $action, $additionalData = []) {
    $postData = json_encode(array_merge(['action' => $action], $additionalData));
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $result = @file_get_contents($testFile, false, $context);
    
    if ($result === false) {
        return [
            'success' => false,
            'message' => 'Test dosyası çalıştırılamadı: ' . $testFile,
            'http_status' => 500
        ];
    }
    
    $decodedResult = json_decode($result, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Test sonucu parse edilemedi: ' . json_last_error_msg(),
            'raw_result' => $result,
            'http_status' => 500
        ];
    }
    
    return $decodedResult;
}

function runAdditionalTest($testName) {
    switch ($testName) {
        case 'memory_stress':
            return runMemoryStressTest();
        case 'concurrent_requests':
            return runConcurrentRequestTest();
        case 'error_recovery':
            return runErrorRecoveryTest();
        default:
            return [
                'success' => false,
                'message' => 'Bilinmeyen ek test: ' . $testName,
                'http_status' => 400
            ];
    }
}

function runStressTest($testName) {
    switch ($testName) {
        case 'memory_limit':
            return runMemoryLimitStressTest();
        case 'execution_time':
            return runExecutionTimeStressTest();
        case 'concurrent_errors':
            return runConcurrentErrorTest();
        case 'error_cascade':
            return runErrorCascadeTest();
        default:
            return [
                'success' => false,
                'message' => 'Bilinmeyen stres testi: ' . $testName,
                'http_status' => 400
            ];
    }
}

function runMemoryStressTest() {
    $startMemory = memory_get_usage(true);
    $peakMemory = memory_get_peak_usage(true);
    
    try {
        // Bellek kullanımını artır
        $largeArray = [];
        for ($i = 0; $i < 100000; $i++) {
            $largeArray[] = str_repeat('x', 1000);
        }
        
        $endMemory = memory_get_usage(true);
        $newPeakMemory = memory_get_peak_usage(true);
        
        return [
            'success' => true,
            'message' => 'Bellek stres testi tamamlandı',
            'start_memory' => formatBytes($startMemory),
            'end_memory' => formatBytes($endMemory),
            'peak_memory' => formatBytes($newPeakMemory),
            'memory_increase' => formatBytes($endMemory - $startMemory),
            'http_status' => 200
        ];
    } catch (Error $e) {
        return [
            'success' => false,
            'message' => 'Bellek stres testi hatası: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'http_status' => 500
        ];
    }
}

function runConcurrentRequestTest() {
    // Eşzamanlı istek simülasyonu
    $testResults = [];
    $successCount = 0;
    
    for ($i = 0; $i < 5; $i++) {
        $result = runTest('test-server.php', 'server_resources');
        $testResults[] = $result;
        if ($result['success']) {
            $successCount++;
        }
    }
    
    $success = $successCount === 5;
    
    return [
        'success' => $success,
        'message' => $success ? 'Eşzamanlı istek testi başarılı' : 'Eşzamanlı istek testi başarısız',
        'total_requests' => 5,
        'successful_requests' => $successCount,
        'test_results' => $testResults,
        'http_status' => $success ? 200 : 500
    ];
}

function runErrorRecoveryTest() {
    // Hata kurtarma testi
    $recoveryTests = [];
    $passedTests = 0;
    $totalTests = 0;
    
    // Test 1: Exception handling
    $totalTests++;
    try {
        throw new Exception('Test exception for recovery');
    } catch (Exception $e) {
        $recoveryTests[] = 'Exception yakalandı: ' . $e->getMessage();
        $passedTests++;
    }
    
    // Test 2: Error handling
    $totalTests++;
    try {
        $result = 1 / 0;
    } catch (DivisionByZeroError $e) {
        $recoveryTests[] = 'Division by zero yakalandı: ' . $e->getMessage();
        $passedTests++;
    } catch (Error $e) {
        $recoveryTests[] = 'Error yakalandı: ' . $e->getMessage();
        $passedTests++;
    }
    
    $success = $passedTests === $totalTests;
    
    return [
        'success' => $success,
        'message' => $success ? 'Hata kurtarma testi başarılı' : 'Hata kurtarma testi başarısız',
        'passed_tests' => $passedTests,
        'total_tests' => $totalTests,
        'recovery_tests' => $recoveryTests,
        'http_status' => $success ? 200 : 500
    ];
}

function runMemoryLimitStressTest() {
    $originalLimit = ini_get('memory_limit');
    $testResults = [];
    
    try {
        // Farklı bellek limitleri test et
        $limits = ['1M', '2M', '4M', '8M'];
        
        foreach ($limits as $limit) {
            ini_set('memory_limit', $limit);
            $currentLimit = ini_get('memory_limit');
            
            try {
                $largeArray = [];
                for ($i = 0; $i < 50000; $i++) {
                    $largeArray[] = str_repeat('x', 1000);
                }
                $testResults[] = "Limit $limit: OK";
            } catch (Error $e) {
                $testResults[] = "Limit $limit: FAIL - " . $e->getMessage();
            }
        }
        
        ini_set('memory_limit', $originalLimit);
        
        return [
            'success' => true,
            'message' => 'Bellek limit stres testi tamamlandı',
            'test_results' => $testResults,
            'http_status' => 200
        ];
    } catch (Exception $e) {
        ini_set('memory_limit', $originalLimit);
        return [
            'success' => false,
            'message' => 'Bellek limit stres testi hatası: ' . $e->getMessage(),
            'http_status' => 500
        ];
    }
}

function runExecutionTimeStressTest() {
    $startTime = microtime(true);
    $testResults = [];
    
    try {
        // Farklı sürelerde işlemler test et
        $durations = [1, 2, 3, 5];
        
        foreach ($durations as $duration) {
            $testStart = microtime(true);
            sleep($duration);
            $testEnd = microtime(true);
            $actualDuration = $testEnd - $testStart;
            
            $testResults[] = "Beklenen: {$duration}s, Gerçek: " . round($actualDuration, 2) . "s";
        }
        
        $totalTime = microtime(true) - $startTime;
        
        return [
            'success' => true,
            'message' => 'Çalışma süresi stres testi tamamlandı',
            'total_time' => round($totalTime, 2) . 's',
            'test_results' => $testResults,
            'http_status' => 200
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Çalışma süresi stres testi hatası: ' . $e->getMessage(),
            'http_status' => 500
        ];
    }
}

function runConcurrentErrorTest() {
    // Eşzamanlı hata testi
    $errorTypes = ['memory_limit', 'timeout', 'database', 'file_permission'];
    $testResults = [];
    $successCount = 0;
    
    foreach ($errorTypes as $errorType) {
        $result = runTest('test-server.php', 'simulate_server_error', ['error_type' => $errorType]);
        $testResults[] = [
            'error_type' => $errorType,
            'success' => $result['success'],
            'message' => $result['message']
        ];
        
        if ($result['success'] || (isset($result['http_status']) && $result['http_status'] == 500)) {
            $successCount++;
        }
    }
    
    $success = $successCount === count($errorTypes);
    
    return [
        'success' => $success,
        'message' => $success ? 'Eşzamanlı hata testi başarılı' : 'Eşzamanlı hata testi başarısız',
        'test_results' => $testResults,
        'http_status' => $success ? 200 : 500
    ];
}

function runErrorCascadeTest() {
    // Hata zinciri testi
    $cascadeResults = [];
    $errorCount = 0;
    
    try {
        // İlk hata
        try {
            throw new Exception('İlk hata');
        } catch (Exception $e) {
            $cascadeResults[] = 'İlk hata yakalandı: ' . $e->getMessage();
            $errorCount++;
            
            // İkinci hata
            try {
                throw new Exception('İkinci hata');
            } catch (Exception $e2) {
                $cascadeResults[] = 'İkinci hata yakalandı: ' . $e2->getMessage();
                $errorCount++;
                
                // Üçüncü hata
                try {
                    throw new Exception('Üçüncü hata');
                } catch (Exception $e3) {
                    $cascadeResults[] = 'Üçüncü hata yakalandı: ' . $e3->getMessage();
                    $errorCount++;
                }
            }
        }
        
        $success = $errorCount === 3;
        
        return [
            'success' => $success,
            'message' => $success ? 'Hata zinciri testi başarılı' : 'Hata zinciri testi başarısız',
            'error_count' => $errorCount,
            'cascade_results' => $cascadeResults,
            'http_status' => $success ? 200 : 500
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Hata zinciri testi hatası: ' . $e->getMessage(),
            'http_status' => 500
        ];
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
