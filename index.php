<?php
require_once 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - <?= SITE_DESCRIPTION ?></title>
    <meta name="description" content="<?= SITE_DESCRIPTION ?>">
    <meta name="keywords" content="<?= SITE_KEYWORDS ?>">
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/assets/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 800px;
        }
        h1 {
            font-size: 64px;
            margin-bottom: 24px;
        }
        h2 {
            font-size: 32px;
            margin-bottom: 16px;
        }
        p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        .buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: white;
            color: #6366f1;
        }
        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.2);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .features {
            margin-top: 80px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        .feature {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 16px;
        }
        .feature-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .feature h3 {
            font-size: 20px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 <?= SITE_NAME ?></h1>
        <h2>Shorten. Share. Earn.</h2>
        <p><?= SITE_DESCRIPTION ?></p>
        
        <div class="buttons">
            <a href="login.php" class="btn btn-primary">Get Started</a>
            <a href="https://hypechats.com" target="_blank" class="btn btn-secondary">Powered by HypeChats</a>
        </div>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">🔗</div>
                <h3>Short Links</h3>
                <p>Create short, memorable links that are easy to share</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📱</div>
                <h3>Bio Links</h3>
                <p>All your links in one beautiful page</p>
            </div>
            <div class="feature">
                <div class="feature-icon">💰</div>
                <h3>Earn Money</h3>
                <p>Get paid for every click on your links</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>Analytics</h3>
                <p>Track clicks and performance in real-time</p>
            </div>
        </div>
    </div>
</body>
</html>
