<?php
/**
 * HypeChats OAuth Callback Handler
 * Handles authentication via HypeChats OAuth
 * 
 * OAuth Flow:
 * 1. User clicks: https://hypechats.com/oauth?app_id={APP_ID}
 * 2. User authorizes on HypeChats
 * 3. Redirected here with code: https://yourdomain.com/auth.php?code=XXX
 * 4. We exchange code for access_token
 * 5. We get user data with access_token
 */

session_start();

// Enable error logging for debugging
ini_set('display_errors', 0); // Don't show errors to user
error_reporting(E_ALL);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Debug: Log all incoming parameters
error_log("HypeChats OAuth Callback - Full GET: " . print_r($_GET, true));
error_log("HypeChats OAuth - APP_ID: " . (defined('APP_ID') ? APP_ID : 'NOT DEFINED'));
error_log("HypeChats OAuth - APP_SECRET: " . (defined('APP_SECRET') ? (empty(APP_SECRET) ? 'EMPTY' : 'SET') : 'NOT DEFINED'));

$db = new Database();

// Check if HypeChats OAuth is configured
if (!defined('APP_ID') || !defined('APP_SECRET')) {
    $_SESSION['error'] = 'HypeChats OAuth is not configured. Please add APP_ID and APP_SECRET to config.php.';
    error_log("HypeChats Error: APP_ID or APP_SECRET not defined");
    header('Location: login.php');
    exit;
}

if (empty(APP_ID) || empty(APP_SECRET) || 
    APP_ID === 'your_app_id_here' || 
    APP_ID === 'your_hypechats_app_id_here') {
    $_SESSION['error'] = 'HypeChats OAuth not configured. Update APP_ID and APP_SECRET in config.php with real values.';
    error_log("HypeChats Error: APP_ID or APP_SECRET has default/empty value");
    header('Location: login.php');
    exit;
}

