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
            $query = "SELECT DISTINCT YEAR(created_at) as year FROM myopc_students ORDER BY year DESC";
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
            $query = "SELECT * FROM myopc_students WHERE YEAR(created_at) = ? ORDER BY created_at DESC";
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
            $query = "INSERT INTO myopc_students (student_number, first_name, last_name, email, phone, year, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssssi", 
                $studentData['student_number'],
                $studentData['first_name'],
                $studentData['last_name'],
                $studentData['email'],
                $studentData['phone'],
                $studentData['year']
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'id' => $this->db->insert_id];
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
            $query = "UPDATE myopc_students SET student_number = ?, first_name = ?, last_name = ?, email = ?, phone = ?, year = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssssii", 
                $studentData['student_number'],
                $studentData['first_name'],
                $studentData['last_name'],
                $studentData['email'],
                $studentData['phone'],
                $studentData['year'],
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
            $query = "DELETE FROM myopc_students WHERE id = ?";
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
            $query = "SELECT * FROM myopc_students WHERE id = ?";
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
}