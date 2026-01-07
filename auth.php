<?php
// auth.php - HypeChats OAuth callback handler
session_start();
require_once 'config.php';
require_once 'includes/db.php';

$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    $_SESSION['error'] = 'OAuth failed: ' . htmlspecialchars($error);
    header('Location: login.php');
    exit;
}

if (empty($code)) {
    header('Location: login.php');
    exit;
}

try {
    // Exchange code for access token
    $token_url = 'https://hypechats.com/api/oauth/token';
    $token_data = [
        'grant_type' => 'authorization_code',
        'client_id' => APP_ID,
        'client_secret' => APP_SECRET,
        'code' => $code,
        'redirect_uri' => SITE_URL . '/auth.php'
    ];
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('Failed to get access token');
    }
    
    $token_result = json_decode($response, true);
    $access_token = $token_result['access_token'] ?? '';
    
    if (empty($access_token)) {
        throw new Exception('Invalid access token received');
    }
    
    // Get user info from HypeChats
    $user_url = 'https://hypechats.com/api/user/me';
    $ch = curl_init($user_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    $user_response = curl_exec($ch);
    $user_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($user_http_code !== 200) {
        throw new Exception('Failed to get user information');
    }
    
    $hype_user = json_decode($user_response, true);
    
    if (empty($hype_user['id'])) {
        throw new Exception('Invalid user data received');
    }
    
    $db = new Database();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE hype_id = ?");
    $stmt->execute([$hype_user['id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update existing user
        $stmt = $db->prepare("UPDATE users SET access_token = ?, first_name = ?, last_name = ?, profile_picture = ? WHERE hype_id = ?");
        $stmt->execute([
            $access_token,
            $hype_user['first_name'] ?? '',
            $hype_user['last_name'] ?? '',
            $hype_user['avatar'] ?? '',
            $hype_user['id']
        ]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_picture'] = $hype_user['avatar'] ?? $user['profile_picture'];
    } else {
        // Create new user
        $username = $hype_user['username'] ?? 'user' . uniqid();
        $email = $hype_user['email'] ?? $username . '@hypechats.local';
        
        // Ensure unique username
        $original_username = $username;
        $counter = 1;
        while (true) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if (!$stmt->fetch()) break;
            $username = $original_username . $counter;
            $counter++;
        }
        
        $stmt = $db->prepare("INSERT INTO users (hype_id, username, email, first_name, last_name, profile_picture, access_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $hype_user['id'],
            $username,
            $email,
            $hype_user['first_name'] ?? '',
            $hype_user['last_name'] ?? '',
            $hype_user['avatar'] ?? '',
            $access_token
        ]);
        
        $user_id = $db->lastInsertId();
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['profile_picture'] = $hype_user['avatar'] ?? '';
    }
    
    header('Location: dashboard.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Authentication failed: ' . $e->getMessage();
    header('Location: login.php');
    exit;
}
?>
