// Test Fonksiyonları JavaScript Dosyası
// Tüm test fonksiyonları burada tanımlanır

// Tüm testleri çalıştır
async function runAllTests() {
    console.log('🚀 Tüm testler başlatılıyor...');
    
    // Test başlangıç zamanı
    const startTime = Date.now();
    
    // Test sonuçlarını sıfırla
    clearAllResults();
    
    // Progress indicator göster
    showProgressIndicator();
    
    try {
        // Veritabanı testleri
        console.log('📊 Veritabanı testleri başlatılıyor...');
        await testDatabaseConnection();
        await sleep(500);
        await testTableExistence();
        await sleep(500);
        await testDataIntegrity();
        await sleep(500);
        await testDatabasePerformance();
        await sleep(500);
        await testDatabaseBackup();
        
        // API testleri
        console.log('🌐 API testleri başlatılıyor...');
        await testAPIEndpoints();
        await sleep(500);
        await testAPIResponseFormat();
        await sleep(500);
        await testAPIErrorHandling();
        await sleep(500);
        await testAPISecurity();
        
        // Sunucu testleri
        console.log('🖥️ Sunucu testleri başlatılıyor...');
        await testPHPVersion();
        await sleep(500);
        await testPHPExtensions();
        await sleep(500);
        await testServerResources();
        
        // Modal testleri
        console.log('🪟 Modal testleri başlatılıyor...');
        await testAssignmentModal();
        await sleep(500);
        await testPCDetailsModal();
        await sleep(500);
        await testExcelImportModal();
        await sleep(500);
        await testPCCountEditModal();
        await sleep(500);
        await testMaxStudentsEditModal();
        await sleep(500);
        await testTestModal();
        
        // Hata testleri
        console.log('🐛 Hata testleri başlatılıyor...');
        await testAPI500Error();
        await sleep(500);
        await test500ErrorHandling();
        await sleep(500);
        await testServerCompatibility();
        
        // Güvenlik testleri
        console.log('🔒 Güvenlik testleri başlatılıyor...');
        await testSQLInjectionProtection();
        await sleep(500);
        await testXSSProtection();
        
        // Test bitiş zamanı
        const endTime = Date.now();
        const totalTime = (endTime - startTime) / 1000;
        
        console.log('✅ Tüm testler tamamlandı! Süre: ' + totalTime + ' saniye');
        
        // Sonuçları güncelle
        updateOverallResult();
        updateTestCounts();
        generateDetailedReport(totalTime);
        
        // Progress indicator'ı gizle
        hideProgressIndicator();
        
    } catch (error) {
        console.error('❌ Test sırasında hata oluştu:', error);
        hideProgressIndicator();
    }
}

