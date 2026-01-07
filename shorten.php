// ===========================
// FILE: shorten.php
// ===========================

<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];

$url = $_POST['url'] ?? '';
$custom_code = $_POST['custom_code'] ?? null;
$title = $_POST['title'] ?? null;

if (empty($url)) {
    $_SESSION['error'] = 'URL is required';
    header('Location: dashboard.php');
    exit;
}

$url = sanitizeUrl($url);
if (!$url) {
    $_SESSION['error'] = 'Invalid URL';
    header('Location: dashboard.php');
    exit;
}

if ($custom_code && !preg_match('/^[a-zA-Z0-9-_]+$/', $custom_code)) {
    $_SESSION['error'] = 'Custom code can only contain letters, numbers, hyphens and underscores';
    header('Location: dashboard.php');
    exit;
}

$result = createShortLink($db, $user_id, $url, $custom_code, $title);

if ($result['success']) {
    $_SESSION['success'] = 'Short link created: ' . $result['url'];
} else {
    $_SESSION['error'] = $result['message'];
}

header('Location: dashboard.php');
exit;
?>
