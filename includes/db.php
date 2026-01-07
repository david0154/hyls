<?php
// ===========================
// FILE: includes/db.php
// ===========================

class Database {
    private $conn;
    
    public function __construct() {
        // Check if config.php exists
        if (!file_exists(__DIR__ . '/../config.php')) {
            $this->showConfigError(
                'Configuration file missing',
                'The config.php file does not exist.',
                [
                    'Run the installation wizard',
                    'If you already installed, check file permissions',
                    'Verify the file exists at: ' . realpath(__DIR__ . '/..') . '/config.php'
                ]
            );
        }
        
        // Load config.php
        require_once __DIR__ . '/../config.php';
        
        // Check if database constants are defined
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
            $this->showConfigError(
                'Database configuration incomplete',
                'Database constants are not properly defined in config.php.',
                [
                    'Re-run the installation wizard',
                    'Check if config.php contains DB_HOST, DB_NAME, DB_USER, DB_PASS',
                    'Verify config.php is not empty or corrupted'
                ]
            );
        }
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            
            // Check specific error types
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                $this->showConfigError(
                    'Database access denied',
                    'Wrong database username or password.',
                    [
                        'Check your database credentials',
                        'Verify DB_USER and DB_PASS in config.php',
                        'Run install.php?mode=repair to update credentials'
                    ],
                    $e->getMessage()
                );
            } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
                $this->showConfigError(
                    'Database does not exist',
                    'The database specified in config.php was not found.',
                    [
                        'Run install.php to create the database',
                        'Or manually create database: ' . (defined('DB_NAME') ? DB_NAME : 'hyls'),
                        'Then run install.php?mode=repair'
                    ],
                    $e->getMessage()
                );
            } elseif (strpos($e->getMessage(), "Can't connect") !== false || strpos($e->getMessage(), 'Connection refused') !== false) {
                $this->showConfigError(
                    'Database server not reachable',
                    'Cannot connect to MySQL server.',
                    [
                        'Check if MySQL is running: sudo systemctl status mysql',
                        'Start MySQL: sudo systemctl start mysql',
                        'Verify DB_HOST in config.php is correct'
                    ],
                    $e->getMessage()
                );
            } else {
                $this->showConfigError(
                    'Database connection error',
                    'Could not connect to the database.',
                    [
                        'Check error details below',
                        'Verify all database settings in config.php',
                        'Run install.php?mode=repair to fix configuration'
                    ],
                    $e->getMessage()
                );
            }
        }
    }
    
    private function showConfigError($title, $message, $suggestions = [], $technical_details = '') {
        $install_url = $this->getInstallUrl();
        
        die('
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Error - HYLS</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .error-container {
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 600px;
                    width: 100%;
                    padding: 40px;
                }
                .error-icon {
                    width: 80px;
                    height: 80px;
                    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 40px;
                    margin: 0 auto 20px;
                    color: white;
                }
                h1 {
                    color: #1f2937;
                    font-size: 24px;
                    text-align: center;
                    margin-bottom: 10px;
                }
                .message {
                    color: #6b7280;
                    text-align: center;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                .suggestions {
                    background: #f3f4f6;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                .suggestions h3 {
                    color: #374151;
                    font-size: 16px;
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .suggestions ul {
                    list-style: none;
                    padding: 0;
                }
                .suggestions li {
                    color: #4b5563;
                    padding: 8px 0;
                    padding-left: 24px;
                    position: relative;
                    font-size: 14px;
                    line-height: 1.5;
                }
                .suggestions li:before {
                    content: "✓";
                    position: absolute;
                    left: 0;
                    color: #10b981;
                    font-weight: bold;
                }
                .technical-details {
                    background: #fef3c7;
                    border: 1px solid #fde68a;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 20px;
                }
                .technical-details h3 {
                    color: #78350f;
                    font-size: 14px;
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .technical-details code {
                    display: block;
                    background: white;
                    padding: 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    color: #991b1b;
                    word-wrap: break-word;
                    font-family: "Courier New", monospace;
                }
                .action-buttons {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }
                .btn {
                    flex: 1;
                    min-width: 150px;
                    padding: 12px 20px;
                    border-radius: 8px;
                    text-decoration: none;
                    text-align: center;
                    font-weight: 600;
                    transition: transform 0.2s;
                    display: inline-block;
                }
                .btn:hover {
                    transform: translateY(-2px);
                }
                .btn-primary {
                    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                    color: white;
                }
                .btn-secondary {
                    background: #e5e7eb;
                    color: #374151;
                }
                .file-location {
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    padding: 10px;
                    margin-top: 20px;
                    font-size: 12px;
                    color: #6b7280;
                    text-align: center;
                    font-family: "Courier New", monospace;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h1>' . htmlspecialchars($title) . '</h1>
                <p class="message">' . htmlspecialchars($message) . '</p>
                
                ' . (!empty($suggestions) ? '
                <div class="suggestions">
                    <h3>💡 How to fix this:</h3>
                    <ul>
                        ' . implode('', array_map(function($s) { return '<li>' . htmlspecialchars($s) . '</li>'; }, $suggestions)) . '
                    </ul>
                </div>
                ' : '') . '
                
                ' . (!empty($technical_details) ? '
                <div class="technical-details">
                    <h3>🔍 Technical Details:</h3>
                    <code>' . htmlspecialchars($technical_details) . '</code>
                </div>
                ' : '') . '
                
                <div class="action-buttons">
                    <a href="' . htmlspecialchars($install_url) . '" class="btn btn-primary">🚀 Run Installer</a>
                    <a href="' . htmlspecialchars($install_url) . '?mode=repair" class="btn btn-secondary">🔧 Repair Config</a>
                </div>
                
                <div class="file-location">
                    Expected config location: ' . htmlspecialchars(realpath(__DIR__ . '/..') . '/config.php') . '
                </div>
            </div>
        </body>
        </html>
        ');
    }
    
    private function getInstallUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base_path = dirname(dirname($script));
        if ($base_path === '/' || $base_path === '\\') {
            $base_path = '';
        }
        return $protocol . '://' . $host . $base_path . '/install.php';
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
