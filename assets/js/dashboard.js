document.addEventListener('DOMContentLoaded', function() {
    // URL parametrelerini kontrol et
    const urlParams = new URLSearchParams(window.location.search);
    const labId = urlParams.get('lab_id');
    const pcManagement = urlParams.get('pc_management');
    
    // Başlangıçta grid oluşturma, laboratuvar seçilene kadar boş bırak
    showEmptyState();
    
    // İstatistikleri sıfırla
    clearLabSelection();
    
    // Eğer URL'de lab_id varsa, o laboratuvarı seç
    if (labId) {
        const labSelect = document.getElementById('labSelect');
        if (labSelect) {
            labSelect.value = labId;
            changeLab();
            
            // Eğer PC yönetim modu açılmak isteniyorsa, PC sayısını düzenle modalını aç
            if (pcManagement === 'true') {
                setTimeout(() => {
                    editPCCount();
                }, 500); // Laboratuvar yüklendikten sonra modalı aç
            }
        }
    }
    
    // Modal içindeki atama butonu event listener
    document.getElementById('assignBtnModal').addEventListener('click', function() {
        const studentId = document.getElementById('studentSelectModal').value;
        const pcId = document.getElementById('pcSelectModal').value;
        
        if (studentId && pcId) {
            assignStudentToPC(studentId, pcId);
            // Modal'ı kapat
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignmentFormModal'));
            modal.hide();
        } else {
            showToast('Lütfen öğrenci ve bilgisayar seçin!', 'warning');
        }
    });
    
    // Assignment form modal açıldığında dropdown'ları güncelle
    const assignmentFormModal = document.getElementById('assignmentFormModal');
    if (assignmentFormModal) {
        assignmentFormModal.addEventListener('show.bs.modal', function() {
            updateStudentDropdownModal();
            updatePCDropdownModal();
            // Laboratuvar seçimini mevcut değerlerle güncelle
            const labSelectModal = document.getElementById('labSelectModal');
            if (labSelectModal) {
                labSelectModal.value = currentLab || '';
            }
        });
    }
});

// Global değişkenler
let currentLab = null;
let currentRows = 9;
let currentCols = 4;
let currentTotal = 54;

// Mevcut laboratuvar ismini al
function getCurrentLabName() {
    const labSelect = document.getElementById('labSelect');
    if (!labSelect || !currentLab) return '';
    
    const selectedOption = labSelect.options[labSelect.selectedIndex];
    if (selectedOption && selectedOption.text) {
        return selectedOption.text.split(' - ')[0]; // Kullanıcı tipini al (örn: "Mekanik")
    }
    return '';
}
let currentFilter = null; // 'assigned', 'available', null
let currentYear = null; // yıl filtresi

// Toast Bildirim Sistemi
function showToast(message, type = 'info') {
    const toast = document.getElementById('systemToast');
    const toastMessage = document.getElementById('toastMessage');
    const toastHeader = toast.querySelector('.toast-header');
    const icon = toastHeader.querySelector('i');
    
    // Mesajı ayarla
    toastMessage.textContent = message;
    
    // Tip'e göre ikon ve renk ayarla
    icon.className = 'fas me-2';
    toastHeader.className = 'toast-header';
    
    switch(type) {
        case 'success':
            icon.classList.add('fa-check-circle', 'text-success');
            break;
        case 'warning':
            icon.classList.add('fa-exclamation-triangle', 'text-warning');
            break;
        case 'error':
            icon.classList.add('fa-times-circle', 'text-danger');
            break;
        case 'info':
        default:
            icon.classList.add('fa-info-circle', 'text-primary');
            break;
    }
    
    // Toast'ı göster
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 4000
    });
    bsToast.show();
}

