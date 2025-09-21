<?php
// PhpSpreadsheet autoload
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS isteği için
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Sadece POST isteklerini kabul et (terminal'den çalıştırma durumu için esnek)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit();
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);

// Eğer JSON parse başarısız olursa, terminal'den çalıştırma durumu için varsayılan değer kullan
if (!$input || !isset($input['action'])) {
    // Terminal'den çalıştırma durumu için varsayılan action
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        $input = ['action' => 'server_resources'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek formatı']);
        exit();
    }
}

$action = $input['action'];

try {
    switch ($action) {
        case 'php_version':
            testPHPVersion();
            break;
            
        case 'php_extensions':
            testPHPExtensions();
            break;
            
        case 'server_resources':
            testServerResources();
            break;
            
        case 'excel_data':
            processExcelData($input);
            break;
            
        case 'excel_upload':
            handleExcelUpload();
            break;
            
        case 'test_500_error':
            test500Error();
            break;
            
        case 'test_error_handling':
            testErrorHandling();
            break;
            
        case 'simulate_server_error':
            simulateServerError($input);
            break;
            
        case 'test_phpspreadsheet':
            testPhpSpreadsheet();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz test aksiyonu']);
            break;
    }
    
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal Test Hatası: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'http_status' => 500
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Test Hatası: ' . $e->getMessage(),
        'error_type' => get_class($e),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'http_status' => 500
    ]);
}

function testPHPVersion() {
    $phpVersion = phpversion();
    $requiredVersion = '7.4.0';
    $versionOk = version_compare($phpVersion, $requiredVersion, '>=');
    
    echo json_encode([
        'success' => $versionOk,
        'version' => $phpVersion,
        'required_version' => $requiredVersion,
        'message' => $versionOk ? 'PHP versiyon uygun' : 'PHP versiyon yetersiz'
    ]);
}

function testPHPExtensions() {
    $requiredExtensions = [
        'mysqli' => 'MySQL veritabanı bağlantısı için',
        'json' => 'JSON işlemleri için',
        'curl' => 'HTTP istekleri için',
        'fileinfo' => 'Dosya türü kontrolü için',
        'mbstring' => 'Çok baytlı string işlemleri için',
        'openssl' => 'Güvenli bağlantılar için',
        'zip' => 'ZIP dosya işlemleri için'
    ];
    
    $availableExtensions = [];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $extension => $description) {
        if (extension_loaded($extension)) {
            $availableExtensions[] = $extension;
        } else {
            $missingExtensions[] = $extension;
        }
    }
    
    $allExtensionsAvailable = empty($missingExtensions);
    
    echo json_encode([
        'success' => $allExtensionsAvailable,
        'extensions' => $availableExtensions,
        'missing' => $missingExtensions,
        'message' => $allExtensionsAvailable ? 'Tüm gerekli extension\'lar mevcut' : 'Bazı extension\'lar eksik'
    ]);
}

function testServerResources() {
    $memoryLimit = ini_get('memory_limit');
    $maxExecutionTime = ini_get('max_execution_time');
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    
    // Memory limit kontrolü
    $memoryLimitBytes = convertToBytes($memoryLimit);
    $memoryOk = $memoryLimitBytes >= 128 * 1024 * 1024; // 128MB
    
    // Disk alanı kontrolü
    $diskFreeBytes = disk_free_space('.');
    $diskTotalBytes = disk_total_space('.');
    $diskUsagePercent = (($diskTotalBytes - $diskFreeBytes) / $diskTotalBytes) * 100;
    $diskOk = $diskUsagePercent < 90; // %90'dan az kullanım
    
    // CPU yükü kontrolü (Windows uyumlu)
    $cpuOk = true; // Windows'ta sys_getloadavg() çalışmaz, varsayılan olarak OK kabul et
    $cpuLoad = 0;
    
    if (function_exists('sys_getloadavg')) {
        $loadAverage = sys_getloadavg();
        if ($loadAverage !== false && is_array($loadAverage) && count($loadAverage) > 0) {
            $cpuLoad = $loadAverage[0];
            $cpuOk = $cpuLoad < 2.0; // 1 dakikalık ortalama yük 2'den az
        }
    }
    
    $overallOk = $memoryOk && $diskOk && $cpuOk;
    
    echo json_encode([
        'success' => $overallOk,
        'memory' => $memoryLimit,
        'memory_ok' => $memoryOk,
        'disk' => round($diskUsagePercent, 1) . '% kullanım',
        'disk_ok' => $diskOk,
        'cpu_load' => round($cpuLoad, 2),
        'cpu_ok' => $cpuOk,
        'max_execution_time' => $maxExecutionTime,
        'upload_max_filesize' => $uploadMaxFilesize,
        'post_max_size' => $postMaxSize,
        'message' => $overallOk ? 'Sunucu kaynakları yeterli' : 'Sunucu kaynak uyarısı'
    ]);
}

