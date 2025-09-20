/**
 * PC Güncelleme Sistemi
 * Laboratuvardaki PC kartlarını açıp güncelleme yapma
 */

class PCUpdateManager {
    constructor() {
        this.currentLabId = null;
        this.currentLabName = null;
        this.selectedPC = null;
        this.updateModal = null;
        this.init();
    }

    /**
     * Sistemi başlat
     */
    init() {
        this.createUpdateModal();
        this.bindEvents();
        // console.log('🔄 PC Update Manager initialized');
    }

    /**
     * Güncelleme modalını oluştur
     */
    createUpdateModal() {
        const modalHTML = `
            <div class="modal fade" id="pcUpdateModal" tabindex="-1" aria-labelledby="pcUpdateModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="pcUpdateModalLabel">
                                <i class="fas fa-edit me-2"></i>
                                PC Güncelleme - <span id="updateLabName"></span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-4">
                                <!-- PC Listesi -->
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-desktop me-2"></i>
                                                PC Listesi
                                            </h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div id="updatePcList" class="row g-3" style="max-height: 500px; overflow-y: auto;">
                                                <!-- PC kartları buraya gelecek -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Seçili PC Detayları -->
                                <div class="col-lg-6">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-info-circle me-2"></i>
                                                PC Detayları
                                            </h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div id="selectedPcDetails">
                                                <div class="text-center text-muted py-5">
                                                    <i class="fas fa-mouse-pointer fa-3x mb-3 text-muted"></i>
                                                    <h6 class="text-muted">PC Seçin</h6>
                                                    <p class="text-muted mb-0">Güncellemek için sol taraftan bir PC seçin</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Kapat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Modal'ı body'ye ekle
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.updateModal = new bootstrap.Modal(document.getElementById('pcUpdateModal'));
    }

    /**
     * Event listener'ları bağla
     */
    bindEvents() {
        // Modal kapatıldığında temizle
        document.getElementById('pcUpdateModal').addEventListener('hidden.bs.modal', () => {
            this.resetModal();
        });
    }

    /**
     * Base URL'i al (DRY prensibi)
     */
    getBaseUrl() {
        return window.location.origin + '/myopc';
    }

    /**
     * API isteği yap (DRY prensibi)
     */
    async makeApiRequest(endpoint, options = {}) {
        const baseUrl = this.getBaseUrl();
        const url = `${baseUrl}/controllers/AssignmentController.php?${endpoint}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            ...options
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    /**
     * POST API isteği yap (DRY prensibi)
     */
    async makePostRequest(action, formData) {
        const baseUrl = this.getBaseUrl();
        const url = `${baseUrl}/controllers/AssignmentController.php?action=${action}`;
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
    }

    /**
     * Hata durumunu göster (DRY prensibi)
     */
    showErrorState(container, message, retryCallback = null) {
        const retryButton = retryCallback ? `
            <button class="btn btn-outline-primary btn-sm" onclick="(${retryCallback.toString()})()">
                <i class="fas fa-redo me-2"></i>Tekrar Dene
            </button>
        ` : '';
        
        container.innerHTML = `
            <div class="col-12 text-center py-4">
                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                <p class="text-muted">${message}</p>
                ${retryButton}
            </div>
        `;
    }

    /**
     * Güncelleme modalını aç
     */
    openUpdateModal(labId, labName) {
        this.currentLabId = labId || window.currentLabId;
        this.currentLabName = labName || window.currentLabName;
        
        // console.log('🚀 Update Modal açılıyor - labId:', this.currentLabId, 'labName:', this.currentLabName);
        
        // Modal başlığını güncelle
        document.getElementById('updateLabName').textContent = this.currentLabName;
        
        // PC listesini yükle
        this.loadPCsForUpdate();
        
        // Modal'ı aç
        this.updateModal.show();
    }

    /**
     * Güncelleme için PC'leri yükle
     */
    async loadPCsForUpdate() {
        const pcListContainer = document.getElementById('updatePcList');
        const loadingHTML = `
            <div class="col-12 text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
                <p class="mt-2 text-muted">PC'ler yükleniyor...</p>
            </div>
        `;
        pcListContainer.innerHTML = loadingHTML;

        try {
            // console.log('🔄 PC yükleme başlıyor - computerId:', this.currentLabId);
            const data = await this.makeApiRequest(`action=get_lab_pcs&computer_id=${this.currentLabId}`);
            // console.log('📋 API Data:', data);

            if (data.success) {
                this.displayPCsForUpdate(data.pcs);
            } else {
                throw new Error(data.message || 'PC verileri yüklenemedi');
            }
        } catch (error) {
            console.error('PC yükleme hatası:', error);
            this.showErrorState(pcListContainer, 'PC verileri yüklenirken hata oluştu', () => {
                this.loadPCsForUpdate();
            });
        }
    }

    /**
     * Güncelleme için PC'leri görüntüle
     */
    displayPCsForUpdate(pcs) {
        const pcListContainer = document.getElementById('updatePcList');
        
        if (!pcs || pcs.length === 0) {
            pcListContainer.innerHTML = `
                <div class="col-12 text-center py-4">
                    <i class="fas fa-desktop fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Bu laboratuvarda PC bulunmuyor</p>
                </div>
            `;
            return;
        }

        const pcCardsHTML = pcs.map(pc => {
            const isOccupied = pc.is_occupied && pc.students && pc.students.length > 0;
            const studentCount = pc.students ? pc.students.length : 0;
            const firstStudent = pc.students && pc.students.length > 0 ? pc.students[0] : null;
            
            return `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card pc-update-card h-100 ${isOccupied ? 'occupied' : 'available'}" 
                         data-pc-id="${pc.pc_id}" 
                         data-pc-number="${pc.pc_number}"
                         onclick="pcUpdateManager.selectPC(${pc.pc_id}, '${pc.pc_number}', ${isOccupied})">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-title mb-0 fw-bold">PC ${pc.pc_number}</h6>
                                <span class="badge ${isOccupied ? 'bg-danger' : 'bg-success'} px-2 py-1">
                                    ${isOccupied ? 'Dolu' : 'Boş'}
                                </span>
                            </div>
                            ${isOccupied ? `
                                <div class="student-info">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <small class="text-dark fw-medium">${firstStudent ? firstStudent.full_name : 'Öğrenci'}</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-id-card text-secondary me-2"></i>
                                        <small class="text-muted">${firstStudent ? firstStudent.sdt_nmbr : 'Numara'}</small>
                                    </div>
                                    ${studentCount > 1 ? `
                                        <div class="d-flex align-items-center mt-1">
                                            <i class="fas fa-users text-info me-2"></i>
                                            <small class="text-info">+${studentCount - 1} öğrenci daha</small>
                                        </div>
                                    ` : ''}
                                </div>
                            ` : `
                                <div class="text-center text-muted py-2">
                                    <i class="fas fa-user-slash fa-2x mb-2 text-muted"></i>
                                    <div class="small">Öğrenci yok</div>
                                </div>
                            `}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        pcListContainer.innerHTML = pcCardsHTML;
    }

    /**
     * PC seç
     */
    selectPC(pcId, pcNumber, isOccupied) {
        // Önceki seçimi temizle
        document.querySelectorAll('.pc-update-card').forEach(card => {
            card.classList.remove('selected');
        });

        // Yeni seçimi işaretle
        const selectedCard = document.querySelector(`[data-pc-id="${pcId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }

        this.selectedPC = { pcId, pcNumber, isOccupied };
        this.displayPCDetails(pcId, pcNumber, isOccupied);
        
    }

    /**
     * Seçili PC detaylarını göster
     */
    async displayPCDetails(pcId, pcNumber, isOccupied) {
        const detailsContainer = document.getElementById('selectedPcDetails');
        
        if (!isOccupied) {
            detailsContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-desktop fa-3x text-muted mb-3"></i>
                    <h6>PC ${pcNumber}</h6>
                    <p class="text-muted">Bu PC'de öğrenci ataması bulunmuyor</p>
                </div>
            `;
            return;
        }

        // PC'deki öğrenci bilgilerini getir
        try {
            const data = await this.makeApiRequest(`action=get_pc_students&pc_id=${pcId}`);

            if (data.success) {
                this.displayStudentDetails(data.students, pcId, pcNumber);
            } else {
                throw new Error(data.message || 'Öğrenci bilgileri alınamadı');
            }
        } catch (error) {
            console.error('Öğrenci bilgileri alma hatası:', error);
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Öğrenci bilgileri yüklenirken hata oluştu
                </div>
            `;
        }
    }

    /**
     * Öğrenci detaylarını göster
     */
    displayStudentDetails(students, pcId, pcNumber) {
        const detailsContainer = document.getElementById('selectedPcDetails');
        
        const studentsHTML = students.map(student => `
            <div class="student-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="card-title mb-0 fw-bold text-dark">${student.full_name}</h6>
                            <div class="btn-group-vertical btn-group-sm">
                                <button class="btn btn-outline-warning btn-sm" 
                                        onclick="pcUpdateManager.transferStudent(${student.student_id}, ${pcId}, '${pcNumber}')"
                                        title="PC Değiştir">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="pcUpdateManager.removeStudent(${student.student_id}, ${pcId}, '${pcNumber}')"
                                        title="Atamayı Kaldır">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="student-info">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-id-card text-primary me-2"></i>
                                <small class="text-dark fw-medium">${student.sdt_nmbr}</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar text-secondary me-2"></i>
                                <small class="text-muted">${student.academic_year}</small>
                            </div>
                        </div>
                </div>
        `).join('');

        detailsContainer.innerHTML = `
            <div class="pc-details">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-desktop me-2"></i>
                        PC ${pcNumber} - Atanmış Öğrenciler
                    </h6>
                    <span class="badge bg-primary">${students.length} Öğrenci</span>
                </div>
                <div class="students-grid">
                    ${studentsHTML}
                </div>
            </div>
        `;
    }




    /**
     * Öğrenci transfer et
     */
    transferStudent(studentId, currentPcId, pcNumber) {
        // Transfer modalını aç
        this.openTransferModal(studentId, currentPcId, pcNumber);
    }

    /**
     * Transfer modalını aç
     */
    openTransferModal(studentId, currentPcId, pcNumber) {
        // Transfer modalı oluştur ve aç
        const transferModalHTML = `
            <div class="modal fade" id="transferModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-exchange-alt me-2"></i>
                                PC Transfer
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Mevcut PC:</strong> PC ${pcNumber}
                            </div>
                            <div class="form-group mb-3">
                                <label for="newPcSelect" class="form-label">Yeni PC Seçin</label>
                                <select class="form-select" id="newPcSelect" required>
                                    <option value="">Yeni PC seçin...</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Dolu PC'leri seçerseniz, o PC'deki öğrenci ile yer değiştirilir.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="button" class="btn btn-warning" onclick="pcUpdateManager.confirmTransfer(${studentId}, ${currentPcId})">
                                <i class="fas fa-exchange-alt me-2"></i>Transfer Et
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Modal'ı ekle ve aç
        document.body.insertAdjacentHTML('beforeend', transferModalHTML);
        const transferModal = new bootstrap.Modal(document.getElementById('transferModal'));
        
        // Boş PC'leri yükle
        this.loadAvailablePCsForTransfer(studentId, currentPcId);
        
        transferModal.show();
        
        // Modal kapatıldığında temizle
        document.getElementById('transferModal').addEventListener('hidden.bs.modal', () => {
            document.getElementById('transferModal').remove();
        });
    }

    /**
     * Transfer için PC'leri yükle (hem boş hem dolu)
     */
    async loadAvailablePCsForTransfer(studentId, currentPcId) {
        try {
            // Önce tüm PC'leri getir
            const data = await this.makeApiRequest(`action=get_lab_pcs&computer_id=${this.currentLabId}`);

            if (data.success) {
                const select = document.getElementById('newPcSelect');
                select.innerHTML = '<option value="">Yeni PC seçin...</option>';
                
                data.pcs.forEach(pc => {
                    // Mevcut PC'yi atla
                    if (pc.pc_id == currentPcId) {
                        return;
                    }
                    
                    const option = document.createElement('option');
                    // PC'nin dolu olup olmadığını kontrol et
                    const isOccupied = (pc.students && pc.students.length > 0) || (pc.student_count && pc.student_count > 0);
                    const studentCount = pc.students ? pc.students.length : (pc.student_count || 0);
                    const statusText = isOccupied ? `${studentCount} kişi` : 'Boş';
                    const statusClass = isOccupied ? 'text-warning' : 'text-success';
                    
                    console.log('📋 Transfer PC:', pc.pc_id, pc.pc_number, 'isOccupied:', isOccupied, 'students:', pc.students, 'student_count:', pc.student_count, 'statusText:', statusText);
                    
                    option.value = pc.pc_id;
                    option.innerHTML = `PC ${pc.pc_number} (${statusText})`;
                    option.className = statusClass;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('PC yükleme hatası:', error);
        }
    }

    /**
     * Transferi onayla
     */
    async confirmTransfer(studentId, currentPcId) {
        const newPcId = document.getElementById('newPcSelect').value;
        
        if (!newPcId) {
            showToast('Hata', 'Lütfen yeni PC seçin', 'error');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('new_pc_id', newPcId);
            formData.append('computer_id', this.currentLabId);

            const data = await this.makePostRequest('transfer_student', formData);

            if (data.success) {
                showToast('Başarılı', 'Öğrenci başarıyla transfer edildi', 'success');
                bootstrap.Modal.getInstance(document.getElementById('transferModal')).hide();
                this.loadPCsForUpdate(); // PC listesini yenile
            } else {
                throw new Error(data.message || 'Transfer başarısız');
            }
        } catch (error) {
            console.error('Transfer hatası:', error);
            showToast('Hata', error.message || 'Transfer sırasında hata oluştu', 'error');
        }
    }

    /**
     * Öğrenci atamasını kaldır
     */
    async removeStudent(studentId, pcId, pcNumber) {
        if (!confirm(`PC ${pcNumber}'deki öğrenci atamasını kaldırmak istediğinizden emin misiniz?`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('computer_id', this.currentLabId);

            const data = await this.makePostRequest('unassign_student', formData);

            if (data.success) {
                showToast('Başarılı', 'Atama başarıyla kaldırıldı', 'success');
                this.loadPCsForUpdate(); // PC listesini yenile
            } else {
                throw new Error(data.message || 'Atama kaldırılamadı');
            }
        } catch (error) {
            console.error('Atama kaldırma hatası:', error);
            showToast('Hata', error.message || 'Atama kaldırılırken hata oluştu', 'error');
        }
    }


    /**
     * Modal'ı sıfırla
     */
    resetModal() {
        this.selectedPC = null;
        document.getElementById('selectedPcDetails').innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-mouse-pointer fa-2x mb-3"></i>
                <p>Güncellemek için bir PC seçin</p>
            </div>
        `;
    }
}

// Global instance oluştur
const pcUpdateManager = new PCUpdateManager();

// Global fonksiyonlar
function openPCUpdate(labId, labName) {
    console.log('🔧 openPCUpdate çağrıldı:', labId, labName);
    console.log('🔧 window.currentLabId:', window.currentLabId);
    console.log('🔧 window.currentLabName:', window.currentLabName);
    console.log('🔧 pcUpdateManager:', pcUpdateManager);
    
    // Eğer parametreler verilmemişse global değişkenleri kullan
    const finalLabId = labId || window.currentLabId;
    const finalLabName = labName || window.currentLabName;
    
    console.log('🔧 finalLabId:', finalLabId);
    console.log('🔧 finalLabName:', finalLabName);
    
    if (!finalLabId || !finalLabName) {
        console.error('❌ Laboratuvar bilgileri eksik');
        showToast('Hata', 'Laboratuvar bilgileri bulunamadı', 'error');
        return;
    }
    
    if (!pcUpdateManager) {
        console.error('❌ pcUpdateManager tanımlı değil');
        showToast('Hata', 'PC güncelleme sistemi yüklenemedi', 'error');
        return;
    }
    
    console.log('🔧 pcUpdateManager.openUpdateModal çağrılıyor');
    pcUpdateManager.openUpdateModal(finalLabId, finalLabName);
}

// Toast bildirimi fonksiyonu (eğer yoksa)
if (typeof showToast === 'undefined') {
    function showToast(title, message, type) {
        const toastContainer = document.querySelector('.toast-container') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast show`;
        toast.innerHTML = `
            <div class="toast-header">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
}