function showEmptyState() {
    const grid = document.getElementById('pcGrid');
    grid.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; color: #666;">
            <i class="fas fa-building" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
            <h3>Laboratuvar Seçin</h3>
            <p>PC'leri görüntülemek için yukarıdan bir laboratuvar seçin.</p>
        </div>
    `;
    grid.style.gridTemplateColumns = '1fr';
}

function createPCGrid() {
    const grid = document.getElementById('pcGrid');
    grid.innerHTML = '';
    
    // Grid CSS'ini güncelle
    grid.style.gridTemplateColumns = `repeat(${currentCols}, 1fr)`;
    
    // Bilgisayar kartlarını oluştur
    for (let i = 1; i <= currentTotal; i++) {
        const pcCard = document.createElement('div');
        pcCard.className = 'pc-card';
        pcCard.id = `pc-${i}`;
        pcCard.innerHTML = `
            <div class="pc-number">
                <span class="lab-name">${getCurrentLabName()}</span> PC${i.toString().padStart(2, '0')}
            </div>
            <div class="pc-status available">
                <i class="fas fa-desktop"></i>
                <span>Boş</span>
            </div>
            <div class="pc-students" id="students-${i}">
                <!-- Öğrenciler buraya gelecek -->
            </div>
            <div class="pc-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="selectPC(${i})">
                    <i class="fas fa-mouse-pointer"></i>
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="openAssignmentModalForPC(${i})">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="clearAllStudents(${i})" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        grid.appendChild(pcCard);
    }
}

function changeLab() {
    const labSelect = document.getElementById('labSelect');
    const selectedOption = labSelect.options[labSelect.selectedIndex];
    
    // Laboratuvar değiştiğinde filtreleri temizle
    currentFilter = null;
    currentYear = null;
    updateFilterButtons();
    updateYearButtons();
    
    if (selectedOption.value === '') {
        // Laboratuvar seçilmediğinde boş durumu göster
        currentLab = null;
        currentRows = 0;
        currentCols = 0;
        currentTotal = 0;
        showEmptyState();
        updateSelectedLabCard('', '');
        
        // PC düzenleme butonunu gizle
        document.getElementById('pcEditButton').style.display = 'none';
    } else {
        // Seçilen laboratuvar bilgilerini al
        currentLab = selectedOption.value;
        currentRows = parseInt(selectedOption.dataset.rows);
        currentCols = parseInt(selectedOption.dataset.cols);
        currentTotal = parseInt(selectedOption.dataset.total);
        
        // Seçili laboratuvar kartını güncelle
        const displayName = selectedOption.text.split(' - ')[0]; // Kullanıcı tipini al (örn: "Mekanik")
        updateSelectedLabCard(displayName, selectedOption.text);
        
        // PC düzenleme butonunu göster
        document.getElementById('pcEditButton').style.display = 'block';
        
        // Grid'i oluştur
        createPCGrid();
        updateStats();
    }
    
    // İstatistik kartlarını güncelle
    updateStatsCards();
}

// Seçili laboratuvar kartını güncelle
function updateSelectedLabCard(labName, labInfo) {
    const selectedLabName = document.getElementById('selectedLabName');
    
    if (labName) {
        selectedLabName.textContent = labName;
    } else {
        selectedLabName.textContent = 'Laboratuvar Seçin';
    }
}

function updateStatsCards() {
    // Laboratuvar adını güncelle
    const labSelect = document.getElementById('labSelect');
    if (!labSelect) return;
    
    const selectedOption = labSelect.options[labSelect.selectedIndex];
    const labName = selectedOption.value === '' ? '-' : selectedOption.textContent.split(' - ')[0];
    
    // Toplam PC sayısını güncelle
    const totalPcs = currentTotal;
    
    // Atanmış ve boş PC sayılarını hesapla (şu an için 0 atanmış, hepsi boş)
    const assignedPcs = 0; // Gerçek uygulamada bu değer hesaplanacak
    const availablePcs = totalPcs - assignedPcs;
    
    // İstatistik kartlarını güncelle - daha güvenli selector kullan
    const statNumbers = document.querySelectorAll('.stat-item .stat-number');
    
    if (statNumbers.length >= 4) {
        // Laboratuvar adı
        statNumbers[0].textContent = labName;
        // Toplam PC sayısı
        statNumbers[1].textContent = totalPcs;
        // Atanmış PC sayısı
        statNumbers[2].textContent = assignedPcs;
        // Boş PC sayısı
        statNumbers[3].textContent = availablePcs;
    }
    
    // Laboratuvar seçilmediğinde istatistikleri sıfırla
    if (selectedOption.value === '') {
        if (statNumbers.length >= 4) {
            statNumbers[0].textContent = '-';
            statNumbers[1].textContent = '0';
            statNumbers[2].textContent = '0';
            statNumbers[3].textContent = '0';
        }
    }
}

// Filtreleme Fonksiyonları
function filterByStatus(status) {
    // Laboratuvar seçilmediğinde filtreleme yapma
    if (!currentLab) {
        showToast('Lütfen önce bir laboratuvar seçin!', 'warning');
        return;
    }
    
    currentFilter = currentFilter === status ? null : status;
    applyFilters();
    updateFilterButtons();
}

