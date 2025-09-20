<?php
// JSON formatında örnek öğrenci verileri oluştur
$sampleData = [
    [
        'sdt_nmbr' => '2024001',
        'first_name' => 'Ahmet',
        'last_name' => 'Yılmaz',
        'academic_year' => 2024,
        'department' => 'Bilgisayar Programcılığı',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024002',
        'first_name' => 'Ayşe',
        'last_name' => 'Demir',
        'academic_year' => 2024,
        'department' => 'Bilgisayar Programcılığı',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024003',
        'first_name' => 'Mehmet',
        'last_name' => 'Kaya',
        'academic_year' => 2024,
        'department' => 'Bilgisayar Programcılığı',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024004',
        'first_name' => 'Çağla',
        'last_name' => 'Özkan',
        'academic_year' => 2024,
        'department' => 'Bilgisayar Programcılığı',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024005',
        'first_name' => 'İbrahim',
        'last_name' => 'Şahin',
        'academic_year' => 2024,
        'department' => 'Bilgisayar Programcılığı',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024006',
        'first_name' => 'Ümit',
        'last_name' => 'Çelik',
        'academic_year' => 2024,
        'department' => 'Elektronik Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024007',
        'first_name' => 'Özlem',
        'last_name' => 'Güneş',
        'academic_year' => 2024,
        'department' => 'Elektronik Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024008',
        'first_name' => 'Şule',
        'last_name' => 'Işık',
        'academic_year' => 2024,
        'department' => 'Elektronik Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024009',
        'first_name' => 'Gül',
        'last_name' => 'Yıldız',
        'academic_year' => 2024,
        'department' => 'Elektronik Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024010',
        'first_name' => 'Emre',
        'last_name' => 'Arslan',
        'academic_year' => 2024,
        'department' => 'Elektronik Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024011',
        'first_name' => 'Zeynep',
        'last_name' => 'Koç',
        'academic_year' => 2024,
        'department' => 'Makine Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024012',
        'first_name' => 'Burak',
        'last_name' => 'Öztürk',
        'academic_year' => 2024,
        'department' => 'Makine Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024013',
        'first_name' => 'Elif',
        'last_name' => 'Kurt',
        'academic_year' => 2024,
        'department' => 'Makine Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024014',
        'first_name' => 'Can',
        'last_name' => 'Yılmaz',
        'academic_year' => 2024,
        'department' => 'Makine Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024015',
        'first_name' => 'Selin',
        'last_name' => 'Aydın',
        'academic_year' => 2024,
        'department' => 'Makine Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024016',
        'first_name' => 'Deniz',
        'last_name' => 'Çakır',
        'academic_year' => 2024,
        'department' => 'İnşaat Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024017',
        'first_name' => 'Ece',
        'last_name' => 'Doğan',
        'academic_year' => 2024,
        'department' => 'İnşaat Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024018',
        'first_name' => 'Furkan',
        'last_name' => 'Erdoğan',
        'academic_year' => 2024,
        'department' => 'İnşaat Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024019',
        'first_name' => 'Gamze',
        'last_name' => 'Fidan',
        'academic_year' => 2024,
        'department' => 'İnşaat Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024020',
        'first_name' => 'Hakan',
        'last_name' => 'Güler',
        'academic_year' => 2024,
        'department' => 'İnşaat Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024021',
        'first_name' => 'İrem',
        'last_name' => 'Hızır',
        'academic_year' => 2024,
        'department' => 'Elektrik Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024022',
        'first_name' => 'Kemal',
        'last_name' => 'İpek',
        'academic_year' => 2024,
        'department' => 'Elektrik Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024023',
        'first_name' => 'Leyla',
        'last_name' => 'Jale',
        'academic_year' => 2024,
        'department' => 'Elektrik Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024024',
        'first_name' => 'Murat',
        'last_name' => 'Kılıç',
        'academic_year' => 2024,
        'department' => 'Elektrik Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024025',
        'first_name' => 'Nazlı',
        'last_name' => 'Lale',
        'academic_year' => 2024,
        'department' => 'Elektrik Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024026',
        'first_name' => 'Oğuz',
        'last_name' => 'Mert',
        'academic_year' => 2024,
        'department' => 'Otomotiv Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024027',
        'first_name' => 'Pınar',
        'last_name' => 'Nur',
        'academic_year' => 2024,
        'department' => 'Otomotiv Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024028',
        'first_name' => 'Rıza',
        'last_name' => 'Okan',
        'academic_year' => 2024,
        'department' => 'Otomotiv Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024029',
        'first_name' => 'Seda',
        'last_name' => 'Peker',
        'academic_year' => 2024,
        'department' => 'Otomotiv Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024030',
        'first_name' => 'Tolga',
        'last_name' => 'Rüzgar',
        'academic_year' => 2024,
        'department' => 'Otomotiv Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024031',
        'first_name' => 'Umut',
        'last_name' => 'Sarı',
        'academic_year' => 2024,
        'department' => 'Gıda Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024032',
        'first_name' => 'Vildan',
        'last_name' => 'Tuna',
        'academic_year' => 2024,
        'department' => 'Gıda Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024033',
        'first_name' => 'Yasin',
        'last_name' => 'Uçar',
        'academic_year' => 2024,
        'department' => 'Gıda Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024034',
        'first_name' => 'Zehra',
        'last_name' => 'Vural',
        'academic_year' => 2024,
        'department' => 'Gıda Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024035',
        'first_name' => 'Ali',
        'last_name' => 'Yaman',
        'academic_year' => 2024,
        'department' => 'Gıda Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024036',
        'first_name' => 'Betül',
        'last_name' => 'Zengin',
        'academic_year' => 2024,
        'department' => 'Tekstil Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024037',
        'first_name' => 'Cem',
        'last_name' => 'Akın',
        'academic_year' => 2024,
        'department' => 'Tekstil Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024038',
        'first_name' => 'Derya',
        'last_name' => 'Bakır',
        'academic_year' => 2024,
        'department' => 'Tekstil Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024039',
        'first_name' => 'Eren',
        'last_name' => 'Çelik',
        'academic_year' => 2024,
        'department' => 'Tekstil Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024040',
        'first_name' => 'Fatma',
        'last_name' => 'Demir',
        'academic_year' => 2024,
        'department' => 'Tekstil Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024041',
        'first_name' => 'Gökhan',
        'last_name' => 'Erdoğan',
        'academic_year' => 2024,
        'department' => 'Kimya Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024042',
        'first_name' => 'Hilal',
        'last_name' => 'Fidan',
        'academic_year' => 2024,
        'department' => 'Kimya Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024043',
        'first_name' => 'İsmail',
        'last_name' => 'Güler',
        'academic_year' => 2024,
        'department' => 'Kimya Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024044',
        'first_name' => 'Jale',
        'last_name' => 'Hızır',
        'academic_year' => 2024,
        'department' => 'Kimya Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024045',
        'first_name' => 'Kaan',
        'last_name' => 'İpek',
        'academic_year' => 2024,
        'department' => 'Kimya Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024046',
        'first_name' => 'Lale',
        'last_name' => 'Jale',
        'academic_year' => 2024,
        'department' => 'Muhasebe ve Vergi Uygulamaları',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024047',
        'first_name' => 'Mert',
        'last_name' => 'Kılıç',
        'academic_year' => 2024,
        'department' => 'Muhasebe ve Vergi Uygulamaları',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024048',
        'first_name' => 'Nur',
        'last_name' => 'Lale',
        'academic_year' => 2024,
        'department' => 'Muhasebe ve Vergi Uygulamaları',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024049',
        'first_name' => 'Okan',
        'last_name' => 'Mert',
        'academic_year' => 2024,
        'department' => 'Muhasebe ve Vergi Uygulamaları',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024050',
        'first_name' => 'Peker',
        'last_name' => 'Nur',
        'academic_year' => 2024,
        'department' => 'Muhasebe ve Vergi Uygulamaları',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024051',
        'first_name' => 'Rüzgar',
        'last_name' => 'Okan',
        'academic_year' => 2024,
        'department' => 'İnsan Kaynakları Yönetimi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024052',
        'first_name' => 'Sarı',
        'last_name' => 'Peker',
        'academic_year' => 2024,
        'department' => 'İnsan Kaynakları Yönetimi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024053',
        'first_name' => 'Tuna',
        'last_name' => 'Rüzgar',
        'academic_year' => 2024,
        'department' => 'İnsan Kaynakları Yönetimi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024054',
        'first_name' => 'Uçar',
        'last_name' => 'Sarı',
        'academic_year' => 2024,
        'department' => 'İnsan Kaynakları Yönetimi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024055',
        'first_name' => 'Vural',
        'last_name' => 'Tuna',
        'academic_year' => 2024,
        'department' => 'İnsan Kaynakları Yönetimi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024056',
        'first_name' => 'Yaman',
        'last_name' => 'Uçar',
        'academic_year' => 2024,
        'department' => 'Lojistik',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024057',
        'first_name' => 'Zengin',
        'last_name' => 'Vural',
        'academic_year' => 2024,
        'department' => 'Lojistik',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024058',
        'first_name' => 'Akın',
        'last_name' => 'Yaman',
        'academic_year' => 2024,
        'department' => 'Lojistik',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024059',
        'first_name' => 'Bakır',
        'last_name' => 'Zengin',
        'academic_year' => 2024,
        'department' => 'Lojistik',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024060',
        'first_name' => 'Çelik',
        'last_name' => 'Akın',
        'academic_year' => 2024,
        'department' => 'Lojistik',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024061',
        'first_name' => 'Demir',
        'last_name' => 'Bakır',
        'academic_year' => 2024,
        'department' => 'Turizm ve Otel İşletmeciliği',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024062',
        'first_name' => 'Erdoğan',
        'last_name' => 'Çelik',
        'academic_year' => 2024,
        'department' => 'Turizm ve Otel İşletmeciliği',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024063',
        'first_name' => 'Fidan',
        'last_name' => 'Demir',
        'academic_year' => 2024,
        'department' => 'Turizm ve Otel İşletmeciliği',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024064',
        'first_name' => 'Güler',
        'last_name' => 'Erdoğan',
        'academic_year' => 2024,
        'department' => 'Turizm ve Otel İşletmeciliği',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024065',
        'first_name' => 'Hızır',
        'last_name' => 'Fidan',
        'academic_year' => 2024,
        'department' => 'Turizm ve Otel İşletmeciliği',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024066',
        'first_name' => 'İpek',
        'last_name' => 'Güler',
        'academic_year' => 2024,
        'department' => 'Grafik Tasarım',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024067',
        'first_name' => 'Jale',
        'last_name' => 'Hızır',
        'academic_year' => 2024,
        'department' => 'Grafik Tasarım',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024068',
        'first_name' => 'Kılıç',
        'last_name' => 'İpek',
        'academic_year' => 2024,
        'department' => 'Grafik Tasarım',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024069',
        'first_name' => 'Lale',
        'last_name' => 'Jale',
        'academic_year' => 2024,
        'department' => 'Grafik Tasarım',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024070',
        'first_name' => 'Mert',
        'last_name' => 'Kılıç',
        'academic_year' => 2024,
        'department' => 'Grafik Tasarım',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024071',
        'first_name' => 'Nur',
        'last_name' => 'Lale',
        'academic_year' => 2024,
        'department' => 'İç Mimarlık ve Çevre Tasarımı',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024072',
        'first_name' => 'Okan',
        'last_name' => 'Mert',
        'academic_year' => 2024,
        'department' => 'İç Mimarlık ve Çevre Tasarımı',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024073',
        'first_name' => 'Peker',
        'last_name' => 'Nur',
        'academic_year' => 2024,
        'department' => 'İç Mimarlık ve Çevre Tasarımı',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024074',
        'first_name' => 'Rüzgar',
        'last_name' => 'Okan',
        'academic_year' => 2024,
        'department' => 'İç Mimarlık ve Çevre Tasarımı',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2024075',
        'first_name' => 'Sarı',
        'last_name' => 'Peker',
        'academic_year' => 2024,
        'department' => 'İç Mimarlık ve Çevre Tasarımı',
        'class_level' => '1. Sınıf'
    ],
    // 2023 yılı öğrencileri (mezun olacaklar)
    [
        'sdt_nmbr' => '2023001',
        'first_name' => 'Ahmet',
        'last_name' => 'Yılmaz',
        'academic_year' => 2023,
        'department' => 'Bilgisayar Programcılığı',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2023002',
        'first_name' => 'Ayşe',
        'last_name' => 'Demir',
        'academic_year' => 2023,
        'department' => 'Elektronik Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2023003',
        'first_name' => 'Mehmet',
        'last_name' => 'Kaya',
        'academic_year' => 2023,
        'department' => 'Makine Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2023004',
        'first_name' => 'Çağla',
        'last_name' => 'Özkan',
        'academic_year' => 2023,
        'department' => 'İnşaat Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    [
        'sdt_nmbr' => '2023005',
        'first_name' => 'İbrahim',
        'last_name' => 'Şahin',
        'academic_year' => 2023,
        'department' => 'Elektrik Teknolojisi',
        'class_level' => '2. Sınıf'
    ],
    // 2025 yılı öğrencileri (yeni kayıtlar)
    [
        'sdt_nmbr' => '2025001',
        'first_name' => 'Ümit',
        'last_name' => 'Çelik',
        'academic_year' => 2025,
        'department' => 'Bilgisayar Programcılığı',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2025002',
        'first_name' => 'Özlem',
        'last_name' => 'Güneş',
        'academic_year' => 2025,
        'department' => 'Elektronik Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2025003',
        'first_name' => 'Şule',
        'last_name' => 'Işık',
        'academic_year' => 2025,
        'department' => 'Makine Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2025004',
        'first_name' => 'Gül',
        'last_name' => 'Yıldız',
        'academic_year' => 2025,
        'department' => 'İnşaat Teknolojisi',
        'class_level' => '1. Sınıf'
    ],
    [
        'sdt_nmbr' => '2025005',
        'first_name' => 'Emre',
        'last_name' => 'Arslan',
        'academic_year' => 2025,
        'department' => 'Elektrik Teknolojisi',
        'class_level' => '1. Sınıf'
    ]
];

