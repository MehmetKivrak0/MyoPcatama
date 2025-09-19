// Dashboard yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Tooltip'leri başlat
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Excel import modal açma
function openExcelImport() {
    const modal = new bootstrap.Modal(document.getElementById('excelImportModal'));
    modal.show();
    
    // Form ve result alanını temizle
    document.getElementById('excelImportForm').reset();
    document.getElementById('importResult').innerHTML = '';
    document.getElementById('importProgress').style.display = 'none';
}


// Toast bildirimi göster
function showToast(message, type = 'info', title = null) {
    const toast = document.getElementById('systemToast');
    const toastMessage = document.getElementById('toastMessage');
    
    // Eğer title verilmişse, mesajı daha detaylı göster
    let displayMessage = message;
    if (title) {
        displayMessage = `<strong>${title}</strong><br>${message}`;
    }
    
    // Toast içeriğini güncelle
    toastMessage.innerHTML = displayMessage;
    
    // Toast tipine göre stil uygula
    toast.className = 'toast';
    if (type === 'success') {
        toast.classList.add('bg-success', 'text-white');
    } else if (type === 'error') {
        toast.classList.add('bg-danger', 'text-white');
        // Hata mesajlarını daha uzun göster
        toast.setAttribute('data-bs-delay', '8000');
    } else if (type === 'warning') {
        toast.classList.add('bg-warning');
        toast.setAttribute('data-bs-delay', '6000');
    } else {
        toast.classList.add('bg-info', 'text-white');
    }
    
    // Toast'ı göster
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
}

// Sayfa yenilendiğinde animasyon
function refreshStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    statNumbers.forEach(stat => {
        stat.innerHTML = '<div class="loading"></div>';
    });
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Klavye kısayolları
document.addEventListener('keydown', function(e) {
    // Ctrl + S = Öğrenci yönetimi
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        window.location.href = 'student_management.php';
    }
    
    // Ctrl + L = Laboratuvar yönetimi
    if (e.ctrlKey && e.key === 'l') {
        e.preventDefault();
        window.location.href = 'lab_list.php';
    }
    
    // Ctrl + A = Atama işlemleri
    if (e.ctrlKey && e.key === 'a') {
        e.preventDefault();
        window.location.href = 'assign.php';
    }
});

// Lab PC Viewer Functionality
const labSelector = document.getElementById('labSelector');
const pcCardsContainer = document.getElementById('pcCardsContainer');
const pcCardsGrid = document.getElementById('pcCardsGrid');
const pcLoadingIndicator = document.getElementById('pcLoadingIndicator');
const refreshBtn = document.getElementById('refreshPCs');
const editPCBtn = document.getElementById('editPCCount');
// selectedLabName artık modal içindeki element için kullanılacak
const availablePCs = document.getElementById('availablePCs');
const occupiedPCs = document.getElementById('occupiedPCs');

// PC kartlarından öğrenci verilerini al
function getStudentsFromPCCards() {
    const students = [];
    const pcCards = document.querySelectorAll('.pc-card');
    
    pcCards.forEach(card => {
        const pcNumber = card.querySelector('.pc-number')?.textContent?.trim();
        const studentElements = card.querySelectorAll('.student-item, .student-item-simple');
        
        studentElements.forEach(element => {
            const name = element.querySelector('.student-name, .student-name-simple')?.textContent?.trim();
            const yearElement = element.querySelector('.student-year, .student-details small, .student-details-simple small');
            let year = null;
            
            if (yearElement) {
                const yearText = yearElement.textContent.trim();
                
                // Academic year formatını ara (2024, 2023, vb.)
                const yearMatch = yearText.match(/(\d{4})/);
                if (yearMatch) {
                    year = parseInt(yearMatch[1]);
                }
            }
            
            if (name) {
                students.push({
                    name: name,
                    year: year,
                    pcNumber: pcNumber
                });
            }
        });
    });
    
    return students;
}

// Lab seçimi değiştiğinde
labSelector.addEventListener('change', function() {
    const selectedLabId = this.value;
    const selectedLabText = this.options[this.selectedIndex].text;
    
    console.log('🔍 Laboratuvar seçildi - selectedLabId:', selectedLabId, 'selectedLabText:', selectedLabText);
    
    if (selectedLabId) {
        console.log('✅ Laboratuvar ID var, loadPCCards çağrılıyor');
        loadPCCards(selectedLabId, selectedLabText);
        editPCBtn.style.display = 'block'; // PC düzenleme butonunu göster
        editMaxStudentsBtn.style.display = 'block'; // Maksimum öğrenci sayısı butonunu göster
        
        // Dışa aktar butonunu aktif hale getir
        if (typeof updateExportButtonState === 'function') {
            updateExportButtonState();
        }
        
        // Filtreleme sistemini tetikle
        if (window.studentYearFilter) {
            // Lab değişikliği eventi gönder
            const labData = {
                name: selectedLabText.split(' (')[0],
                students: getStudentsFromPCCards() // PC kartlarından öğrenci verilerini al
            };
            
            const event = new CustomEvent('labChanged', { detail: labData });
            document.dispatchEvent(event);
        }
    } else {
        console.log('❌ Laboratuvar ID yok, PC kartları gizleniyor');
        pcCardsContainer.style.display = 'none';
        editPCBtn.style.display = 'none'; // PC düzenleme butonunu gizle
        editMaxStudentsBtn.style.display = 'none'; // Maksimum öğrenci sayısı butonunu gizle
        
        // Dışa aktar butonunu pasif hale getir
        if (typeof updateExportButtonState === 'function') {
            updateExportButtonState();
        }
        
        // Filtreleme panelini gizle
        if (window.studentYearFilter) {
            window.studentYearFilter.hideFilterPanel();
        }
    }
});

// Yenile butonu
refreshBtn.addEventListener('click', function() {
    const selectedLabId = labSelector.value;
    const selectedLabText = labSelector.options[labSelector.selectedIndex].text;
    
    if (selectedLabId) {
        loadPCCards(selectedLabId, selectedLabText);
    }
});

// PC sayısı düzenleme butonu
editPCBtn.addEventListener('click', function() {
    const selectedLabId = labSelector.value;
    const selectedOption = labSelector.options[labSelector.selectedIndex];
    const labName = selectedOption.text.split(' (')[0]; // Lab adını al
    const currentPCCount = selectedOption.getAttribute('data-pc-count');
    
    if (selectedLabId && currentPCCount) {
        openEditPCCountModal(selectedLabId, labName, currentPCCount);
    }
});

// Maksimum öğrenci sayısı düzenleme butonu
const editMaxStudentsBtn = document.getElementById('editMaxStudents');
editMaxStudentsBtn.addEventListener('click', function() {
    const selectedLabId = labSelector.value;
    const selectedOption = labSelector.options[labSelector.selectedIndex];
    const labName = selectedOption.text.split(' (')[0]; // Lab adını al
    
    if (selectedLabId) {
        openEditMaxStudentsModal(selectedLabId, labName);
    }
});

// PC kartlarını yükle
function loadPCCards(labId, labName) {
    console.log('🔄 loadPCCards çağrıldı - labId:', labId, 'labName:', labName);
    pcLoadingIndicator.style.display = 'block';
    pcCardsContainer.style.display = 'none';
    
    // AJAX ile PC verilerini getir
    const url = `../controllers/AssignmentController.php?action=get_lab_pcs&lab_id=${labId}`;
    console.log('📡 İstek URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📡 Response data:', data);
            if (data.success) {
                console.log('✅ PC verileri başarıyla yüklendi, PC sayısı:', data.pcs ? data.pcs.length : 0);
                displayPCCards(data.pcs, labName, labId);
            } else {
                console.error('❌ PC verileri yüklenirken hata:', data.message);
                showToast('PC verileri yüklenirken hata oluştu: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('❌ Fetch hatası:', error);
            showToast('PC verileri yüklenirken bir hata oluştu', 'error');
        })
        .finally(() => {
            console.log('🔄 Loading indicator kapatılıyor');
            pcLoadingIndicator.style.display = 'none';
        });
}