function filterByYear(year) {
    // Laboratuvar seçilmediğinde filtreleme yapma
    if (!currentLab) {
        showToast('Lütfen önce bir laboratuvar seçin!', 'warning');
        return;
    }
    
    currentYear = year === '' ? null : year;
    applyFilters();
    updateYearButtons();
    
    // Yıl değiştiğinde öğrenci dropdown'ını güncelle
    if (currentYear) {
        updateStudentDropdownByYear(currentYear);
    } else {
        updateStudentDropdownModal();
    }
}

function clearFilters() {
    // Laboratuvar seçilmediğinde filtreleme yapma
    if (!currentLab) {
        showToast('Lütfen önce bir laboratuvar seçin!', 'warning');
        return;
    }
    
    currentFilter = null;
    currentYear = null;
    
    // UI'yi sıfırla
    updateFilterButtons();
    updateYearButtons();
    applyFilters();
    
    showToast('Filtreler temizlendi!', 'success');
}

function applyFilters() {
    const pcCards = document.querySelectorAll('.pc-card');
    
    pcCards.forEach(card => {
        let showCard = true;
        
        // Durum filtresi
        if (currentFilter) {
            const statusElement = card.querySelector('.pc-status');
            const isAssigned = statusElement.classList.contains('assigned') || 
                              statusElement.querySelector('.fa-user-check') !== null;
            
            if (currentFilter === 'assigned' && !isAssigned) {
                showCard = false;
            } else if (currentFilter === 'available' && isAssigned) {
                showCard = false;
            }
        }
        
        // Yıl filtresi (örnek veri - gerçek uygulamada veritabanından gelecek)
        if (currentYear) {
            // Şu an için tüm kartları göster, gerçek uygulamada yıl kontrolü yapılacak
            // const cardYear = card.dataset.year || '2024';
            // if (cardYear !== currentYear) {
            //     showCard = false;
            // }
        }
        
        // Kartı göster/gizle
        if (showCard) {
            card.style.display = 'block';
            card.classList.remove('filtered-out');
        } else {
            card.style.display = 'none';
            card.classList.add('filtered-out');
        }
    });
    
    // Filtrelenmiş sonuç sayısını güncelle
    updateFilteredStats();
}

function updateFilterButtons() {
    const statItems = document.querySelectorAll('.clickable-stat');
    
    statItems.forEach(item => {
        const status = item.dataset.status;
        if (currentFilter === status) {
            item.classList.add('active-filter');
        } else {
            item.classList.remove('active-filter');
        }
    });
}

function updateYearButtons() {
    const yearButtons = document.querySelectorAll('.year-btn');
    
    yearButtons.forEach(button => {
        const year = button.dataset.year;
        if (currentYear === year) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
}

function updateFilteredStats() {
    const visibleCards = document.querySelectorAll('.pc-card:not(.filtered-out)');
    const assignedCards = Array.from(visibleCards).filter(card => {
        const statusElement = card.querySelector('.pc-status');
        return statusElement.classList.contains('assigned') || 
               statusElement.querySelector('.fa-user-check') !== null;
    });
    
    const availableCards = visibleCards.length - assignedCards.length;
    
    // Filtrelenmiş istatistikleri güncelle
    const assignedElement = document.getElementById('assignedPcs');
    const availableElement = document.getElementById('availablePcs');
    
    if (assignedElement) {
        assignedElement.textContent = assignedCards.length;
    }
    if (availableElement) {
        availableElement.textContent = availableCards;
    }
}

function updateStudentDropdownModal() {
    const studentSelect = document.getElementById('studentSelectModal');
    studentSelect.innerHTML = '<option value="">Öğrenci seçin...</option>';
    
    // AJAX ile öğrencileri çek
    fetch('get_students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_all_students'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            data.students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.first_name} ${student.last_name} (${student.student_number})`;
                studentSelect.appendChild(option);
            });
        } else {
            console.error('Öğrenci listesi alınamadı:', data.message);
            // Hata durumunda örnek veri göster
            showSampleStudents(studentSelect);
        }
    })
    .catch(error => {
        console.error('Hata:', error);
        // Hata durumunda örnek veri göster
        showSampleStudents(studentSelect);
    });
}

function updateStudentDropdownByYear(year) {
    const studentSelect = document.getElementById('studentSelectModal');
    studentSelect.innerHTML = '<option value="">Öğrenci seçin...</option>';
    
    // AJAX ile belirli yıla ait öğrencileri çek
    fetch('get_students.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_students_by_year&year=${year}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            data.students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.first_name} ${student.last_name} (${student.student_number})`;
                studentSelect.appendChild(option);
            });
        } else {
            console.error('Öğrenci listesi alınamadı:', data.message);
        }
    })
    .catch(error => {
        console.error('Hata:', error);
    });
}

