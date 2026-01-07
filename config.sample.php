<?php
/**
 * HYLS Configuration File
 * Copy this file to config.php and update with your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Site Configuration
define('SITE_NAME', 'HYLS');
define('SITE_URL', 'https://yourdomain.com');
define('SITE_DESCRIPTION', 'Modern URL Shortener');

// HypeChats OAuth Configuration
// Get your credentials from: https://hypechats.com/developers
// IMPORTANT: Set redirect URI to: https://yourdomain.com/auth.php
define('APP_ID', 'your_hypechats_app_id_here');
define('APP_SECRET', 'your_hypechats_app_secret_here');

// Google OAuth Configuration (Optional)
// Get credentials from: https://console.cloud.google.com/
// Set redirect URI to: https://yourdomain.com/google-auth.php
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');

// Security
define('SESSION_LIFETIME', 3600); // 1 hour
define('ENCRYPTION_KEY', 'change_this_to_random_string');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');
?>