// PC kartlarını görüntüle
function displayPCCards(pcs, labName, labId) {
    console.log('🎨 displayPCCards çağrıldı - pcs:', pcs, 'labName:', labName, 'labId:', labId);
    
    const pcCardsLabName = document.getElementById('pcCardsLabName');
    if (pcCardsLabName) {
        pcCardsLabName.textContent = labName + ' PC\'leri';
    }
    
    // Global labId'yi sakla
    window.currentLabId = labId;
    window.currentLabName = labName;
    
    let availableCount = 0;
    let occupiedCount = 0;
    
    let cardsHTML = '';
    
    pcs.forEach(pc => {
        const isOccupied = pc.students && pc.students.length > 0;
        if (isOccupied) {
            occupiedCount++;
        } else {
            availableCount++;
        }
        
        const statusClass = isOccupied ? 'occupied' : 'available';
        const statusText = isOccupied ? 'Dolu' : 'Boş';
        const statusIcon = isOccupied ? 'fas fa-user' : 'fas fa-desktop';
        
        // Öğrenci sayısı bilgisini ekle
        const studentCount = pc.students ? pc.students.length : 0;
        
        let studentInfo = '';
        if (isOccupied && pc.students && pc.students.length > 0) {
            if (pc.students.length >= 1) {
                // Her öğrenciyi ayrı kutu içinde göster
                let studentsList = '';
                pc.students.forEach(student => {
                    studentsList += `
                        <div class="student-item">
                            <div class="student-name clickable-student" data-student-number="${student.sdt_nmbr}" data-student-name="${student.full_name}">${student.full_name}</div>
                            <div class="student-year">${student.academic_year || 'N/A'}</div>
                        </div>
                    `;
                });
                
                studentInfo = `<div class="student-info">
                    <div class="students-list">
                        ${studentsList}
                    </div>
                </div>`;
            }
            
        }
        
        // PC numarasını güvenli şekilde oluştur
        let pcNumber = '0';
        if (pc.pc_number !== undefined && pc.pc_number !== null) {
            pcNumber = pc.pc_number.toString();
        }
        const pcDisplayNumber = `PC${pcNumber.padStart(2, '0')}`;
        
        // PC ID'si olarak gerçek PC ID'sini kullan
        const pcId = pc.pc_id || pcNumber; // Önce pc_id, yoksa PC numarası
        
        // Çok sayıda öğrenci için özel sınıf (4 veya daha fazla öğrenci)
        const manyStudentsClass = pc.students && pc.students.length >= 4 ? 'many-students' : '';
        
        cardsHTML += `
            <div class="pc-card ${statusClass} ${manyStudentsClass}" data-pc-id="${pcId}" data-pc-number="${pcNumber}">
                <div class="pc-card-header">
                    <div class="pc-number">${pcDisplayNumber}</div>
                    <div class="pc-status">
                        <i class="${statusIcon}"></i>
                        <span>${statusText}</span>
                        ${isOccupied ? `<span class="student-count-badge">${studentCount}</span>` : ''}
                    </div>
                </div>
                <div class="pc-card-body">
                    ${studentInfo}
                    ${!isOccupied ? '<div class="empty-pc"><i class="fas fa-plus-circle"></i><span>Öğrenci Atanabilir</span></div>' : ''}
                </div>
                <div class="pc-card-actions">
                    <button class="action-btn update-btn" onclick="openPCUpdate(window.currentLabId, window.currentLabName)" title="PC Güncelle">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${isOccupied ? `<button class="action-btn view-btn" onclick="viewPCDetails(${pcId}, '${pcNumber}')" title="PC Detayları"><i class="fas fa-eye"></i></button>` : ''}
                    <button class="action-btn assign-btn" onclick="assignStudent(${pcId}, '${pcNumber}')" title="Öğrenci Ata"><i class="fas fa-user-plus"></i></button>
                </div>
            </div>
        `;
    });
    
    console.log('🎨 PC kartları HTML oluşturuldu, kart sayısı:', pcs.length);
    console.log('🎨 Available PCs:', availableCount, 'Occupied PCs:', occupiedCount);
    
    pcCardsGrid.innerHTML = cardsHTML;
    availablePCs.textContent = availableCount;
    occupiedPCs.textContent = occupiedCount;
    
    console.log('🎨 pcCardsContainer görünür yapılıyor');
    pcCardsContainer.style.display = 'block';
    
    // Kartlara animasyon ekle
    setTimeout(() => {
        const cards = document.querySelectorAll('.pc-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 50);
        });
        
        // Öğrenci isimlerine tıklama event listener'ı ekle
        addStudentNameClickListeners();
    }, 100);
    
    // Filtreleme sistemini güncelle
    if (window.studentYearFilter) {
        // PC kartları güncellendi eventi gönder
        const event = new CustomEvent('pcCardsUpdated');
        document.dispatchEvent(event);
    }
}


// Öğrenci isimlerine tıklama event listener'ları ekle
function addStudentNameClickListeners() {
    const clickableStudents = document.querySelectorAll('.clickable-student');
    clickableStudents.forEach(studentName => {
        studentName.addEventListener('click', function() {
            const studentNumber = this.getAttribute('data-student-number');
            const studentName = this.getAttribute('data-student-name');
            
            // Okul numarasını bildirim olarak göster
            showToast(`Öğrenci: ${studentName}<br>Okul Numarası: ${studentNumber}`, 'info', 'Öğrenci Bilgisi');
        });
    });
}

