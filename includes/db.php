<?php
// ===========================
// FILE: includes/db.php
// ===========================

class Database {
    private $conn;
    
    public function __construct() {
        try {
            // Check if configuration is complete
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
                die(
                    '<h1>⚠️ Configuration Error</h1>' .
                    '<p>The application is not properly configured.</p>' .
                    '<p>Please run the installation wizard at:</p>' .
                    '<p><a href="install.php">https://yourdomain.com/install.php</a></p>' .
                    '<p>Or contact your administrator.</p>' .
                    '<p style="color: #999; margin-top: 20px; font-size: 12px;">If install.php has been deleted, restore it with: git checkout install.php</p>'
                );
            }
            
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die(
                '<h1>🔴 Database Connection Error</h1>' .
                '<p>Could not connect to the database.</p>' .
                '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>' .
                '<p style="color: #999; margin-top: 20px; font-size: 12px;">Please check:</p>' .
                '<ul style="color: #999; font-size: 12px;">' .
                '<li>MySQL is running</li>' .
                '<li>Database credentials are correct in config.php</li>' .
                '<li>Database server is accessible</li>' .
                '</ul>'
            );
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
