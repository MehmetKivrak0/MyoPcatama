<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: iambatman.php');
    exit;
}

require_once '../config/db.php';
require_once '../models/Assignment.php';
require_once '../models/Student.php';
require_once '../models/Lab.php';
require_once '../models/Pc.php';

// Veritabanı bağlantısını al
$db = Database::getInstance();

// Laboratuvarları getir
$labModel = new Lab($db);
$labs = $labModel->getAll();

// Assignment modelini de oluştur
$assignmentModel = new Assignment($db);

// Seçili laboratuvar
$selectedComputerId = $_GET['computer_id'] ?? null;
$pcs = [];
$assignments = [];
$stats = null;

// Öğrencileri getir (sayfalama ile)
$studentModel = new Student($db);

// Sayfalama parametreleri
$page = $_GET['page'] ?? 1;
$limit = 50; // Sayfa başına 50 öğrenci
$offset = ($page - 1) * $limit;

// Arama ve filtreleme parametreleri
$search = $_GET['search'] ?? '';
$year_filter = $_GET['year'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Öğrencileri getir
$allStudents = $studentModel->getStudentsPaginated($offset, $limit, $year_filter, $search, $department_filter);
$totalStudents = $studentModel->getTotalStudents($year_filter, $search, $department_filter);

// Bölümleri getir
$departments = $studentModel->getAvailableDepartments();

// Eğer laboratuvar seçildiyse, sadece bu laboratuvarda atanmamış olanları filtrele
if ($selectedComputerId) {
    // Sadece bu laboratuvarda atanmış öğrenci ID'lerini al
    $pcIdStart = $selectedComputerId * 100 + 1;
    $pcIdEnd = $selectedComputerId * 100 + 99;
    
    $assignedStudentsSql = "SELECT DISTINCT student_id FROM myopc_assignments WHERE computer_id BETWEEN ? AND ?";
    $assignedStudentsResult = $db->fetchAll($assignedStudentsSql, [$pcIdStart, $pcIdEnd]);
    
    $assignedStudentIds = [];
    foreach ($assignedStudentsResult as $assigned) {
        $assignedStudentIds[] = $assigned['student_id'];
    }
    
    error_log("DEBUG FILTER - Lab ID: $selectedComputerId - Bu labda atanmış öğrenci ID'leri: " . implode(', ', $assignedStudentIds));
    
    $filteredStudents = [];
    $assignedStudents = [];
    
    foreach ($allStudents as $student) {
        // Sadece bu laboratuvarda atanmış mı kontrol et
        $isAssignedToCurrentLab = in_array($student['student_id'], $assignedStudentIds);
        
        if (!$isAssignedToCurrentLab) {
            $filteredStudents[] = $student;
        } else {
            $assignedStudents[] = $student;
        }
    }
    
    $students = $filteredStudents;
    $totalStudents = count($filteredStudents);
    
    error_log("DEBUG FILTER - Lab ID: $selectedComputerId - Toplam: " . count($allStudents) . " - Filtrelenmiş: " . count($filteredStudents) . " - Atanmış: " . count($assignedStudents));
} else {
    $students = $allStudents;
}

$totalPages = ceil($totalStudents / $limit);

// Debug: Öğrenci sayısını kontrol et
error_log("DEBUG - Laboratuvar ID: $selectedComputerId, Toplam öğrenci sayısı: " . $totalStudents . ", Sayfa: " . $page . ", Limit: " . $limit);

// Debug: Mevcut atamaları kontrol et
if ($selectedComputerId) {
    $allAssignmentsSql = "SELECT a.student_id, s.full_name, a.computer_id, (a.computer_id % 100) as pc_number 
                          FROM myopc_assignments a 
                          INNER JOIN myopc_students s ON a.student_id = s.student_id 
                          WHERE a.computer_id BETWEEN ? AND ?";
    $pcIdStart = $selectedComputerId * 100 + 1;
    $pcIdEnd = $selectedComputerId * 100 + 99;
    $allAssignments = $this->db->fetchAll($allAssignmentsSql, [$pcIdStart, $pcIdEnd]);
    
    error_log("DEBUG - Bu labdaki tüm atamalar:");
    foreach ($allAssignments as $assignment) {
        error_log("  - {$assignment['full_name']} (ID: {$assignment['student_id']}) -> PC{$assignment['pc_number']} (PC ID: {$assignment['computer_id']})");
    }
}

// Test: Belirli bir öğrencinin atama durumunu kontrol et
if ($selectedComputerId && !empty($students)) {
    $testStudent = $students[0];
    $isAssigned = $assignmentModel->isStudentAssignedToLabBoolean($testStudent['student_id'], $selectedComputerId);
    error_log("DEBUG - Test öğrenci: {$testStudent['full_name']} (ID: {$testStudent['student_id']}) - Bu labda atanmış mı: " . ($isAssigned ? 'EVET' : 'HAYIR'));
}

// Laboratuvar seçildiyse PC'leri ve atamaları getir
if ($selectedComputerId) {
    $pcs = $assignmentModel->getPCAssignmentsByLab($selectedComputerId);
    $assignments = $pcs; // PC'ler zaten atama bilgilerini içeriyor
    $stats = $assignmentModel->getAssignmentStats($selectedComputerId);
    
    // Bu laboratuvarda atanmamış öğrenci sayısını hesapla
    $assignedStudentsInLab = $stats['assigned_students'] ?? 0;
    $availableStudentsCount = $totalStudents - $assignedStudentsInLab;
} else {
    $assignedStudentsInLab = 0;
    $availableStudentsCount = $totalStudents;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Atama Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Mevcut CSS Files -->
    <link href="../assets/css/navbar.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <!-- Sayfa Başlığı -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="assignment-section">
                    <div class="assignment-header">
                        <h1 class="assignment-title">
                            <i class="fas fa-users-cog me-3"></i>
                            Öğrenci Atama Sistemi
                        </h1>
                        <p class="assignment-subtitle">
                            Laboratuvar, öğrenci ve PC seçerek atama yapın
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laboratuvar Seçimi -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="assignment-section">
                    <div class="assignment-controls">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="labSelect" class="form-label">
                                        <i class="fas fa-building me-2"></i>
                                        Laboratuvar Seçin
                                    </label>
                                    <select class="form-select" id="labSelect" name="computer_id">
                                        <option value="">Laboratuvar seçin...</option>
                                        <?php if (!empty($labs)): ?>
                                            <?php foreach ($labs as $lab): ?>
                                                <option value="<?php echo $lab['computer_id']; ?>" 
                                                        <?php echo ($selectedComputerId == $lab['computer_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($lab['lab_name']); ?> - <?php echo $lab['pc_count']; ?> PC
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>Henüz laboratuvar bulunmuyor</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        İstatistikler
                                    </label>
                                    <div class="stats-display" id="statsDisplay">
                                        <?php if ($stats): ?>
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <i class="fas fa-users text-primary"></i>
                                                        <span class="stat-number"><?php echo $availableStudentsCount; ?></span>
                                                        <span class="stat-label">Atanabilir Öğrenci</span>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <i class="fas fa-user-check text-success"></i>
                                                        <span class="stat-number"><?php echo $stats['assigned_students']; ?></span>
                                                        <span class="stat-label">Bu Lab'da Atanmış</span>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <i class="fas fa-desktop text-info"></i>
                                                        <span class="stat-number"><?php echo $stats['used_pcs']; ?></span>
                                                        <span class="stat-label">Kullanılan PC</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">Laboratuvar seçin</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selectedComputerId): ?>
        <!-- Atama Alanı -->
        <div class="row">
            <!-- Öğrenci Listesi -->
            <div class="col-md-6">
                <div class="assignment-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">
                            <i class="fas fa-user-graduate me-2"></i>
                            Öğrenci Listesi 
                            <span class="badge bg-primary"><?php echo $availableStudentsCount; ?> öğrenci</span>
                            <?php if ($selectedComputerId): ?>
                                <small class="text-muted">(Bu lab'da atanabilir)</small>
                            <?php endif; ?>
                        </h4>
                        
                        <!-- Arama ve Filtreleme -->
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" id="searchInput" 
                                   placeholder="Öğrenci ara..." value="<?php echo htmlspecialchars($search); ?>" 
                                   style="width: 200px;">
                            <select class="form-select form-select-sm" id="yearFilter" style="width: 120px;">
                                <option value="">Tüm Yıllar</option>
                                <?php
                                $years = $studentModel->getAvailableYears();
                                foreach ($years as $year) {
                                    $selected = ($year_filter == $year['year']) ? 'selected' : '';
                                    echo "<option value='{$year['year']}' $selected>{$year['year']}</option>";
                                }
                                ?>
                            </select>
                            <select class="form-select form-select-sm" id="departmentFilter" style="width: 150px;">
                                <option value="">Tüm Bölümler</option>
                                <?php
                                foreach ($departments as $department) {
                                    $selected = ($department_filter == $department['department']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($department['department']) . "' $selected>" . htmlspecialchars($department['department']) . "</option>";
                                }
                                ?>
                            </select>
                            <button class="btn btn-sm btn-outline-primary" onclick="applyFilters()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="student-list-container" style="max-height: none; overflow-y: visible;">
                        <div class="row" id="studentList">
                            <?php if (empty($students)): ?>
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Hiç öğrenci bulunamadı. Öğrenci verilerini Excel'den import etmeyi deneyin.
                                        <br>
                                        <small>Debug: Toplam öğrenci sayısı: <?php echo count($students); ?></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <?php
                                // Bu öğrencinin tüm atamalarını getir
                                $studentAssignments = $assignmentModel->getStudentAssignments($student['student_id']);
                                
                                // Öğrencinin herhangi bir laboratuvarda ataması var mı kontrol et
                                $hasAnyAssignment = !empty($studentAssignments);
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="student-card assignment-card <?php echo $hasAnyAssignment ? 'has-other-assignments' : ''; ?>" 
                                         data-student-id="<?php echo $student['student_id']; ?>"
                                         data-assigned="false">
                                        <div class="student-header">
                                            <h6 class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></h6>
                                            <div class="student-actions">
                                                <button class="btn btn-sm btn-outline-primary assign-btn" 
                                                        data-student-id="<?php echo $student['student_id']; ?>"
                                                        title="Bu Lab'a Ata">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="student-info">
                                            <div class="student-detail-item">
                                                <i class="fas fa-id-card"></i>
                                                <span class="student-detail-label">Numara:</span>
                                                <span class="student-detail-value"><?php echo htmlspecialchars($student['sdt_nmbr']); ?></span>
                                            </div>
                                            <div class="student-detail-item">
                                                <i class="fas fa-calendar"></i>
                                                <span class="student-detail-label">Yıl:</span>
                                                <span class="student-detail-value"><?php echo $student['academic_year']; ?></span>
                                            </div>
                                            <!-- Diğer laboratuvarlardaki atamaları göster -->
                                            <?php if (!empty($studentAssignments)): ?>
                                                <div class="student-detail-item">
                                                    <i class="fas fa-list"></i>
                                                    <span class="student-detail-label">Diğer Lab'larda Atanmış:</span>
                                                    <div class="other-assignments">
                                                        <?php foreach ($studentAssignments as $assignment): ?>
                                                            <span class="badge bg-secondary me-1">
                                                                <?php echo $assignment['lab_name']; ?> - PC <?php echo $assignment['pc_number']; ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Sayfalama -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Öğrenci sayfalama" class="mt-3">
                            <ul class="pagination pagination-sm justify-content-center">
                                <!-- Önceki sayfa -->
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Sayfa numaraları -->
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Sonraki sayfa -->
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            
                            <!-- Sayfa bilgisi -->
                            <div class="text-center text-muted small">
                                Sayfa <?php echo $page; ?> / <?php echo $totalPages; ?> 
                                (<?php echo $availableStudentsCount; ?> öğrenci)
                                <?php if ($selectedComputerId): ?>
                                    - Bu lab'da atanabilir
                                <?php endif; ?>
                            </div>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PC Listesi -->
            <div class="col-md-6">
                <div class="assignment-section">
                    <h4 class="mb-3">
                        <i class="fas fa-desktop me-2"></i>
                        PC Listesi
                    </h4>
                    <div class="pc-list-container" style="max-height: 500px; overflow-y: auto;">
                        <div class="row" id="pcList">
                            <?php if ($selectedComputerId && !empty($assignments)): ?>
                                <?php 
                                // Debug: Atama verilerini kontrol et
                                error_log("Assignments data: " . print_r($assignments, true));
                                ?>
                                <?php foreach ($assignments as $assignment): ?>
                                <?php
                                $isOccupied = !empty($assignment['students']) && count($assignment['students']) > 0;
                                $assignedStudents = $isOccupied ? $assignment['students'] : [];
                                
                                // Debug: Her PC için detaylı bilgi yazdır
                                error_log("PC {$assignment['pc_number']} (ID: {$assignment['pc_id']}) - student_count: " . count($assignedStudents) . " - isOccupied: " . ($isOccupied ? 'true' : 'false'));
                                if (!empty($assignedStudents)) {
                                    foreach ($assignedStudents as $student) {
                                        error_log("  - Atanmış öğrenci: {$student['full_name']} (ID: {$student['student_id']})");
                                    }
                                }
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="pc-card assignment-card <?php echo $isOccupied ? 'occupied' : 'available'; ?>" 
                                         data-pc-id="<?php echo $assignment['pc_id']; ?>"
                                         data-occupied="<?php echo $isOccupied ? 'true' : 'false'; ?>">
                                        <div class="pc-header">
                                            <h6 class="pc-number">PC <?php echo $assignment['pc_number']; ?></h6>
                                            <div class="pc-status <?php echo $isOccupied ? 'occupied' : 'available'; ?>">
                                                <?php if ($isOccupied): ?>
                                                    <i class="fas fa-users"></i>
                                                    <?php echo count($assignedStudents); ?> Öğrenci
                                                <?php else: ?>
                                                    <i class="fas fa-check-circle"></i>
                                                    Boş
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($isOccupied && !empty($assignedStudents)): ?>
                                            <div class="assigned-students">
                                                <?php foreach ($assignedStudents as $student): ?>
                                                <div class="assigned-student">
                                                    <div class="student-info">
                                                        <div class="student-detail-item">
                                                            <i class="fas fa-user"></i>
                                                            <span class="student-detail-label">Öğrenci:</span>
                                                            <span class="student-detail-value"><?php echo htmlspecialchars($student['full_name']); ?></span>
                                                        </div>
                                                        <div class="student-detail-item">
                                                            <i class="fas fa-id-card"></i>
                                                            <span class="student-detail-label">Numara:</span>
                                                            <span class="student-detail-value"><?php echo htmlspecialchars($student['sdt_nmbr']); ?></span>
                                                        </div>
                                                        <div class="student-detail-item">
                                                            <i class="fas fa-calendar"></i>
                                                            <span class="student-detail-label">Yıl:</span>
                                                            <span class="student-detail-value"><?php echo $student['academic_year']; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="empty-pc">
                                                <i class="fas fa-plus-circle"></i>
                                                <span>Öğrenci Atanabilir</span>
                                                <button class="btn btn-sm btn-primary mt-2" onclick="assignStudent(<?php echo $assignment['pc_id']; ?>, <?php echo $assignment['pc_number']; ?>)">
                                                    <i class="fas fa-user-plus"></i> Atama Yap
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5">
                                    <i class="fas fa-desktop fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Laboratuvar seçin</h5>
                                    <p class="text-muted">PC kartlarını görmek için önce bir laboratuvar seçin.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Atama Modal -->
        <div class="modal fade" id="assignmentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-users-cog me-2"></i>
                            Öğrenci Atama
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="assignmentForm">
                            <input type="hidden" id="selectedStudentId" name="student_id">
                            <input type="hidden" id="selectedComputerId" name="computer_id" value="<?php echo $selectedComputerId; ?>">
                            
                            <div class="form-group mb-3">
                                <label for="pcSelect" class="form-label">PC Seçin</label>
                                <select class="form-select" id="pcSelect" name="pc_id" required>
                                    <option value="">PC seçin...</option>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <?php
                                        $isOccupied = !empty($assignment['students']) && count($assignment['students']) > 0;
                                        $pcId = $selectedComputerId * 100 + $assignment['pc_number'];
                                        ?>
                                        <option value="<?php echo $pcId; ?>" 
                                                <?php echo $isOccupied ? 'disabled' : ''; ?>>
                                            PC <?php echo $assignment['pc_number']; ?> 
                                            <?php echo $isOccupied ? '(Dolu)' : '(Boş)'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Seçilen öğrenci belirtilen PC'ye atanacaktır.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="button" class="btn btn-primary" id="confirmAssignment">
                            <i class="fas fa-check me-2"></i>
                            Atamayı Onayla
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
    /* Atama Sayfası Özel Stilleri */
    .assignment-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        padding: 15px;
        border-radius: 8px;
        background: white;
        margin-bottom: 15px;
    }

    .assignment-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .assignment-card.assigned {
        border-color: #28a745;
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
    }

    .assignment-card.occupied {
        border-color: #dc3545;
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%);
    }

    .assignment-card.available {
        border-color: #17a2b8;
        background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(23, 162, 184, 0.05) 100%);
    }

    .pc-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .pc-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .pc-status.available {
        background: #d4edda;
        color: #155724;
    }

    .pc-status.occupied {
        background: #f8d7da;
        color: #721c24;
    }

    .assigned-student {
        margin-top: 10px;
        padding: 10px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 6px;
    }
    
    .assigned-students {
        margin-top: 10px;
    }
    
    .assigned-students .assigned-student {
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
    }
    
    .assigned-students .assigned-student:last-child {
        margin-bottom: 0;
    }

    .stats-display .stat-item {
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .stats-display .stat-number {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .stats-display .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .student-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .student-actions {
        display: flex;
        gap: 5px;
    }

    .student-detail-item {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .student-detail-item i {
        width: 16px;
        margin-right: 8px;
        color: #6c757d;
    }

    .student-detail-label {
        font-weight: 600;
        margin-right: 5px;
        color: #495057;
    }

    .student-detail-value {
        color: #6c757d;
    }

    .empty-pc {
        text-align: center;
        padding: 20px;
        color: #6c757d;
    }

    .empty-pc i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
    }

    .empty-pc span {
        font-size: 0.9rem;
    }

    .other-assignments {
        margin-top: 5px;
    }

    .other-assignments .badge {
        font-size: 0.7rem;
        margin-bottom: 2px;
        display: inline-block;
    }

    .student-card.has-other-assignments {
        border-left: 4px solid #ffc107;
    }

    .student-card.has-other-assignments .student-name::after {
        content: " (Diğer Lab'larda Atanmış)";
        font-size: 0.7rem;
        color: #ffc107;
        font-weight: normal;
    }
    
    .student-card.assigned {
        border-left: 4px solid #28a745;
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
    }
    
    .student-card.assigned .student-name::after {
        content: " (Bu Lab'da Atanmış)";
        font-size: 0.7rem;
        color: #28a745;
        font-weight: normal;
    }
    
    .student-card.assigned .badge {
        font-size: 0.7rem;
        padding: 4px 8px;
        background-color: #28a745 !important;
        color: white !important;
    }
    

    /* Responsive */
    @media (max-width: 768px) {
        .assignment-card {
            margin-bottom: 15px;
        }
        
        .student-list-container,
        .pc-list-container {
            max-height: none;
        }
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dasboard.js"></script>
    <script src="js/pc-update.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const labSelect = document.getElementById('labSelect');
        const studentList = document.getElementById('studentList');
        const pcList = document.getElementById('pcList');
        const assignmentModal = new bootstrap.Modal(document.getElementById('assignmentModal'));
        const assignmentForm = document.getElementById('assignmentForm');
        const confirmAssignmentBtn = document.getElementById('confirmAssignment');
        
        let selectedStudentId = null;
        
        // Laboratuvar değiştiğinde
        labSelect.addEventListener('change', function() {
            const computerId = this.value;
            if (computerId) {
                window.location.href = 'assign.php?computer_id=' + computerId;
            } else {
                window.location.href = 'assign.php';
            }
        });
        
        // Filtreleme fonksiyonu
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const year = document.getElementById('yearFilter').value;
            const department = document.getElementById('departmentFilter').value;
            const computerId = <?php echo $selectedComputerId ?: 'null'; ?>;
            
            let url = 'assign.php';
            const params = [];
            
            if (search) params.push('search=' + encodeURIComponent(search));
            if (year) params.push('year=' + encodeURIComponent(year));
            if (department) params.push('department=' + encodeURIComponent(department));
            if (computerId) params.push('computer_id=' + computerId);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            window.location.href = url;
        }
        
        // Atama butonu tıklandığında
        document.addEventListener('click', function(e) {
            if (e.target.closest('.assign-btn')) {
                const studentCard = e.target.closest('.student-card');
                selectedStudentId = studentCard.dataset.studentId;
                
                document.getElementById('selectedStudentId').value = selectedStudentId;
                assignmentModal.show();
            }
        });
        
        // Atamayı onayla
        confirmAssignmentBtn.addEventListener('click', function() {
            const formData = new FormData(assignmentForm);
            const pcId = formData.get('pc_id');
            const computerId = formData.get('computer_id');
            const studentId = formData.get('student_id');
            
            console.log('Atama verileri:', {
                pcId: pcId,
                computerId: computerId,
                studentId: studentId
            });
            
            if (!pcId) {
                showToast('Hata', 'Lütfen bir PC seçin', 'error');
                return;
            }
            
            // PC ID'sini hesapla
            let finalPcId = pcId;
            if (computerId && pcId < 100) {
                finalPcId = computerId * 100 + pcId;
            }
            
            const assignmentData = {
                student_id: studentId,
                pc_id: finalPcId,
                computer_id: computerId
            };
            
            fetch('../api/assignments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(assignmentData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.type === 'success') {
                    showToast('Başarılı', data.message, 'success');
                    assignmentModal.hide();
                    location.reload();
                } else {
                    showToast('Hata', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Hata', 'Bir hata oluştu', 'error');
            });
        });
        
        
        // Toast bildirimi göster
        function showToast(title, message, type) {
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast show`;
            
            // Tip'e göre renk belirle
            let bgColor = '#007bff'; // default blue
            if (type === 'success') bgColor = '#28a745';
            else if (type === 'error') bgColor = '#dc3545';
            else if (type === 'warning') bgColor = '#ffc107';
            
            toast.innerHTML = `
                <div class="toast-header" style="background-color: ${bgColor}; color: white;">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
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
        
        // Öğrenci atama fonksiyonu
        function assignStudent(pcId, pcNumber) {
            console.log('assignStudent çağrıldı - PC ID:', pcId, 'PC Number:', pcNumber);
            
            // Mevcut assignmentModal'ı aç
            const modalElement = document.getElementById('assignmentModal');
            if (modalElement) {
                // PC bilgilerini modal başlığına yaz
                const modalTitle = document.querySelector('#assignmentModal .modal-title');
                if (modalTitle) {
                    modalTitle.innerHTML = `<i class="fas fa-user-plus me-2"></i>PC ${pcNumber} - Öğrenci Ata`;
                }
                
                // PC ID'sini hesapla ve seçili yap
                const computerId = <?php echo $selectedComputerId; ?>;
                const finalPcId = computerId * 100 + pcNumber;
                const pcSelect = document.getElementById('pcSelect');
                if (pcSelect) {
                    pcSelect.value = finalPcId;
                }
                
                // Modalı aç
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else {
                console.error('assignmentModal bulunamadı!');
                alert('Modal bulunamadı!');
            }
        }
    });
    </script>
</body>
</html>