// Handle OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $app_id = APP_ID;
    $app_secret = APP_SECRET;
    
    error_log("HypeChats OAuth: Received authorization code: $code");
    
    try {
        // Step 1: Exchange code for access_token
        // According to HypeChats docs: https://hypechats.com/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}
        $authorize_url = "https://hypechats.com/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}";
        
        error_log("HypeChats OAuth: Calling authorize endpoint");
        
        // Use file_get_contents as per HypeChats documentation
        $response = @file_get_contents($authorize_url);
        
        if ($response === false) {
            $error = error_get_last();
            throw new Exception('Failed to connect to HypeChats: ' . ($error['message'] ?? 'Unknown error'));
        }
        
        error_log("HypeChats OAuth: Authorize response: $response");
        
        $json = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from HypeChats: ' . json_last_error_msg());
        }
        
        if (empty($json['access_token'])) {
            $error_msg = isset($json['error']) ? $json['error'] : 'No access token received';
            $api_status = isset($json['api_status']) ? $json['api_status'] : 'unknown';
            throw new Exception("Failed to get access token. Status: $api_status, Error: $error_msg");
        }
        
        $access_token = $json['access_token'];
        error_log("HypeChats OAuth: Got access token successfully");
        
        // Step 2: Get user data with access_token
        // According to HypeChats docs: https://hypechats.com/app_api?access_token={$access_token}&type=get_user_data
        $api_url = "https://hypechats.com/app_api?access_token={$access_token}&type=get_user_data";
        
        error_log("HypeChats OAuth: Fetching user data");
        
        $user_response = @file_get_contents($api_url);
        
        if ($user_response === false) {
            $error = error_get_last();
            throw new Exception('Failed to get user data from HypeChats: ' . ($error['message'] ?? 'Unknown error'));
        }
        
        error_log("HypeChats OAuth: User data response: $user_response");
        
        $user_json = json_decode($user_response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in user data: ' . json_last_error_msg());
        }
        
        if ($user_json['api_status'] !== 'success' || empty($user_json['user_data'])) {
            $status = $user_json['api_status'] ?? 'unknown';
            throw new Exception("HypeChats API error. Status: $status");
        }
        
        $user_data = $user_json['user_data'];
        
        // Extract user information according to HypeChats response format
        $hypechats_id = $user_data['id'] ?? '';
        $username = $user_data['username'] ?? '';
        $first_name = $user_data['first_name'] ?? '';
        $last_name = $user_data['last_name'] ?? '';
        $profile_picture = $user_data['profile_picture'] ?? '';
        $cover_picture = $user_data['cover_picture'] ?? '';
        $verified = $user_data['verified'] ?? 0;
        $about = $user_data['about'] ?? '';
        $website = $user_data['website'] ?? '';
        $hypechats_url = $user_data['url'] ?? '';
        
        error_log("HypeChats OAuth: User data extracted - ID: $hypechats_id, Username: $username");
        
        if (empty($hypechats_id) || empty($username)) {
            throw new Exception('Incomplete user data from HypeChats. Missing ID or username.');
        }
        
        // Generate email (HypeChats doesn't provide email)
        $email = $username . '@hypechats.user';
        
        // Check if user exists by HypeChats ID
        $stmt = $db->prepare("SELECT * FROM users WHERE hypechats_id = ?");
        $stmt->execute([$hypechats_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Existing user - update and log in
            error_log("HypeChats OAuth: Existing user found - ID: {$user['id']}");
            
            // Update profile info
            $stmt = $db->prepare("
                UPDATE users 
                SET profile_picture = ?, 
                    updated_at = NOW(), 
                    last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$profile_picture, $user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = $profile_picture;
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            $_SESSION['email'] = $user['email'];
            
            error_log("HypeChats OAuth: User logged in - ID: {$user['id']}");
            
            $_SESSION['success'] = 'Welcome back, ' . htmlspecialchars($user['username']) . '!';
            header('Location: dashboard.php');
            exit;
        } else {
            // New user - create account
            error_log("HypeChats OAuth: Creating new user - Username: $username");
            
            // Check if username exists
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_user && empty($existing_user['hypechats_id'])) {
                // Link HypeChats to existing account
                $stmt = $db->prepare("
                    UPDATE users 
                    SET hypechats_id = ?, 
                        profile_picture = ?, 
                        updated_at = NOW(), 
                        last_login = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$hypechats_id, $profile_picture, $existing_user['id']]);
                
                $_SESSION['user_id'] = $existing_user['id'];
                $_SESSION['username'] = $existing_user['username'];
                $_SESSION['profile_picture'] = $profile_picture;
                $_SESSION['is_admin'] = $existing_user['is_admin'] ?? 0;
                $_SESSION['email'] = $existing_user['email'];
                
                $_SESSION['success'] = 'HypeChats account linked successfully!';
                header('Location: dashboard.php');
                exit;
            } elseif ($existing_user) {
                // Username taken, add suffix
                $username = $username . '_hc' . substr($hypechats_id, -4);
            }
            
            // Make username unique
            $base_username = $username;
            $counter = 1;
            while ($counter < 100) {
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) break;
                $username = $base_username . $counter;
                $counter++;
            }
            
            // Create new user
            $stmt = $db->prepare("
                INSERT INTO users 
                (username, email, hypechats_id, profile_picture, email_verified, created_at, updated_at, last_login) 
                VALUES (?, ?, ?, ?, 0, NOW(), NOW(), NOW())
            ");
            $stmt->execute([$username, $email, $hypechats_id, $profile_picture]);
            
            $user_id = $db->lastInsertId();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['profile_picture'] = $profile_picture;
            $_SESSION['is_admin'] = 0;
            $_SESSION['email'] = $email;
            
            error_log("HypeChats OAuth: New user created - ID: $user_id, Username: $username");
            
            $_SESSION['success'] = 'Welcome to HYLS! Your account has been created.';
            header('Location: dashboard.php');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("HypeChats OAuth Error: " . $e->getMessage());
        error_log("HypeChats OAuth Stack: " . $e->getTraceAsString());
        $_SESSION['error'] = 'HypeChats Login Error: ' . $e->getMessage();
        header('Location: login.php');
        exit;
    }
    
} else {
    // No code parameter - user didn't authorize or error
    error_log("HypeChats OAuth: No code parameter received");
    
    if (isset($_GET['error'])) {
        $error_msg = $_GET['error_description'] ?? $_GET['error'] ?? 'Unknown error';
        error_log("HypeChats OAuth: Error from HypeChats - $error_msg");
        $_SESSION['error'] = 'Authorization failed: ' . $error_msg;
    } else {
        $_SESSION['error'] = 'No authorization code received from HypeChats.';
    }
    
    header('Location: login.php');
    exit;
}
?>