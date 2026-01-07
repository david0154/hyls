<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please login first';
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: dashboard.php');
    exit;
}

try {
    $db = new Database();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $user_id = $_SESSION['user_id'];

    // Get POST data
    $url = $_POST['url'] ?? '';
    $custom_code = $_POST['custom_code'] ?? null;
    $title = $_POST['title'] ?? null;
    
    error_log("Shorten.php - URL received: " . $url);
    error_log("Shorten.php - Custom code: " . $custom_code);
    error_log("Shorten.php - Title: " . $title);

    // Validate URL
    if (empty($url)) {
        $_SESSION['error'] = 'URL is required';
        header('Location: dashboard.php');
        exit;
    }
    
    // Sanitize URL
    $url = sanitizeUrl($url);
    if (!$url) {
        $_SESSION['error'] = 'Invalid URL format. Make sure it starts with http:// or https://';
        error_log("Shorten.php - URL sanitization failed");
        header('Location: dashboard.php');
        exit;
    }
    
    error_log("Shorten.php - Sanitized URL: " . $url);

    // Validate custom code if provided
    if (!empty($custom_code)) {
        $custom_code = trim($custom_code);
        if (!preg_match('/^[a-zA-Z0-9\-_]{2,20}$/', $custom_code)) {
            $_SESSION['error'] = 'Custom code can only contain letters, numbers, hyphens and underscores (2-20 chars)';
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $custom_code = null;
    }

    // Create short link
    error_log("Shorten.php - Calling createShortLink function");
    $result = createShortLink($db, $user_id, $url, $custom_code, $title);
    
    error_log("Shorten.php - Result: " . json_encode($result));

    if ($result['success']) {
        $_SESSION['success'] = 'Short link created successfully! 🎉<br>Link: ' . $result['url'];
        error_log("Shorten.php - Link created: " . $result['url']);
    } else {
        $_SESSION['error'] = 'Failed to create link: ' . $result['message'];
        error_log("Shorten.php - Error: " . $result['message']);
    }

    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    error_log("Shorten.php Exception: " . $e->getMessage());
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
    header('Location: dashboard.php');
    exit;
}
?>