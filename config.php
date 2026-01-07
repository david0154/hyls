<?php
// ===========================
// FILE: config.php
// ===========================
// System configuration file
// DO NOT MODIFY unless you know what you're doing

// App Version
define('APP_VERSION', '1.0.0');

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'hyls');
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
?>