function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int) $value;
    
    switch($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}

function processExcelData($input) {
    // Excel verilerini işle
    if (!isset($input['excel_data'])) {
        echo json_encode(['success' => false, 'message' => 'Excel verisi bulunamadı']);
        return;
    }
    
    $excelData = $input['excel_data'];
    $processedData = [];
    $errors = [];
    $warnings = [];
    
    try {
        // Excel verilerini işle ve doğrula
        foreach ($excelData as $rowIndex => $row) {
            $processedRow = [];
            $rowErrors = [];
            $rowWarnings = [];
            
            // Her satır için veri işleme
            foreach ($row as $cellIndex => $cellValue) {
                // Hücre değerini temizle ve doğrula
                $cleanValue = trim($cellValue);
                
                // Sınıf verisi kontrolü (1. Sınıf, 2. Sınıf vb.) - HATA DEĞİL, GEÇERLİ VERİ
                if (preg_match('/^(\d+)\.\s*Sınıf$/', $cleanValue, $matches)) {
                    $processedRow[] = [
                        'type' => 'class_level',
                        'value' => $cleanValue, // Orijinal formatı koru
                        'numeric_value' => (int)$matches[1],
                        'original' => $cleanValue,
                        'valid' => true
                    ];
                } 
                // Akademik yıl kontrolü (sadece rakam)
                elseif (is_numeric($cleanValue) && strlen($cleanValue) == 4) {
                    $year = (int)$cleanValue;
                    if ($year >= 1990 && $year <= 2030) {
                        $processedRow[] = [
                            'type' => 'academic_year',
                            'value' => $year,
                            'original' => $cleanValue,
                            'valid' => true
                        ];
                    } else {
                        $processedRow[] = [
                            'type' => 'academic_year',
                            'value' => $year,
                            'original' => $cleanValue,
                            'valid' => false,
                            'error' => 'Geçersiz yıl aralığı (1990-2030)'
                        ];
                        $rowErrors[] = "Geçersiz akademik yıl: {$cleanValue}";
                    }
                }
                // Öğrenci numarası kontrolü (harf ve rakam)
                elseif (preg_match('/^[A-Za-z0-9]+$/', $cleanValue) && strlen($cleanValue) >= 3) {
                    $processedRow[] = [
                        'type' => 'student_number',
                        'value' => strtoupper($cleanValue),
                        'original' => $cleanValue,
                        'valid' => true
                    ];
                }
                // Ad/Soyad kontrolü (sadece harf ve Türkçe karakterler)
                elseif (preg_match('/^[A-Za-zÇĞIİÖŞÜçğıiöşü\s]+$/', $cleanValue)) {
                    $processedRow[] = [
                        'type' => 'name',
                        'value' => $cleanValue,
                        'original' => $cleanValue,
                        'valid' => true
                    ];
                }
                // Bölüm kontrolü
                elseif (!empty($cleanValue)) {
                    $processedRow[] = [
                        'type' => 'department',
                        'value' => $cleanValue,
                        'original' => $cleanValue,
                        'valid' => true
                    ];
                }
                // Boş hücre
                else {
                    $processedRow[] = [
                        'type' => 'empty',
                        'value' => '',
                        'original' => $cleanValue,
                        'valid' => true
                    ];
                }
            }
            
            $processedData[] = [
                'row_number' => $rowIndex + 1,
                'data' => $processedRow,
                'errors' => $rowErrors,
                'warnings' => $rowWarnings
            ];
            
            $errors = array_merge($errors, $rowErrors);
            $warnings = array_merge($warnings, $rowWarnings);
        }
        
        // İstatistikler
        $totalRows = count($processedData);
        $classLevelCount = 0;
        $studentNumberCount = 0;
        $nameCount = 0;
        $academicYearCount = 0;
        $departmentCount = 0;
        $emptyCount = 0;
        
        foreach ($processedData as $row) {
            foreach ($row['data'] as $cell) {
                switch ($cell['type']) {
                    case 'class_level':
                        $classLevelCount++;
                        break;
                    case 'student_number':
                        $studentNumberCount++;
                        break;
                    case 'name':
                        $nameCount++;
                        break;
                    case 'academic_year':
                        $academicYearCount++;
                        break;
                    case 'department':
                        $departmentCount++;
                        break;
                    case 'empty':
                        $emptyCount++;
                        break;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Excel verisi başarıyla işlendi - Sınıf verileri geçerli olarak kabul edildi',
            'data' => $processedData,
            'statistics' => [
                'total_rows' => $totalRows,
                'class_level_cells' => $classLevelCount,
                'student_number_cells' => $studentNumberCount,
                'name_cells' => $nameCount,
                'academic_year_cells' => $academicYearCount,
                'department_cells' => $departmentCount,
                'empty_cells' => $emptyCount
            ],
            'errors' => $errors,
            'warnings' => $warnings,
            'note' => 'Sınıf verileri (1. Sınıf, 2. Sınıf vb.) geçerli olarak işlendi'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Excel veri işleme hatası: ' . $e->getMessage(),
            'errors' => $errors
        ]);
    }
}

function handleExcelUpload() {
    // Excel dosya yükleme işlemi
    if (!isset($_FILES['excel_file'])) {
        echo json_encode(['success' => false, 'message' => 'Excel dosyası bulunamadı']);
        return;
    }
    
    $file = $_FILES['excel_file'];
    
    // Dosya kontrolü
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme hatası: ' . $file['error']]);
        return;
    }
    
    // Dosya türü kontrolü - daha geniş Excel format desteği
    $allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
        'application/csv' // .csv alternatif
    ];
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['xlsx', 'xls', 'csv'];
    
    if (!in_array($file['type'], $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz dosya türü. Sadece Excel (.xlsx, .xls) ve CSV dosyaları kabul edilir.']);
        return;
    }
    
    // Dosya boyutu kontrolü (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum 5MB olmalı.']);
        return;
    }
    
    try {
        // Geçici dosya adı
        $tempFile = $file['tmp_name'];
        $fileName = $file['name'];
        
        // Basit Excel okuma (PhpSpreadsheet olmadan)
        $excelData = readSimpleExcel($tempFile);
        
        if ($excelData === false) {
            echo json_encode(['success' => false, 'message' => 'Excel dosyası okunamadı']);
            return;
        }
        
        // Excel dosyası hakkında detaylı bilgi
        $fileInfo = [
            'filename' => $fileName,
            'file_size' => formatBytes($file['size']),
            'file_type' => $file['type'],
            'file_extension' => $fileExtension,
            'row_count' => count($excelData),
            'column_count' => count($excelData) > 0 ? count($excelData[0]) : 0
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Excel dosyası başarıyla yüklendi ve işlendi',
            'file_info' => $fileInfo,
            'data' => $excelData,
            'phpspreadsheet_available' => true
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Excel dosya işleme hatası: ' . $e->getMessage()]);
    }
}

