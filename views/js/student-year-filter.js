/**
 * Öğrenci Yılı Filtreleme Sistemi
 * Seçilen lab'daki öğrencilerin yıllarına göre filtrelenmesi
 */

class StudentYearFilter {
    constructor() {
        this.currentFilter = 'all';
        this.availableYears = new Set();
        this.studentData = [];
        this.filteredStudents = [];
        this.isInitialized = false;
        
        this.init();
    }

    /**
     * Filtreleme sistemini başlat
     */
    init() {
        this.createFilterPanel();
        this.bindEvents();
        this.isInitialized = true;
        console.log('Student Year Filter initialized');
    }

    /**
     * Filtreleme panelini oluştur
     */
    createFilterPanel() {
        // Mevcut lab seçici kartından sonra filtreleme panelini ekle
        const labSelectorCard = document.querySelector('.lab-selector-card');
        if (!labSelectorCard) {
            console.error('Lab selector card not found');
            return;
        }

        const filterPanelHTML = `
            <div class="student-filter-panel" id="studentFilterPanel" style="display: none;">
                <div class="filter-header">
                    <h3 class="filter-title">
                        <i class="fas fa-filter"></i>
                        Öğrenci Yılı Filtreleme
                    </h3>
                    <div class="filter-controls">
                        <div class="year-filter-buttons" id="yearFilterButtons">
                            <!-- Yıl butonları dinamik olarak eklenecek -->
                        </div>
                        <button class="show-all-btn active" id="showAllBtn">
                            <i class="fas fa-eye"></i>
                            Tümünü Göster
                        </button>
                        <button class="clear-filters-btn" id="clearFiltersBtn">
                            <i class="fas fa-times"></i>
                            Temizle
                        </button>
                    </div>
                </div>
                <div class="filter-stats" id="filterStats">
                    <div class="filter-stat-item">
                        <i class="fas fa-users"></i>
                        <span>Toplam: <span class="filter-stat-number" id="totalStudents">0</span></span>
                    </div>
                    <div class="filter-stat-item">
                        <i class="fas fa-eye"></i>
                        <span>Görünen: <span class="filter-stat-number" id="visibleStudents">0</span></span>
                    </div>
                    <div class="filter-stat-item">
                        <i class="fas fa-filter"></i>
                        <span>Filtre: <span class="filter-stat-number" id="currentFilterText">Tümü</span></span>
                    </div>
                </div>
                <div class="filter-results-summary" id="filterResultsSummary" style="display: none;">
                    <h6>Filtreleme Sonuçları</h6>
                    <p id="filterResultsText"></p>
                </div>
            </div>
        `;

        labSelectorCard.insertAdjacentHTML('afterend', filterPanelHTML);
    }

    /**
     * Event listener'ları bağla
     */
    bindEvents() {
        // Tümünü göster butonu
        document.getElementById('showAllBtn')?.addEventListener('click', () => {
            this.showAllStudents();
        });

        // Temizle butonu
        document.getElementById('clearFiltersBtn')?.addEventListener('click', () => {
            this.clearFilters();
        });

        // Lab değişikliği eventi
        document.addEventListener('labChanged', (event) => {
            this.onLabChanged(event.detail);
        });

        // PC kartları güncellendiğinde
        document.addEventListener('pcCardsUpdated', () => {
            this.updateStudentData();
        });
    }

    /**
     * Lab değiştiğinde çağrılır
     */
    onLabChanged(labData) {
        if (!labData || !labData.students) {
            this.hideFilterPanel();
            return;
        }

        this.studentData = labData.students;
        this.extractYears();
        this.updateFilterButtons();
        this.updateStats();
        this.showFilterPanel();
    }

    /**
     * Öğrenci verilerinden yılları çıkar
     */
    extractYears() {
        this.availableYears.clear();
        
        this.studentData.forEach(student => {
            if (student.year) {
                this.availableYears.add(student.year);
            }
        });

        // Yılları sırala
        this.availableYears = new Set([...this.availableYears].sort());
    }

    /**
     * Filtreleme butonlarını güncelle
     */
    updateFilterButtons() {
        const buttonsContainer = document.getElementById('yearFilterButtons');
        if (!buttonsContainer) return;

        buttonsContainer.innerHTML = '';

        // Her academic year için buton oluştur
        this.availableYears.forEach(year => {
            const button = document.createElement('button');
            button.className = 'year-filter-btn';
            button.textContent = `${year}`;
            button.dataset.year = year;
            button.addEventListener('click', () => {
                this.filterByYear(year);
            });
            buttonsContainer.appendChild(button);
        });
    }

    /**
     * Belirli bir yıla göre filtrele
     */
    filterByYear(year) {
        this.currentFilter = year;
        this.updateButtonStates();
        this.applyFilter();
        this.updateStats();
        this.showFilterStatus(`Filtre: ${year}`);
    }

    /**
     * Tüm öğrencileri göster
     */
    showAllStudents() {
        this.currentFilter = 'all';
        this.updateButtonStates();
        this.applyFilter();
        this.updateStats();
        this.showFilterStatus('Tüm öğrenciler gösteriliyor');
    }