// PC detaylarını görüntüle
function viewPCDetails(pcId, pcNumber) {
    console.log('🚀 === viewPCDetails BAŞLADI ===');
    console.log('📋 Gelen pcId:', pcId, 'Type:', typeof pcId);
    console.log('📋 Gelen pcNumber:', pcNumber, 'Type:', typeof pcNumber);
    
    // PC ID'sini global değişkene kaydet
    window.currentPCId = pcId;
    window.currentPCNumber = pcNumber;
    
    // Modal elementlerini kontrol et
    const titleElement = document.getElementById('pcDetailsTitle');
    const modalElement = document.getElementById('pcDetailsModal');
    
    if (!titleElement) {
        console.error('❌ pcDetailsTitle elementi bulunamadı!');
        showToast('PC detayları modalı yüklenemedi', 'error');
        return;
    }
    
    if (!modalElement) {
        console.error('❌ pcDetailsModal elementi bulunamadı!');
        showToast('PC detayları modalı bulunamadı', 'error');
        return;
    }
    
    // Modal başlığını güncelle
    titleElement.textContent = `PC ${pcNumber} - Atanmış Öğrenciler`;
    
    // PC detaylarını yükle
    loadPCDetails(pcId, pcNumber);
    
    // Modal'ı aç
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

// PC detaylarını yükle
function loadPCDetails(pcId, pcNumber) {
    console.log('📋 PC detayları yükleniyor:', pcId, pcNumber);
    
    // Students list elementini kontrol et
    const studentsListElement = document.getElementById('pcStudentsList');
    if (!studentsListElement) {
        console.error('❌ pcStudentsList elementi bulunamadı!');
        return;
    }
    
    // Loading göster
    studentsListElement.innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="fas fa-spinner fa-spin me-2"></i>Yükleniyor...
        </div>
    `;
    
    // AJAX ile PC detaylarını getir
    fetch('../controllers/AssignmentController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_pc_details&pc_id=${pcId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('📋 PC detayları yanıtı:', data);
        if (data.success) {
            displayPCDetails(data.pc, data.students, data.lab);
        } else {
            showToast('PC detayları yüklenirken hata oluştu: ' + data.message, 'error');
            document.getElementById('pcStudentsList').innerHTML = `
                <div class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>Hata: ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('❌ PC detayları yükleme hatası:', error);
        showToast('PC detayları yüklenirken bir hata oluştu', 'error');
        document.getElementById('pcStudentsList').innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="fas fa-exclamation-triangle me-2"></i>Bağlantı hatası
            </div>
        `;
    });
}

// PC detaylarını görüntüle
function displayPCDetails(pc, students, lab) {
    console.log('📋 PC detayları görüntüleniyor:', pc, students, lab);
    
    // Elementleri güvenli şekilde güncelle
    const updateElement = (id, value) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        } else {
            console.warn(`⚠️ Element bulunamadı: ${id}`);
        }
    };
    
    // PC bilgilerini güncelle
    updateElement('pcDetailsNumber', pc.pc_number || pc.name || pc.number || 'Bilinmiyor');
    updateElement('pcDetailsLab', pc.lab_name || (lab ? lab.name : 'Bilinmiyor'));
    updateElement('pcDetailsStudentCount', pc.student_count || (students ? students.length : 0));
    
    // Durum badge'ini güncelle
    const statusElement = document.getElementById('pcDetailsStatus');
    if (statusElement) {
        if (pc.status === 'occupied' || (pc.student_count && pc.student_count > 0)) {
            statusElement.textContent = 'Dolu';
            statusElement.className = 'badge bg-danger';
        } else {
            statusElement.textContent = 'Boş';
            statusElement.className = 'badge bg-success';
        }
    }
    
    // Son atama tarihini güncelle
    const lastAssignment = document.getElementById('pcDetailsLastAssignment');
    if (lastAssignment) {
        if (pc.last_assignment) {
            lastAssignment.textContent = new Date(pc.last_assignment).toLocaleDateString('tr-TR');
        } else if (students && students.length > 0) {
            // En son atanan öğrenciyi bul
            const lastStudent = students[students.length - 1];
            lastAssignment.textContent = lastStudent.assigned_at ? new Date(lastStudent.assigned_at).toLocaleDateString('tr-TR') : 'Bilinmiyor';
        } else {
            lastAssignment.textContent = 'Atanmamış';
        }
    }
    
    // Öğrenci listesini görüntüle
    displayPCStudents(students);
}

// PC'ye atanmış öğrencileri görüntüle
function displayPCStudents(students) {
    const studentsList = document.getElementById('pcStudentsList');
    
    if (!studentsList) {
        console.error('❌ pcStudentsList elementi bulunamadı!');
        return;
    }
    
    if (!students || students.length === 0) {
        studentsList.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-user-slash me-2"></i>Bu PC'ye henüz öğrenci atanmamış
            </div>
        `;
        return;
    }
    
    let studentsHTML = '<div class="row">';
    
    students.forEach((student, index) => {
        // Her 4 öğrenciden sonra yeni satır başlat
        if (index > 0 && index % 4 === 0) {
            studentsHTML += '</div><div class="row">';
        }
        
        studentsHTML += `
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column h-100">
                            <div class="flex-grow-1">
                                <h6 class="mb-2 text-truncate" title="${student.full_name}">${student.full_name}</h6>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-id-card me-1"></i>${student.sdt_nmbr}
                                </div>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-calendar me-1"></i>${student.academic_year}
                                </div>
                                ${student.assigned_at ? `
                                    <div class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>${new Date(student.assigned_at).toLocaleDateString('tr-TR')}
                                    </div>
                                ` : ''}
                            </div>
                            <div class="mt-auto">
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-outline-warning btn-sm" onclick="transferStudent(${student.student_id}, ${student.assignment_id})" title="Taşı">
                                        <i class="fas fa-exchange-alt me-1"></i>Taşı
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="removeStudentFromPC(${student.assignment_id})" title="Kaldır">
                                        <i class="fas fa-times me-1"></i>Kaldır
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    studentsHTML += '</div>';
    studentsList.innerHTML = studentsHTML;
}

// PC detaylarını yenile
function refreshPCDetails() {
    if (window.currentPCId && window.currentPCNumber) {
        loadPCDetails(window.currentPCId, window.currentPCNumber);
    }
}

// PC'den öğrenci kaldır
function removeStudentFromPC(assignmentId) {
    if (!confirm('Bu öğrenciyi PC\'den kaldırmak istediğinizden emin misiniz?')) {
        return;
    }
    
    console.log('📋 PC\'den öğrenci kaldırılıyor:', assignmentId);
    
    fetch('../controllers/AssignmentController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_student_from_pc&assignment_id=${assignmentId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('📋 Kaldırma yanıtı:', data);
        if (data.success) {
            showToast('Öğrenci PC\'den kaldırıldı!', 'success');
            
            // PC detaylarını yenile
            refreshPCDetails();
            
            // Ana sayfadaki PC kartlarını yenile
            if (typeof loadPCCards === 'function') {
                loadPCCards(window.currentLabId, window.currentLabName);
            }
        } else {
            showToast('Kaldırma sırasında hata oluştu: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Kaldırma hatası:', error);
        showToast('Kaldırma sırasında bir hata oluştu', 'error');
    });
}

// Öğrenciyi başka PC'ye taşı
function transferStudent(studentId, assignmentId) {
    console.log('📋 Öğrenci taşınıyor:', studentId, assignmentId);
    
    // Mevcut PC bilgilerini al
    const currentPCId = window.currentPCId;
    const currentPCNumber = window.currentPCNumber;
    const currentLabId = window.currentLabId;
    
    if (!currentPCId || !currentLabId) {
        showToast('PC bilgileri bulunamadı!', 'error');
        return;
    }
    
    // Transfer modalını göster
    showTransferModal(studentId, assignmentId, currentPCId, currentPCNumber, currentLabId);
}

// Transfer modalını göster
function showTransferModal(studentId, assignmentId, currentPCId, currentPCNumber, currentLabId) {
    // Modal HTML'ini oluştur
    const modalHTML = `
        <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="transferModalLabel">
                            <i class="fas fa-exchange-alt me-2"></i>Öğrenci Taşı
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Mevcut PC:</strong> PC ${currentPCNumber} - Laboratuvar ${currentLabId}
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hedef PC Seçin:</label>
                            <div id="availablePCsList" class="row">
                                <div class="col-12 text-center">
                                    <i class="fas fa-spinner fa-spin me-2"></i>PC'ler yükleniyor...
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning" id="transferWarning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="transferWarningText"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-warning" id="confirmTransfer" disabled>
                            <i class="fas fa-exchange-alt me-2"></i>Taşı
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Modal'ı DOM'a ekle
    const existingModal = document.getElementById('transferModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Modal'ı göster
    const modal = new bootstrap.Modal(document.getElementById('transferModal'));
    modal.show();
    
    // Mevcut PC'yi seçili olarak işaretle
    window.transferData = {
        studentId: studentId,
        assignmentId: assignmentId,
        currentPCId: currentPCId,
        currentPCNumber: currentPCNumber,
        currentLabId: currentLabId,
        selectedPCId: null
    };
    
    // Kullanılabilir PC'leri yükle
    loadAvailablePCsForTransfer(currentLabId, currentPCId);
}

// Transfer için kullanılabilir PC'leri yükle
function loadAvailablePCsForTransfer(labId, excludePCId) {
    const availablePCsList = document.getElementById('availablePCsList');
    
    console.log('📋 Transfer için PCler yükleniyor - labId:', labId, 'excludePCId:', excludePCId);
    
    fetch(`../controllers/AssignmentController.php?action=get_lab_pcs&computer_id=${labId}`)
    .then(response => response.json())
    .then(data => {
        console.log('📋 Transfer PC yanıtı:', data);
        if (data.success) {
            displayAvailablePCsForTransfer(data.pcs, excludePCId);
        } else {
            availablePCsList.innerHTML = `
                <div class="col-12 text-center text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>PC'ler yüklenirken hata oluştu: ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('❌ PC yükleme hatası:', error);
        availablePCsList.innerHTML = `
            <div class="col-12 text-center text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>Bağlantı hatası
            </div>
        `;
    });
}

// Transfer için kullanılabilir PC'leri göster
function displayAvailablePCsForTransfer(pcs, excludePCId) {
    const availablePCsList = document.getElementById('availablePCsList');
    
    console.log('📋 Transfer PCleri gösteriliyor:', pcs, 'excludePCId:', excludePCId);
    
    if (!pcs || pcs.length === 0) {
        availablePCsList.innerHTML = `
            <div class="col-12 text-center text-muted">
                <i class="fas fa-desktop me-2"></i>Bu laboratuvarda başka PC bulunamadı
            </div>
        `;
        return;
    }
    
    let pcsHTML = '';
    pcs.forEach(pc => {
        // Mevcut PC'yi hariç tut
        if (pc.pc_id == excludePCId) {
            console.log('📋 PC haric tutuluyor:', pc.pc_id, 'excludePCId:', excludePCId);
            return;
        }
        
        const isOccupied = pc.student_count > 0;
        const statusClass = isOccupied ? 'border-warning' : 'border-success';
        const statusText = isOccupied ? 'Dolu' : 'Boş';
        const statusIcon = isOccupied ? 'fas fa-user' : 'fas fa-user-plus';
        
        console.log('📋 PC ekleniyor:', pc.pc_id, pc.pc_number, 'isOccupied:', isOccupied);
        
        pcsHTML += `
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                <div class="card pc-transfer-card ${statusClass} cursor-pointer h-100" 
                     data-pc-id="${pc.pc_id}" 
                     data-pc-number="${pc.pc_number}"
                     onclick="selectPCForTransfer(${pc.pc_id}, '${pc.pc_number}', ${isOccupied})">
                    <div class="card-body text-center p-2">
                        <h6 class="card-title mb-1">PC ${pc.pc_number}</h6>
                        <div class="mb-1">
                            <i class="${statusIcon} me-1"></i>
                            <span class="badge ${isOccupied ? 'bg-warning' : 'bg-success'}">${statusText}</span>
                        </div>
                        ${isOccupied ? `
                            <small class="text-muted">${pc.student_count} öğrenci</small>
                        ` : `
                            <small class="text-success">Müsait</small>
                        `}
                    </div>
                </div>
            </div>
        `;
    });
    
    availablePCsList.innerHTML = pcsHTML;
    console.log('📋 Transfer PC HTML oluşturuldu, PC sayısı:', pcsHTML.split('col-lg-2').length - 1);
}

// Transfer için PC seç
function selectPCForTransfer(pcId, pcNumber, isOccupied) {
    // Önceki seçimi temizle
    document.querySelectorAll('.pc-transfer-card').forEach(card => {
        card.classList.remove('selected', 'border-primary');
        card.classList.add('border-light');
    });
    
    // Yeni seçimi işaretle
    const selectedCard = document.querySelector(`[data-pc-id="${pcId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected', 'border-primary');
        selectedCard.classList.remove('border-light');
    }
    
    // Transfer verilerini güncelle
    window.transferData.selectedPCId = pcId;
    window.transferData.selectedPCNumber = pcNumber;
    
    // Uyarı mesajını göster/gizle
    const warningDiv = document.getElementById('transferWarning');
    const warningText = document.getElementById('transferWarningText');
    const confirmBtn = document.getElementById('confirmTransfer');
    
    if (isOccupied) {
        warningDiv.style.display = 'block';
        warningText.textContent = `Bu PC'de zaten ${pcNumber} öğrenci var. Taşıma işlemi devam edecek.`;
    } else {
        warningDiv.style.display = 'none';
    }
    
    // Transfer butonunu aktif et
    confirmBtn.disabled = false;
    confirmBtn.onclick = () => executeTransfer();
}

// Transfer işlemini gerçekleştir
function executeTransfer() {
    const transferData = window.transferData;
    
    if (!transferData || !transferData.selectedPCId) {
        showToast('Lütfen hedef PC seçin!', 'error');
        return;
    }
    
    if (!confirm(`Öğrenciyi PC ${transferData.currentPCNumber} 'den PC ${transferData.selectedPCNumber} 'ye taşımak istediğinizden emin misiniz?`)) {
        return;
    }
    
    const confirmBtn = document.getElementById('confirmTransfer');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Taşınıyor...';
    
    fetch('../controllers/AssignmentController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=transfer_student&student_id=${transferData.studentId}&new_pc_id=${transferData.selectedPCId}&computer_id=${transferData.currentLabId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Öğrenci başarıyla taşındı!', 'success');
            
            // Modal'ı kapat
            const modal = bootstrap.Modal.getInstance(document.getElementById('transferModal'));
            modal.hide();
            
            // PC detaylarını yenile
            refreshPCDetails();
            
            // Ana sayfadaki PC kartlarını yenile
            if (typeof loadPCCards === 'function') {
                loadPCCards(window.currentLabId, window.currentLabName);
            }
        } else {
            showToast('Taşıma sırasında hata oluştu: ' + data.message, 'error');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Taşı';
        }
    })
    .catch(error => {
        console.error('❌ Transfer hatası:', error);
        showToast('Taşıma sırasında bir hata oluştu', 'error');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-exchange-alt me-2"></i>Taşı';
    });
}

// Öğrenci atama
function assignStudent(pcId, pcNumber) {
    console.log('🚀 === assignStudent BAŞLADI ===');
    console.log('📋 Gelen pcId:', pcId, 'Type:', typeof pcId);
    console.log('📋 Gelen pcNumber:', pcNumber, 'Type:', typeof pcNumber);
    
    // pcId ve pcNumber değerlerini kontrol et
    if (!pcId || pcId === 'undefined' || pcId === 'null' || pcId === 0) {
        console.error('❌ Geçersiz PC ID:', pcId);
        showToast('PC ID geçersiz! Lütfen sayfayı yenileyin.', 'error', 'Atama Hatası');
        return;
    }
    
    if (!pcNumber || pcNumber === 'undefined' || pcNumber === 'null' || pcNumber === 0) {
        console.error('❌ Geçersiz PC Number:', pcNumber);
        showToast('PC Numarası geçersiz! Lütfen sayfayı yenileyin.', 'error', 'Atama Hatası');
        return;
    }
    
    // Seçili laboratuvar ID'sini al - hem dashboard hem assign sayfası için
    let selectedLabId;
    
    // Dashboard sayfası için
    if (typeof labSelector !== 'undefined' && labSelector) {
        selectedLabId = labSelector.value;
    }
    // Assign sayfası için URL'den al
    else if (window.location.pathname.includes('assign.php')) {
        const urlParams = new URLSearchParams(window.location.search);
        selectedLabId = urlParams.get('computer_id');
    }
    
    console.log('📋 selectedLabId:', selectedLabId, 'Type:', typeof selectedLabId);
    
    if (!selectedLabId) {
        console.error('❌ Laboratuvar seçilmemiş!');
        showToast('Lütfen önce bir laboratuvar seçin!', 'error');
        return;
    }
    
    // PC kartından pc-number değerini al (HTML'den)
    const pcCard = document.querySelector(`[data-pc-id="${pcId}"]`);
    let pcDisplayNumber = `PC${pcNumber.toString().padStart(2, '0')}`; // varsayılan değer
    
    console.log('📋 PC kartı bulundu mu:', !!pcCard);
    
    if (pcCard) {
        const pcNumberElement = pcCard.querySelector('.pc-card-header .pc-number');
        if (pcNumberElement) {
            pcDisplayNumber = pcNumberElement.textContent.trim();
            console.log('📋 PC kartından alınan numara:', pcDisplayNumber);
        }
    }
    
    // PC ID'yi doğru formatta hesapla (computer_id * 100 + pc_number)
    const finalPcId = parseInt(selectedLabId) * 100 + parseInt(pcNumber);
    console.log('📋 Hesaplanan finalPcId:', finalPcId, '(selectedLabId:', selectedLabId, 'x 100 + pcNumber:', pcNumber, ')');
    
    // Atama sistemi modal'ını aç
    console.log('📋 Modal açılıyor - finalPcId:', finalPcId, 'pcNumber:', pcNumber, 'selectedLabId:', selectedLabId);
    openAssignmentModal(finalPcId, pcNumber, selectedLabId, pcDisplayNumber);
}

// Atama sistemi modal'ını aç
function openAssignmentModal(pcId, pcNumber, selectedLabId = null, pcDisplayNumber = null) {
    console.log('🚀 === openAssignmentModal BAŞLADI ===');
    console.log('📋 pcId:', pcId, 'Type:', typeof pcId);
    console.log('📋 pcNumber:', pcNumber, 'Type:', typeof pcNumber);
    console.log('📋 selectedLabId:', selectedLabId, 'Type:', typeof selectedLabId);
    console.log('📋 pcDisplayNumber:', pcDisplayNumber);
    
    // Eğer selectedLabId verilmemişse labSelector'dan al
    if (!selectedLabId) {
        selectedLabId = labSelector.value;
        console.log('📋 selectedLabId labSelector\'dan alındı:', selectedLabId);
    }
    
    if (!selectedLabId) {
        console.error('❌ Laboratuvar ID bulunamadı!');
        showToast('Lütfen önce bir laboratuvar seçin!', 'error');
        return;
    }
    
    // Laboratuvar adını al
    const selectedOption = labSelector.options[labSelector.selectedIndex];
    const fullText = selectedOption.text;
    const labName = fullText.split(' (')[0];
    const pcInfo = fullText.split(' (')[1] ? fullText.split(' (')[1].replace(')', '') : '';
    
    // Modal'ı göster
    const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
    modal.show();
    
    // Modal tamamen açıldıktan sonra içeriği güncelle
    document.getElementById('assignmentModal').addEventListener('shown.bs.modal', function() {
        console.log('📋 Modal tamamen açıldı, içerik güncelleniyor...');
        
        // Modal başlığını güncelle
        const titleElement = document.getElementById('assignmentModalTitle');
        if (titleElement) {
            titleElement.textContent = `PC${pcNumber.toString().padStart(2, '0')} - Öğrenci Ekle`;
            console.log('📋 Modal başlığı güncellendi:', titleElement.textContent);
        }
        
        // Hidden input'ları güncelle
        const pcIdInput = document.getElementById('selectedPCId');
        const computerIdInput = document.getElementById('selectedComputerId');
        
        console.log('📋 PC ID Input Element:', pcIdInput);
        console.log('📋 Computer ID Input Element:', computerIdInput);
        
        if (pcIdInput) {
            // pcId değerini kontrol et ve temizle
            let cleanPcId = pcId;
            if (typeof pcId === 'string' && pcId.includes('+ pc.pc_id +')) {
                console.warn('⚠️ PC ID template string olarak geldi, varsayılan değer kullanılıyor');
                cleanPcId = '1'; // Varsayılan değer
            }
            
            pcIdInput.value = cleanPcId;
            console.log('📋 PC ID set edildi:', pcIdInput.value, 'Orijinal:', pcId);
        } else {
            console.error('❌ PC ID input bulunamadı!');
        }
        
        if (computerIdInput) {
            computerIdInput.value = selectedLabId;
            console.log('📋 Computer ID set edildi:', computerIdInput.value);
        } else {
            console.error('❌ Computer ID input bulunamadı!');
        }
        
        // PC numarasını güncelle
        const pcNumberElement = document.getElementById('selectedPCNumber');
        if (pcNumberElement) {
            const pcDisplayText = pcDisplayNumber || `PC${pcNumber.toString().padStart(2, '0')}`;
            pcNumberElement.innerHTML = pcDisplayText;
            pcNumberElement.textContent = pcDisplayText;
            console.log('📋 PC Number güncellendi:', pcDisplayText);
        } else {
            console.error('❌ PC Number element bulunamadı!');
        }
        
        // Laboratuvar adını güncelle
        const labNameElement = document.getElementById('selectedLabName');
        if (labNameElement) {
            labNameElement.innerHTML = labName;
            labNameElement.textContent = labName;
            console.log('📋 Lab name güncellendi:', labName);
        } else {
            console.error('❌ Lab name element bulunamadı!');
        }
        
        // Öğrenci verilerini yükle
        console.log('📋 Öğrenci verileri yükleniyor - selectedLabId:', selectedLabId, 'pcId:', pcId);
        loadStudentCards(selectedLabId, pcId);
        
    }, { once: true });
}


// Öğrenci listesini yükle
function loadStudentCards(computerId, pcId) {
    const studentListContainer = document.getElementById('studentListContainer');
    const loadingIndicator = document.getElementById('studentLoadingIndicator');
    
    // Loading göster
    loadingIndicator.style.display = 'block';
    studentListContainer.innerHTML = '';
    
    // AJAX ile öğrenci verilerini getir
    fetch(`../controllers/AssignmentController.php?action=get_students_for_assignment&computer_id=${computerId}&pc_id=${pcId}&max_students=4`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySimpleStudentList(data.students, data.maxStudentsPerCard || 4);
            } else {
                showToast('Öğrenci verileri yüklenirken hata oluştu: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Öğrenci verileri yüklenirken bir hata oluştu', 'error');
        })
        .finally(() => {
            loadingIndicator.style.display = 'none';
        });
}

// Basit öğrenci listesi görüntüle
function displaySimpleStudentList(students, maxStudentsPerCard = 4) {
    const studentListContainer = document.getElementById('studentListContainer');
    
    // Sadece atanmamış öğrencileri filtrele
    const availableStudents = students.filter(student => !student.is_assigned);
    
    // Mevcut PC'deki öğrenci sayısını al
    const currentStudentCount = getCurrentPCStudentCount();
    const remainingSlots = maxStudentsPerCard - currentStudentCount;
    
    // Sınır uyarısını göster/gizle
    updateStudentLimitWarning(currentStudentCount, maxStudentsPerCard, remainingSlots);
    
    if (availableStudents.length === 0) {
        studentListContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Tüm öğrenciler zaten atanmış</h6>
                <p class="text-muted">Bu PC'ye atanabilecek öğrenci bulunmuyor.</p>
            </div>
        `;
        return;
    }
    
    // Eğer PC dolu ise uyarı göster
    if (remainingSlots <= 0) {
        studentListContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h6 class="text-warning">PC Dolu!</h6>
                <p class="text-muted">Bu PC'ye maksimum ${maxStudentsPerCard} öğrenci atanabilir. Önce mevcut öğrencilerden birini kaldırın.</p>
            </div>
        `;
        return;
    }
    
    let listHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-sm btn-outline-primary" onclick="selectAllStudents()">
                <i class="fas fa-check-double me-1"></i>Tümünü Seç
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="deselectAllStudents()">
                <i class="fas fa-times me-1"></i>Tümünü Kaldır
            </button>
        </div>
        <div class="row">
    `;
    
    // Kalan slot sayısına göre öğrenci sayısını sınırla
    const limitedStudents = availableStudents.slice(0, remainingSlots);
    
    limitedStudents.forEach(student => {
        listHTML += `
            <div class="col-md-6 mb-3">
                <div class="student-item-simple">
                    <div class="form-check">
                        <input class="form-check-input student-checkbox" 
                               type="checkbox" 
                               value="${student.student_id}"
                               id="student_${student.student_id}"
                               onchange="checkStudentLimit(${maxStudentsPerCard})">
                        <label class="form-check-label w-100" for="student_${student.student_id}">
                            <div class="student-info-simple">
                                <div class="student-name-simple">${student.full_name}</div>
                                <div class="student-details-simple">
                                    <small class="text-muted">
                                        <i class="fas fa-id-card me-1"></i>${student.sdt_nmbr}
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-calendar me-1"></i>${student.academic_year}
                                    </small>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Eğer daha fazla öğrenci varsa uyarı göster
    if (availableStudents.length > remainingSlots) {
        listHTML += `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Sadece ${remainingSlots} öğrenci daha atanabilir. ${availableStudents.length - remainingSlots} öğrenci gösterilmiyor.
                </div>
            </div>
        `;
    }
    
    listHTML += '</div>';
    studentListContainer.innerHTML = listHTML;
    
    // Seçili öğrenci sayısını güncelle
    updateSelectedCount();
}

// Tüm öğrencileri seç
function selectAllStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const currentStudentCount = getCurrentPCStudentCount();
    const maxStudentsPerCard = 4; // Varsayılan sınır
    const remainingSlots = maxStudentsPerCard - currentStudentCount;
    
    let selectedCount = 0;
    checkboxes.forEach(checkbox => {
        if (selectedCount < remainingSlots) {
            checkbox.checked = true;
            selectedCount++;
        } else {
            checkbox.checked = false;
        }
    });
    
    if (checkboxes.length > remainingSlots) {
        showToast(`Sadece ${remainingSlots} öğrenci daha seçilebilir. ${checkboxes.length - remainingSlots} öğrenci seçilemedi.`, 'warning', 'Sınır Uyarısı');
    }
    
    updateSelectedCount();
}

// Tüm öğrencileri kaldır
function deselectAllStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

// Seçili öğrenci sayısını güncelle
function updateSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    const selectedCountElement = document.getElementById('selectedStudentCount');
    
    if (selectedCountElement) {
        selectedCountElement.textContent = selectedCount;
    }
}

