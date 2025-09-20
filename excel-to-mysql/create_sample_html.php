<?php
// HTML tablosu olarak örnek öğrenci verileri oluştur
$sampleData = [
    // 2024 yılı öğrencileri - Bilgisayar Programcılığı
    ['2024001', 'Ahmet', 'Yılmaz', '2024', 'Bilgisayar Programcılığı', '1. Sınıf'],
    ['2024002', 'Ayşe', 'Demir', '2024', 'Bilgisayar Programcılığı', '1. Sınıf'],
    ['2024003', 'Mehmet', 'Kaya', '2024', 'Bilgisayar Programcılığı', '2. Sınıf'],
    ['2024004', 'Çağla', 'Özkan', '2024', 'Bilgisayar Programcılığı', '1. Sınıf'],
    ['2024005', 'İbrahim', 'Şahin', '2024', 'Bilgisayar Programcılığı', '2. Sınıf'],
    
    // 2024 yılı öğrencileri - Elektronik Teknolojisi
    ['2024006', 'Ümit', 'Çelik', '2024', 'Elektronik Teknolojisi', '1. Sınıf'],
    ['2024007', 'Özlem', 'Güneş', '2024', 'Elektronik Teknolojisi', '2. Sınıf'],
    ['2024008', 'Şule', 'Işık', '2024', 'Elektronik Teknolojisi', '1. Sınıf'],
    ['2024009', 'Gül', 'Yıldız', '2024', 'Elektronik Teknolojisi', '2. Sınıf'],
    ['2024010', 'Emre', 'Arslan', '2024', 'Elektronik Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Makine Teknolojisi
    ['2024011', 'Zeynep', 'Koç', '2024', 'Makine Teknolojisi', '1. Sınıf'],
    ['2024012', 'Burak', 'Öztürk', '2024', 'Makine Teknolojisi', '2. Sınıf'],
    ['2024013', 'Elif', 'Kurt', '2024', 'Makine Teknolojisi', '1. Sınıf'],
    ['2024014', 'Can', 'Yılmaz', '2024', 'Makine Teknolojisi', '2. Sınıf'],
    ['2024015', 'Selin', 'Aydın', '2024', 'Makine Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - İnşaat Teknolojisi
    ['2024016', 'Deniz', 'Çakır', '2024', 'İnşaat Teknolojisi', '1. Sınıf'],
    ['2024017', 'Ece', 'Doğan', '2024', 'İnşaat Teknolojisi', '2. Sınıf'],
    ['2024018', 'Furkan', 'Erdoğan', '2024', 'İnşaat Teknolojisi', '1. Sınıf'],
    ['2024019', 'Gamze', 'Fidan', '2024', 'İnşaat Teknolojisi', '2. Sınıf'],
    ['2024020', 'Hakan', 'Güler', '2024', 'İnşaat Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Elektrik Teknolojisi
    ['2024021', 'İrem', 'Hızır', '2024', 'Elektrik Teknolojisi', '1. Sınıf'],
    ['2024022', 'Kemal', 'İpek', '2024', 'Elektrik Teknolojisi', '2. Sınıf'],
    ['2024023', 'Leyla', 'Jale', '2024', 'Elektrik Teknolojisi', '1. Sınıf'],
    ['2024024', 'Murat', 'Kılıç', '2024', 'Elektrik Teknolojisi', '2. Sınıf'],
    ['2024025', 'Nazlı', 'Lale', '2024', 'Elektrik Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Otomotiv Teknolojisi
    ['2024026', 'Oğuz', 'Mert', '2024', 'Otomotiv Teknolojisi', '1. Sınıf'],
    ['2024027', 'Pınar', 'Nur', '2024', 'Otomotiv Teknolojisi', '2. Sınıf'],
    ['2024028', 'Rıza', 'Okan', '2024', 'Otomotiv Teknolojisi', '1. Sınıf'],
    ['2024029', 'Seda', 'Peker', '2024', 'Otomotiv Teknolojisi', '2. Sınıf'],
    ['2024030', 'Tolga', 'Rüzgar', '2024', 'Otomotiv Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Gıda Teknolojisi
    ['2024031', 'Umut', 'Sarı', '2024', 'Gıda Teknolojisi', '1. Sınıf'],
    ['2024032', 'Vildan', 'Tuna', '2024', 'Gıda Teknolojisi', '2. Sınıf'],
    ['2024033', 'Yasin', 'Uçar', '2024', 'Gıda Teknolojisi', '1. Sınıf'],
    ['2024034', 'Zehra', 'Vural', '2024', 'Gıda Teknolojisi', '2. Sınıf'],
    ['2024035', 'Ali', 'Yaman', '2024', 'Gıda Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Tekstil Teknolojisi
    ['2024036', 'Betül', 'Zengin', '2024', 'Tekstil Teknolojisi', '1. Sınıf'],
    ['2024037', 'Cem', 'Akın', '2024', 'Tekstil Teknolojisi', '2. Sınıf'],
    ['2024038', 'Derya', 'Bakır', '2024', 'Tekstil Teknolojisi', '1. Sınıf'],
    ['2024039', 'Eren', 'Çelik', '2024', 'Tekstil Teknolojisi', '2. Sınıf'],
    ['2024040', 'Fatma', 'Demir', '2024', 'Tekstil Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Kimya Teknolojisi
    ['2024041', 'Gökhan', 'Erdoğan', '2024', 'Kimya Teknolojisi', '1. Sınıf'],
    ['2024042', 'Hilal', 'Fidan', '2024', 'Kimya Teknolojisi', '2. Sınıf'],
    ['2024043', 'İsmail', 'Güler', '2024', 'Kimya Teknolojisi', '1. Sınıf'],
    ['2024044', 'Jale', 'Hızır', '2024', 'Kimya Teknolojisi', '2. Sınıf'],
    ['2024045', 'Kaan', 'İpek', '2024', 'Kimya Teknolojisi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Muhasebe ve Vergi Uygulamaları
    ['2024046', 'Lale', 'Jale', '2024', 'Muhasebe ve Vergi Uygulamaları', '1. Sınıf'],
    ['2024047', 'Mert', 'Kılıç', '2024', 'Muhasebe ve Vergi Uygulamaları', '2. Sınıf'],
    ['2024048', 'Nur', 'Lale', '2024', 'Muhasebe ve Vergi Uygulamaları', '1. Sınıf'],
    ['2024049', 'Okan', 'Mert', '2024', 'Muhasebe ve Vergi Uygulamaları', '2. Sınıf'],
    ['2024050', 'Peker', 'Nur', '2024', 'Muhasebe ve Vergi Uygulamaları', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - İnsan Kaynakları Yönetimi
    ['2024051', 'Rüzgar', 'Okan', '2024', 'İnsan Kaynakları Yönetimi', '1. Sınıf'],
    ['2024052', 'Sarı', 'Peker', '2024', 'İnsan Kaynakları Yönetimi', '2. Sınıf'],
    ['2024053', 'Tuna', 'Rüzgar', '2024', 'İnsan Kaynakları Yönetimi', '1. Sınıf'],
    ['2024054', 'Uçar', 'Sarı', '2024', 'İnsan Kaynakları Yönetimi', '2. Sınıf'],
    ['2024055', 'Vural', 'Tuna', '2024', 'İnsan Kaynakları Yönetimi', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Lojistik
    ['2024056', 'Yaman', 'Uçar', '2024', 'Lojistik', '1. Sınıf'],
    ['2024057', 'Zengin', 'Vural', '2024', 'Lojistik', '2. Sınıf'],
    ['2024058', 'Akın', 'Yaman', '2024', 'Lojistik', '1. Sınıf'],
    ['2024059', 'Bakır', 'Zengin', '2024', 'Lojistik', '2. Sınıf'],
    ['2024060', 'Çelik', 'Akın', '2024', 'Lojistik', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Turizm ve Otel İşletmeciliği
    ['2024061', 'Demir', 'Bakır', '2024', 'Turizm ve Otel İşletmeciliği', '1. Sınıf'],
    ['2024062', 'Erdoğan', 'Çelik', '2024', 'Turizm ve Otel İşletmeciliği', '2. Sınıf'],
    ['2024063', 'Fidan', 'Demir', '2024', 'Turizm ve Otel İşletmeciliği', '1. Sınıf'],
    ['2024064', 'Güler', 'Erdoğan', '2024', 'Turizm ve Otel İşletmeciliği', '2. Sınıf'],
    ['2024065', 'Hızır', 'Fidan', '2024', 'Turizm ve Otel İşletmeciliği', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - Grafik Tasarım
    ['2024066', 'İpek', 'Güler', '2024', 'Grafik Tasarım', '1. Sınıf'],
    ['2024067', 'Jale', 'Hızır', '2024', 'Grafik Tasarım', '2. Sınıf'],
    ['2024068', 'Kılıç', 'İpek', '2024', 'Grafik Tasarım', '1. Sınıf'],
    ['2024069', 'Lale', 'Jale', '2024', 'Grafik Tasarım', '2. Sınıf'],
    ['2024070', 'Mert', 'Kılıç', '2024', 'Grafik Tasarım', '1. Sınıf'],
    
    // 2024 yılı öğrencileri - İç Mimarlık ve Çevre Tasarımı
    ['2024071', 'Nur', 'Lale', '2024', 'İç Mimarlık ve Çevre Tasarımı', '1. Sınıf'],
    ['2024072', 'Okan', 'Mert', '2024', 'İç Mimarlık ve Çevre Tasarımı', '2. Sınıf'],
    ['2024073', 'Peker', 'Nur', '2024', 'İç Mimarlık ve Çevre Tasarımı', '1. Sınıf'],
    ['2024074', 'Rüzgar', 'Okan', '2024', 'İç Mimarlık ve Çevre Tasarımı', '2. Sınıf'],
    ['2024075', 'Sarı', 'Peker', '2024', 'İç Mimarlık ve Çevre Tasarımı', '1. Sınıf'],
    
    // 2023 yılı öğrencileri (mezun olacaklar)
    ['2023001', 'Ahmet', 'Yılmaz', '2023', 'Bilgisayar Programcılığı', '2. Sınıf'],
    ['2023002', 'Ayşe', 'Demir', '2023', 'Elektronik Teknolojisi', '2. Sınıf'],
    ['2023003', 'Mehmet', 'Kaya', '2023', 'Makine Teknolojisi', '2. Sınıf'],
    ['2023004', 'Çağla', 'Özkan', '2023', 'İnşaat Teknolojisi', '2. Sınıf'],
    ['2023005', 'İbrahim', 'Şahin', '2023', 'Elektrik Teknolojisi', '2. Sınıf'],
    
    // 2025 yılı öğrencileri (yeni kayıtlar)
    ['2025001', 'Ümit', 'Çelik', '2025', 'Bilgisayar Programcılığı', '1. Sınıf'],
    ['2025002', 'Özlem', 'Güneş', '2025', 'Elektronik Teknolojisi', '1. Sınıf'],
    ['2025003', 'Şule', 'Işık', '2025', 'Makine Teknolojisi', '1. Sınıf'],
    ['2025004', 'Gül', 'Yıldız', '2025', 'İnşaat Teknolojisi', '1. Sınıf'],
    ['2025005', 'Emre', 'Arslan', '2025', 'Elektrik Teknolojisi', '1. Sınıf'],
];

// HTML dosyasını oluştur
$htmlFile = 'sample_students.html';
$html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Örnek Öğrenci Verileri</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e6f3ff;
        }
        .info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .download-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 5px;
        }
        .download-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Örnek Öğrenci Verileri</h1>
        
        <div class="info">
            <h3>📋 Dosya Formatı Bilgileri:</h3>
            <ul>
                <li><strong>A Sütunu:</strong> Öğrenci No (3-20 karakter, sadece harf ve rakam)</li>
                <li><strong>B Sütunu:</strong> Ad (sadece harf ve Türkçe karakterler)</li>
                <li><strong>C Sütunu:</strong> Soyad (sadece harf ve Türkçe karakterler)</li>
                <li><strong>D Sütunu:</strong> Akademik Yıl (1990-2030 arası)</li>
                <li><strong>E Sütunu:</strong> Bölüm (sadece harf ve Türkçe karakterler)</li>
                <li><strong>F Sütunu:</strong> Sınıf Durumu (sadece harf ve Türkçe karakterler)</li>
            </ul>
            <p><em>Not: B ve C sütunları birleştirilerek full_name olarak kaydedilir.</em></p>
        </div>
        
        <div>
            <a href="sample_students.csv" class="download-btn" download>📥 CSV Dosyasını İndir</a>
            <a href="sample_students.json" class="download-btn" download>📥 JSON Dosyasını İndir</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Öğrenci No</th>
                    <th>Ad</th>
                    <th>Soyad</th>
                    <th>Akademik Yıl</th>
                    <th>Bölüm</th>
                    <th>Sınıf Durumu</th>
                </tr>
            </thead>
            <tbody>';

foreach ($sampleData as $row) {
    $html .= '<tr>';
    foreach ($row as $cell) {
        $html .= '<td>' . htmlspecialchars($cell) . '</td>';
    }
    $html .= '</tr>';
}

$html .= '
            </tbody>
        </table>
        
        <div class="info">
            <h3>🎯 Kullanım Talimatları:</h3>
            <ol>
                <li>Bu tabloyu Excel\'e kopyalayın</li>
                <li>CSV dosyasını Excel\'de açın</li>
                <li>Verileri .xlsx formatında kaydedin</li>
                <li>MyOPC sistemine yükleyin</li>
            </ol>
        </div>
    </div>
</body>
</html>';

file_put_contents($htmlFile, $html);

echo "✅ HTML dosyası oluşturuldu: sample_students.html\n";
echo "📊 Toplam öğrenci sayısı: " . count($sampleData) . "\n";
echo "🌐 Dosyayı tarayıcıda açarak görüntüleyebilirsiniz.\n";
echo "📁 Dosya yolu: " . realpath($htmlFile) . "\n";
echo "💡 Bu dosyayı Excel'e kopyalayarak .xlsx formatında kaydedebilirsiniz.\n";
?>