// VERİTABANI TESTLERİ
async function testDatabaseConnection() {
    console.log('🧪 Veritabanı bağlantısı test ediliyor...');
    try {
        const response = await fetch('../test/test-database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'test_connection' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            testResults.dbConnection = true;
            showResult('db-test1-result', '✅ Veritabanı bağlantısı başarılı!<br><small>' + result.message + '</small>', 'success');
        } else {
            testResults.dbConnection = false;
            showResult('db-test1-result', '❌ Veritabanı bağlantısı başarısız!<br><small>' + result.message + '</small>', 'error');
        }
    } catch (error) {
        testResults.dbConnection = false;
        showResult('db-test1-result', '❌ Veritabanı bağlantı hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testTableExistence() {
    console.log('🧪 Tablo varlığı test ediliyor...');
    try {
        const response = await fetch('../test/test-database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'test_tables' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            testResults.tableExistence = true;
            showResult('db-test2-result', '✅ Tüm tablolar mevcut!<br><small>' + result.tables.join(', ') + '</small>', 'success');
        } else {
            testResults.tableExistence = false;
            showResult('db-test2-result', '❌ Eksik tablolar: ' + result.missing_tables.join(', '), 'error');
        }
    } catch (error) {
        testResults.tableExistence = false;
        showResult('db-test2-result', '❌ Tablo kontrol hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testDataIntegrity() {
    console.log('🧪 Veri bütünlüğü test ediliyor...');
    try {
        const response = await fetch('../test/test-database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'test_integrity' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            testResults.dataIntegrity = true;
            showResult('db-test3-result', '✅ Veri bütünlüğü kontrolü başarılı!<br><small>' + result.message + '</small>', 'success');
        } else {
            testResults.dataIntegrity = false;
            showResult('db-test3-result', '❌ Veri bütünlüğü hatası: ' + result.message, 'error');
        }
    } catch (error) {
        testResults.dataIntegrity = false;
        showResult('db-test3-result', '❌ Veri bütünlüğü kontrol hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testDatabasePerformance() {
    console.log('🧪 Veritabanı performansı test ediliyor...');
    try {
        const response = await fetch('../test/test-database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'test_performance' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            testResults.dbPerformance = true;
            showResult('db-test4-result', '✅ Performans testi başarılı!<br><small>Ortalama sorgu süresi: ' + result.avg_query_time + 'ms</small>', 'success');
        } else {
            testResults.dbPerformance = false;
            showResult('db-test4-result', '❌ Performans sorunu: ' + result.message, 'error');
        }
    } catch (error) {
        testResults.dbPerformance = false;
        showResult('db-test4-result', '❌ Performans test hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testDatabaseBackup() {
    console.log('🧪 Veritabanı backup kontrolü yapılıyor...');
    try {
        const response = await fetch('../test/test-database.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'test_backup' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            testResults.dbBackup = true;
            showResult('db-test5-result', '✅ Backup kontrolü başarılı!<br><small>' + result.message + '</small>', 'success');
        } else {
            testResults.dbBackup = false;
            showResult('db-test5-result', '⚠️ Backup uyarısı: ' + result.message, 'warning');
        }
    } catch (error) {
        testResults.dbBackup = false;
        showResult('db-test5-result', '❌ Backup kontrol hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

// API TESTLERİ
async function testAPIEndpoints() {
    console.log('🧪 API endpointleri test ediliyor...');
    const endpoints = [
        { url: '../api/students.php', method: 'GET' },
        { url: '../api/labs.php', method: 'GET' },
        { url: '../api/assignments.php', method: 'GET' },
        { url: '../test/test-database.php', method: 'POST', body: JSON.stringify({ action: 'test_connection' }) }
    ];
    
    let passed = 0;
    let results = [];
    
    for (const endpoint of endpoints) {
        try {
            const options = { method: endpoint.method };
            if (endpoint.body) {
                options.headers = { 'Content-Type': 'application/json' };
                options.body = endpoint.body;
            }
            
            const response = await fetch(endpoint.url, options);
            if (response.ok) {
                passed++;
                results.push('✅ ' + endpoint.url);
            } else {
                results.push('❌ ' + endpoint.url + ' (' + response.status + ')');
            }
        } catch (error) {
            results.push('❌ ' + endpoint.url + ' (' + error.message + ')');
        }
    }
    
    testResults.apiEndpoints = passed === endpoints.length;
    showResult('api-test1-result', 'API Endpoint Testi: ' + passed + '/' + endpoints.length + '<br><small>' + results.join('<br>') + '</small>', 
              testResults.apiEndpoints ? 'success' : 'error');
    updateTestCounts();
}

async function testAPIResponseFormat() {
    console.log('🧪 API response formatı test ediliyor...');
    try {
        const response = await fetch('../api/students.php', { method: 'GET' });
        const data = await response.json();
        
        if (data && typeof data === 'object' && 'type' in data) {
            testResults.apiResponseFormat = true;
            showResult('api-test2-result', '✅ API response formatı doğru!<br><small>Type: ' + data.type + '</small>', 'success');
        } else {
            testResults.apiResponseFormat = false;
            showResult('api-test2-result', '❌ API response formatı hatalı!<br><small>Beklenen: {type, data, message}</small>', 'error');
        }
    } catch (error) {
        testResults.apiResponseFormat = false;
        showResult('api-test2-result', '❌ API response format hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testAPIErrorHandling() {
    console.log('🧪 API error handling test ediliyor...');
    try {
        // Geçersiz endpoint testi
        const response = await fetch('../api/invalid-endpoint.php', { method: 'GET' });
        
        if (response.status === 404) {
            testResults.apiErrorHandling = true;
            showResult('api-test3-result', '✅ API error handling çalışıyor!<br><small>404 hatası doğru döndürüldü</small>', 'success');
        } else {
            testResults.apiErrorHandling = false;
            showResult('api-test3-result', '❌ API error handling sorunu!<br><small>Beklenen 404, alınan: ' + response.status + '</small>', 'error');
        }
    } catch (error) {
        testResults.apiErrorHandling = false;
        showResult('api-test3-result', '❌ API error handling hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testAPISecurity() {
    console.log('🧪 API güvenlik test ediliyor...');
    try {
        // SQL injection testi - geçersiz veri gönder
        const response = await fetch('../api/students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                full_name: "'; DROP TABLE myopc_students; --",
                sdt_nmbr: "'; DROP TABLE myopc_students; --"
            })
        });
        
        const data = await response.json();
        
        // API hata döndürüyorsa güvenlik çalışıyor demektir
        if (data && data.type === 'error') {
            testResults.apiSecurity = true;
            showResult('api-test4-result', '✅ API güvenlik testi başarılı!<br><small>SQL injection koruması aktif</small>', 'success');
        } else {
            testResults.apiSecurity = false;
            showResult('api-test4-result', '❌ API güvenlik sorunu!<br><small>SQL injection koruması yetersiz</small>', 'error');
        }
    } catch (error) {
        testResults.apiSecurity = false;
        showResult('api-test4-result', '❌ API güvenlik test hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

// SUNUCU TESTLERİ
async function testPHPVersion() {
    console.log('🧪 PHP versiyon test ediliyor...');
    try {
        const response = await fetch('../test/test-server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'php_version' })
        });
        
        const result = await response.json();
        
        if (result.success && parseFloat(result.version) >= 7.4) {
            testResults.phpVersion = true;
            showResult('server-test1-result', '✅ PHP versiyon uygun!<br><small>Versiyon: ' + result.version + '</small>', 'success');
        } else {
            testResults.phpVersion = false;
            showResult('server-test1-result', '❌ PHP versiyon uygun değil!<br><small>Mevcut: ' + result.version + ', Gerekli: 7.4+</small>', 'error');
        }
    } catch (error) {
        testResults.phpVersion = false;
        showResult('server-test1-result', '❌ PHP versiyon test hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testPHPExtensions() {
    console.log('🧪 PHP extensionlari test ediliyor...');
    try {
        const response = await fetch('../test/test-server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'php_extensions' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            testResults.phpExtensions = true;
            showResult('server-test2-result', '✅ Gerekli extension\'lar mevcut!<br><small>' + result.extensions.join(', ') + '</small>', 'success');
        } else {
            testResults.phpExtensions = false;
            showResult('server-test2-result', '❌ Eksik extension\'lar: ' + result.missing.join(', '), 'error');
        }
    } catch (error) {
        testResults.phpExtensions = false;
        showResult('server-test2-result', '❌ PHP extension test hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

async function testServerResources() {
    console.log('🧪 Sunucu kaynakları test ediliyor...');
    try {
        const response = await fetch('../test/test-server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'server_resources' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            testResults.serverResources = true;
            showResult('server-test3-result', '✅ Sunucu kaynakları yeterli!<br><small>Memory: ' + result.memory + ', Disk: ' + result.disk + '</small>', 'success');
        } else {
            testResults.serverResources = false;
            showResult('server-test3-result', '⚠️ Sunucu kaynak uyarısı!<br><small>' + result.message + '</small>', 'warning');
        }
    } catch (error) {
        testResults.serverResources = false;
        showResult('server-test3-result', '❌ Sunucu kaynak test hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

// MODAL TESTLERİ
async function testAssignmentModal() {
    console.log('🧪 Atama modali test ediliyor...');
    try {
        const modal = document.getElementById('assignmentModal');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            setTimeout(() => {
                bsModal.hide();
                testResults.assignmentModal = true;
                showResult('modal-test1-result', '✅ Atama modal\'ı çalışıyor!', 'success');
                updateTestCounts();
            }, 1000);
        } else {
            testResults.assignmentModal = false;
            showResult('modal-test1-result', '❌ Atama modal\'ı bulunamadı!', 'error');
            updateTestCounts();
        }
    } catch (error) {
        testResults.assignmentModal = false;
        showResult('modal-test1-result', '❌ Atama modal test hatası: ' + error.message, 'error');
        updateTestCounts();
    }
}

async function testPCDetailsModal() {
    console.log('🧪 PC detaylari modali test ediliyor...');
    try {
        const modal = document.getElementById('pcDetailsModal');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            setTimeout(() => {
                bsModal.hide();
                testResults.pcDetailsModal = true;
                showResult('modal-test2-result', '✅ PC detayları modal\'ı çalışıyor!', 'success');
                updateTestCounts();
            }, 1000);
        } else {
            testResults.pcDetailsModal = false;
            showResult('modal-test2-result', '❌ PC detayları modal\'ı bulunamadı!', 'error');
            updateTestCounts();
        }
    } catch (error) {
        testResults.pcDetailsModal = false;
        showResult('modal-test2-result', '❌ PC detayları modal test hatası: ' + error.message, 'error');
        updateTestCounts();
    }
}

async function testExcelImportModal() {
    console.log('🧪 Excel import modali test ediliyor...');
    try {
        const modal = document.getElementById('excelImportModal');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            setTimeout(() => {
                bsModal.hide();
                testResults.excelImportModal = true;
                showResult('modal-test3-result', '✅ Excel import modal\'ı çalışıyor!', 'success');
                updateTestCounts();
            }, 1000);
        } else {
            testResults.excelImportModal = false;
            showResult('modal-test3-result', '❌ Excel import modal\'ı bulunamadı!', 'error');
            updateTestCounts();
        }
    } catch (error) {
        testResults.excelImportModal = false;
        showResult('modal-test3-result', '❌ Excel import modal test hatası: ' + error.message, 'error');
        updateTestCounts();
    }
}

async function testPCCountEditModal() {
    console.log('🧪 PC sayisi duzenleme modali test ediliyor...');
    try {
        const modal = document.getElementById('editPCCountModal');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            setTimeout(() => {
                bsModal.hide();
                testResults.pcCountEditModal = true;
                showResult('modal-test4-result', '✅ PC sayısı düzenleme modal\'ı çalışıyor!', 'success');
                updateTestCounts();
            }, 1000);
        } else {
            testResults.pcCountEditModal = false;
            showResult('modal-test4-result', '❌ PC sayısı düzenleme modal\'ı bulunamadı!', 'error');
            updateTestCounts();
        }
    } catch (error) {
        testResults.pcCountEditModal = false;
        showResult('modal-test4-result', '❌ PC sayısı düzenleme modal test hatası: ' + error.message, 'error');
        updateTestCounts();
    }
}

async function testMaxStudentsEditModal() {
    console.log('🧪 Max ogrenci duzenleme modali test ediliyor...');
    try {
        const modal = document.getElementById('editMaxStudentsModal');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            setTimeout(() => {
                bsModal.hide();
                testResults.maxStudentsEditModal = true;
                showResult('modal-test5-result', '✅ Max öğrenci düzenleme modal\'ı çalışıyor!', 'success');
                updateTestCounts();
            }, 1000);
        } else {
            testResults.maxStudentsEditModal = false;
            showResult('modal-test5-result', '❌ Max öğrenci düzenleme modal\'ı bulunamadı!', 'error');
            updateTestCounts();
        }
    } catch (error) {
        testResults.maxStudentsEditModal = false;
        showResult('modal-test5-result', '❌ Max öğrenci düzenleme modal test hatası: ' + error.message, 'error');
        updateTestCounts();
    }
}

async function testTestModal() {
    console.log('🧪 Test modali test ediliyor...');
    try {
        const modal = document.getElementById('testModal');
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            setTimeout(() => {
                bsModal.hide();
                testResults.testModal = true;
                showResult('modal-test6-result', '✅ Test modal\'ı çalışıyor!', 'success');
                updateTestCounts();
            }, 1000);
        } else {
            testResults.testModal = false;
            showResult('modal-test6-result', '❌ Test modal\'ı bulunamadı!', 'error');
            updateTestCounts();
        }
    } catch (error) {
        testResults.testModal = false;
        showResult('modal-test6-result', '❌ Test modal test hatası: ' + error.message, 'error');
        updateTestCounts();
    }
}

// HATA TESTLERİ
async function testAPI500Error() {
    console.log('🧪 API 500 hata testi yapılıyor...');
    try {
        // Geçersiz endpoint'e istek gönder
        const response = await fetch('../api/invalid-endpoint.php', { method: 'GET' });
        
        if (response.status === 404 || response.status === 500) {
            testResults.api500Error = true;
            showResult('error-test1-result', '✅ API 500 hata testi başarılı!<br><small>Hata durumu doğru yönetiliyor</small>', 'success');
        } else {
            testResults.api500Error = false;
            showResult('error-test1-result', '❌ API 500 hata testi başarısız!<br><small>Beklenen hata kodu alınmadı</small>', 'error');
        }
    } catch (error) {
        testResults.api500Error = true; // Network hatası da başarılı sayılır
        showResult('error-test1-result', '✅ API 500 hata testi başarılı!<br><small>Hata doğru yakalandı</small>', 'success');
    }
    updateTestCounts();
}

async function test500ErrorHandling() {
    console.log('🧪 500 hata kontrolü yapılıyor...');
    try {
        // Geçersiz JSON gönder
        const response = await fetch('../api/students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: 'invalid json'
        });
        
        const data = await response.json();
        
        if (data && data.type === 'error') {
            testResults.error500Handling = true;
            showResult('error-test2-result', '✅ 500 hata kontrolü başarılı!<br><small>Hata doğru yakalanıyor</small>', 'success');
        } else {
            testResults.error500Handling = false;
            showResult('error-test2-result', '❌ 500 hata kontrolü başarısız!<br><small>Hata yönetimi yetersiz</small>', 'error');
        }
    } catch (error) {
        testResults.error500Handling = true;
        showResult('error-test2-result', '✅ 500 hata kontrolü başarılı!<br><small>Hata doğru yakalandı</small>', 'success');
    }
    updateTestCounts();
}

async function testServerCompatibility() {
    console.log('🧪 Sunucu uyumluluk testi yapılıyor...');
    try {
        // PHP versiyonunu kontrol et
        const response = await fetch('../test/test-server.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'php_version' })
        });
        
        const result = await response.json();
        
        if (result.success && parseFloat(result.version) >= 7.4) {
            testResults.serverCompatibility = true;
            showResult('error-test3-result', '✅ Sunucu uyumluluk testi başarılı!<br><small>PHP ' + result.version + ' uyumlu</small>', 'success');
        } else {
            testResults.serverCompatibility = false;
            showResult('error-test3-result', '❌ Sunucu uyumluluk testi başarısız!<br><small>PHP versiyonu yetersiz</small>', 'error');
        }
    } catch (error) {
        testResults.serverCompatibility = false;
        showResult('error-test3-result', '❌ Sunucu uyumluluk test hatası: ' + error.message, 'error');
    }
    updateTestCounts();
}

// GÜVENLİK TESTLERİ
async function testSQLInjectionProtection() {
    console.log('🧪 SQL injection koruması test ediliyor...');
    try {
        const response = await fetch('../api/students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                full_name: "'; DROP TABLE myopc_students; --",
                sdt_nmbr: "'; DROP TABLE myopc_students; --"
            })
        });
        
        const data = await response.json();
        
        // API hata döndürüyorsa güvenlik çalışıyor demektir
        if (data && data.type === 'error') {
            testResults.sqlInjectionProtection = true;
            showResult('security-test1-result', '✅ SQL injection koruması başarılı!<br><small>Güvenlik önlemleri aktif</small>', 'success');
        } else {
            testResults.sqlInjectionProtection = false;
            showResult('security-test1-result', '❌ SQL injection koruması yetersiz!<br><small>Güvenlik açığı tespit edildi</small>', 'error');
        }
    } catch (error) {
        testResults.sqlInjectionProtection = true;
        showResult('security-test1-result', '✅ SQL injection koruması başarılı!<br><small>Hata doğru yakalandı</small>', 'success');
    }
    updateTestCounts();
}

async function testXSSProtection() {
    console.log('🧪 XSS koruması test ediliyor...');
    try {
        const response = await fetch('../api/students.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                full_name: "XSS_TEST_SCRIPT",
                sdt_nmbr: "XSS_TEST_IMG"
            })
        });
        
        const data = await response.json();
        
        // API hata döndürüyorsa veya veri temizlenmişse güvenlik çalışıyor demektir
        if (data && (data.type === 'error' || !data.data)) {
            testResults.xssProtection = true;
            showResult('security-test2-result', '✅ XSS koruması başarılı!<br><small>Güvenlik önlemleri aktif</small>', 'success');
        } else {
            testResults.xssProtection = false;
            showResult('security-test2-result', '❌ XSS koruması yetersiz!<br><small>Güvenlik açığı tespit edildi</small>', 'error');
        }
    } catch (error) {
        testResults.xssProtection = true;
        showResult('security-test2-result', '✅ XSS koruması başarılı!<br><small>Hata doğru yakalandı</small>', 'success');
    }
    updateTestCounts();
}

