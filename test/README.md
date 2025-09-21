# Test Sistemi

Bu klasör, Öğrenci Atama Sistemi'nin kapsamlı test dosyalarını içerir. **500 hata testleri ve gelişmiş hata yönetimi** özelliklerini içerir.

## Dosyalar

### test-database.php
Veritabanı testlerini gerçekleştiren API dosyası.

**Temel Testler:**
- Veritabanı bağlantısı kontrolü
- Tablo varlığı kontrolü
- Veri bütünlüğü kontrolü
- Performans testi
- Backup kontrolü

**500 Hata Testleri:**
- `test_database_errors`: Veritabanı hata testleri
- `test_connection_errors`: Bağlantı hata testleri
- `test_query_errors`: Sorgu hata testleri

### test-server.php
Sunucu testlerini gerçekleştiren API dosyası.

**Temel Testler:**
- PHP versiyon kontrolü
- PHP extension kontrolü
- Sunucu kaynak kontrolü

**500 Hata Testleri:**
- `test_500_error`: Kapsamlı 500 hata testleri
- `test_error_handling`: Hata yönetimi testleri
- `simulate_server_error`: Sunucu hata simülasyonu

### test-runner.php ⭐ YENİ
Kapsamlı test çalıştırıcısı - Tüm hata testlerini koordine eder.

**Test Türleri:**
- `all_500_tests`: Tüm 500 hata testleri
- `all_error_handling_tests`: Tüm hata yönetimi testleri
- `comprehensive_test`: Kapsamlı test paketi
- `stress_test`: Stres testleri

## 500 Hata Testleri

### Sunucu Hata Testleri
- **Memory Limit Testi**: Bellek limit aşımı testi
- **Division by Zero**: Sıfıra bölme hatası testi
- **Undefined Function**: Tanımsız fonksiyon hatası testi
- **File Not Found**: Dosya bulunamama hatası testi
- **JSON Decode Error**: JSON parse hatası testi

### Veritabanı Hata Testleri
- **Invalid SQL**: Geçersiz SQL sorgusu testi
- **Non-existent Table**: Olmayan tablo testi
- **Non-existent Column**: Olmayan sütun testi
- **SQL Injection**: SQL injection koruması testi
- **Transaction Rollback**: Transaction rollback testi

### Bağlantı Hata Testleri
- **Invalid Host**: Geçersiz host testi
- **Invalid Credentials**: Geçersiz kimlik bilgileri testi
- **Invalid Database**: Geçersiz veritabanı testi
- **Connection Timeout**: Bağlantı timeout testi

### Sorgu Hata Testleri
- **Syntax Error**: SQL syntax hatası testi
- **Invalid Data Type**: Geçersiz veri türü testi
- **Constraint Violation**: Constraint ihlali testi
- **Deadlock Simulation**: Deadlock simülasyonu
- **Query Timeout**: Sorgu timeout testi

## Hata Yönetimi Özellikleri

### Gelişmiş Error Handling
- **Try-Catch Blokları**: Kapsamlı exception handling
- **Error Reporting**: Detaylı hata raporlama
- **Error Logging**: Hata loglama sistemi
- **HTTP Status Codes**: Doğru HTTP durum kodları
- **Memory Monitoring**: Bellek kullanım izleme

### Hata Simülasyonu
- **Memory Limit**: Bellek limit aşımı simülasyonu
- **Timeout**: Zaman aşımı simülasyonu
- **Database Error**: Veritabanı hatası simülasyonu
- **File Permission**: Dosya izin hatası simülasyonu
- **Syntax Error**: Syntax hatası simülasyonu

## Kullanım

### Temel Kullanım
```bash
# 500 hata testleri
curl -X POST http://localhost/myopc/test/test-server.php \
  -H "Content-Type: application/json" \
  -d '{"action": "test_500_error"}'

# Veritabanı hata testleri
curl -X POST http://localhost/myopc/test/test-database.php \
  -H "Content-Type: application/json" \
  -d '{"action": "test_database_errors"}'
```

### Kapsamlı Test Çalıştırma
```bash
# Tüm 500 hata testleri
curl -X POST http://localhost/myopc/test/test-runner.php \
  -H "Content-Type: application/json" \
  -d '{"test_type": "all_500_tests"}'

# Kapsamlı test paketi
curl -X POST http://localhost/myopc/test/test-runner.php \
  -H "Content-Type: application/json" \
  -d '{"test_type": "comprehensive_test"}'
```

## API Endpoints

### POST /test/test-database.php
**Temel Testler:**
- `test_connection`, `test_tables`, `test_integrity`, `test_performance`, `test_backup`

**500 Hata Testleri:**
- `test_database_errors`, `test_connection_errors`, `test_query_errors`

### POST /test/test-server.php
**Temel Testler:**
- `php_version`, `php_extensions`, `server_resources`

**500 Hata Testleri:**
- `test_500_error`, `test_error_handling`, `simulate_server_error`

### POST /test/test-runner.php
**Test Türleri:**
- `all_500_tests`, `all_error_handling_tests`, `comprehensive_test`, `stress_test`

## Güvenlik

- Sadece POST istekleri kabul edilir
- CORS başlıkları ayarlanmıştır
- JSON formatında yanıt döner
- **Gelişmiş hata güvenliği**: Hata detayları güvenli şekilde loglanır
- **HTTP Status Code Validation**: Doğru HTTP durum kodları döner

## Hata Raporlama

Tüm testler detaylı hata raporları döner:
- **Hata türü**: Exception/Error sınıfı
- **Hata dosyası**: Hatanın oluştuğu dosya
- **Hata satırı**: Hatanın oluştuğu satır
- **HTTP durum kodu**: Uygun HTTP durum kodu
- **Detaylı mesaj**: Açıklayıcı hata mesajı
