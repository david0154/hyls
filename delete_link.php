// ===========================
// FILE: delete_link.php
// ===========================

<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$link_id = $_GET['id'];

$stmt = $db->prepare("DELETE FROM short_links WHERE id = ? AND user_id = ?");
$stmt->execute([$link_id, $user_id]);

$_SESSION['success'] = 'Link deleted successfully';
header('Location: dashboard.php');
exit;
?>