function showSampleStudents(studentSelect) {
    // Hata durumunda örnek veri göster
    const students = [
        'Ahmet Yılmaz', 'Ayşe Demir', 'Mehmet Kaya', 'Fatma Öz', 'Ali Çelik',
        'Zeynep Arslan', 'Elif Şahin', 'Can Öztürk', 'Selin Kaya', 'Burak Demir',
        'Deniz Yıldız', 'Ece Özkan', 'Furkan Çelik', 'Gizem Arslan', 'Hakan Yılmaz'
    ];
    
    students.forEach((student, index) => {
        const option = document.createElement('option');
        option.value = index + 1;
        option.textContent = student;
        studentSelect.appendChild(option);
    });
}

function updatePCDropdownModal() {
    const pcSelect = document.getElementById('pcSelectModal');
    pcSelect.innerHTML = '<option value="">Bilgisayar seçin...</option>';
    
    for (let i = 1; i <= currentTotal; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = `PC${i.toString().padStart(2, '0')}`;
        pcSelect.appendChild(option);
    }
}

function changeLabModal() {
    const labSelect = document.getElementById('labSelectModal');
    const selectedOption = labSelect.options[labSelect.selectedIndex];
    
    if (selectedOption.value === '') {
        // Varsayılan değerler
        currentLab = null;
        currentRows = 9;
        currentCols = 4;
        currentTotal = 54;
    } else {
        // Seçilen laboratuvar bilgilerini al
        currentLab = selectedOption.value;
        currentRows = parseInt(selectedOption.dataset.rows);
        currentCols = parseInt(selectedOption.dataset.cols);
        currentTotal = parseInt(selectedOption.dataset.total);
    }
    
    // PC dropdown'ını güncelle
    updatePCDropdownModal();
}

