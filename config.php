<?php
// ===========================
// FILE: config.php
// ===========================
// System configuration file
// DO NOT MODIFY unless you know what you're doing

// App Version
define('APP_VERSION', '1.0.0');

// Database Configuration
// Try to get from environment variables first, then use defaults
// For production, set these environment variables:
// export DB_HOST="your_host"
// export DB_USER="your_user"
// export DB_PASS="your_password"
// export DB_NAME="your_database"

$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

// Use environment variables if set, otherwise use defaults
define('DB_HOST', !empty($db_host) ? $db_host : 'localhost');
define('DB_USER', !empty($db_user) ? $db_user : 'root');
define('DB_PASS', !empty($db_pass) ? $db_pass : '');
define('DB_NAME', !empty($db_name) ? $db_name : 'hyls');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'HYLS');
define('SITE_URL', 'https://hyls.space');
define('SITE_KEYWORDS', 'short links, bio link, link shortener');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes

// Upload Settings
define('UPLOAD_DIR', 'uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Enable error logging to file
error_log('[' . date('d-M-Y H:i:s \U\T\C') . '] Config loaded successfully', 3, __DIR__ . '/error.log');
?>