// JSON dosyasını oluştur
$jsonFile = 'sample_students.json';
$jsonData = [
    'metadata' => [
        'title' => 'Örnek Öğrenci Verileri',
        'description' => 'MyOPC sistemi için hazırlanmış örnek öğrenci verileri',
        'version' => '1.0',
        'created_at' => date('Y-m-d H:i:s'),
        'total_students' => count($sampleData),
        'format_info' => [
            'sdt_nmbr' => 'Öğrenci numarası (3-20 karakter, sadece harf ve rakam)',
            'first_name' => 'Ad (sadece harf ve Türkçe karakterler)',
            'last_name' => 'Soyad (sadece harf ve Türkçe karakterler)',
            'academic_year' => 'Akademik yıl (1990-2030 arası)',
            'department' => 'Bölüm (sadece harf ve Türkçe karakterler)',
            'class_level' => 'Sınıf durumu (sadece harf ve Türkçe karakterler)'
        ]
    ],
    'students' => $sampleData
];

file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ JSON dosyası oluşturuldu: sample_students.json\n";
echo "📊 Toplam öğrenci sayısı: " . count($sampleData) . "\n";
echo "📋 Dosya formatı:\n";
echo "   - sdt_nmbr: Öğrenci numarası (3-20 karakter, sadece harf ve rakam)\n";
echo "   - first_name: Ad (sadece harf ve Türkçe karakterler)\n";
echo "   - last_name: Soyad (sadece harf ve Türkçe karakterler)\n";
echo "   - academic_year: Akademik yıl (1990-2030 arası)\n";
echo "   - department: Bölüm (sadece harf ve Türkçe karakterler)\n";
echo "   - class_level: Sınıf durumu (sadece harf ve Türkçe karakterler)\n";
echo "\n🎯 Bu dosya tüm validasyon kurallarına uygun olarak hazırlanmıştır.\n";
echo "💡 Bu JSON dosyasını Excel'e import edebilir veya programatik olarak kullanabilirsiniz.\n";
echo "📁 Dosya yolu: " . realpath($jsonFile) . "\n";
?>
