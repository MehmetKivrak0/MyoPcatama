<?php
require_once '../vendor/autoload.php';
require_once '../utils/TurkishCharacterHelper.php'; // Türkçe karakter yardımcısı

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Test verileri (hem doğru hem hatalı örnekler)
$testData = [
    // Doğru veriler - Türkçe karakterler
    ['2024001', 'Ahmet', 'Yılmaz', '2024'],
    ['2024002', 'Ayşe', 'Demir', '2024'],
    ['2024003', 'Mehmet', 'Kaya', '2024'],
    ['2024004', 'Çağla', 'Özkan', '2024'],
    ['2024005', 'İbrahim', 'Şahin', '2024'],
    ['2024006', 'Ümit', 'Çelik', '2024'],
    ['2024007', 'Özlem', 'Güneş', '2024'],
    ['2024008', 'Şule', 'Işık', '2024'],
    ['2024009', 'Gül', 'Yıldız', '2024'],
    
    // Bozuk Türkçe karakterler (test için)
    ['2024010', 'Cagla', 'Oezkan', '2024'], // Ç, Ö eksik
    ['2024011', 'Ibrahim', 'Sahin', '2024'], // İ, Ş eksik
    ['2024012', 'Uemit', 'Celik', '2024'], // Ü, Ç eksik
    ['2024013', 'Ozlem', 'Gunes', '2024'], // Ö, Ü eksik
    ['2024014', 'Sule', 'Isik', '2024'], // Ş, I eksik
    ['2024015', 'Gul', 'Yildiz', '2024'], // Ü, I eksik
    
    // Hatalı veriler (test için)
    ['', 'Boş', 'Numara', '2024'], // Boş öğrenci numarası
    ['2024016', '123', 'Rakam', '2024'], // Ad sadece rakam
    ['2024017', 'Ahmet@', 'Özel', '2024'], // Ad'da özel karakter
    ['2024018', 'Ali', 'Veli', 'abc'], // Akademik yıl string
    ['2024019', 'Fatma', 'Özkan', '1800'], // Geçersiz yıl
    ['2024020', 'Zeynep', 'Çelik', '2035'], // Gelecek yıl
];

// Hatalı sütun testi için ayrı dosya oluştur
$testDataWrongColumns = [
    // Yanlış sütun sayısı (5 sütun)
    ['2024001', 'Ahmet', 'Yılmaz', '2024', 'Ekstra Sütun'],
    ['2024002', 'Ayşe', 'Demir', '2024', 'Ekstra Sütun'],
];

// Yanlış başlık testi için ayrı dosya oluştur
$testDataWrongHeaders = [
    // Yanlış başlıklar
    ['Öğrenci ID', 'İsim', 'Soyisim', 'Yıl'],
    ['2024001', 'Ahmet', 'Yılmaz', '2024'],
    ['2024002', 'Ayşe', 'Demir', '2024'],
];

$spreadsheet = new Spreadsheet();
$worksheet = $spreadsheet->getActiveSheet();

// Başlıkları ekle (doğru başlıklar)
$worksheet->setCellValue('A1', 'Öğrenci No');
$worksheet->setCellValue('B1', 'Ad');
$worksheet->setCellValue('C1', 'Soyad');
$worksheet->setCellValue('D1', 'Akademik Yıl');

// Başlık stilini ayarla
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
];
$worksheet->getStyle('A1:D1')->applyFromArray($headerStyle);

// Test verilerini ekle
$row = 2;
foreach ($testData as $data) {
    $worksheet->setCellValue('A' . $row, $data[0]);
    $worksheet->setCellValue('B' . $row, $data[1]);
    $worksheet->setCellValue('C' . $row, $data[2]);
    $worksheet->setCellValue('D' . $row, $data[3]);
    $row++;
}

// Sütun genişliklerini ayarla
$worksheet->getColumnDimension('A')->setWidth(15);
$worksheet->getColumnDimension('B')->setWidth(15);
$worksheet->getColumnDimension('C')->setWidth(15);
$worksheet->getColumnDimension('D')->setWidth(15);

// Dosyayı kaydet
$writer = new Xlsx($spreadsheet);
$writer->save('test_validation.xlsx');

echo "Test Excel dosyası oluşturuldu: test_validation.xlsx\n";
echo "Bu dosyayı import ederek validasyon sistemini test edebilirsiniz.\n";
?>
