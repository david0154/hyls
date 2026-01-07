<?php
// auth.php - HypeChats OAuth callback handler
// Uses official HypeChats API as per documentation
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
    $_SESSION['error'] = 'No authorization code received';
    header('Location: login.php');
    exit;
}

try {
    // Step 1: Get Access Token from HypeChats
    // Using official endpoint: https://hypechats.com/authorize
    $token_url = "https://hypechats.com/authorize?app_id=" . urlencode(APP_ID) . 
                 "&app_secret=" . urlencode(APP_SECRET) . 
                 "&code=" . urlencode($code);
    
    // Use file_get_contents as per HypeChats documentation
    $response = @file_get_contents($token_url);
    
    if ($response === false) {
        throw new Exception('Failed to connect to HypeChats API. Please check your internet connection.');
    }
    
    $token_result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid response from HypeChats API');
    }
    
    if (empty($token_result['access_token'])) {
        error_log("HypeChats Token Error: " . json_encode($token_result));
        throw new Exception('Failed to obtain access token. Please try again.');
    }
    
    $access_token = $token_result['access_token'];
    
    // Step 2: Get User Data from HypeChats
    // Using official endpoint: https://hypechats.com/app_api
    $user_url = "https://hypechats.com/app_api?access_token=" . urlencode($access_token) . 
                "&type=get_user_data";
    
    $user_response = @file_get_contents($user_url);
    
    if ($user_response === false) {
        throw new Exception('Failed to retrieve user data from HypeChats');
    }
    
    $user_result = json_decode($user_response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid user data response from HypeChats');
    }
    
    if (empty($user_result['user_data']) || empty($user_result['user_data']['id'])) {
        error_log("HypeChats User Error: " . json_encode($user_result));
        throw new Exception('Failed to retrieve user information');
    }
    
    $hype_user = $user_result['user_data'];
    
    // Initialize Database
    $db = new Database();
    
    // Step 3: Check if user exists in our database
    $stmt = $db->prepare("SELECT * FROM users WHERE hype_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: Failed to prepare statement');
    }
    
    $stmt->execute([$hype_user['id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // User exists - Update their data
        $update_stmt = $db->prepare("UPDATE users SET 
            access_token = ?, 
            first_name = ?, 
            last_name = ?, 
            profile_picture = ?,
            updated_at = NOW() 
            WHERE hype_id = ?");
        
        if (!$update_stmt) {
            throw new Exception('Database error: Failed to prepare update statement');
        }
        
        $update_stmt->execute([
            $access_token,
            $hype_user['first_name'] ?? '',
            $hype_user['last_name'] ?? '',
            $hype_user['profile_picture'] ?? '',
            $hype_user['id']
        ]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_picture'] = $hype_user['profile_picture'] ?? $user['profile_picture'];
        $_SESSION['success'] = 'Welcome back, ' . htmlspecialchars($hype_user['first_name'] ?? $user['username']) . '!';
        
        error_log("User login: " . $user['username'] . " (ID: " . $user['id'] . ")");
    } else {
        // New user - Create account
        $username = $hype_user['username'] ?? 'user' . uniqid();
        $email = 'user' . uniqid() . '@hypechats.local'; // Generate unique email
        
        // Ensure username is unique
        $original_username = $username;
        $counter = 1;
        while (true) {
            $check_stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            if (!$check_stmt) {
                throw new Exception('Database error: Failed to check username');
            }
            
            $check_stmt->execute([$username]);
            if (!$check_stmt->fetch()) {
                break; // Username is unique
            }
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Insert new user
        $insert_stmt = $db->prepare("INSERT INTO users 
            (hype_id, username, email, first_name, last_name, profile_picture, access_token, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if (!$insert_stmt) {
            throw new Exception('Database error: Failed to prepare insert statement');
        }
        
        $insert_stmt->execute([
            $hype_user['id'],
            $username,
            $email,
            $hype_user['first_name'] ?? '',
            $hype_user['last_name'] ?? '',
            $hype_user['profile_picture'] ?? '',
            $access_token
        ]);
        
        $user_id = $db->lastInsertId();
        
        // Create default bio link for new users
        $bio_stmt = $db->prepare("INSERT INTO bio_links 
            (user_id, username, display_name, theme_color, created_at) 
            VALUES (?, ?, ?, ?, NOW())");
        
        if ($bio_stmt) {
            $display_name = trim(($hype_user['first_name'] ?? '') . ' ' . ($hype_user['last_name'] ?? ''));
            if (empty($display_name)) {
                $display_name = $username;
            }
            
            $bio_stmt->execute([
                $user_id,
                $username,
                $display_name,
                '#6366f1' // Default theme color
            ]);
        }
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['profile_picture'] = $hype_user['profile_picture'] ?? '';
        $_SESSION['success'] = 'Welcome to HYLS! Your account has been created successfully. Your bio page is ready to customize.';
        
        error_log("New user created: " . $username . " (ID: " . $user_id . ") via HypeChats OAuth");
    }
    
    // Redirect to dashboard on success
    header('Location: dashboard.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = htmlspecialchars($e->getMessage());
    error_log('Auth.php Error: ' . $e->getMessage() . ' | Code: ' . $code);
    header('Location: login.php');
    exit;
}
?>