// Mevcut PC'deki öğrenci sayısını al
function getCurrentPCStudentCount() {
    // PC kartından mevcut öğrenci sayısını al
    const pcCard = document.querySelector(`[data-pc-id="${window.currentPCId}"]`);
    if (pcCard) {
        const studentCountBadge = pcCard.querySelector('.student-count-badge');
        if (studentCountBadge) {
            return parseInt(studentCountBadge.textContent) || 0;
        }
    }
    return 0;
}

// Öğrenci sınırı uyarısını güncelle
function updateStudentLimitWarning(currentCount, maxCount, remainingSlots) {
    const warningDiv = document.getElementById('studentLimitWarning');
    const maxStudentsElement = document.getElementById('maxStudentsCount');
    const warningMaxStudentsElement = document.getElementById('warningMaxStudents');
    const currentStudentCountElement = document.getElementById('currentStudentCount');
    const maxStudentsLimitElement = document.getElementById('maxStudentsLimit');
    
    if (warningDiv && maxStudentsElement && warningMaxStudentsElement && currentStudentCountElement) {
        maxStudentsElement.textContent = maxCount;
        warningMaxStudentsElement.textContent = maxCount;
        currentStudentCountElement.textContent = currentCount;
        
        if (remainingSlots <= 2) {
            warningDiv.style.display = 'block';
            if (maxStudentsLimitElement) {
                maxStudentsLimitElement.style.display = 'inline-block';
            }
        } else {
            warningDiv.style.display = 'none';
            if (maxStudentsLimitElement) {
                maxStudentsLimitElement.style.display = 'none';
            }
        }
    }
}

