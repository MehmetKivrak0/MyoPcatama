// Test Sistemi JavaScript Dosyası
// Dashboard.php'den ayrılan test fonksiyonları

// Test verileri
let testResults = {
    // Veritabanı testleri
    dbConnection: false,
    tableExistence: false,
    dataIntegrity: false,
    dbPerformance: false,
    dbBackup: false,
    
    // API testleri
    apiEndpoints: false,
    apiResponseFormat: false,
    apiErrorHandling: false,
    apiSecurity: false,
    
    // Sunucu testleri
    phpVersion: false,
    phpExtensions: false,
    serverResources: false,
    
    // Modal testleri
    assignmentModal: false,
    pcDetailsModal: false,
    excelImportModal: false,
    pcCountEditModal: false,
    maxStudentsEditModal: false,
    testModal: false,
    
    // Hata testleri
    api500Error: false,
    error500Handling: false,
    serverCompatibility: false,
    
    // Güvenlik testleri
    sqlInjectionProtection: false,
    xssProtection: false
};

// Test sayfasını aç
function openTestPage() {
    console.log('🧪 Test sayfası açılıyor...');
    try {
        loadTestContent();
        const modal = new bootstrap.Modal(document.getElementById('testModal'));
        modal.show();
        console.log('✅ Test modal başarıyla açıldı');
    } catch (error) {
        console.error('❌ Test modal açılırken hata:', error);
        alert('Test modal açılırken hata oluştu: ' + error.message);
    }
}

// Test içeriğini yükle
function loadTestContent() {
    const testContent = document.getElementById('testContent');
    testContent.innerHTML = `
        <div style="padding: 20px; font-family: Arial, sans-serif;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; margin: -20px -20px 20px -20px;">
                <h2><i class="fas fa-bug me-2"></i>Kapsamlı Atama Sistemi Test Sayfası</h2>
                <p>Veritabanı, API, sunucu uyumluluğu ve tüm modalların test edildiği kapsamlı test sayfası</p>
            </div>
            
            <!-- Test Kategorileri -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-database fa-2x text-primary mb-2"></i>
                            <h6>Veritabanı Testleri</h6>
                            <span class="badge bg-primary" id="db-test-count">0/5</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-globe fa-2x text-success mb-2"></i>
                            <h6>API Testleri</h6>
                            <span class="badge bg-success" id="api-test-count">0/4</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-server fa-2x text-warning mb-2"></i>
                            <h6>Sunucu Testleri</h6>
                            <span class="badge bg-warning" id="server-test-count">0/3</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-window-maximize fa-2x text-info mb-2"></i>
                            <h6>Modal Testleri</h6>
                            <span class="badge bg-info" id="modal-test-count">0/6</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-bug fa-2x text-danger mb-2"></i>
                            <h6>Hata Testleri</h6>
                            <span class="badge bg-danger" id="error-test-count">0/3</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-shield-alt fa-2x text-dark mb-2"></i>
                            <h6>Güvenlik Testleri</h6>
                            <span class="badge bg-dark" id="security-test-count">0/2</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tüm Testleri Çalıştır Butonu -->
            <div class="text-center mb-4">
                <button class="btn btn-primary btn-lg" onclick="runAllTests()">
                    <i class="fas fa-play me-2"></i>Tüm Testleri Çalıştır
                </button>
                <button class="btn btn-secondary btn-lg ms-2" onclick="clearAllResults()">
                    <i class="fas fa-trash me-2"></i>Sonuçları Temizle
                </button>
                <button class="btn btn-success btn-lg ms-2" onclick="exportTestResults()" id="exportTestBtn" disabled>
                    <i class="fas fa-download me-2"></i>Test Sonuçlarını Export Et
                </button>
            </div>

            <!-- Test içerikleri buraya gelecek -->
            <div id="test-sections-container">
                <!-- Test bölümleri dinamik olarak yüklenecek -->
            </div>
        </div>
    `;
    
    // Test bölümlerini yükle
    loadTestSections();
}

// Test bölümlerini yükle
function loadTestSections() {
    const container = document.getElementById('test-sections-container');
    container.innerHTML = getTestSectionsHTML();
}

