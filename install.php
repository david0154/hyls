<?php
session_start();

if (file_exists('config.php')) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $site_url = $_POST['site_url'] ?? '';
    $app_id = $_POST['app_id'] ?? '';
    $app_secret = $_POST['app_secret'] ?? '';
    $ga_tracking_id = $_POST['ga_tracking_id'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `hype_id` varchar(100) DEFAULT NULL,
          `username` varchar(50) NOT NULL,
          `email` varchar(100) NOT NULL,
          `password` varchar(255) DEFAULT NULL,
          `first_name` varchar(50) DEFAULT NULL,
          `last_name` varchar(50) DEFAULT NULL,
          `profile_picture` varchar(255) DEFAULT NULL,
          `access_token` varchar(255) DEFAULT NULL,
          `is_admin` tinyint(1) DEFAULT 0,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `short_links` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `short_code` varchar(10) NOT NULL,
          `original_url` text NOT NULL,
          `title` varchar(255) DEFAULT NULL,
          `clicks` int(11) DEFAULT 0,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `short_code` (`short_code`),
          KEY `user_id` (`user_id`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `bio_links` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `username` varchar(50) NOT NULL,
          `display_name` varchar(100) DEFAULT NULL,
          `bio` text DEFAULT NULL,
          `profile_image` varchar(255) DEFAULT NULL,
          `theme_color` varchar(7) DEFAULT '#6366f1',
          `facebook` varchar(255) DEFAULT NULL,
          `instagram` varchar(255) DEFAULT NULL,
          `twitter` varchar(255) DEFAULT NULL,
          `linkedin` varchar(255) DEFAULT NULL,
          `youtube` varchar(255) DEFAULT NULL,
          `tiktok` varchar(255) DEFAULT NULL,
          `github` varchar(255) DEFAULT NULL,
          `website` varchar(255) DEFAULT NULL,
          `email` varchar(100) DEFAULT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `views` int(11) DEFAULT 0,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          KEY `user_id` (`user_id`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `setting_key` varchar(50) NOT NULL,
          `setting_value` text DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `analytics` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `link_id` int(11) NOT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text DEFAULT NULL,
          `referrer` varchar(255) DEFAULT NULL,
          `country` varchar(50) DEFAULT NULL,
          `clicked_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `link_id` (`link_id`),
          FOREIGN KEY (`link_id`) REFERENCES `short_links`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($sql);
        
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->execute([$admin_username, $admin_email, $hashed_password]);
        
        $settings = [
            ['site_url', $site_url],
            ['app_id', $app_id],
            ['app_secret', $app_secret],
            ['ga_tracking_id', $ga_tracking_id],
            ['site_name', 'HYLS - HypeLink Shortener'],
            ['theme_color', '#6366f1'],
            ['ads_enabled', '1'],
            ['ads_duration', '5']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        
        $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('SITE_URL', '$site_url');
define('APP_ID', '$app_id');
define('APP_SECRET', '$app_secret');
define('GA_TRACKING_ID', '$ga_tracking_id');
";
        
        file_put_contents('config.php', $config_content);
        
        if (!is_dir('uploads')) mkdir('uploads', 0755, true);
        if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0755, true);
        
        $success = 'Installation completed successfully! Redirecting to login...';
        echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 2000);</script>";
        
    } catch (Exception $e) {
        $error = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYLS Installation</title>
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
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #6366f1;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .logo p {
            color: #64748b;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #334155;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .section-title {
            color: #6366f1;
            font-size: 18px;
            font-weight: 700;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .help-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔗 HYLS</h1>
            <p>HypeLink Shortener - Installation Wizard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="section-title">📊 Database Configuration</div>
            
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" required>
                <div class="help-text">Database will be created if it doesn't exist</div>
            </div>

            <div class="form-group">
                <label>Database Username</label>
                <input type="text" name="db_user" required>
            </div>

            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass">
            </div>

            <div class="section-title">👤 Admin Account</div>

            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="admin_username" required>
            </div>

            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="admin_email" required>
            </div>

            <div class="form-group">
                <label>Admin Password</label>
                <input type="password" name="admin_password" required>
            </div>

            <div class="section-title">⚙️ Site Configuration</div>

            <div class="form-group">
                <label>Site URL</label>
                <input type="text" name="site_url" placeholder="https://yourdomain.com" required>
                <div class="help-text">Your website URL (without trailing slash)</div>
            </div>

            <div class="section-title">🔐 HypeChats OAuth</div>

            <div class="form-group">
                <label>App ID</label>
                <input type="text" name="app_id" required>
            </div>

            <div class="form-group">
                <label>App Secret</label>
                <input type="password" name="app_secret" required>
            </div>

            <div class="section-title">📈 Google Analytics (Optional)</div>

            <div class="form-group">
                <label>Tracking ID</label>
                <input type="text" name="ga_tracking_id" placeholder="G-XXXXXXXXXX">
                <div class="help-text">Leave empty to skip Google Analytics</div>
            </div>

            <button type="submit">🚀 Install HYLS</button>
        </form>
    </div>
</body>
</html>