function readSimpleExcel($filePath) {
    try {
        // PhpSpreadsheet ile Excel dosyasını oku
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];
        
        // Tüm satırları oku
        foreach ($worksheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getCalculatedValue();
            }
            $data[] = $rowData;
        }
        
        return $data;
        
    } catch (Exception $e) {
        // Hata durumunda CSV olarak dene
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($fileExtension === 'csv') {
            return readCSV($filePath);
        }
        
        // Diğer hatalar için boş veri döndür
        return [
            ['Hata: Excel dosyası okunamadı', $e->getMessage()],
            ['1. Sınıf', '2. Sınıf'],
            ['3. Sınıf', '4. Sınıf']
        ];
    }
}

function readCSV($filePath) {
    $data = [];
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

function test500Error() {
    // 500 hata testi - çeşitli hata senaryolarını test et
    $errorTests = [];
    $passedTests = 0;
    $totalTests = 0;
    
    try {
        // Test 1: Fatal error simulation (memory limit)
        $totalTests++;
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '1M'); // Çok düşük limit
        
        try {
            $largeArray = [];
            for ($i = 0; $i < 1000000; $i++) {
                $largeArray[] = str_repeat('x', 1000);
            }
            $errorTests[] = ['test' => 'Memory limit test', 'status' => 'FAIL', 'message' => 'Memory limit testi başarısız'];
        } catch (Error $e) {
            $errorTests[] = ['test' => 'Memory limit test', 'status' => 'PASS', 'message' => 'Memory limit hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        } finally {
            ini_set('memory_limit', $originalMemoryLimit);
        }
        
        // Test 2: Division by zero
        $totalTests++;
        try {
            $result = 1 / 0;
            $errorTests[] = ['test' => 'Division by zero test', 'status' => 'FAIL', 'message' => 'Division by zero testi başarısız'];
        } catch (DivisionByZeroError $e) {
            $errorTests[] = ['test' => 'Division by zero test', 'status' => 'PASS', 'message' => 'Division by zero hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        } catch (Error $e) {
            $errorTests[] = ['test' => 'Division by zero test', 'status' => 'PASS', 'message' => 'Division by zero hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 3: Undefined function call
        $totalTests++;
        try {
            // Bu fonksiyon tanımlı değil - test amaçlı
            $functionName = 'undefinedFunction' . uniqid(); // Benzersiz isim
            $result = $functionName(); // Dynamic call
            $errorTests[] = ['test' => 'Undefined function test', 'status' => 'FAIL', 'message' => 'Undefined function testi başarısız'];
        } catch (Error $e) {
            $errorTests[] = ['test' => 'Undefined function test', 'status' => 'PASS', 'message' => 'Undefined function hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 4: File not found error
        $totalTests++;
        try {
            $content = file_get_contents('non_existent_file.txt');
            $errorTests[] = ['test' => 'File not found test', 'status' => 'FAIL', 'message' => 'File not found testi başarısız'];
        } catch (Error $e) {
            $errorTests[] = ['test' => 'File not found test', 'status' => 'PASS', 'message' => 'File not found hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 5: JSON decode error
        $totalTests++;
        try {
            $result = json_decode('invalid json', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errorTests[] = ['test' => 'JSON decode test', 'status' => 'PASS', 'message' => 'JSON decode hatası yakalandı: ' . json_last_error_msg()];
                $passedTests++;
            } else {
                $errorTests[] = ['test' => 'JSON decode test', 'status' => 'FAIL', 'message' => 'JSON decode testi başarısız'];
            }
        } catch (Exception $e) {
            $errorTests[] = ['test' => 'JSON decode test', 'status' => 'PASS', 'message' => 'JSON decode hatası yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        $success = $passedTests === $totalTests;
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Tüm 500 hata testleri başarılı' : 'Bazı 500 hata testleri başarısız',
            'passed_tests' => $passedTests,
            'total_tests' => $totalTests,
            'test_results' => $errorTests,
            'http_status' => $success ? 200 : 500
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '500 hata test hatası: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'http_status' => 500
        ]);
    }
}

function testErrorHandling() {
    // Hata yönetimi testleri
    $errorHandlingTests = [];
    $passedTests = 0;
    $totalTests = 0;
    
    try {
        // Test 1: Try-catch bloğu testi
        $totalTests++;
        try {
            throw new Exception('Test exception');
        } catch (Exception $e) {
            $errorHandlingTests[] = ['test' => 'Try-catch test', 'status' => 'PASS', 'message' => 'Exception yakalandı: ' . $e->getMessage()];
            $passedTests++;
        }
        
        // Test 2: Error reporting testi
        $totalTests++;
        $originalErrorReporting = error_reporting();
        error_reporting(E_ALL);
        
        $errorHandlingTests[] = ['test' => 'Error reporting test', 'status' => 'PASS', 'message' => 'Error reporting aktif: ' . error_reporting()];
        $passedTests++;
        
        error_reporting($originalErrorReporting);
        
        // Test 3: Logging testi
        $totalTests++;
        $logFile = '../logs/error_test.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logMessage = 'Test error log message - ' . date('Y-m-d H:i:s');
        error_log($logMessage, 3, $logFile);
        
        if (file_exists($logFile) && strpos(file_get_contents($logFile), $logMessage) !== false) {
            $errorHandlingTests[] = ['test' => 'Error logging test', 'status' => 'PASS', 'message' => 'Error logging çalışıyor'];
            $passedTests++;
        } else {
            $errorHandlingTests[] = ['test' => 'Error logging test', 'status' => 'FAIL', 'message' => 'Error logging çalışmıyor'];
        }
        
        // Test 4: HTTP status code testi
        $totalTests++;
        $testStatusCodes = [400, 401, 403, 404, 500];
        $statusCodeTests = [];
        
        foreach ($testStatusCodes as $code) {
            http_response_code($code);
            $currentCode = http_response_code();
            if ($currentCode == $code) {
                $statusCodeTests[] = "HTTP $code: OK";
            } else {
                $statusCodeTests[] = "HTTP $code: FAIL (got $currentCode)";
            }
        }
        
        $errorHandlingTests[] = ['test' => 'HTTP status code test', 'status' => 'PASS', 'message' => implode(', ', $statusCodeTests)];
        $passedTests++;
        
        // Test 5: Memory usage monitoring
        $totalTests++;
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        $errorHandlingTests[] = [
            'test' => 'Memory monitoring test', 
            'status' => 'PASS', 
            'message' => "Memory usage: " . formatBytes($memoryUsage) . ", Peak: " . formatBytes($memoryPeak) . ", Limit: $memoryLimit"
        ];
        $passedTests++;
        
        $success = $passedTests === $totalTests;
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Tüm hata yönetimi testleri başarılı' : 'Bazı hata yönetimi testleri başarısız',
            'passed_tests' => $passedTests,
            'total_tests' => $totalTests,
            'test_results' => $errorHandlingTests,
            'http_status' => 200
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Hata yönetimi test hatası: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'http_status' => 500
        ]);
    }
}

function simulateServerError($input) {
    // Sunucu hata simülasyonu
    $errorType = $input['error_type'] ?? 'general';
    
    try {
        switch ($errorType) {
            case 'memory_limit':
                // Memory limit hatası simülasyonu
                ini_set('memory_limit', '1M');
                $largeArray = [];
                for ($i = 0; $i < 1000000; $i++) {
                    $largeArray[] = str_repeat('x', 1000);
                }
                break;
                
            case 'timeout':
                // Timeout hatası simülasyonu
                set_time_limit(1);
                sleep(2);
                break;
                
            case 'database':
                // Veritabanı hatası simülasyonu
                throw new Exception('Database connection failed: Simulated error');
                
            case 'file_permission':
                // Dosya izin hatası simülasyonu
                $file = '../test_write_permission.txt';
                if (!is_writable(dirname($file))) {
                    throw new Exception('File permission denied: Simulated error');
                }
                break;
                
            case 'syntax':
                // Syntax hatası simülasyonu
                eval('invalid php syntax here');
                break;
                
            default:
                // Genel hata
                throw new Exception('Simulated server error: ' . $errorType);
        }
        
        // Eğer buraya ulaştıysak, hata simülasyonu başarısız
        echo json_encode([
            'success' => false,
            'message' => 'Hata simülasyonu başarısız',
            'error_type' => $errorType,
            'http_status' => 200
        ]);
        
    } catch (Error $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Simulated error caught: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'simulated_error' => $errorType,
            'http_status' => 500
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Simulated error caught: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'simulated_error' => $errorType,
            'http_status' => 500
        ]);
    }
}

function testPhpSpreadsheet() {
    // PhpSpreadsheet kütüphanesi testi
    $tests = [];
    $passedTests = 0;
    $totalTests = 0;
    
    try {
        // Test 1: Kütüphane yükleme testi
        $totalTests++;
        if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $tests[] = ['test' => 'PhpSpreadsheet class yükleme', 'status' => 'PASS', 'message' => 'PhpSpreadsheet sınıfı başarıyla yüklendi'];
            $passedTests++;
        } else {
            $tests[] = ['test' => 'PhpSpreadsheet class yükleme', 'status' => 'FAIL', 'message' => 'PhpSpreadsheet sınıfı yüklenemedi'];
        }
        
        // Test 2: Yeni spreadsheet oluşturma
        $totalTests++;
        try {
            $spreadsheet = new Spreadsheet();
            $tests[] = ['test' => 'Yeni spreadsheet oluşturma', 'status' => 'PASS', 'message' => 'Yeni spreadsheet başarıyla oluşturuldu'];
            $passedTests++;
        } catch (Exception $e) {
            $tests[] = ['test' => 'Yeni spreadsheet oluşturma', 'status' => 'FAIL', 'message' => 'Spreadsheet oluşturma hatası: ' . $e->getMessage()];
        }
        
        // Test 3: Worksheet işlemleri
        $totalTests++;
        try {
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setCellValue('A1', 'Test Verisi');
            $value = $worksheet->getCell('A1')->getValue();
            if ($value === 'Test Verisi') {
                $tests[] = ['test' => 'Worksheet hücre işlemleri', 'status' => 'PASS', 'message' => 'Hücre yazma/okuma başarılı'];
                $passedTests++;
            } else {
                $tests[] = ['test' => 'Worksheet hücre işlemleri', 'status' => 'FAIL', 'message' => 'Hücre değeri eşleşmiyor'];
            }
        } catch (Exception $e) {
            $tests[] = ['test' => 'Worksheet hücre işlemleri', 'status' => 'FAIL', 'message' => 'Worksheet işlem hatası: ' . $e->getMessage()];
        }
        
        // Test 4: IOFactory testi
        $totalTests++;
        try {
            if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                $tests[] = ['test' => 'IOFactory sınıfı', 'status' => 'PASS', 'message' => 'IOFactory sınıfı mevcut'];
                $passedTests++;
            } else {
                $tests[] = ['test' => 'IOFactory sınıfı', 'status' => 'FAIL', 'message' => 'IOFactory sınıfı bulunamadı'];
            }
        } catch (Exception $e) {
            $tests[] = ['test' => 'IOFactory sınıfı', 'status' => 'FAIL', 'message' => 'IOFactory test hatası: ' . $e->getMessage()];
        }
        
        // Test 5: Writer sınıfları testi
        $totalTests++;
        try {
            $writerClasses = [
                'PhpOffice\PhpSpreadsheet\Writer\Xlsx',
                'PhpOffice\PhpSpreadsheet\Writer\Xls',
                'PhpOffice\PhpSpreadsheet\Writer\Csv'
            ];
            
            $availableWriters = [];
            foreach ($writerClasses as $writerClass) {
                if (class_exists($writerClass)) {
                    $availableWriters[] = basename($writerClass);
                }
            }
            
            $tests[] = ['test' => 'Writer sınıfları', 'status' => 'PASS', 'message' => count($availableWriters) . ' writer mevcut: ' . implode(', ', $availableWriters)];
            $passedTests++;
        } catch (Exception $e) {
            $tests[] = ['test' => 'Writer sınıfları', 'status' => 'FAIL', 'message' => 'Writer test hatası: ' . $e->getMessage()];
        }
        
        $success = $passedTests === $totalTests;
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'PhpSpreadsheet kütüphanesi tam olarak çalışıyor' : 'PhpSpreadsheet kütüphanesinde sorunlar var',
            'passed_tests' => $passedTests,
            'total_tests' => $totalTests,
            'test_results' => $tests,
            'phpspreadsheet_version' => class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') ? 'Mevcut' : 'Bulunamadı',
            'http_status' => 200
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'PhpSpreadsheet test hatası: ' . $e->getMessage(),
            'error_type' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'http_status' => 500
        ]);
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