// Test bölümlerinin HTML'ini döndür
function getTestSectionsHTML() {
    return `
        <!-- VERİTABANI TESTLERİ -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-database me-2"></i>Veritabanı Testleri</h4>
            </div>
            <div class="card-body">
                <!-- Test 1: Veritabanı Bağlantısı -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-plug me-2"></i>Veritabanı Bağlantısı</h6>
                            <small class="text-muted">MySQL veritabanı bağlantısının kontrolü</small>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="testDatabaseConnection()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="db-test1-result" class="mt-2"></div>
                </div>

                <!-- Test 2: Tablo Varlığı -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-table me-2"></i>Tablo Varlığı Kontrolü</h6>
                            <small class="text-muted">Gerekli tabloların varlığının kontrolü</small>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="testTableExistence()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="db-test2-result" class="mt-2"></div>
                </div>

                <!-- Test 3: Veri Bütünlüğü -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-shield-alt me-2"></i>Veri Bütünlüğü</h6>
                            <small class="text-muted">Foreign key ve constraint kontrolü</small>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="testDataIntegrity()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="db-test3-result" class="mt-2"></div>
                </div>

                <!-- Test 4: Performans -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-tachometer-alt me-2"></i>Performans Testi</h6>
                            <small class="text-muted">Sorgu performansının kontrolü</small>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="testDatabasePerformance()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="db-test4-result" class="mt-2"></div>
                </div>

                <!-- Test 5: Backup Kontrolü -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-save me-2"></i>Backup Kontrolü</h6>
                            <small class="text-muted">Veritabanı yedekleme durumu</small>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="testDatabaseBackup()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="db-test5-result" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- API TESTLERİ -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-globe me-2"></i>API Testleri</h4>
            </div>
            <div class="card-body">
                <!-- Test 1: API Endpoint Kontrolü -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-link me-2"></i>API Endpoint Kontrolü</h6>
                            <small class="text-muted">Tüm API endpoint'lerinin erişilebilirliği</small>
                        </div>
                        <button class="btn btn-outline-success btn-sm" onclick="testAPIEndpoints()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="api-test1-result" class="mt-2"></div>
                </div>

                <!-- Test 2: Response Format -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-code me-2"></i>Response Format Kontrolü</h6>
                            <small class="text-muted">API response formatının doğruluğu</small>
                        </div>
                        <button class="btn btn-outline-success btn-sm" onclick="testAPIResponseFormat()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="api-test2-result" class="mt-2"></div>
                </div>

                <!-- Test 3: Error Handling -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Error Handling</h6>
                            <small class="text-muted">Hata durumlarında API davranışı</small>
                        </div>
                        <button class="btn btn-outline-success btn-sm" onclick="testAPIErrorHandling()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="api-test3-result" class="mt-2"></div>
                </div>

                <!-- Test 4: Security -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-lock me-2"></i>Güvenlik Kontrolü</h6>
                            <small class="text-muted">API güvenlik önlemlerinin kontrolü</small>
                        </div>
                        <button class="btn btn-outline-success btn-sm" onclick="testAPISecurity()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="api-test4-result" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- SUNUCU TESTLERİ -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h4><i class="fas fa-server me-2"></i>Sunucu Testleri</h4>
            </div>
            <div class="card-body">
                <!-- Test 1: PHP Versiyon -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-code me-2"></i>PHP Versiyon Kontrolü</h6>
                            <small class="text-muted">PHP versiyonunun uyumluluğu</small>
                        </div>
                        <button class="btn btn-outline-warning btn-sm" onclick="testPHPVersion()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="server-test1-result" class="mt-2"></div>
                </div>

                <!-- Test 2: Extensions -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-puzzle-piece me-2"></i>PHP Extensions</h6>
                            <small class="text-muted">Gerekli PHP extension'larının varlığı</small>
                        </div>
                        <button class="btn btn-outline-warning btn-sm" onclick="testPHPExtensions()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="server-test2-result" class="mt-2"></div>
                </div>

                <!-- Test 3: Server Resources -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-memory me-2"></i>Sunucu Kaynakları</h6>
                            <small class="text-muted">Memory, disk alanı ve CPU kullanımı</small>
                        </div>
                        <button class="btn btn-outline-warning btn-sm" onclick="testServerResources()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="server-test3-result" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- MODAL TESTLERİ -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h4><i class="fas fa-window-maximize me-2"></i>Modal Testleri</h4>
            </div>
            <div class="card-body">
                <!-- Test 1: Assignment Modal -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-user-plus me-2"></i>Atama Modal'ı</h6>
                            <small class="text-muted">Öğrenci atama modal'ının çalışması</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="testAssignmentModal()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="modal-test1-result" class="mt-2"></div>
                </div>

                <!-- Test 2: PC Details Modal -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-desktop me-2"></i>PC Detayları Modal'ı</h6>
                            <small class="text-muted">PC detayları modal'ının çalışması</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="testPCDetailsModal()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="modal-test2-result" class="mt-2"></div>
                </div>

                <!-- Test 3: Excel Import Modal -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-file-excel me-2"></i>Excel Import Modal'ı</h6>
                            <small class="text-muted">Excel import modal'ının çalışması</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="testExcelImportModal()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="modal-test3-result" class="mt-2"></div>
                </div>

                <!-- Test 4: PC Count Edit Modal -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-edit me-2"></i>PC Sayısı Düzenleme Modal'ı</h6>
                            <small class="text-muted">PC sayısı düzenleme modal'ının çalışması</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="testPCCountEditModal()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="modal-test4-result" class="mt-2"></div>
                </div>

                <!-- Test 5: Max Students Edit Modal -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-users me-2"></i>Max Öğrenci Düzenleme Modal'ı</h6>
                            <small class="text-muted">Max öğrenci düzenleme modal'ının çalışması</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="testMaxStudentsEditModal()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="modal-test5-result" class="mt-2"></div>
                </div>

                <!-- Test 6: Test Modal -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-bug me-2"></i>Test Modal'ı</h6>
                            <small class="text-muted">Test modal'ının çalışması</small>
                        </div>
                        <button class="btn btn-outline-info btn-sm" onclick="testTestModal()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="modal-test6-result" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- HATA TESTLERİ -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h4><i class="fas fa-bug me-2"></i>Hata Testleri</h4>
            </div>
            <div class="card-body">
                <!-- Test 1: API 500 Hata Testi -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-bug me-2"></i>API 500 Hata Testi</h6>
                            <small class="text-muted">API'nin 500 hatalarını doğru yönetmesi</small>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" onclick="testAPI500Error()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="error-test1-result" class="mt-2"></div>
                </div>

                <!-- Test 2: 500 Hata Kontrolü -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-shield-alt me-2"></i>500 Hata Kontrolü</h6>
                            <small class="text-muted">Sunucu hatalarının doğru yakalanması</small>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" onclick="test500ErrorHandling()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="error-test2-result" class="mt-2"></div>
                </div>

                <!-- Test 3: Sunucu Uyumluluk Testi -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-server me-2"></i>Sunucu Uyumluluk Testi</h6>
                            <small class="text-muted">Farklı sunucu ortamlarında uyumluluk</small>
                        </div>
                        <button class="btn btn-outline-danger btn-sm" onclick="testServerCompatibility()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="error-test3-result" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- GÜVENLİK TESTLERİ -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h4><i class="fas fa-shield-alt me-2"></i>Güvenlik Testleri</h4>
            </div>
            <div class="card-body">
                <!-- Test 1: SQL Injection Koruması -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-database me-2"></i>SQL Injection Koruması</h6>
                            <small class="text-muted">Veritabanı güvenlik önlemlerinin kontrolü</small>
                        </div>
                        <button class="btn btn-outline-dark btn-sm" onclick="testSQLInjectionProtection()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="security-test1-result" class="mt-2"></div>
                </div>

                <!-- Test 2: XSS Koruması -->
                <div class="test-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6><i class="fas fa-code me-2"></i>XSS Koruması</h6>
                            <small class="text-muted">Cross-site scripting saldırılarına karşı koruma</small>
                        </div>
                        <button class="btn btn-outline-dark btn-sm" onclick="testXSSProtection()">
                            <i class="fas fa-play me-1"></i>Test Et
                        </button>
                    </div>
                    <div id="security-test2-result" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- GENEL TEST SONUÇLARI -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h4><i class="fas fa-chart-bar me-2"></i>Genel Test Sonuçları</h4>
            </div>
            <div class="card-body">
                <div id="overall-result"></div>
                <div id="detailed-report" class="mt-3"></div>
            </div>
        </div>
    `;
}

