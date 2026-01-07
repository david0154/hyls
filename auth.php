<?php
// auth.php - HypeChats OAuth callback handler
session_start();
require_once 'config.php';
require_once 'includes/db.php';

$code = $_GET['code'] ?? '';
$error = $_GET['error'] ?? '';
$error_description = $_GET['error_description'] ?? '';

if ($error) {
    $_SESSION['error'] = 'OAuth failed: ' . htmlspecialchars($error);
    if (!empty($error_description)) {
        $_SESSION['error'] .= ' - ' . htmlspecialchars($error_description);
    }
    header('Location: login.php');
    exit;
}

if (empty($code)) {
    $_SESSION['error'] = 'No authorization code received';
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curl_error)) {
        throw new Exception('CURL Error: ' . $curl_error);
    }
    
    if ($http_code !== 200) {
        error_log("HypeChats Token Error (Code: $http_code): " . $response);
        throw new Exception('Failed to get access token. HTTP Status: ' . $http_code);
    }
    
    $token_result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from token endpoint');
    }
    
    $access_token = $token_result['access_token'] ?? '';
    if (empty($access_token)) {
        error_log("HypeChats Token Response: " . $response);
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $user_response = curl_exec($ch);
    $user_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $user_curl_error = curl_error($ch);
    curl_close($ch);
    
    if (!empty($user_curl_error)) {
        throw new Exception('CURL Error fetching user: ' . $user_curl_error);
    }
    
    if ($user_http_code !== 200) {
        error_log("HypeChats User Error (Code: $user_http_code): " . $user_response);
        throw new Exception('Failed to get user information. HTTP Status: ' . $user_http_code);
    }
    
    $hype_user = json_decode($user_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from user endpoint');
    }
    
    if (empty($hype_user['id'])) {
        error_log("Invalid HypeChats User Response: " . $user_response);
        throw new Exception('Invalid user data received from HypeChats');
    }
    
    $db = new Database();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE hype_id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed');
    }
    
    $stmt->execute([$hype_user['id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update existing user
        $update_stmt = $db->prepare("UPDATE users SET access_token = ?, first_name = ?, last_name = ?, profile_picture = ?, updated_at = NOW() WHERE hype_id = ?");
        if (!$update_stmt) {
            throw new Exception('Update statement failed');
        }
        
        $update_stmt->execute([
            $access_token,
            $hype_user['first_name'] ?? '',
            $hype_user['last_name'] ?? '',
            $hype_user['avatar'] ?? '',
            $hype_user['id']
        ]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_picture'] = $hype_user['avatar'] ?? $user['profile_picture'];
        $_SESSION['success'] = 'Welcome back, ' . htmlspecialchars($user['first_name'] ?? $user['username']) . '!';
    } else {
        // Create new user
        $username = $hype_user['username'] ?? 'user' . uniqid();
        $email = $hype_user['email'] ?? $username . '@hypechats.local';
        
        // Ensure unique username
        $original_username = $username;
        $counter = 1;
        while (true) {
            $check_stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            if (!$check_stmt) {
                throw new Exception('Check username statement failed');
            }
            
            $check_stmt->execute([$username]);
            if (!$check_stmt->fetch()) {
                break;
            }
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Insert new user
        $insert_stmt = $db->prepare("INSERT INTO users (hype_id, username, email, first_name, last_name, profile_picture, access_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$insert_stmt) {
            throw new Exception('Insert statement failed');
        }
        
        $insert_stmt->execute([
            $hype_user['id'],
            $username,
            $email,
            $hype_user['first_name'] ?? '',
            $hype_user['last_name'] ?? '',
            $hype_user['avatar'] ?? '',
            $access_token
        ]);
        
        $user_id = $db->lastInsertId();
        
        // Create default bio link
        $bio_stmt = $db->prepare("INSERT INTO bio_links (user_id, username, display_name, theme_color, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($bio_stmt) {
            $bio_stmt->execute([
                $user_id,
                $username,
                $hype_user['first_name'] . ' ' . ($hype_user['last_name'] ?? '') ?? $username,
                '#6366f1'
            ]);
        }
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['profile_picture'] = $hype_user['avatar'] ?? '';
        $_SESSION['success'] = 'Welcome to HYLS! Your account has been created successfully.';
    }
    
    header('Location: dashboard.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Authentication Error: ' . $e->getMessage();
    error_log('Auth.php Error: ' . $e->getMessage());
    header('Location: login.php');
    exit;
}
?>