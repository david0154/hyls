<?php
/**
 * HypeChats OAuth Callback Handler
 * Handles authentication via HypeChats OAuth
 */

session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = new Database();

// Check if HypeChats OAuth is configured
if (!defined('APP_ID') || !defined('APP_SECRET') || empty(APP_ID) || empty(APP_SECRET) || APP_ID === 'your_app_id_here') {
    $_SESSION['error'] = 'HypeChats OAuth is not properly configured. Please contact administrator.';
    header('Location: login.php');
    exit;
}

// Handle OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $app_id = APP_ID;
    $app_secret = APP_SECRET;
    
    try {
        // Step 1: Get access token
        $authorize_url = "https://hypechats.com/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}";
        
        $response = @file_get_contents($authorize_url);
        
        if ($response === false) {
            throw new Exception('Failed to connect to HypeChats. Please try again.');
        }
        
        $json = json_decode($response, true);
        
        if (empty($json['access_token'])) {
            throw new Exception('Failed to get access token from HypeChats');
        }
        
        $access_token = $json['access_token'];
        
        // Step 2: Get user data
        $api_url = "https://hypechats.com/app_api?access_token={$access_token}&type=get_user_data";
        
        $user_response = @file_get_contents($api_url);
        
        if ($user_response === false) {
            throw new Exception('Failed to get user data from HypeChats');
        }
        
        $user_json = json_decode($user_response, true);
        
        if ($user_json['api_status'] !== 'success' || empty($user_json['user_data'])) {
            throw new Exception('Invalid response from HypeChats API');
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
        
        if (empty($hypechats_id) || empty($username)) {
            throw new Exception('Incomplete user data received from HypeChats');
        }
        
        // Generate email if not provided (HypeChats doesn't return email)
        $email = $username . '@hypechats.user';
        
        // Check if user exists by HypeChats ID
        $stmt = $db->prepare("SELECT * FROM users WHERE hypechats_id = ?");
        $stmt->execute([$hypechats_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // User exists - log them in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            $_SESSION['email'] = $user['email'];
            
            // Update profile picture if changed
            if ($profile_picture != $user['profile_picture']) {
                $stmt = $db->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$profile_picture, $user['id']]);
                $_SESSION['profile_picture'] = $profile_picture;
            }
            
            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $_SESSION['success'] = 'Welcome back, ' . htmlspecialchars($user['username']) . '!';
            header('Location: dashboard.php');
            exit;
        } else {
            // Check if username already exists
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_user) {
                // Check if this is the same user trying to link their account
                if (empty($existing_user['hypechats_id'])) {
                    // Link HypeChats account to existing user
                    $stmt = $db->prepare("UPDATE users SET hypechats_id = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hypechats_id, $profile_picture, $existing_user['id']]);
                    
                    $_SESSION['user_id'] = $existing_user['id'];
                    $_SESSION['username'] = $existing_user['username'];
                    $_SESSION['profile_picture'] = $profile_picture;
                    $_SESSION['is_admin'] = $existing_user['is_admin'] ?? 0;
                    $_SESSION['email'] = $existing_user['email'];
                    
                    // Update last login
                    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$existing_user['id']]);
                    
                    $_SESSION['success'] = 'Welcome back! Your HypeChats account has been linked.';
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Username taken by different account
                    $username = $username . '_hc' . substr($hypechats_id, -4);
                }
            }
            
            // Create new user
            // Make sure username is unique
            $base_username = $username;
            $counter = 1;
            $max_attempts = 100;
            while ($counter < $max_attempts) {
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) {
                    break;
                }
                $username = $base_username . $counter;
                $counter++;
            }
            
            // Insert new user
            $stmt = $db->prepare("
                INSERT INTO users (username, email, hypechats_id, profile_picture, email_verified, created_at, updated_at, last_login) 
                VALUES (?, ?, ?, ?, 0, NOW(), NOW(), NOW())
            ");
            $stmt->execute([
                $username,
                $email,
                $hypechats_id,
                $profile_picture
            ]);
            
            $user_id = $db->lastInsertId();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['profile_picture'] = $profile_picture;
            $_SESSION['is_admin'] = 0;
            $_SESSION['email'] = $email;
            
            // Try to send welcome email (non-blocking)
            try {
                $smtp_settings_result = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
                $smtp_settings = [];
                if ($smtp_settings_result) {
                    while ($row = $smtp_settings_result->fetch(PDO::FETCH_ASSOC)) {
                        $smtp_settings[$row['setting_key']] = $row['setting_value'];
                    }
                }
                
                if (!empty($smtp_settings['smtp_enabled']) && !empty($smtp_settings['smtp_host'])) {
                    require_once 'includes/mailer.php';
                    $mailer = new Mailer($smtp_settings);
                    
                    $welcome_subject = 'Welcome to HYLS!';
                    $welcome_message = "
                        <h2>Welcome to HYLS, {$username}!</h2>
                        <p>Your account has been created successfully using HypeChats.</p>
                        <p><strong>Username:</strong> {$username}</p>
                        <p><strong>HypeChats Profile:</strong> <a href='{$hypechats_url}'>View Profile</a></p>
                        <p>You can now start creating and managing your short links!</p>
                        <p><a href='" . SITE_URL . "'>Visit HYLS</a></p>
                    ";
                    
                    $mailer->send($email, $welcome_subject, $welcome_message);
                }
            } catch (Exception $e) {
                // Log but don't block registration
                error_log("Failed to send welcome email: " . $e->getMessage());
            }
            
            $_SESSION['success'] = 'Welcome to HYLS! Your account has been created successfully.';
            header('Location: dashboard.php');
            exit;
        }
        
    } catch (Exception $e) {
        error_log("HypeChats OAuth Error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage() . ' Please try again or use email login.';
        header('Location: login.php');
        exit;
    }
    
} else {
    // No code provided, redirect to login
    $_SESSION['error'] = 'OAuth authentication failed. Please try again.';
    header('Location: login.php');
    exit;
}
?>