// Öğrenci seçimi sırasında sınır kontrolü
function checkStudentLimit(maxStudentsPerCard) {
    const currentStudentCount = getCurrentPCStudentCount();
    const selectedCount = document.querySelectorAll('.student-checkbox:checked').length;
    const totalSelected = currentStudentCount + selectedCount;
    
    if (totalSelected > maxStudentsPerCard) {
        // Sınır aşıldı, son seçimi geri al
        const checkboxes = document.querySelectorAll('.student-checkbox:checked');
        const lastChecked = checkboxes[checkboxes.length - 1];
        if (lastChecked) {
            lastChecked.checked = false;
        }
        
        showToast(`Maksimum ${maxStudentsPerCard} öğrenci atanabilir! Şu anda ${currentStudentCount} öğrenci atanmış durumda.`, 'warning', 'Sınır Aşıldı');
    }
    
    updateSelectedCount();
}


// Atama işlemini gerçekleştir
function performAssignment() {
    console.log('🚀 === performAssignment BAŞLADI ===');
    
    const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
    const selectedStudentIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    const pcId = document.getElementById('selectedPCId').value;
    const computerId = document.getElementById('selectedComputerId').value;
    
    console.log('📋 Seçili öğrenci sayısı:', selectedStudentIds.length);
    console.log('📋 Seçili öğrenci ID\'leri:', selectedStudentIds);
    console.log('📋 PC ID (form\'dan):', pcId, 'Type:', typeof pcId);
    console.log('📋 Computer ID (form\'dan):', computerId, 'Type:', typeof computerId);
    
    if (selectedStudentIds.length === 0) {
        showToast('Lütfen en az bir öğrenci seçin!', 'warning', 'Uyarı');
        return;
    }
    
    // Öğrenci sınırı kontrolü
    const currentStudentCount = getCurrentPCStudentCount();
    const totalAfterAssignment = currentStudentCount + selectedStudentIds.length;
    const maxStudentsPerCard = 4; // Varsayılan sınır
    
    if (totalAfterAssignment > maxStudentsPerCard) {
        showToast(`Maksimum ${maxStudentsPerCard} öğrenci atanabilir! Şu anda ${currentStudentCount} öğrenci atanmış durumda. ${selectedStudentIds.length} öğrenci daha ekleyemezsiniz.`, 'error', 'Sınır Aşıldı');
        return;
    }
    
    // Loading durumu
    const assignBtn = document.getElementById('confirmAssignment');
    const originalText = assignBtn.innerHTML;
    assignBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Atanıyor...';
    assignBtn.disabled = true;
    
    // Atama verilerini hazırla - Tabloya uygun format
    if (!pcId || pcId === 'undefined' || pcId === 'null') {
        console.error('❌ PC ID geçersiz:', pcId);
        showToast('PC ID bulunamadı! Lütfen sayfayı yenileyin ve tekrar deneyin.', 'error', 'Atama Hatası');
        return;
    }
    
    // Seçilen PC ID'sini kullan - String olarak da gelebilir, integer'a çevir
    const selectedPCId = parseInt(pcId);
    const labId = parseInt(computerId);
    
    console.log('📋 Seçilen PC ID (integer):', selectedPCId, 'Type:', typeof selectedPCId);
    console.log('📋 Laboratuvar ID (integer):', labId, 'Type:', typeof labId);
    
    const assignments = selectedStudentIds.map(studentId => ({
        student_id: parseInt(studentId),
        pc_id: selectedPCId  // Seçilen PC ID'si (integer)
    }));
    
    console.log('📋 Atama verileri:', assignments);
    
    const requestBody = `assignments=${JSON.stringify(assignments)}&computer_id=${computerId}`;
    console.log('📋 Request Body:', requestBody);
    
    // AJAX ile atama yap
    fetch('../controllers/AssignmentController.php?action=bulk_assign', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: requestBody
    })
    .then(response => {
        console.log('📋 Response Status:', response.status);
        console.log('📋 Response OK:', response.ok);
        return response.json();
    })
    .then(data => {
        console.log('📋 Response Data:', data);
        if (data.success) {
            console.log('✅ Atama başarılı!');
            showToast(`${selectedStudentIds.length} öğrenci başarıyla atandı!`, 'success', 'Atama Başarılı');
            
            // Modal'ı kapat
            bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();
            
            // PC kartlarını yenile
            const selectedLabText = labSelector.options[labSelector.selectedIndex].text;
            console.log('🔄 PC kartları yenileniyor - Lab ID:', computerId, 'Lab Name:', selectedLabText);
            loadPCCards(computerId, selectedLabText);
        } else {
            console.error('❌ Atama başarısız:', data.message);
            showToast(data.message || 'Bilinmeyen hata oluştu!', 'error', 'Atama Hatası');
        }
    })
    .catch(error => {
        console.error('❌ AJAX Error:', error);
        showToast('Atama işlemi sırasında bir hata oluştu! Lütfen internet bağlantınızı kontrol edin.', 'error', 'Bağlantı Hatası');
    })
    .finally(() => {
        // Loading durumunu kaldır
        assignBtn.innerHTML = originalText;
        assignBtn.disabled = false;
    });
}

