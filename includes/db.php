<?php
// ===========================
// FILE: includes/db.php
// ===========================

class Database {
    private $conn;
    
    public function __construct() {
        try {
            // Verify that database constants are defined
            if (!defined('DB_HOST')) {
                throw new Exception('Database configuration error: DB_HOST is not defined. Check your config.php or environment variables.');
            }
            if (!defined('DB_NAME')) {
                throw new Exception('Database configuration error: DB_NAME is not defined. Check your config.php or environment variables.');
            }
            if (!defined('DB_USER')) {
                throw new Exception('Database configuration error: DB_USER is not defined. Check your config.php or environment variables.');
            }
            
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            die($e->getMessage());
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