    /**
     * Filtreleri temizle
     */
    clearFilters() {
        this.currentFilter = 'all';
        this.updateButtonStates();
        this.applyFilter();
        this.updateStats();
        this.hideFilterStatus();
    }

    /**
     * Buton durumlarını güncelle
     */
    updateButtonStates() {
        // Tüm butonları pasif yap
        document.querySelectorAll('.year-filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.getElementById('showAllBtn')?.classList.remove('active');
        document.getElementById('clearFiltersBtn')?.classList.remove('active');

        // Aktif filtreye göre butonu aktif yap
        if (this.currentFilter === 'all') {
            document.getElementById('showAllBtn')?.classList.add('active');
        } else {
            document.querySelector(`[data-year="${this.currentFilter}"]`)?.classList.add('active');
        }
    }

    /**
     * Filtreyi uygula
     */
    applyFilter() {
        if (this.currentFilter === 'all') {
            this.filteredStudents = [...this.studentData];
        } else {
            this.filteredStudents = this.studentData.filter(student => 
                student.year == this.currentFilter
            );
        }

        // PC kartlarını güncelle
        this.updatePCVisibility();
        
        // Öğrenci kartlarını güncelle
        this.updateStudentCards();
    }

    /**
     * PC kartlarının görünürlüğünü güncelle
     */
    updatePCVisibility() {
        const pcCards = document.querySelectorAll('.pc-card');
        
        pcCards.forEach(card => {
            const studentElements = card.querySelectorAll('.student-item, .student-item-simple');
            let hasVisibleStudents = false;

            studentElements.forEach(studentElement => {
                const studentName = studentElement.querySelector('.student-name, .student-name-simple')?.textContent?.trim();
                const studentYear = this.getStudentYearFromElement(studentElement);
                
                if (this.isStudentVisible(studentName, studentYear)) {
                    studentElement.classList.remove('student-card-filtered');
                    studentElement.classList.add('student-card-visible');
                    hasVisibleStudents = true;
                } else {
                    studentElement.classList.remove('student-card-visible');
                    studentElement.classList.add('student-card-filtered');
                }
            });

            // PC kartının kendisini güncelle - animasyon yok
            if (this.currentFilter === 'all') {
                // Tümünü göster seçiliyse, tüm PC'ler görünür
                card.classList.remove('filtered');
                card.classList.add('visible');
            } else if (hasVisibleStudents) {
                // Seçilen academic year'da öğrenci varsa PC görünür
                card.classList.remove('filtered');
                card.classList.add('visible');
            } else {
                // Seçilen academic year'da öğrenci yoksa PC gizli
                card.classList.remove('visible');
                card.classList.add('filtered');
            }
        });
    }

    /**
     * Öğrenci kartlarını güncelle
     */
    updateStudentCards() {
        // Öğrenci listelerindeki kartları güncelle
        const studentCards = document.querySelectorAll('.student-item, .student-item-simple');
        
        studentCards.forEach(card => {
            const studentName = card.querySelector('.student-name, .student-name-simple')?.textContent?.trim();
            const studentYear = this.getStudentYearFromElement(card);
            
            if (this.isStudentVisible(studentName, studentYear)) {
                card.classList.remove('student-card-filtered');
                card.classList.add('student-card-visible');
            } else {
                card.classList.remove('student-card-visible');
                card.classList.add('student-card-filtered');
            }
        });
    }

    /**
     * Öğrenci elementinden academic year bilgisini al
     */
    getStudentYearFromElement(element) {
        // Önce .student-year sınıfını ara
        let yearElement = element.querySelector('.student-year');
        
        // Eğer bulunamazsa, diğer olasılıkları dene
        if (!yearElement) {
            yearElement = element.querySelector('.student-details small, .student-details-simple small');
        }
        
        // Eğer hala bulunamazsa, tüm small elementlerini kontrol et
        if (!yearElement) {
            const smallElements = element.querySelectorAll('small');
            for (let small of smallElements) {
                const text = small.textContent.trim();
                if (text.match(/\d{4}/)) {
                    yearElement = small;
                    break;
                }
            }
        }
        
        if (yearElement) {
            const yearText = yearElement.textContent.trim();
            
            // Academic year formatını ara (2024, 2023, vb.)
            const yearMatch = yearText.match(/(\d{4})/);
            if (yearMatch) {
                return parseInt(yearMatch[1]);
            }
        }
        return null;
    }

    /**
     * Öğrencinin görünür olup olmadığını kontrol et
     */
    isStudentVisible(studentName, studentYear) {
        if (this.currentFilter === 'all') {
            return true;
        }

        if (!studentName) return false;

        // Öğrenci verilerinden eşleşen öğrenciyi bul
        const student = this.studentData.find(s => 
            s.name && s.name.toLowerCase().includes(studentName.toLowerCase())
        );

        if (student) {
            return student.year == this.currentFilter;
        }

        // Eğer veri bulunamazsa, element üzerindeki yıl bilgisini kullan
        if (studentYear) {
            return studentYear == this.currentFilter;
        }

        return false;
    }

    /**
     * İstatistikleri güncelle
     */
    updateStats() {
        const totalStudents = this.studentData.length;
        const visibleStudents = this.currentFilter === 'all' ? totalStudents : this.filteredStudents.length;
        const filterText = this.currentFilter === 'all' ? 'Tümü' : `${this.currentFilter}`;

        document.getElementById('totalStudents').textContent = totalStudents;
        document.getElementById('visibleStudents').textContent = visibleStudents;
        document.getElementById('currentFilterText').textContent = filterText;
    }

    /**
     * Filtreleme panelini göster
     */
    showFilterPanel() {
        const panel = document.getElementById('studentFilterPanel');
        if (panel) {
            panel.style.display = 'block';
            // CSS transition için kısa bir gecikme
            setTimeout(() => {
                panel.classList.add('show');
            }, 10);
        }
    }

    /**
     * Filtreleme panelini gizle
     */
    hideFilterPanel() {
        const panel = document.getElementById('studentFilterPanel');
        if (panel) {
            panel.classList.remove('show');
            // CSS transition tamamlandıktan sonra gizle
            setTimeout(() => {
                panel.style.display = 'none';
            }, 300);
        }
    }

    /**
     * Filtreleme durumu göstergesini göster
     */
    showFilterStatus(message) {
        const indicator = document.getElementById('filterStatusIndicator');
        if (!indicator) {
            this.createFilterStatusIndicator();
        }

        const statusIndicator = document.getElementById('filterStatusIndicator');
        if (statusIndicator) {
            statusIndicator.querySelector('span').textContent = message;
            statusIndicator.classList.add('show');
            
            setTimeout(() => {
                statusIndicator.classList.remove('show');
            }, 3000);
        }
    }

    /**
     * Filtreleme durumu göstergesini gizle
     */
    hideFilterStatus() {
        const indicator = document.getElementById('filterStatusIndicator');
        if (indicator) {
            indicator.classList.remove('show');
        }
    }

    /**
     * Filtreleme durumu göstergesini oluştur
     */
    createFilterStatusIndicator() {
        const indicatorHTML = `
            <div class="filter-status-indicator" id="filterStatusIndicator">
                <i class="fas fa-filter"></i>
                <span>Filtreleme aktif</span>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', indicatorHTML);
    }

    /**
     * Öğrenci verilerini güncelle
     */
    updateStudentData() {
        // Mevcut PC kartlarından öğrenci verilerini topla
        const pcCards = document.querySelectorAll('.pc-card');
        this.studentData = [];

        pcCards.forEach(card => {
            const pcNumber = card.querySelector('.pc-number')?.textContent?.trim();
            const studentElements = card.querySelectorAll('.student-item, .student-item-simple');
            
            studentElements.forEach(element => {
                const name = element.querySelector('.student-name, .student-name-simple')?.textContent?.trim();
                const year = this.getStudentYearFromElement(element);
                
                if (name) {
                    this.studentData.push({
                        name: name,
                        year: year,
                        pcNumber: pcNumber
                    });
                }
            });
        });

        this.extractYears();
        this.updateFilterButtons();
        this.updateStats();
    }

    /**
     * Filtreleme sonuçlarını göster
     */
    showFilterResults() {
        const resultsSummary = document.getElementById('filterResultsSummary');
        const resultsText = document.getElementById('filterResultsText');
        
        if (resultsSummary && resultsText) {
            const total = this.studentData.length;
            const visible = this.currentFilter === 'all' ? total : this.filteredStudents.length;
            const hidden = total - visible;
            
            resultsText.textContent = `${visible} öğrenci gösteriliyor, ${hidden} öğrenci gizlendi.`;
            resultsSummary.style.display = 'block';
        }
    }

    /**
     * Filtreleme sonuçlarını gizle
     */
    hideFilterResults() {
        const resultsSummary = document.getElementById('filterResultsSummary');
        if (resultsSummary) {
            resultsSummary.style.display = 'none';
        }
    }

    /**
     * Animasyonları başlat
     */
    startAnimations() {
        const cards = document.querySelectorAll('.pc-card, .student-item, .student-item-simple');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('student-card-fade-in');
            }, index * 50);
        });
    }

    /**
     * Filtreleme sistemini sıfırla
     */
    reset() {
        this.currentFilter = 'all';
        this.availableYears.clear();
        this.studentData = [];
        this.filteredStudents = [];
        this.hideFilterPanel();
        this.hideFilterStatus();
        this.hideFilterResults();
    }

    /**
     * Mevcut filtreyi al
     */
    getCurrentFilter() {
        return this.currentFilter;
    }

    /**
     * Filtreleme durumunu al
     */
    getFilterStatus() {
        return {
            currentFilter: this.currentFilter,
            totalStudents: this.studentData.length,
            visibleStudents: this.currentFilter === 'all' ? this.studentData.length : this.filteredStudents.length,
            availableYears: [...this.availableYears]
        };
    }
}

// Global instance oluştur
window.studentYearFilter = new StudentYearFilter();

// Sayfa yüklendiğinde başlat
document.addEventListener('DOMContentLoaded', function() {
    if (window.studentYearFilter) {
        console.log('Student Year Filter loaded');
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StudentYearFilter;
}
