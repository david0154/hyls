<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = new Database();

// Get Google OAuth settings
$settings_result = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'google_%'");
$settings = [];
while ($row = $settings_result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$google_oauth_enabled = $settings['google_oauth_enabled'] ?? 0;
$client_id = $settings['google_client_id'] ?? '';
$client_secret = $settings['google_client_secret'] ?? '';
$redirect_uri = SITE_URL . '/google-auth.php';

// Check if Google OAuth is enabled
if (!$google_oauth_enabled) {
    $_SESSION['error'] = 'Google login is currently disabled';
    header('Location: login.php');
    exit;
}

// Check if we have the required credentials
if (empty($client_id) || empty($client_secret)) {
    $_SESSION['error'] = 'Google OAuth is not properly configured. Please contact administrator.';
    header('Location: login.php');
    exit;
}

// Handle OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange authorization code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200) {
        error_log("Google OAuth Token Error: " . $response);
        $_SESSION['error'] = 'Failed to authenticate with Google. Please try again.';
        header('Location: login.php');
        exit;
    }
    
    $token_response = json_decode($response, true);
    $access_token = $token_response['access_token'] ?? null;
    
    if (!$access_token) {
        $_SESSION['error'] = 'Failed to get access token from Google';
        header('Location: login.php');
        exit;
    }
    
    // Get user info from Google
    $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($userinfo_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $userinfo_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200) {
        error_log("Google OAuth UserInfo Error: " . $userinfo_response);
        $_SESSION['error'] = 'Failed to get user information from Google';
        header('Location: login.php');
        exit;
    }
    
    $user_data = json_decode($userinfo_response, true);
    
    if (!isset($user_data['email'])) {
        $_SESSION['error'] = 'Could not retrieve email from Google account';
        header('Location: login.php');
        exit;
    }
    
    $google_id = $user_data['id'];
    $email = $user_data['email'];
    $name = $user_data['name'] ?? '';
    $profile_picture = $user_data['picture'] ?? '';
    $verified_email = $user_data['verified_email'] ?? false;
    
    // Check if user exists by Google ID
    $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$google_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User exists - log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
        
        // Update profile picture if changed
        if ($profile_picture != $user['profile_picture']) {
            $stmt = $db->prepare("UPDATE users SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$profile_picture, $user['id']]);
            $_SESSION['profile_picture'] = $profile_picture;
        }
        
        header('Location: dashboard.php');
        exit;
    } else {
        // Check if email already exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user) {
            // Link Google account to existing user
            $stmt = $db->prepare("UPDATE users SET google_id = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$google_id, $profile_picture, $existing_user['id']]);
            
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['username'] = $existing_user['username'];
            $_SESSION['profile_picture'] = $profile_picture;
            $_SESSION['is_admin'] = $existing_user['is_admin'] ?? 0;
            
            header('Location: dashboard.php');
            exit;
        } else {
            // Create new user
            // Generate username from name or email
            $username = strtolower(str_replace(' ', '', $name));
            if (empty($username)) {
                $username = explode('@', $email)[0];
            }
            
            // Make sure username is unique
            $base_username = $username;
            $counter = 1;
            while (true) {
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) {
                    break;
                }
                $username = $base_username . $counter;
                $counter++;
            }
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, google_id, profile_picture, email_verified, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $username,
                    $email,
                    $google_id,
                    $profile_picture,
                    $verified_email ? 1 : 0
                ]);
                
                $user_id = $db->lastInsertId();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['profile_picture'] = $profile_picture;
                $_SESSION['is_admin'] = 0;
                
                // Send welcome email if SMTP is configured
                try {
                    $smtp_settings_result = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
                    $smtp_settings = [];
                    while ($row = $smtp_settings_result->fetch(PDO::FETCH_ASSOC)) {
                        $smtp_settings[$row['setting_key']] = $row['setting_value'];
                    }
                    
                    if (!empty($smtp_settings['smtp_enabled'])) {
                        require_once 'includes/mailer.php';
                        $mailer = new Mailer($smtp_settings);
                        $mailer->sendWelcome($email, $username);
                    }
                } catch (Exception $e) {
                    error_log("Failed to send welcome email: " . $e->getMessage());
                }
                
                header('Location: dashboard.php');
                exit;
                
            } catch (Exception $e) {
                error_log("Google OAuth Registration Error: " . $e->getMessage());
                $_SESSION['error'] = 'Failed to create account. Please try again.';
                header('Location: login.php');
                exit;
            }
        }
    }
    
} else {
    // Redirect to Google OAuth
    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';
    $params = [
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    
    $google_oauth_url = $auth_url . '?' . http_build_query($params);
    header('Location: ' . $google_oauth_url);
    exit;
}
