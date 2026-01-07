<?php
/**
 * HypeChats OAuth Callback Handler
 * Handles authentication via HypeChats OAuth
 */

session_start();

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Debug: Log all incoming parameters
error_log("HypeChats Auth - GET params: " . print_r($_GET, true));
error_log("HypeChats Auth - APP_ID: " . (defined('APP_ID') ? APP_ID : 'NOT DEFINED'));
error_log("HypeChats Auth - APP_SECRET: " . (defined('APP_SECRET') ? (empty(APP_SECRET) ? 'EMPTY' : 'SET') : 'NOT DEFINED'));

$db = new Database();

// Check if HypeChats OAuth is configured
if (!defined('APP_ID') || !defined('APP_SECRET')) {
    $_SESSION['error'] = 'HypeChats OAuth is not configured in config.php. Please add APP_ID and APP_SECRET.';
    error_log("HypeChats Auth Error: APP_ID or APP_SECRET not defined");
    header('Location: login.php');
    exit;
}

if (empty(APP_ID) || empty(APP_SECRET) || APP_ID === 'your_app_id_here' || APP_ID === 'your_hypechats_app_id_here') {
    $_SESSION['error'] = 'HypeChats OAuth is not properly configured. Please update APP_ID and APP_SECRET in config.php with real values.';
    error_log("HypeChats Auth Error: APP_ID or APP_SECRET has default value");
    header('Location: login.php');
    exit;
}

// Handle OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $app_id = APP_ID;
    $app_secret = APP_SECRET;
    
    error_log("HypeChats Auth: Received code: $code");
    
    try {
        // Step 1: Get access token
        $authorize_url = "https://hypechats.com/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}";
        
        error_log("HypeChats Auth: Calling authorize URL (without secret)");
        
        // Use cURL for better error handling
        $ch = curl_init($authorize_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        error_log("HypeChats Auth: HTTP Code: $http_code");
        error_log("HypeChats Auth: Response: $response");
        
        if ($curl_error) {
            throw new Exception('cURL Error: ' . $curl_error);
        }
        
        if ($response === false || empty($response)) {
            throw new Exception('Failed to connect to HypeChats authorization server.');
        }
        
        $json = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from HypeChats: ' . json_last_error_msg());
        }
        
        error_log("HypeChats Auth: Decoded JSON: " . print_r($json, true));
        
        if (empty($json['access_token'])) {
            $error_msg = isset($json['error']) ? $json['error'] : 'No access token received';
            throw new Exception('Failed to get access token: ' . $error_msg);
        }
        
        $access_token = $json['access_token'];
        error_log("HypeChats Auth: Got access token");
        
        // Step 2: Get user data
        $api_url = "https://hypechats.com/app_api?access_token={$access_token}&type=get_user_data";
        
        error_log("HypeChats Auth: Fetching user data");
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $user_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("HypeChats Auth: User data HTTP Code: $http_code");
        error_log("HypeChats Auth: User data response: $user_response");
        
        if ($user_response === false || empty($user_response)) {
            throw new Exception('Failed to get user data from HypeChats.');
        }
        
        $user_json = json_decode($user_response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in user data response: ' . json_last_error_msg());
        }
        
        error_log("HypeChats Auth: User data JSON: " . print_r($user_json, true));
        
        if ($user_json['api_status'] !== 'success' || empty($user_json['user_data'])) {
            throw new Exception('Invalid response from HypeChats API. Status: ' . ($user_json['api_status'] ?? 'unknown'));
        }
        
        $user_data = $user_json['user_data'];
        
        // Extract user information
        $hypechats_id = $user_data['id'] ?? '';
        $username = $user_data['username'] ?? '';
        $first_name = $user_data['first_name'] ?? '';
        $last_name = $user_data['last_name'] ?? '';
        $profile_picture = $user_data['profile_picture'] ?? '';
        $verified = $user_data['verified'] ?? 0;
        $hypechats_url = $user_data['url'] ?? '';
        
        error_log("HypeChats Auth: User ID: $hypechats_id, Username: $username");
        
        if (empty($hypechats_id) || empty($username)) {
            throw new Exception('Incomplete user data received from HypeChats. ID or username missing.');
        }
        
        // Generate email if not provided
        $email = $username . '@hypechats.user';
        
        // Check if user exists by HypeChats ID
        $stmt = $db->prepare("SELECT * FROM users WHERE hypechats_id = ?");
        $stmt->execute([$hypechats_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Existing user - log them in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            $_SESSION['email'] = $user['email'];
            
            // Update profile picture
            if ($profile_picture != $user['profile_picture']) {
                $stmt = $db->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$profile_picture, $user['id']]);
                $_SESSION['profile_picture'] = $profile_picture;
            }
            
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            error_log("HypeChats Auth: User logged in successfully - User ID: {$user['id']}");
            
            $_SESSION['success'] = 'Welcome back, ' . htmlspecialchars($user['username']) . '!';
            header('Location: dashboard.php');
            exit;
        } else {
            // New user - create account
            // Check if username exists
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_user && empty($existing_user['hypechats_id'])) {
                // Link to existing account
                $stmt = $db->prepare("UPDATE users SET hypechats_id = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hypechats_id, $profile_picture, $existing_user['id']]);
                
                $_SESSION['user_id'] = $existing_user['id'];
                $_SESSION['username'] = $existing_user['username'];
                $_SESSION['profile_picture'] = $profile_picture;
                $_SESSION['is_admin'] = $existing_user['is_admin'] ?? 0;
                $_SESSION['email'] = $existing_user['email'];
                
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$existing_user['id']]);
                
                $_SESSION['success'] = 'Welcome back! Your HypeChats account has been linked.';
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
                INSERT INTO users (username, email, hypechats_id, profile_picture, email_verified, created_at, updated_at, last_login) 
                VALUES (?, ?, ?, ?, 0, NOW(), NOW(), NOW())
            ");
            $stmt->execute([$username, $email, $hypechats_id, $profile_picture]);
            
            $user_id = $db->lastInsertId();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['profile_picture'] = $profile_picture;
            $_SESSION['is_admin'] = 0;
            $_SESSION['email'] = $email;
            
            error_log("HypeChats Auth: New user created - User ID: $user_id, Username: $username");
            
            $_SESSION['success'] = 'Welcome to HYLS! Your account has been created successfully.';
            header('Location: dashboard.php');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("HypeChats OAuth Error: " . $e->getMessage());
        error_log("HypeChats OAuth Stack Trace: " . $e->getTraceAsString());
        $_SESSION['error'] = 'HypeChats Login Error: ' . $e->getMessage();
        header('Location: login.php');
        exit;
    }
    
} else {
    // No code provided
    error_log("HypeChats Auth: No code parameter received");
    $_SESSION['error'] = 'OAuth authentication failed. No authorization code received from HypeChats.';
    header('Location: login.php');
    exit;
}
?>