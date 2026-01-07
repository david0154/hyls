<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';

$db = new Database();

if (!isset($_GET['code'])) {
    header('Location: https://hypechats.com/oauth?app_id=' . APP_ID);
    exit;
}

$code = $_GET['code'];

try {
    $get = file_get_contents("https://hypechats.com/authorize?app_id=" . APP_ID . "&app_secret=" . APP_SECRET . "&code=" . $code);
    $json = json_decode($get, true);
    
    if (empty($json['access_token'])) {
        throw new Exception('Failed to get access token');
    }
    
    $access_token = $json['access_token'];
    
    $user_data = file_get_contents("https://hypechats.com/app_api?access_token={$access_token}&type=get_user_data");
    $user_json = json_decode($user_data, true);
    
    if ($user_json['api_status'] !== 'success') {
        throw new Exception('Failed to get user data');
    }
    
    $user = $user_json['user_data'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE hype_id = ? OR username = ?");
    $stmt->execute([$user['id'], $user['username']]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        $stmt = $db->prepare("UPDATE users SET access_token = ?, first_name = ?, last_name = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([
            $access_token,
            $user['first_name'],
            $user['last_name'],
            $user['profile_picture'],
            $existing_user['id']
        ]);
        $user_id = $existing_user['id'];
    } else {
        $email = $user['email'] ?? $user['username'] . '@hypechats.user';
        $stmt = $db->prepare("INSERT INTO users (hype_id, username, email, first_name, last_name, profile_picture, access_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            $user['username'],
            $email,
            $user['first_name'],
            $user['last_name'],
            $user['profile_picture'],
            $access_token
        ]);
        $user_id = $db->lastInsertId();
    }
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $user['username'];
    $_SESSION['profile_picture'] = $user['profile_picture'];
    
    header('Location: dashboard.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Authentication failed: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>
