<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $db = new Database();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['password'] && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

// Get Google OAuth settings
$db = new Database();
$settings_result = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'google_%'");
$settings = [];
while ($row = $settings_result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$google_oauth_enabled = $settings['google_oauth_enabled'] ?? 0;

// Build OAuth URL with proper URL encoding
$oauth_params = [
    'client_id' => APP_ID,
    'redirect_uri' => SITE_URL . '/auth.php',
    'response_type' => 'code',
    'scope' => 'profile email'
];
$oauth_url = 'https://hypechats.com/oauth/authorize?' . http_build_query($oauth_params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <meta name="description" content="<?= SITE_DESCRIPTION ?>">
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #6366f1;
            font-size: 32px;
            margin-bottom: 8px;
        }
        .logo p {
            color: #64748b;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 16px;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .oauth-btn {
            width: 100%;
            padding: 14px;
            background: white;
            color: #334155;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            margin-bottom: 12px;
        }
        .oauth-btn:hover {
            background: #f8f9ff;
            border-color: #6366f1;
            transform: translateY(-2px);
        }
        .oauth-btn.google {
            background: white;
            color: #333;
        }
        .oauth-btn.google:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .oauth-btn.hypechats {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        .oauth-btn.hypechats:hover {
            background: #4f46e5;
        }
        .google-icon {
            width: 20px;
            height: 20px;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #64748b;
            position: relative;
        }
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e2e8f0;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            font-size: 14px;
        }
        .signup-link a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <h1>🔗 <?= SITE_NAME ?></h1>
            <p>Sign in to your account</p>
        </div>

        <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($google_oauth_enabled): ?>
        <a href="google-auth.php" class="oauth-btn google">
            <svg class="google-icon" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
        </a>
        <?php endif; ?>

        <a href="<?= htmlspecialchars($oauth_url) ?>" class="oauth-btn hypechats">
            <i class="fas fa-comments"></i> Continue with HypeChats
        </a>

        <div class="divider">OR</div>

        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>

        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>
</body>
</html>