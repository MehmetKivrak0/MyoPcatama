# Test Sistemi

Bu klasör, Öğrenci Atama Sistemi'nin test dosyalarını içerir.

## Dosyalar

### test-database.php
Veritabanı testlerini gerçekleştiren API dosyası.

**Testler:**
- Veritabanı bağlantısı kontrolü
- Tablo varlığı kontrolü
- Veri bütünlüğü kontrolü
- Performans testi
- Backup kontrolü

### test-server.php
Sunucu testlerini gerçekleştiren API dosyası.

**Testler:**
- PHP versiyon kontrolü
- PHP extension kontrolü
- Sunucu kaynak kontrolü

## Kullanım

Bu testler, dashboard.php sayfasındaki "Atama Testi" butonuna tıklayarak çalıştırılabilir.

## API Endpoints

### POST /test/test-database.php
Veritabanı testlerini çalıştırır.

**Parametreler:**
- `action`: Test türü (test_connection, test_tables, test_integrity, test_performance, test_backup)

### POST /test/test-server.php
Sunucu testlerini çalıştırır.

**Parametreler:**
- `action`: Test türü (php_version, php_extensions, server_resources)

## Güvenlik

- Sadece POST istekleri kabul edilir
- CORS başlıkları ayarlanmıştır
- JSON formatında yanıt döner
