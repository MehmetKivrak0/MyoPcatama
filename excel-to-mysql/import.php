<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php'; // Composer autoload
require_once '../utils/TurkishCharacterHelper.php'; // Türkçe karakter yardımcısı

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImporter {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Excel dosyasını analiz et (import yapmadan)
     */
    public function analyzeFile($excelFile) {
        try {
            // Excel dosyasını yükle
            $spreadsheet = IOFactory::load($excelFile);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $rows = $worksheet->toArray();
            $header = array_shift($rows);
            
            // Sütun sayısını kontrol et
            $columnCount = count($header);
            if ($columnCount !== 4) {
                return [
                    'success' => false,
                    'message' => "❌ Excel dosyasında 4 sütun olmalıdır. Bulunan sütun sayısı: {$columnCount}",
                    'errors' => ["Excel dosyasında {$columnCount} sütun bulundu. Sadece 4 sütun (Öğrenci No, Ad, Soyad, Akademik Yıl) kabul edilir."]
                ];
            }
            
            // Mevcut öğrenci numaralarını al
            $existingNumbers = $this->getExistingStudentNumbers();
            
            $validRows = [];
            $invalidRows = [];
            $duplicateRows = [];
            $warningRows = [];
            
            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue; // Boş satırları atla
                
                $rowNumber = $index + 2;
                $rawData = [
                    'sdt_nmbr' => trim($row[0] ?? ''),
                    'first_name' => trim($row[1] ?? ''),
                    'last_name' => trim($row[2] ?? ''),
                    'academic_year' => trim($row[3] ?? '')
                ];
                
                $validationResult = $this->validateStudentData($rawData, $rowNumber, $existingNumbers);
                
                if (!$validationResult['valid']) {
                    $invalidRows[] = [
                        'row_number' => $rowNumber,
                        'data' => $rawData,
                        'errors' => $validationResult['errors']
                    ];
                } else if (in_array($validationResult['data']['sdt_nmbr'], $existingNumbers)) {
                    $duplicateRows[] = [
                        'row_number' => $rowNumber,
                        'data' => $rawData,
                        'student_number' => $validationResult['data']['sdt_nmbr']
                    ];
                } else {
                    $validRows[] = [
                        'row_number' => $rowNumber,
                        'data' => $validationResult['data']
                    ];
                    
                    if (!empty($validationResult['warnings'])) {
                        $warningRows[] = [
                            'row_number' => $rowNumber,
                            'data' => $rawData,
                            'warnings' => $validationResult['warnings']
                        ];
                    }
                    
                    // Geçici olarak eklenen numarayı listeye ekle
                    $existingNumbers[] = $validationResult['data']['sdt_nmbr'];
                }
            }
            
            return [
                'success' => true,
                'analysis' => true,
                'total_rows' => count($rows),
                'valid_rows' => count($validRows),
                'invalid_rows' => count($invalidRows),
                'duplicate_rows' => count($duplicateRows),
                'warning_rows' => count($warningRows),
                'valid_data' => $validRows,
                'invalid_data' => $invalidRows,
                'duplicate_data' => $duplicateRows,
                'warning_data' => $warningRows,
                'message' => $this->generateAnalysisMessage(count($validRows), count($invalidRows), count($duplicateRows), count($warningRows))
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Excel dosyası okunamadı: ' . $e->getMessage()
            ];
        }
    }
    
    public function importStudents($excelFile, $importValidOnly = false) {
        try {
            // Excel dosyasını yükle
            $spreadsheet = IOFactory::load($excelFile);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Excel'den veri okurken encoding sorunlarını önle
            $worksheet->getParent()->getProperties()->setCreator('MyOPC System');
            $worksheet->getParent()->getProperties()->setLastModifiedBy('MyOPC System');
            
            $rows = $worksheet->toArray();
            
            // İlk satır başlık olduğu için atla
            $header = array_shift($rows);
            
            // Sütun sayısını kontrol et (4 sütun olmalı)
            $columnCount = count($header);
            if ($columnCount !== 4) {
                return [
                    'success' => false,
                    'message' => "❌ Excel dosyasında 4 sütun olmalıdır. Bulunan sütun sayısı: {$columnCount}",
                    'imported_count' => 0,
                    'duplicate_count' => 0,
                    'error_count' => 0,
                    'warning_count' => 0,
                    'errors' => ["Excel dosyasında {$columnCount} sütun bulundu. Sadece 4 sütun (Öğrenci No, Ad, Soyad, Akademik Yıl) kabul edilir."],
                    'warnings' => []
                ];
            }
            
            // Sütun başlıklarını kontrol et
            $expectedHeaders = ['Öğrenci No', 'Ad', 'Soyad', 'Akademik Yıl'];
            $actualHeaders = array_map('trim', $header);
            
            // Başlık kontrolü
            $headerErrors = [];
            for ($i = 0; $i < 4; $i++) {
                if (empty($actualHeaders[$i])) {
                    $headerErrors[] = "Sütun " . ($i + 1) . " başlığı boş";
                } elseif (strtolower($actualHeaders[$i]) !== strtolower($expectedHeaders[$i])) {
                    $headerErrors[] = "Sütun " . ($i + 1) . " başlığı '{$actualHeaders[$i]}' olarak bulundu, '{$expectedHeaders[$i]}' olmalı";
                }
            }
            
            if (!empty($headerErrors)) {
                return [
                    'success' => false,
                    'message' => "❌ Excel dosyası sütun başlıkları hatalı!",
                    'imported_count' => 0,
                    'duplicate_count' => 0,
                    'error_count' => 0,
                    'warning_count' => 0,
                    'errors' => $headerErrors,
                    'warnings' => []
                ];
            }
            
            $importedCount = 0;
            $errors = [];
            $warnings = [];
            $duplicateCount = 0;
            
            // Mevcut öğrenci numaralarını önceden al (duplicate kontrolü için)
            $existingNumbers = $this->getExistingStudentNumbers();
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Excel satır numarası (başlık + 1)
                
                // Boş satırları atla
                if (empty(array_filter($row))) {
                    continue;
                }
                
                try {
                    // Excel'den veri al (sütun sırasına göre) ve Türkçe karakterleri düzelt
                    $rawData = [
                        'sdt_nmbr' => TurkishCharacterHelper::fixTurkishCharacters(trim($row[0] ?? '')),
                        'first_name' => TurkishCharacterHelper::fixTurkishCharacters(trim($row[1] ?? '')),
                        'last_name' => TurkishCharacterHelper::fixTurkishCharacters(trim($row[2] ?? '')),
                        'academic_year' => TurkishCharacterHelper::fixTurkishCharacters(trim($row[3] ?? ''))
                    ];
                    
                    // Kapsamlı veri doğrulama
                    $validationResult = $this->validateStudentData($rawData, $rowNumber, $existingNumbers);
                    
                    if (!$validationResult['valid']) {
                        if (!$importValidOnly) {
                            $errors = array_merge($errors, $validationResult['errors']);
                        }
                        continue;
                    }
                    
                    if (!empty($validationResult['warnings'])) {
                        $warnings = array_merge($warnings, $validationResult['warnings']);
                    }
                    
                    // Duplicate kontrolü
                    if (in_array($validationResult['data']['sdt_nmbr'], $existingNumbers)) {
                        if (!$importValidOnly) {
                            $errors[] = "❌ Satır {$rowNumber}: Öğrenci numarası '{$validationResult['data']['sdt_nmbr']}' zaten sistemde mevcut";
                        }
                        $duplicateCount++;
                        continue;
                    }
                    
                    // Veritabanına ekle
                    $this->insertStudent($validationResult['data']);
                    $importedCount++;
                    
                    // Eklenen numarayı listeye ekle (aynı import içinde duplicate olmasın)
                    $existingNumbers[] = $validationResult['data']['sdt_nmbr'];
                    
                } catch (Exception $e) {
                    $errors[] = "Satır " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            return [
                'success' => true,
                'imported_count' => $importedCount,
                'duplicate_count' => $duplicateCount,
                'error_count' => count($errors),
                'warning_count' => count($warnings),
                'errors' => $errors,
                'warnings' => $warnings,
                'message' => $this->generateSummaryMessage($importedCount, $duplicateCount, count($errors), count($warnings))
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Excel dosyası okunamadı: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Mevcut öğrenci numaralarını getir
     */
    private function getExistingStudentNumbers() {
        try {
            $result = $this->db->query("SELECT sdt_nmbr FROM myopc_students");
            $numbers = [];
            while ($row = $result->fetch_assoc()) {
                $numbers[] = $row['sdt_nmbr'];
            }
            return $numbers;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Kapsamlı veri doğrulama
     */
    private function validateStudentData($rawData, $rowNumber, $existingNumbers) {
        $errors = [];
        $warnings = [];
        $data = [];
        
        // 1. Öğrenci Numarası Doğrulama
        if (empty($rawData['sdt_nmbr'])) {
            $errors[] = "❌ Satır {$rowNumber}: Öğrenci numarası boş olamaz";
        } else {
            // Sadece rakam ve harf kontrolü
            if (!preg_match('/^[A-Za-z0-9]+$/', $rawData['sdt_nmbr'])) {
                $errors[] = "❌ Satır {$rowNumber}: Öğrenci numarası sadece harf ve rakam içerebilir. Geçersiz karakter: '{$rawData['sdt_nmbr']}'";
            } else {
                $data['sdt_nmbr'] = strtoupper($rawData['sdt_nmbr']); // Büyük harfe çevir
                
                // Uzunluk kontrolü
                if (strlen($data['sdt_nmbr']) < 3 || strlen($data['sdt_nmbr']) > 20) {
                    $errors[] = "❌ Satır {$rowNumber}: Öğrenci numarası 3-20 karakter arasında olmalıdır";
                }
            }
        }
        
        // 2. Ad Doğrulama
        if (empty($rawData['first_name'])) {
            $errors[] = "❌ Satır {$rowNumber}: Ad boş olamaz";
        } else {
            // Özel karakter kontrolü (sadece harf, boşluk, Türkçe karakterler)
            if (!preg_match('/^[A-Za-zÇĞIİÖŞÜçğıiöşü\s]+$/', $rawData['first_name'])) {
                $errors[] = "❌ Satır {$rowNumber}: Ad sadece harf içerebilir. Geçersiz karakter: '{$rawData['first_name']}'";
            } else {
                $data['first_name'] = TurkishCharacterHelper::cleanName($rawData['first_name']);
            }
        }
        
        // 3. Soyad Doğrulama
        if (empty($rawData['last_name'])) {
            $errors[] = "❌ Satır {$rowNumber}: Soyad boş olamaz";
        } else {
            // Özel karakter kontrolü
            if (!preg_match('/^[A-Za-zÇĞIİÖŞÜçğıiöşü\s]+$/', $rawData['last_name'])) {
                $errors[] = "❌ Satır {$rowNumber}: Soyad sadece harf içerebilir. Geçersiz karakter: '{$rawData['last_name']}'";
            } else {
                $data['last_name'] = TurkishCharacterHelper::cleanName($rawData['last_name']);
            }
        }
        
        // 4. Tam Ad Oluşturma
        if (!empty($data['first_name']) && !empty($data['last_name'])) {
            $data['full_name'] = trim($data['first_name'] . ' ' . $data['last_name']);
            
            // Tam ad uzunluk kontrolü
            if (strlen($data['full_name']) > 100) {
                $warnings[] = "⚠️ Satır {$rowNumber}: Tam ad çok uzun, kısaltılabilir";
            }
        }
        
        // 5. Akademik Yıl Doğrulama
        if (empty($rawData['academic_year'])) {
            $data['academic_year'] = date('Y'); // Varsayılan olarak mevcut yıl
            $warnings[] = "⚠️ Satır {$rowNumber}: Akademik yıl boş, mevcut yıl ({$data['academic_year']}) kullanıldı";
        } else {
            // Sadece rakam kontrolü
            if (!is_numeric($rawData['academic_year'])) {
                $errors[] = "❌ Satır {$rowNumber}: Akademik yıl sadece rakam olmalıdır. Geçersiz değer: '{$rawData['academic_year']}'";
            } else {
                $year = intval($rawData['academic_year']);
                
                // Yıl aralığı kontrolü (1990-2030)
                if ($year < 1990 || $year > 2030) {
                    $errors[] = "❌ Satır {$rowNumber}: Akademik yıl 1990-2030 arasında olmalıdır. Geçersiz yıl: {$year}";
                } else {
                    $data['academic_year'] = $year;
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'data' => $data,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Analiz mesajı oluştur
     */
    private function generateAnalysisMessage($valid, $invalid, $duplicate, $warnings) {
        $message = "📊 Dosya analizi tamamlandı.\n";
        $message .= "✅ Geçerli kayıt: {$valid}\n";
        if ($invalid > 0) $message .= "❌ Hatalı kayıt: {$invalid}\n";
        if ($duplicate > 0) $message .= "⚠️ Mevcut kayıt: {$duplicate}\n";
        if ($warnings > 0) $message .= "⚠️ Uyarılı kayıt: {$warnings}";
        
        return $message;
    }
    
    /**
     * Özet mesaj oluştur
     */
    private function generateSummaryMessage($importedCount, $duplicateCount, $errorCount, $warningCount) {
        $message = "İşlem tamamlandı! ";
        
        if ($importedCount > 0) {
            $message .= "✅ {$importedCount} öğrenci başarıyla eklendi. ";
        }
        
        if ($duplicateCount > 0) {
            $message .= "⚠️ {$duplicateCount} öğrenci zaten mevcut (atlandı). ";
        }
        
        if ($errorCount > 0) {
            $message .= "❌ {$errorCount} satırda hata var (eklenmedi). ";
        }
        
        if ($warningCount > 0) {
            $message .= "⚠️ {$warningCount} uyarı var. ";
        }
        
        if ($importedCount == 0 && $errorCount > 0) {
            $message = "❌ Hiçbir öğrenci eklenemedi. Lütfen hataları düzeltip tekrar deneyin.";
        }
        
        return trim($message);
    }
    
    private function insertStudent($data) {
        $sql = "INSERT INTO myopc_students (sdt_nmbr, full_name, academic_year, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())";
        
        $params = [
            $data['sdt_nmbr'],
            $data['full_name'],
            $data['academic_year']
        ];
        
        try {
            $this->db->execute($sql, $params);
        } catch (Exception $e) {
            throw new Exception("Veritabanı hatası: " . $e->getMessage());
        }
    }
}

// AJAX isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // İlk aşama: Dosya analizi
    if (isset($_FILES['excel_file']) && !isset($_POST['confirm_import'])) {
        $uploadDir = '../uploads/excel/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadedFile = $_FILES['excel_file'];
        $fileName = uniqid() . '_' . $uploadedFile['name'];
        $filePath = $uploadDir . $fileName;
        
        // Dosyayı yükle
        if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            $importer = new ExcelImporter();
            $result = $importer->analyzeFile($filePath);
            
            // Dosya yolunu sonuçta gönder
            $result['temp_file'] = $fileName;
            
            echo json_encode($result);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Dosya yüklenemedi'
            ]);
        }
        exit;
    }
    
    // İkinci aşama: Onaylanmış import
    if (isset($_POST['confirm_import']) && isset($_POST['temp_file'])) {
        $uploadDir = '../uploads/excel/';
        $filePath = $uploadDir . $_POST['temp_file'];
        
        if (file_exists($filePath)) {
            $importer = new ExcelImporter();
            $result = $importer->importStudents($filePath, $_POST['import_valid_only'] === 'true');
            
            // Geçici dosyayı sil
            unlink($filePath);
            
            echo json_encode($result);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Geçici dosya bulunamadı'
            ]);
        }
        exit;
    }
    
    // İptal aşaması: Geçici dosyayı sil
    if (isset($_POST['cancel_import']) && isset($_POST['temp_file'])) {
        $uploadDir = '../uploads/excel/';
        $filePath = $uploadDir . $_POST['temp_file'];
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'İşlem iptal edildi'
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel'den Öğrenci İçe Aktarma</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .template-link {
            margin-top: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Excel'den Öğrenci Verilerini İçe Aktar</h2>
        
        <form id="excelForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file">Excel Dosyası Seçin:</label>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
            </div>
            
            <button type="submit">Verileri İçe Aktar</button>
        </form>
        
        <div id="result"></div>
        
        <div class="template-link">
            <h3>Excel Şablonu</h3>
            <p>Doğru formatta veri yüklemek için şablon dosyasını indirin:</p>
            <a href="template.xlsx" download>Şablon Excel Dosyasını İndir</a>
            
            <h4>Excel Dosyası Formatı:</h4>
            <ul>
                <li><strong>A Sütunu:</strong> Öğrenci Numarası (sdt_nmbr)</li>
                <li><strong>B Sütunu:</strong> Ad</li>
                <li><strong>C Sütunu:</strong> Soyad</li>
                <li><strong>D Sütunu:</strong> Akademik Yıl (opsiyonel - boş bırakılırsa mevcut yıl kullanılır)</li>
            </ul>
            <p><em>Not: B ve C sütunları birleştirilerek full_name olarak kaydedilir.</em></p>
        </div>
    </div>

    <script>
        document.getElementById('excelForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            const fileInput = document.getElementById('excel_file');
            
            if (fileInput.files.length === 0) {
                alert('Lütfen bir Excel dosyası seçin');
                return;
            }
            
            formData.append('excel_file', fileInput.files[0]);
            
            fetch('import.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('result');
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="result success">
                            <h3>Başarılı!</h3>
                            <p>${data.imported_count} öğrenci başarıyla içe aktarıldı.</p>
                            ${data.errors.length > 0 ? '<p>Hatalar:</p><ul>' + data.errors.map(error => '<li>' + error + '</li>').join('') + '</ul>' : ''}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <h3>Hata!</h3>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('result').innerHTML = `
                    <div class="result error">
                        <h3>Hata!</h3>
                        <p>Bir hata oluştu: ${error.message}</p>
                    </div>
                `;
            });
        });
    </script>
</body>
</html>
