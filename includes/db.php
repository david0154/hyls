<?php
// ===========================
// FILE: includes/db.php
// ===========================

class Database {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
}