// PC sayısı düzenleme modal'ını aç
function openEditPCCountModal(labId, labName, currentCount) {
    document.getElementById('currentLabName').textContent = labName;
    document.getElementById('newPCCount').value = currentCount;
    
    // Uyarı kutusunu temizle ve gizle
    const warningDiv = document.getElementById('pcCountWarning');
    const warningText = document.getElementById('warningText');
    warningDiv.style.display = 'none';
    warningDiv.className = 'alert alert-warning'; // Varsayılan sarı uyarı
    warningText.textContent = '';
    
    // Mevcut maksimum öğrenci sayısını yükle
    loadMaxStudentsPerPC(labId);
    
    // Modal'ı göster
    const modal = new bootstrap.Modal(document.getElementById('editPCCountModal'));
    modal.show();
}

// Laboratuvar için maksimum öğrenci sayısını yükle
function loadMaxStudentsPerPC(labId) {
    fetch(`../controllers/AssignmentController.php?action=get_lab_max_students&computer_id=${labId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('maxStudentsPerPC').value = data.maxStudentsPerPC || 4;
            } else {
                console.warn('Maksimum öğrenci sayısı yüklenemedi:', data.message);
                document.getElementById('maxStudentsPerPC').value = 4; // Varsayılan değer
            }
        })
        .catch(error => {
            console.error('Maksimum öğrenci sayısı yükleme hatası:', error);
            document.getElementById('maxStudentsPerPC').value = 4; // Varsayılan değer
        });
}

// Maksimum öğrenci sayısı düzenleme modal'ını aç
function openEditMaxStudentsModal(labId, labName) {
    document.getElementById('currentLabNameMaxStudents').textContent = labName;
    
    // Uyarı kutusunu temizle ve gizle
    const warningDiv = document.getElementById('maxStudentsWarning');
    const warningText = document.getElementById('maxStudentsWarningText');
    warningDiv.style.display = 'none';
    warningDiv.className = 'alert alert-warning'; // Varsayılan sarı uyarı
    warningText.textContent = '';
    
    // Mevcut maksimum öğrenci sayısını yükle
    loadMaxStudentsForModal(labId);
    
    // Modal'ı göster
    const modal = new bootstrap.Modal(document.getElementById('editMaxStudentsModal'));
    modal.show();
}

// Modal için maksimum öğrenci sayısını yükle
function loadMaxStudentsForModal(labId) {
    fetch(`../controllers/AssignmentController.php?action=get_lab_max_students&computer_id=${labId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('newMaxStudentsPerPC').value = data.maxStudentsPerPC || 4;
            } else {
                console.warn('Maksimum öğrenci sayısı yüklenemedi:', data.message);
                document.getElementById('newMaxStudentsPerPC').value = 4; // Varsayılan değer
            }
        })
        .catch(error => {
            console.error('Maksimum öğrenci sayısı yükleme hatası:', error);
            document.getElementById('newMaxStudentsPerPC').value = 4; // Varsayılan değer
        });
}

