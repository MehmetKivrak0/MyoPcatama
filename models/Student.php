<?php

class Student {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Mevcut yılları getir
     */
    public function getAvailableYears() {
        try {
            $query = "SELECT DISTINCT academic_year as year FROM myopc_students ORDER BY year DESC";
            $result = $this->db->query($query);
            
            if ($result) {
                $years = [];
                while ($row = $result->fetch_assoc()) {
                    $years[] = $row;
                }
                return $years;
            } else {
                // Eğer tablo yoksa varsayılan yılları döndür
                return [
                    ['year' => 2024],
                    ['year' => 2023],
                    ['year' => 2022],
                    ['year' => 2021]
                ];
            }
        } catch (Exception $e) {
            // Hata durumunda varsayılan yılları döndür
            return [
                ['year' => 2024],
                ['year' => 2023],
                ['year' => 2022],
                ['year' => 2021]
            ];
        }
    }
    
    /**
     * Tüm öğrencileri getir
     */
    public function getAllStudents() {
        try {
            $query = "SELECT * FROM myopc_students ORDER BY created_at DESC";
            $result = $this->db->query($query);
            
            if ($result) {
                $students = [];
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
                return $students;
            } else {
                return [];
            }
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Belirli bir yıla göre öğrencileri getir
     */
    public function getStudentsByYear($year) {
        try {
            $query = "SELECT * FROM myopc_students WHERE academic_year = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $year);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            
            return $students;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Öğrenci ekle
     */
    public function addStudent($studentData) {
        try {
            // Türkçe karakterleri düzelt
            $studentData['full_name'] = TurkishCharacterHelper::cleanName($studentData['full_name']);
            $studentData['sdt_nmbr'] = TurkishCharacterHelper::cleanText($studentData['sdt_nmbr']);
            
            $query = "INSERT INTO myopc_students (sdt_nmbr, full_name, academic_year, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW() + INTERVAL 1 MINUTE)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssi", 
                $studentData['sdt_nmbr'],
                $studentData['full_name'],
                $studentData['academic_year']
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'id' => $this->db->lastInsertId()];
            } else {
                return ['success' => false, 'error' => $stmt->error];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Öğrenci güncelle
     */
    public function updateStudent($id, $studentData) {
        try {
            // Türkçe karakterleri düzelt
            $studentData['full_name'] = TurkishCharacterHelper::cleanName($studentData['full_name']);
            $studentData['sdt_nmbr'] = TurkishCharacterHelper::cleanText($studentData['sdt_nmbr']);
            
            $query = "UPDATE myopc_students SET sdt_nmbr = ?, full_name = ?, academic_year = ?, updated_at = NOW() WHERE student_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssii", 
                $studentData['sdt_nmbr'],
                $studentData['full_name'],
                $studentData['academic_year'],
                $id
            );
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => $stmt->error];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Öğrenci sil
     */
    public function deleteStudent($id) {
        try {
            $query = "DELETE FROM myopc_students WHERE student_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => $stmt->error];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Öğrenci detaylarını getir
     */
    public function getStudentById($id) {
        try {
            $query = "SELECT * FROM myopc_students WHERE student_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return $row;
            } else {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Sayfalama ile öğrenci listesi getir
     */
    public function getStudentsPaginated($offset, $limit, $year_filter = '', $search = '') {
        try {
            $where_conditions = [];
            $params = [];
            
            if (!empty($year_filter)) {
                $where_conditions[] = "academic_year = " . intval($year_filter);
            }
            
            if (!empty($search)) {
                $search_escaped = $this->db->getConnection()->real_escape_string($search);
                $where_conditions[] = "(full_name LIKE '%{$search_escaped}%' OR sdt_nmbr LIKE '%{$search_escaped}%')";
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT * FROM myopc_students {$where_clause} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
            
            $result = $this->db->query($query);
            
            $students = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
            }
            
            return $students;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Toplam öğrenci sayısını getir
     */
    public function getTotalStudents($year_filter = '', $search = '') {
        try {
            $where_conditions = [];
            
            if (!empty($year_filter)) {
                $where_conditions[] = "academic_year = " . intval($year_filter);
            }
            
            if (!empty($search)) {
                $search_escaped = $this->db->getConnection()->real_escape_string($search);
                $where_conditions[] = "(full_name LIKE '%{$search_escaped}%' OR sdt_nmbr LIKE '%{$search_escaped}%')";
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $query = "SELECT COUNT(*) as total FROM myopc_students {$where_clause}";
            
            $result = $this->db->query($query);
            
            if ($result) {
                $row = $result->fetch_assoc();
                return $row['total'];
            }
            
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Öğrenci numarası kontrolü
     */
    public function studentNumberExists($sdt_nmbr, $exclude_id = null) {
        try {
            $sdt_nmbr_escaped = $this->db->getConnection()->real_escape_string($sdt_nmbr);
            $query = "SELECT COUNT(*) as count FROM myopc_students WHERE sdt_nmbr = '{$sdt_nmbr_escaped}'";
            
            if ($exclude_id) {
                $exclude_id_escaped = intval($exclude_id);
                $query .= " AND student_id != {$exclude_id_escaped}";
            }
            
            $result = $this->db->query($query);
            
            if ($result) {
                $row = $result->fetch_assoc();
                return $row['count'] > 0;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Toplam öğrenci sayısını getir
     */
    public function getStudentCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM myopc_students";
            $result = $this->db->query($query);
            
            if ($result) {
                $row = $result->fetch_assoc();
                return $row['count'];
            }
            
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}