// Test sayfasını yenile
function refreshTest() {
    loadTestContent();
}

// Sonuç göster
function showResult(elementId, message, type = 'success') {
    const element = document.getElementById(elementId);
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'exclamation-triangle';
    const bgClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-warning';
    
    element.innerHTML = 
        '<div class="alert ' + bgClass + '">' +
            '<i class="fas fa-' + icon + ' me-2"></i>' +
            message +
        '</div>';
}

// Sleep fonksiyonu
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Progress indicator göster
function showProgressIndicator() {
    const progressHTML = 
        '<div id="test-progress" class="alert alert-info text-center">' +
            '<i class="fas fa-spinner fa-spin me-2"></i>' +
            'Testler çalıştırılıyor... Lütfen bekleyin.' +
        '</div>';
    
    const overallResult = document.getElementById('overall-result');
    if (overallResult) {
        overallResult.innerHTML = progressHTML;
    }
}

// Progress indicator gizle
function hideProgressIndicator() {
    const progressElement = document.getElementById('test-progress');
    if (progressElement) {
        progressElement.remove();
    }
}

// Test sayılarını güncelle
function updateTestCounts() {
    const dbTests = ['dbConnection', 'tableExistence', 'dataIntegrity', 'dbPerformance', 'dbBackup'];
    const apiTests = ['apiEndpoints', 'apiResponseFormat', 'apiErrorHandling', 'apiSecurity'];
    const serverTests = ['phpVersion', 'phpExtensions', 'serverResources'];
    const modalTests = ['assignmentModal', 'pcDetailsModal', 'excelImportModal', 'pcCountEditModal', 'maxStudentsEditModal', 'testModal'];
    const errorTests = ['api500Error', 'error500Handling', 'serverCompatibility'];
    const securityTests = ['sqlInjectionProtection', 'xssProtection'];
    
    const dbPassed = dbTests.filter(test => testResults[test]).length;
    const apiPassed = apiTests.filter(test => testResults[test]).length;
    const serverPassed = serverTests.filter(test => testResults[test]).length;
    const modalPassed = modalTests.filter(test => testResults[test]).length;
    const errorPassed = errorTests.filter(test => testResults[test]).length;
    const securityPassed = securityTests.filter(test => testResults[test]).length;
    
    document.getElementById('db-test-count').textContent = dbPassed + '/' + dbTests.length;
    document.getElementById('api-test-count').textContent = apiPassed + '/' + apiTests.length;
    document.getElementById('server-test-count').textContent = serverPassed + '/' + serverTests.length;
    document.getElementById('modal-test-count').textContent = modalPassed + '/' + modalTests.length;
    document.getElementById('error-test-count').textContent = errorPassed + '/' + errorTests.length;
    document.getElementById('security-test-count').textContent = securityPassed + '/' + securityTests.length;
}

// Sonuçları temizle
function clearAllResults() {
    testResults = {
        dbConnection: false, tableExistence: false, dataIntegrity: false, dbPerformance: false, dbBackup: false,
        apiEndpoints: false, apiResponseFormat: false, apiErrorHandling: false, apiSecurity: false,
        phpVersion: false, phpExtensions: false, serverResources: false,
        assignmentModal: false, pcDetailsModal: false, excelImportModal: false, 
        pcCountEditModal: false, maxStudentsEditModal: false, testModal: false,
        api500Error: false, error500Handling: false, serverCompatibility: false,
        sqlInjectionProtection: false, xssProtection: false
    };
    
    // Tüm result div'lerini temizle
    const resultDivs = document.querySelectorAll('[id$="-result"]');
    resultDivs.forEach(div => div.innerHTML = '');
    
    // Export butonunu deaktif et
    const exportBtn = document.getElementById('exportTestBtn');
    if (exportBtn) {
        exportBtn.disabled = true;
    }
    
    // Detaylı raporu temizle
    const detailedReport = document.getElementById('detailed-report');
    if (detailedReport) {
        detailedReport.innerHTML = '';
    }
    
    updateOverallResult();
    updateTestCounts();
}

