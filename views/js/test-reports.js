// Test Rapor Fonksiyonları JavaScript Dosyası
// Test sonuçları ve raporlama fonksiyonları

// Genel sonuçları güncelle
function updateOverallResult() {
    const totalTests = Object.keys(testResults).length;
    const passedTests = Object.values(testResults).filter(result => result === true).length;
    const failedTests = totalTests - passedTests;
    
    // Kategori bazında sonuçlar
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
    
    let resultHTML = 
        '<div class="row mb-3">' +
            '<div class="col-md-2">' +
                '<div class="card text-center">' +
                    '<div class="card-body">' +
                        '<h5 class="card-title">Veritabanı</h5>' +
                        '<h3 class="text-' + (dbPassed === dbTests.length ? 'success' : 'danger') + '">' + dbPassed + '/' + dbTests.length + '</h3>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<div class="card text-center">' +
                    '<div class="card-body">' +
                        '<h5 class="card-title">API</h5>' +
                        '<h3 class="text-' + (apiPassed === apiTests.length ? 'success' : 'danger') + '">' + apiPassed + '/' + apiTests.length + '</h3>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<div class="card text-center">' +
                    '<div class="card-body">' +
                        '<h5 class="card-title">Sunucu</h5>' +
                        '<h3 class="text-' + (serverPassed === serverTests.length ? 'success' : 'danger') + '">' + serverPassed + '/' + serverTests.length + '</h3>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<div class="card text-center">' +
                    '<div class="card-body">' +
                        '<h5 class="card-title">Modal</h5>' +
                        '<h3 class="text-' + (modalPassed === modalTests.length ? 'success' : 'danger') + '">' + modalPassed + '/' + modalTests.length + '</h3>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<div class="card text-center">' +
                    '<div class="card-body">' +
                        '<h5 class="card-title">Hata</h5>' +
                        '<h3 class="text-' + (errorPassed === errorTests.length ? 'success' : 'danger') + '">' + errorPassed + '/' + errorTests.length + '</h3>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<div class="card text-center">' +
                    '<div class="card-body">' +
                        '<h5 class="card-title">Güvenlik</h5>' +
                        '<h3 class="text-' + (securityPassed === securityTests.length ? 'success' : 'danger') + '">' + securityPassed + '/' + securityTests.length + '</h3>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>' +
        
        '<div class="row">' +
            '<div class="col-md-6">' +
                '<div class="alert alert-success">' +
                    '<i class="fas fa-check-circle me-2"></i>' +
                    'Geçen Testler: ' + passedTests + '/' + totalTests +
                '</div>' +
            '</div>' +
            '<div class="col-md-6">' +
                '<div class="alert ' + (failedTests > 0 ? 'alert-danger' : 'alert-success') + '">' +
                    '<i class="fas fa-' + (failedTests > 0 ? 'times-circle' : 'check-circle') + ' me-2"></i>' +
                    'Başarısız Testler: ' + failedTests + '/' + totalTests +
                '</div>' +
            '</div>' +
        '</div>';
    
    if (passedTests === totalTests) {
        resultHTML += 
            '<div class="alert alert-success mt-3">' +
                '<i class="fas fa-trophy me-2"></i>' +
                '<strong>🎉 Tüm testler başarılı! Atama sistemi tamamen çalışıyor.</strong>' +
            '</div>';
    } else {
        resultHTML += 
            '<div class="alert alert-warning mt-3">' +
                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                '<strong>⚠️ Bazı testler başarısız. Lütfen hataları kontrol edin.</strong>' +
            '</div>';
    }
    
    document.getElementById('overall-result').innerHTML = resultHTML;
}