// PC kartındaki + butonuna tıklandığında atama modalını aç
function openAssignmentModalForPC(pcId) {
    // Atama form modalını aç
    const modal = new bootstrap.Modal(document.getElementById('assignmentFormModal'));
    modal.show();
    
    // Modal açıldığında PC'yi otomatik seç
    setTimeout(() => {
        const pcSelectModal = document.getElementById('pcSelectModal');
        if (pcSelectModal) {
            pcSelectModal.value = pcId;
            
            // PC kartını vurgula
            document.querySelectorAll('.pc-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.getElementById(`pc-${pcId}`).classList.add('selected');
        }
    }, 500); // Modal tamamen açıldıktan sonra çalışması için kısa bir gecikme
}

function selectPC(pcId) {
    // Tüm kartları normal haline getir
    document.querySelectorAll('.pc-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Seçili kartı vurgula
    document.getElementById(`pc-${pcId}`).classList.add('selected');
}

function assignStudentToPC(studentId, pcId) {
    // Öğrenci adını al
    const studentSelect = document.getElementById('studentSelectModal');
    const selectedOption = studentSelect.options[studentSelect.selectedIndex];
    const studentName = selectedOption.textContent;
    
    // Başarı mesajı göster
    showToast(`${studentName} öğrencisi PC${pcId.toString().padStart(2, '0')} bilgisayarına başarıyla atandı!`, 'success');
    
    // Formu temizle
    document.getElementById('studentSelectModal').value = '';
    document.getElementById('pcSelectModal').value = '';
}

function addStudentToPC(pcId) {
    const pcCard = document.getElementById(`pc-${pcId}`);
    const studentsContainer = document.getElementById(`students-${pcId}`);
    const statusDiv = pcCard.querySelector('.pc-status');
    const clearBtn = pcCard.querySelector('.pc-actions .btn-outline-danger');
    
    // Maksimum 4 öğrenci kontrolü
    const currentStudents = studentsContainer.querySelectorAll('.student-item').length;
    if (currentStudents >= 4) {
        showToast('Bir bilgisayara maksimum 4 öğrenci atanabilir!', 'warning');
        return;
    }
    
    // Öğrenci adını simüle et
    const studentNames = ['Ahmet Yılmaz', 'Ayşe Demir', 'Mehmet Kaya', 'Fatma Öz', 'Ali Çelik', 'Zeynep Arslan', 'Elif Şahin', 'Can Öztürk', 'Selin Kaya', 'Burak Demir'];
    const randomName = studentNames[Math.floor(Math.random() * studentNames.length)];
    
    // Yeni öğrenci elementi oluştur
    const studentItem = document.createElement('div');
    studentItem.className = 'student-item';
    studentItem.innerHTML = `
        <div class="student-info">
            <i class="fas fa-user"></i>
            <span class="student-name">${randomName}</span>
        </div>
        <button class="btn btn-xs btn-outline-danger" onclick="removeStudentFromPC(${pcId}, this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    studentsContainer.appendChild(studentItem);
    
    // Durumu güncelle
    const studentCount = studentsContainer.querySelectorAll('.student-item').length;
    if (studentCount === 1) {
        statusDiv.className = 'pc-status assigned';
        statusDiv.innerHTML = '<i class="fas fa-user-check"></i><span>Atanmış</span>';
        clearBtn.style.display = 'inline-block';
    } else {
        statusDiv.innerHTML = `<i class="fas fa-users"></i><span>${studentCount} Öğrenci</span>`;
    }
    
    // İstatistikleri güncelle
    updateStats();
    
    // Dropdown'ları temizle
    document.getElementById('studentSelect').value = '';
    document.getElementById('pcSelect').value = '';
    
    // Seçili kartı temizle
    pcCard.classList.remove('selected');
}

function removeStudentFromPC(pcId, buttonElement) {
    const studentItem = buttonElement.closest('.student-item');
    const pcCard = document.getElementById(`pc-${pcId}`);
    const studentsContainer = document.getElementById(`students-${pcId}`);
    const statusDiv = pcCard.querySelector('.pc-status');
    const clearBtn = pcCard.querySelector('.pc-actions .btn-outline-danger');
    
    studentItem.remove();
    
    // Durumu güncelle
    const studentCount = studentsContainer.querySelectorAll('.student-item').length;
    if (studentCount === 0) {
        statusDiv.className = 'pc-status available';
        statusDiv.innerHTML = '<i class="fas fa-desktop"></i><span>Boş</span>';
        clearBtn.style.display = 'none';
    } else if (studentCount === 1) {
        statusDiv.innerHTML = '<i class="fas fa-user-check"></i><span>Atanmış</span>';
    } else {
        statusDiv.innerHTML = `<i class="fas fa-users"></i><span>${studentCount} Öğrenci</span>`;
    }
    
    // İstatistikleri güncelle
    updateStats();
}

function clearAllStudents(pcId) {
    if (confirm('Bu bilgisayardaki tüm öğrencileri kaldırmak istediğinizden emin misiniz?')) {
        const pcCard = document.getElementById(`pc-${pcId}`);
        const studentsContainer = document.getElementById(`students-${pcId}`);
        const statusDiv = pcCard.querySelector('.pc-status');
        const clearBtn = pcCard.querySelector('.pc-actions .btn-outline-danger');
        
        studentsContainer.innerHTML = '';
        
        statusDiv.className = 'pc-status available';
        statusDiv.innerHTML = '<i class="fas fa-desktop"></i><span>Boş</span>';
        clearBtn.style.display = 'none';
        
        updateStats();
        showToast('Tüm öğrenciler kaldırıldı!', 'success');
    }
}

function removeAssignment(pcId) {
    if (confirm('Bu atamayı kaldırmak istediğinizden emin misiniz?')) {
        const pcCard = document.getElementById(`pc-${pcId}`);
        const statusDiv = pcCard.querySelector('.pc-status');
        const studentDiv = pcCard.querySelector('.pc-student');
        const removeBtn = pcCard.querySelector('.pc-actions .btn-outline-danger');
        const selectBtn = pcCard.querySelector('.pc-actions .btn-outline-primary');
        
        // Kartı sıfırla
        statusDiv.className = 'pc-status available';
        statusDiv.innerHTML = '<i class="fas fa-desktop"></i><span>Boş</span>';
        studentDiv.style.display = 'none';
        removeBtn.style.display = 'none';
        selectBtn.style.display = 'inline-block';
        
        // İstatistikleri güncelle
        updateStats();
        
        showToast('Atama başarıyla kaldırıldı!', 'success');
    }
}

function updateStats() {
    const totalPcs = currentTotal;
    const assignedPcs = document.querySelectorAll('.pc-status.assigned, .pc-status:not(.available)').length;
    const availablePcs = totalPcs - assignedPcs;
    const usagePercent = totalPcs > 0 ? Math.round((assignedPcs / totalPcs) * 100) : 0;
    
    // Toplam öğrenci sayısını hesapla
    let totalStudents = 0;
    for (let i = 1; i <= currentTotal; i++) {
        const studentsContainer = document.getElementById(`students-${i}`);
        if (studentsContainer) {
            totalStudents += studentsContainer.querySelectorAll('.student-item').length;
        }
    }
    
    // Ana sayfa istatistikleri
    document.getElementById('totalPcs').textContent = totalPcs;
    document.getElementById('assignedPcs').textContent = assignedPcs;
    document.getElementById('availablePcs').textContent = availablePcs;
    
    // Modal istatistikleri
    document.getElementById('modalTotalPcs').textContent = totalPcs;
    document.getElementById('modalAssignedPcs').textContent = assignedPcs;
    document.getElementById('modalAvailablePcs').textContent = availablePcs;
    document.getElementById('modalUsagePercent').textContent = usagePercent + '%';
    document.getElementById('modalAssignedCount').textContent = assignedPcs;
    document.getElementById('modalAvailableCount').textContent = availablePcs;
}

// Modal fonksiyonları
function openAssignmentModal() {
    updateModalStats();
    updateLastUpdateTime();
    const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
    modal.show();
}

function updateModalStats() {
    updateStats();
}

function updateLastUpdateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('tr-TR');
    document.getElementById('lastUpdate').textContent = timeString;
}

function selectRandomPC() {
    const availablePCs = document.querySelectorAll('.pc-status.available');
    if (availablePCs.length === 0) {
        showToast('Boş bilgisayar bulunmuyor!', 'warning');
        return;
    }
    
    const randomIndex = Math.floor(Math.random() * availablePCs.length);
    const randomPC = availablePCs[randomIndex];
    const pcCard = randomPC.closest('.pc-card');
    const pcId = pcCard.id.split('-')[1];
    
    selectPC(parseInt(pcId));
    document.getElementById('pcSelect').value = pcId;
    
    showToast(`Rastgele seçilen bilgisayar: PC${pcId.toString().padStart(2, '0')}`, 'info');
}

function showAvailablePCs() {
    const availablePCs = document.querySelectorAll('.pc-status.available');
    let message = 'Boş Bilgisayarlar:\n\n';
    
    availablePCs.forEach(pc => {
        const pcCard = pc.closest('.pc-card');
        const pcNumber = pcCard.querySelector('.pc-number').textContent;
        message += `• ${pcNumber}\n`;
    });
    
    if (availablePCs.length === 0) {
        message = 'Tüm bilgisayarlar atanmış durumda!';
    }
    
    showToast(message, 'info');
}

function showAssignedPCs() {
    const assignedPCs = document.querySelectorAll('.pc-status.assigned');
    let message = 'Atanmış Bilgisayarlar:\n\n';
    
    assignedPCs.forEach(pc => {
        const pcCard = pc.closest('.pc-card');
        const pcNumber = pcCard.querySelector('.pc-number').textContent;
        const studentName = pcCard.querySelector('.student-name').textContent;
        message += `• ${pcNumber} - ${studentName}\n`;
    });
    
    if (assignedPCs.length === 0) {
        message = 'Henüz atanmış bilgisayar bulunmuyor!';
    }
    
    showToast(message, 'info');
}

function exportAssignments() {
    const assignedPCs = document.querySelectorAll('.pc-status.assigned');
    let csvContent = 'PC Numarası,Öğrenci Adı,Atama Tarihi\n';
    
    assignedPCs.forEach(pc => {
        const pcCard = pc.closest('.pc-card');
        const pcNumber = pcCard.querySelector('.pc-number').textContent;
        const studentName = pcCard.querySelector('.student-name').textContent;
        const now = new Date().toLocaleDateString('tr-TR');
        csvContent += `${pcNumber},${studentName},${now}\n`;
    });
    
    if (assignedPCs.length === 0) {
        showToast('Dışa aktarılacak atama bulunmuyor!', 'warning');
        return;
    }
    
    // CSV dosyasını indir
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `bilgisayar_atamalari_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Atamalar başarıyla dışa aktarıldı!', 'success');
}

function refreshAssignments() {
    updateModalStats();
    updateLastUpdateTime();
    
    // Tüm kartları temizle ve yeniden oluştur
    createPCGrid();
    updateStats();
    
    showToast('Atamalar yenilendi!', 'success');
}

// Modal Açma Fonksiyonları
function openAssignmentModal() {
    // Modal açılmadan önce seçili labı temizle
    clearLabSelection();
    
    // Modal içindeki dropdown'ları da temizle
    const labSelectModal = document.getElementById('labSelectModal');
    if (labSelectModal) {
        labSelectModal.value = '';
    }
    
    const studentSelectModal = document.getElementById('studentSelectModal');
    if (studentSelectModal) {
        studentSelectModal.innerHTML = '<option value="">Öğrenci seçin...</option>';
    }
    
    const pcSelectModal = document.getElementById('pcSelectModal');
    if (pcSelectModal) {
        pcSelectModal.innerHTML = '<option value="">Bilgisayar seçin...</option>';
    }
    
    // Modal'ı aç
    const modal = new bootstrap.Modal(document.getElementById('assignmentFormModal'));
    modal.show();
    
    // Öğrenci listesini yeniden yükle
    updateStudentDropdownModal();
}

// Seçili labı temizle
function clearLabSelection() {
    // Ana dropdown'ı temizle
    const labSelect = document.getElementById('labSelect');
    if (labSelect) {
        labSelect.value = '';
    }
    
    // Modal dropdown'ı temizle
    const labSelectModal = document.getElementById('labSelectModal');
    if (labSelectModal) {
        labSelectModal.value = '';
    }
    
    // Seçili lab kartını temizle
    const selectedLabName = document.getElementById('selectedLabName');
    if (selectedLabName) {
        selectedLabName.textContent = 'Laboratuvar Seçin';
    }
    
    // PC düzenleme butonunu gizle
    const pcEditButton = document.getElementById('pcEditButton');
    if (pcEditButton) {
        pcEditButton.style.display = 'none';
    }
    
    // Global değişkenleri sıfırla
    currentLab = null;
    currentRows = 0;
    currentCols = 0;
    currentTotal = 0;
    
    // PC grid'i temizle
    const pcGrid = document.getElementById('pcGrid');
    if (pcGrid) {
        pcGrid.innerHTML = '';
    }
    
    // İstatistikleri sıfırla
    updateStats();
    
    // İstatistik kartlarını manuel olarak sıfırla
    const totalPcsElement = document.getElementById('totalPcs');
    const assignedPcsElement = document.getElementById('assignedPcs');
    const availablePcsElement = document.getElementById('availablePcs');
    
    if (totalPcsElement) totalPcsElement.textContent = '0';
    if (assignedPcsElement) assignedPcsElement.textContent = '0';
    if (availablePcsElement) availablePcsElement.textContent = '0';
}

// PC Yönetim Fonksiyonları
function editPCCount() {
    if (!currentLab) {
        showToast('Lütfen önce bir laboratuvar seçin!', 'warning');
        return;
    }
    
    // Mevcut PC sayısını göster
    document.getElementById('currentPCCountEdit').textContent = currentTotal;
    document.getElementById('newPCCount').value = currentTotal;
    
    const modal = new bootstrap.Modal(document.getElementById('editPCCountModal'));
    modal.show();
}

function confirmEditPCCount() {
    const newCount = parseInt(document.getElementById('newPCCount').value);
    
    if (newCount < 0 || newCount > 1000) {
        showToast('PC sayısı 0-1000 arasında olmalıdır!', 'error');
        return;
    }
    
    fetch('pc_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            lab_id: currentLab,
            count: newCount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.type === 'success') {
            showToast(data.message, 'success');
            // Modal'ı kapat
            const modal = bootstrap.Modal.getInstance(document.getElementById('editPCCountModal'));
            modal.hide();
            // URL'den pc_management parametresini temizle ve sayfayı yenile
            const url = new URL(window.location);
            url.searchParams.delete('pc_management');
            window.history.replaceState({}, '', url);
            location.reload();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Bir hata oluştu: ' + error.message, 'error');
    });
}