// PC sayısı kaydetme
document.getElementById('savePCCount').addEventListener('click', function() {
    const selectedLabId = labSelector.value;
    const newPCCount = document.getElementById('newPCCount').value;
    const selectedOption = labSelector.options[labSelector.selectedIndex];
    const currentPCCount = selectedOption.getAttribute('data-pc-count');
    
    if (!newPCCount || newPCCount < 1 || newPCCount > 100) {
        showToast('Lütfen 1-100 arasında geçerli bir PC sayısı girin!', 'error');
        return;
    }
    
    if (newPCCount == currentPCCount) {
        showToast('Yeni PC sayısı mevcut sayı ile aynı!', 'warning');
        return;
    }
    
    // Loading durumu
    const saveBtn = this;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Kaydediliyor...';
    saveBtn.disabled = true;
    
    // AJAX ile PC sayısını güncelle
    fetch('../controllers/AssignmentController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_pc_count&computer_id=${selectedLabId}&pc_count=${newPCCount}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // Lab selector'ı güncelle
            const selectedOption = labSelector.options[labSelector.selectedIndex];
            selectedOption.setAttribute('data-pc-count', newPCCount);
            selectedOption.text = selectedOption.text.split(' (')[0] + ` (${newPCCount} PC)`;
            
            // PC kartlarını yenile
            const selectedLabText = selectedOption.text;
            loadPCCards(selectedLabId, selectedLabText);
            
            // Modal'ı kapat
            bootstrap.Modal.getInstance(document.getElementById('editPCCountModal')).hide();
        } else {
            // Hata mesajını göster
            showToast(data.message, 'error', 'Kaydetme Hatası');
            
            // Eğer sınırı aşan PC'ler varsa detaylı uyarı göster
            if (data.exceeded_pcs && data.exceeded_pcs.length > 0) {
                const warningDiv = document.getElementById('pcCountWarning');
                const warningText = document.getElementById('warningText');
                
                let warningHTML = '<strong>Sınırı Aşan PC\'ler:</strong><br>';
                data.exceeded_pcs.forEach(pc => {
                    warningHTML += `• PC${pc.pc_number}: ${pc.current_students} öğrenci (maksimum: ${pc.max_allowed})<br>`;
                });
                warningHTML += '<br><small class="text-muted">Lütfen önce bu PC\'lerden öğrenci kaldırın, sonra tekrar deneyin.</small>';
                
                warningText.innerHTML = warningHTML;
                warningDiv.style.display = 'block';
                warningDiv.className = 'alert alert-danger'; // Kırmızı uyarı
                
                // Animasyon ekle
                setTimeout(() => {
                    warningDiv.classList.add('show');
                }, 100);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('PC sayısı güncellenirken bir hata oluştu!', 'error');
    })
    .finally(() => {
        // Loading durumunu kaldır
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
});

// Modal kapandığında warning'i temizle
document.getElementById('editPCCountModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('pcCountWarning').style.display = 'none';
    document.getElementById('newPCCount').value = '';
});

// Maksimum öğrenci sayısı kaydetme
document.getElementById('saveMaxStudents').addEventListener('click', function() {
    const selectedLabId = labSelector.value;
    const newMaxStudents = document.getElementById('newMaxStudentsPerPC').value;
    
    if (!newMaxStudents || newMaxStudents < 1 || newMaxStudents > 20) {
        showToast('Lütfen 1-20 arasında geçerli bir maksimum öğrenci sayısı girin!', 'error');
        return;
    }
    
    // Loading durumu
    const saveBtn = this;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Kaydediliyor...';
    saveBtn.disabled = true;
    
    // AJAX ile maksimum öğrenci sayısını güncelle
    fetch('../controllers/AssignmentController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_max_students&computer_id=${selectedLabId}&max_students_per_pc=${newMaxStudents}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // PC kartlarını yenile
            const selectedLabText = labSelector.options[labSelector.selectedIndex].text;
            loadPCCards(selectedLabId, selectedLabText);
            
            // Modal'ı kapat
            bootstrap.Modal.getInstance(document.getElementById('editMaxStudentsModal')).hide();
        } else {
            // Hata mesajını göster
            showToast(data.message, 'error', 'Kaydetme Hatası');
            
            // Eğer sınırı aşan PC'ler varsa detaylı uyarı göster
            if (data.exceeded_pcs && data.exceeded_pcs.length > 0) {
                const warningDiv = document.getElementById('maxStudentsWarning');
                const warningText = document.getElementById('maxStudentsWarningText');
                
                let warningHTML = '<strong>Sınırı Aşan PC\'ler:</strong><br>';
                data.exceeded_pcs.forEach(pc => {
                    warningHTML += `• PC${pc.pc_number}: ${pc.current_students} öğrenci (maksimum: ${pc.max_allowed})<br>`;
                });
                warningHTML += '<br><small class="text-muted">Lütfen önce bu PC\'lerden öğrenci kaldırın, sonra tekrar deneyin.</small>';
                
                warningText.innerHTML = warningHTML;
                warningDiv.style.display = 'block';
                warningDiv.className = 'alert alert-danger'; // Kırmızı uyarı
                
                // Animasyon ekle
                setTimeout(() => {
                    warningDiv.classList.add('show');
                }, 100);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Maksimum öğrenci sayısı güncellenirken bir hata oluştu!', 'error');
    })
    .finally(() => {
        // Loading durumunu kaldır
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
});

// Maksimum öğrenci sayısı modal kapandığında warning'i temizle
document.getElementById('editMaxStudentsModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('maxStudentsWarning').style.display = 'none';
    document.getElementById('newMaxStudentsPerPC').value = '';
});

// Atama modal'ı için event listener'lar
document.addEventListener('DOMContentLoaded', function() {
    // Öğrenci checkbox'ları değiştiğinde
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('student-checkbox')) {
            updateSelectedCount();
        }
    });
    
    // Atama onay butonu
    const confirmAssignmentBtn = document.getElementById('confirmAssignment');
    if (confirmAssignmentBtn) {
        confirmAssignmentBtn.addEventListener('click', performAssignment);
    }
});

// Excel Import Form Handler
document.getElementById('excelImportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('excel_file');
    const importButton = document.getElementById('importButton');
    const progressDiv = document.getElementById('importProgress');
    const resultDiv = document.getElementById('importResult');
    
    // Dosya seçildi mi kontrol et
    if (fileInput.files.length === 0) {
        showToast('Lütfen bir Excel dosyası seçin!', 'error');
        return;
    }
    
    // Dosya tipini kontrol et
    const file = fileInput.files[0];
    const allowedTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
        showToast('Lütfen sadece Excel dosyası (.xlsx, .xls) seçin!', 'error');
        return;
    }
    
    // UI durumunu güncelle
    const originalButtonText = importButton.innerHTML;
    importButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Yükleniyor...';
    importButton.disabled = true;
    progressDiv.style.display = 'block';
    resultDiv.innerHTML = '';
    
    // FormData oluştur
    const formData = new FormData();
    formData.append('excel_file', file);
    
    // AJAX ile dosyayı gönder
    fetch('../excel-to-mysql/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        progressDiv.style.display = 'none';
        
        if (data.success) {
            // Analiz aşaması mı yoksa import aşaması mı?
            if (data.analysis) {
                // Analiz sonuçlarını göster
                showAnalysisResults(data);
            } else {
                // Import sonuçlarını göster
                showImportResults(data);
            }
        } else {
            // Hatalı analiz/import
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6 class="alert-heading">
                        <i class="fas fa-times-circle me-2"></i>İşlem Başarısız!
                    </h6>
                    <p class="mb-0">${data.message}</p>
                    ${data.errors && data.errors.length > 0 ? `
                        <hr>
                        <div style="max-height: 200px; overflow-y: auto;">
                            ${data.errors.map(error => `<div class="mb-1"><small>${error}</small></div>`).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            showToast('Excel dosyası işlenemedi!', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        progressDiv.style.display = 'none';
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6 class="alert-heading">
                    <i class="fas fa-times-circle me-2"></i>Bağlantı Hatası!
                </h6>
                <p class="mb-0">Sunucuya bağlanırken bir hata oluştu. Lütfen tekrar deneyin.</p>
            </div>
        `;
        showToast('Sunucu bağlantı hatası!', 'error');
    })
    .finally(() => {
        // UI durumunu eski haline getir
        importButton.innerHTML = originalButtonText;
        importButton.disabled = false;
    });
});

