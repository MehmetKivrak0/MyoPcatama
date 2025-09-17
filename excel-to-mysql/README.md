# Excel'den MySQL'e Öğrenci Veri Aktarımı

Bu sistem Excel dosyalarından öğrenci verilerini MySQL veritabanına aktarmak için geliştirilmiştir.

## Kurulum

1. Composer ile gerekli kütüphaneleri yükleyin:
```bash
composer install
```

2. Excel şablon dosyasını oluşturun:
```bash
php create_template.php
```

## Kullanım

1. `import.php` dosyasını tarayıcıda açın
2. Excel şablonunu indirin ve doldurun
3. Doldurulmuş Excel dosyasını yükleyin
4. "Verileri İçe Aktar" butonuna tıklayın

## Excel Dosyası Formatı

| Sütun | Açıklama | Zorunlu |
|-------|----------|---------|
| A | Öğrenci Numarası | Evet |
| B | Ad | Evet |
| C | Soyad | Evet |
| D | E-posta | Hayır |
| E | Telefon | Hayır |
| F | Yıl | Hayır (boş bırakılırsa mevcut yıl kullanılır) |

## Özellikler

- Excel dosyası doğrulama
- Hata raporlama
- Toplu veri aktarımı
- Mevcut veritabanı yapısına uyumlu
- Türkçe arayüz

## Gereksinimler

- PHP 7.4+
- MySQL 5.7+
- PhpSpreadsheet kütüphanesi
- PDO MySQL extension
