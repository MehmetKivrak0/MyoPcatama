// Atamaları Dışa Aktar JavaScript Fonksiyonları
// Bu dosya atamaları Excel formatında dışa aktarma işlemlerini yönetir

/**
 * Atamaları Excel formatında dışa aktar
 * Seçili laboratuvarın atamalarını Excel dosyası olarak indirir
 */
function exportAssignments() {
    console.log('📊 Atamalar dışa aktarılıyor...');
    
    // Seçili laboratuvar kontrolü
    const labSelector = document.getElementById('labSelector');
    const selectedLabId = labSelector ? labSelector.value : null;
    
    if (!selectedLabId) {
        showToast('Lütfen önce bir laboratuvar seçin!', 'warning', 'Uyarı');
        return;
    }
    
    // Loading durumu göster
    showToast('Atamalar hazırlanıyor...', 'info', 'Dışa Aktarma');
    
    // AJAX ile laboratuvar atamalarını dışa aktar
    fetch(`../controllers/AssignmentController.php?action=export_lab_assignments&computer_id=${selectedLabId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.blob();
    })
    .then(blob => {
        // Dosya indirme işlemi
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        
        // Dosya adını tarih ile oluştur
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-');
        const labName = labSelector.options[labSelector.selectedIndex].text.split(' (')[0];
        const safeLabName = labName.replace(/[^a-zA-Z0-9]/g, '_');
        a.download = `${safeLabName}_atamalar_${dateStr}_${timeStr}.xlsx`;
        
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showToast(`${labName} atamaları başarıyla dışa aktarıldı!`, 'success', 'Dışa Aktarma Başarılı');
    })
    .catch(error => {
        console.error('❌ Dışa aktarma hatası:', error);
        showToast('Atamalar dışa aktarılırken bir hata oluştu!', 'error', 'Dışa Aktarma Hatası');
    });
}

/**
 * Belirli bir laboratuvarın atamalarını dışa aktar
 * @param {number} labId - Laboratuvar ID'si
 * @param {string} labName - Laboratuvar adı
 */
function exportLabAssignments(labId, labName) {
    console.log(`📊 ${labName} laboratuvarı atamaları dışa aktarılıyor...`);
    
    // Loading durumu göster
    showToast(`${labName} atamaları hazırlanıyor...`, 'info', 'Dışa Aktarma');
    
    // AJAX ile laboratuvar atamalarını dışa aktar
    fetch(`../controllers/AssignmentController.php?action=export_lab_assignments&computer_id=${labId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.blob();
    })
    .then(blob => {
        // Dosya indirme işlemi
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        
        // Dosya adını tarih ile oluştur
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-');
        const safeLabName = labName.replace(/[^a-zA-Z0-9]/g, '_');
        a.download = `${safeLabName}_atamalar_${dateStr}_${timeStr}.xlsx`;
        
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showToast(`${labName} atamaları başarıyla dışa aktarıldı!`, 'success', 'Dışa Aktarma Başarılı');
    })
    .catch(error => {
        console.error('❌ Laboratuvar dışa aktarma hatası:', error);
        showToast('Laboratuvar atamaları dışa aktarılırken bir hata oluştu!', 'error', 'Dışa Aktarma Hatası');
    });
}

/**
 * Atama istatistiklerini dışa aktar
 * Özet bilgileri içeren Excel dosyası oluşturur
 */
function exportAssignmentStats() {
    console.log('📊 Atama istatistikleri dışa aktarılıyor...');
    
    // Loading durumu göster
    showToast('Atama istatistikleri hazırlanıyor...', 'info', 'Dışa Aktarma');
    
    // AJAX ile atama istatistiklerini dışa aktar
    fetch('../controllers/AssignmentController.php?action=export_assignment_stats', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.blob();
    })
    .then(blob => {
        // Dosya indirme işlemi
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        
        // Dosya adını tarih ile oluştur
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-');
        a.download = `atama_istatistikleri_${dateStr}_${timeStr}.xlsx`;
        
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        showToast('Atama istatistikleri başarıyla dışa aktarıldı!', 'success', 'Dışa Aktarma Başarılı');
    })
    .catch(error => {
        console.error('❌ İstatistik dışa aktarma hatası:', error);
        showToast('Atama istatistikleri dışa aktarılırken bir hata oluştu!', 'error', 'Dışa Aktarma Hatası');
    });
}

/**
 * Dışa aktarma seçenekleri modal'ını göster
 */
function showExportOptions() {
    // Modal HTML'ini oluştur
    const modalHTML = `
        <div class="modal fade" id="exportOptionsModal" tabindex="-1" aria-labelledby="exportOptionsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="exportOptionsModalLabel">
                            <i class="fas fa-download me-2"></i>Dışa Aktarma Seçenekleri
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-list fa-3x text-primary mb-3"></i>
                                        <h5 class="card-title">Tüm Atamalar</h5>
                                        <p class="card-text">Sistemdeki tüm atamaları Excel formatında dışa aktarır.</p>
                                        <button class="btn btn-primary" onclick="exportAssignments(); bootstrap.Modal.getInstance(document.getElementById('exportOptionsModal')).hide();">
                                            <i class="fas fa-download me-2"></i>Tüm Atamaları Dışa Aktar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-bar fa-3x text-success mb-3"></i>
                                        <h5 class="card-title">Atama İstatistikleri</h5>
                                        <p class="card-text">Atama özet bilgilerini ve istatistikleri dışa aktarır.</p>
                                        <button class="btn btn-success" onclick="exportAssignmentStats(); bootstrap.Modal.getInstance(document.getElementById('exportOptionsModal')).hide();">
                                            <i class="fas fa-chart-bar me-2"></i>İstatistikleri Dışa Aktar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Not:</strong> Dışa aktarılan dosyalar Excel formatında (.xlsx) olacaktır ve otomatik olarak indirilecektir.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Kapat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Modal'ı DOM'a ekle
    const existingModal = document.getElementById('exportOptionsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Modal'ı göster
    const modal = new bootstrap.Modal(document.getElementById('exportOptionsModal'));
    modal.show();
}

/**
 * Dışa aktar butonunu aktif/pasif hale getir
 * @param {boolean} enabled - Buton aktif mi?
 */
function toggleExportButton(enabled) {
    const exportBtn = document.getElementById('exportAssignmentsBtn');
    if (exportBtn) {
        exportBtn.disabled = !enabled;
        
        if (enabled) {
            exportBtn.classList.remove('disabled');
            exportBtn.title = 'Seçili laboratuvarın atamalarını dışa aktar';
        } else {
            exportBtn.classList.add('disabled');
            exportBtn.title = 'Önce bir laboratuvar seçin';
        }
    }
}

/**
 * Laboratuvar seçimi değiştiğinde dışa aktar butonunu güncelle
 */
function updateExportButtonState() {
    const labSelector = document.getElementById('labSelector');
    if (labSelector) {
        const selectedLabId = labSelector.value;
        toggleExportButton(!!selectedLabId);
    }
}

// Sayfa yüklendiğinde export fonksiyonlarını hazırla
document.addEventListener('DOMContentLoaded', function() {
    console.log('📊 Export assignments JavaScript yüklendi');
    
    // Dışa aktar butonunu başlangıçta pasif yap
    toggleExportButton(false);
    
    // Laboratuvar seçici değiştiğinde buton durumunu güncelle
    const labSelector = document.getElementById('labSelector');
    if (labSelector) {
        labSelector.addEventListener('change', updateExportButtonState);
        
        // Sayfa yüklendiğinde mevcut durumu kontrol et
        updateExportButtonState();
    }
});