// Analiz sonuçlarını göster
function showAnalysisResults(data) {
    const resultDiv = document.getElementById('importResult');
    
    let resultHTML = `
        <div class="alert alert-info">
            <h6 class="alert-heading">
                <i class="fas fa-search me-2"></i>Dosya Analizi Tamamlandı
            </h6>
            <p class="mb-2">${data.message.replace(/\n/g, '<br>')}</p>
            <hr>
            <div class="row text-center mb-3">
                <div class="col-3">
                    <div class="fw-bold text-success fs-4">${data.valid_rows}</div>
                    <small>Geçerli</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-danger fs-4">${data.invalid_rows}</div>
                    <small>Hatalı</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-warning fs-4">${data.duplicate_rows}</div>
                    <small>Mevcut</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-info fs-4">${data.warning_rows}</div>
                    <small>Uyarılı</small>
                </div>
            </div>
    `;
    
    // Eğer hatalı veya mevcut kayıtlar varsa uyarı göster
    if (data.invalid_rows > 0 || data.duplicate_rows > 0) {
        resultHTML += `
            <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Dikkat:</strong> ${data.invalid_rows + data.duplicate_rows} kayıt içe aktarılamayacak.
                Sadece geçerli ${data.valid_rows} kaydı içe aktarmak istiyor musunuz?
            </div>
        `;
    }
    
    // Karar verme butonları
    resultHTML += `
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-secondary" onclick="cancelImport('${data.temp_file}')">
                <i class="fas fa-times me-2"></i>İptal Et
            </button>
    `;
    
    if (data.valid_rows > 0) {
        resultHTML += `
            <button type="button" class="btn btn-success" onclick="confirmImport('${data.temp_file}', true)">
                <i class="fas fa-check me-2"></i>Sadece Geçerli Kayıtları İçe Aktar (${data.valid_rows})
            </button>
        `;
    }
    
    resultHTML += `
        </div>
        </div>
    `;
    
    // Hataları göster
    if (data.invalid_data && data.invalid_data.length > 0) {
        resultHTML += `
            <div class="alert alert-danger">
                <h6 class="alert-heading">
                    <i class="fas fa-exclamation-triangle me-2"></i>Hatalı Kayıtlar (${data.invalid_data.length})
                </h6>
                <div style="max-height: 200px; overflow-y: auto;">
        `;
        data.invalid_data.forEach(item => {
            resultHTML += `
                <div class="mb-2 p-2 border-start border-danger border-3">
                    <strong>Satır ${item.row_number}:</strong> ${item.data.sdt_nmbr} - ${item.data.first_name} ${item.data.last_name}
                    <br><small class="text-danger">${item.errors.join(', ')}</small>
                </div>
            `;
        });
        resultHTML += '</div></div>';
    }
    
    // Mevcut kayıtları göster
    if (data.duplicate_data && data.duplicate_data.length > 0) {
        resultHTML += `
            <div class="alert alert-warning">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>Mevcut Kayıtlar (${data.duplicate_data.length})
                </h6>
                <div style="max-height: 150px; overflow-y: auto;">
        `;
        data.duplicate_data.forEach(item => {
            resultHTML += `
                <div class="mb-1">
                    <small>Satır ${item.row_number}: ${item.student_number} - Zaten sistemde mevcut</small>
                </div>
            `;
        });
        resultHTML += '</div></div>';
    }
    
    resultDiv.innerHTML = resultHTML;
}

// Import sonuçlarını göster
function showImportResults(data) {
    const resultDiv = document.getElementById('importResult');
    
    let resultHTML = `
        <div class="alert alert-success">
            <h6 class="alert-heading">
                <i class="fas fa-check-circle me-2"></i>İçe Aktarma Başarılı!
            </h6>
            <p class="mb-2">${data.message}</p>
            <hr>
            <div class="row text-center">
                <div class="col-3">
                    <div class="fw-bold text-success fs-4">${data.imported_count}</div>
                    <small>Eklenen</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-warning fs-4">${data.duplicate_count}</div>
                    <small>Mevcut</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-danger fs-4">${data.error_count}</div>
                    <small>Hatalı</small>
                </div>
                <div class="col-3">
                    <div class="fw-bold text-info fs-4">${data.warning_count}</div>
                    <small>Uyarı</small>
                </div>
            </div>
        </div>
    `;
    
    resultDiv.innerHTML = resultHTML;
    showToast(`${data.imported_count} öğrenci başarıyla eklendi!`, 'success');
    
    // 3 saniye sonra sayfayı yenile (istatistikleri güncellemek için)
    setTimeout(() => {
        location.reload();
    }, 3000);
}

// Import'u onayla
function confirmImport(tempFile, importValidOnly) {
    const importButton = document.getElementById('importButton');
    const progressDiv = document.getElementById('importProgress');
    const resultDiv = document.getElementById('importResult');
    
    // UI durumunu güncelle
    const originalButtonText = importButton.innerHTML;
    importButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>İçe Aktarılıyor...';
    importButton.disabled = true;
    progressDiv.style.display = 'block';
    
    // FormData oluştur
    const formData = new FormData();
    formData.append('confirm_import', 'true');
    formData.append('temp_file', tempFile);
    formData.append('import_valid_only', importValidOnly ? 'true' : 'false');
    
    // AJAX ile onaylanmış import'u gönder
    fetch('../excel-to-mysql/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        progressDiv.style.display = 'none';
        
        if (data.success) {
            showImportResults(data);
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6 class="alert-heading">
                        <i class="fas fa-times-circle me-2"></i>İçe Aktarma Başarısız!
                    </h6>
                    <p class="mb-0">${data.message}</p>
                </div>
            `;
            showToast('İçe aktarma işlemi başarısız!', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        progressDiv.style.display = 'none';
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6 class="alert-heading">
                    <i class="fas fa-times-circle me-2"></i>Bağlantı Hatası!
                </h6>
                <p class="mb-0">Sunucuya bağlanırken bir hata oluştu. Lütfen tekrar deneyin.</p>
            </div>
        `;
        showToast('Sunucu bağlantı hatası!', 'error');
    })
    .finally(() => {
        // UI durumunu eski haline getir
        importButton.innerHTML = originalButtonText;
        importButton.disabled = false;
    });
}

// Import'u iptal et
function cancelImport(tempFile) {
    // Geçici dosyayı sil
    const formData = new FormData();
    formData.append('cancel_import', 'true');
    formData.append('temp_file', tempFile);
    
    fetch('../excel-to-mysql/import.php', {
        method: 'POST',
        body: formData
    });
    
    // Modal'ı kapatma - sadece formu temizle
    document.getElementById('excelImportForm').reset();
    document.getElementById('importResult').innerHTML = '';
    document.getElementById('importProgress').style.display = 'none';
    
    showToast('İçe aktarma işlemi iptal edildi. Yeni dosya seçebilirsiniz.', 'info');
}

// Header istatistiklerini güncelle
function updateHeaderStats() {
    console.log('📊 Header istatistikleri güncelleniyor...');
    
    fetch('../controllers/StatsController.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Öğrenci sayısını güncelle
            const studentStat = document.querySelector('.header-stat-item:nth-child(1) .stat-number');
            if (studentStat) {
                studentStat.textContent = data.stats.student_count;
            }
            
            // Laboratuvar sayısını güncelle
            const labStat = document.querySelector('.header-stat-item:nth-child(2) .stat-number');
            if (labStat) {
                labStat.textContent = data.stats.lab_count;
            }
            
            // Atama sayısını güncelle
            const assignmentStat = document.querySelector('.header-stat-item:nth-child(3) .stat-number');
            if (assignmentStat) {
                assignmentStat.textContent = data.stats.assignment_count;
            }
            
            console.log('✅ Header istatistikleri başarıyla güncellendi:', data.stats);
        } else {
            console.error('❌ Header istatistik güncelleme hatası:', data.message);
        }
    })
    .catch(error => {
        console.error('❌ Header istatistik güncelleme hatası:', error);
    });
}


// Sayfa yüklendiğinde istatistikleri güncelle
document.addEventListener('DOMContentLoaded', function() {
    // Header istatistiklerini başlangıçta güncelle
    updateHeaderStats();
    
    // Her 30 saniyede bir header istatistikleri güncelle
    setInterval(updateHeaderStats, 30000);
});