// Detaylı rapor oluştur
function generateDetailedReport(totalTime) {
    const totalTests = Object.keys(testResults).length;
    const passedTests = Object.values(testResults).filter(result => result === true).length;
    const failedTests = totalTests - passedTests;
    
    const reportHTML = 
        '<div class="card mt-3">' +
            '<div class="card-header">' +
                '<h6><i class="fas fa-chart-line me-2"></i>Detaylı Test Raporu</h6>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="row">' +
                    '<div class="col-md-6">' +
                        '<h6>Test İstatistikleri</h6>' +
                        '<ul class="list-unstyled">' +
                            '<li><strong>Toplam Test:</strong> ' + totalTests + '</li>' +
                            '<li><strong>Başarılı:</strong> <span class="text-success">' + passedTests + '</span></li>' +
                            '<li><strong>Başarısız:</strong> <span class="text-danger">' + failedTests + '</span></li>' +
                            '<li><strong>Başarı Oranı:</strong> ' + Math.round((passedTests / totalTests) * 100) + '%</li>' +
                            '<li><strong>Toplam Süre:</strong> ' + totalTime.toFixed(2) + ' saniye</li>' +
                        '</ul>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                        '<h6>Sistem Durumu</h6>' +
                        '<div class="progress mb-2">' +
                            '<div class="progress-bar" style="width: ' + Math.round((passedTests / totalTests) * 100) + '%"></div>' +
                        '</div>' +
                        '<small class="text-muted">Sistem sağlık durumu: ' + (passedTests === totalTests ? 'Mükemmel' : passedTests > totalTests * 0.8 ? 'İyi' : 'Dikkat Gerekli') + '</small>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    
    const detailedReport = document.getElementById('detailed-report');
    if (detailedReport) {
        detailedReport.innerHTML = reportHTML;
    }
    
    // Export butonunu aktif et
    const exportBtn = document.getElementById('exportTestBtn');
    if (exportBtn) {
        exportBtn.disabled = false;
    }
}

// Test sonuçlarını export et
function exportTestResults() {
    const totalTests = Object.keys(testResults).length;
    const passedTests = Object.values(testResults).filter(result => result === true).length;
    const failedTests = totalTests - passedTests;
    const successRate = Math.round((passedTests / totalTests) * 100);
    
    const testData = {
        timestamp: new Date().toISOString(),
        summary: {
            totalTests: totalTests,
            passedTests: passedTests,
            failedTests: failedTests,
            successRate: successRate
        },
        results: testResults,
        categories: {
            database: {
                tests: ['dbConnection', 'tableExistence', 'dataIntegrity', 'dbPerformance', 'dbBackup'],
                passed: ['dbConnection', 'tableExistence', 'dataIntegrity', 'dbPerformance', 'dbBackup'].filter(test => testResults[test]).length
            },
            api: {
                tests: ['apiEndpoints', 'apiResponseFormat', 'apiErrorHandling', 'apiSecurity'],
                passed: ['apiEndpoints', 'apiResponseFormat', 'apiErrorHandling', 'apiSecurity'].filter(test => testResults[test]).length
            },
            server: {
                tests: ['phpVersion', 'phpExtensions', 'serverResources'],
                passed: ['phpVersion', 'phpExtensions', 'serverResources'].filter(test => testResults[test]).length
            },
            modal: {
                tests: ['assignmentModal', 'pcDetailsModal', 'excelImportModal', 'pcCountEditModal', 'maxStudentsEditModal', 'testModal'],
                passed: ['assignmentModal', 'pcDetailsModal', 'excelImportModal', 'pcCountEditModal', 'maxStudentsEditModal', 'testModal'].filter(test => testResults[test]).length
            },
            error: {
                tests: ['api500Error', 'error500Handling', 'serverCompatibility'],
                passed: ['api500Error', 'error500Handling', 'serverCompatibility'].filter(test => testResults[test]).length
            },
            security: {
                tests: ['sqlInjectionProtection', 'xssProtection'],
                passed: ['sqlInjectionProtection', 'xssProtection'].filter(test => testResults[test]).length
            }
        }
    };
    
    // JSON dosyası olarak indir
    const dataStr = JSON.stringify(testData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'test-results-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    // Başarı mesajı göster
    showResult('detailed-report', '✅ Test sonuçları başarıyla export edildi!', 'success');
}

