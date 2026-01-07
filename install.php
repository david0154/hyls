<?php
session_start();

// ===========================
// SMART INSTALLATION WIZARD
// Detects existing installations and offers repair without data loss
// ===========================

$mode = 'install'; // install or repair
$existing_config = false;
$database_exists = false;
$config_data = [];

// Check if config.php exists
if (file_exists('config.php')) {
    $existing_config = true;
    require_once 'config.php';
    $config_data = [
        'db_host' => defined('DB_HOST') ? DB_HOST : '',
        'db_name' => defined('DB_NAME') ? DB_NAME : '',
        'db_user' => defined('DB_USER') ? DB_USER : '',
        'db_pass' => defined('DB_PASS') ? DB_PASS : '',
        'site_url' => defined('SITE_URL') ? SITE_URL : '',
        'site_name' => defined('SITE_NAME') ? SITE_NAME : '',
        'site_description' => defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : '',
        'site_keywords' => defined('SITE_KEYWORDS') ? SITE_KEYWORDS : '',
        'app_id' => defined('APP_ID') ? APP_ID : '',
        'app_secret' => defined('APP_SECRET') ? APP_SECRET : '',
        'ga_tracking_id' => defined('GA_TRACKING_ID') ? GA_TRACKING_ID : '',
        'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : '',
        'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : '',
        'smtp_user' => defined('SMTP_USER') ? SMTP_USER : '',
        'smtp_pass' => defined('SMTP_PASS') ? SMTP_PASS : '',
        'smtp_from' => defined('SMTP_FROM') ? SMTP_FROM : '',
    ];
    
    // Check if database and tables exist
    try {
        $pdo = new PDO("mysql:host={$config_data['db_host']};dbname={$config_data['db_name']};charset=utf8mb4", 
                       $config_data['db_user'], $config_data['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            $database_exists = true;
            $mode = 'repair';
        }
    } catch (Exception $e) {
        // Database doesn't exist or can't connect
        $database_exists = false;
    }
}

// Force mode parameter
if (isset($_GET['mode'])) {
    if ($_GET['mode'] === 'repair' && $existing_config) {
        $mode = 'repair';
    } elseif ($_GET['mode'] === 'install') {
        $mode = 'install';
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'install';
    
    if ($action === 'repair') {
        // REPAIR MODE: Update config.php only, don't touch database
        try {
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';
            $site_url = rtrim($_POST['site_url'] ?? '', '/');
            $site_name = $_POST['site_name'] ?? 'HYLS';
            $site_description = $_POST['site_description'] ?? 'Professional Link Shortener and Bio Link Platform';
            $site_keywords = $_POST['site_keywords'] ?? 'link shortener, bio link, url shortener, hypechats';
            $app_id = $_POST['app_id'] ?? '';
            $app_secret = $_POST['app_secret'] ?? '';
            $ga_tracking_id = $_POST['ga_tracking_id'] ?? '';
            $smtp_host = $_POST['smtp_host'] ?? '';
            $smtp_port = $_POST['smtp_port'] ?? '587';
            $smtp_user = $_POST['smtp_user'] ?? '';
            $smtp_pass = $_POST['smtp_pass'] ?? '';
            $smtp_from = $_POST['smtp_from'] ?? '';
            
            // Test database connection
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Backup old config
            if (file_exists('config.php')) {
                copy('config.php', 'config.php.backup.' . time());
            }
            
            // Write new config
            $config_content = "<?php
define('DB_HOST', " . var_export($db_host, true) . ");
define('DB_NAME', " . var_export($db_name, true) . ");
define('DB_USER', " . var_export($db_user, true) . ");
define('DB_PASS', " . var_export($db_pass, true) . ");
define('SITE_URL', " . var_export($site_url, true) . ");
define('SITE_NAME', " . var_export($site_name, true) . ");
define('SITE_DESCRIPTION', " . var_export($site_description, true) . ");
define('SITE_KEYWORDS', " . var_export($site_keywords, true) . ");
define('APP_ID', " . var_export($app_id, true) . ");
define('APP_SECRET', " . var_export($app_secret, true) . ");
define('GA_TRACKING_ID', " . var_export($ga_tracking_id, true) . ");
define('SMTP_HOST', " . var_export($smtp_host, true) . ");
define('SMTP_PORT', " . var_export($smtp_port, true) . ");
define('SMTP_USER', " . var_export($smtp_user, true) . ");
define('SMTP_PASS', " . var_export($smtp_pass, true) . ");
define('SMTP_FROM', " . var_export($smtp_from, true) . ");
";
            
            file_put_contents('config.php', $config_content);
            chmod('config.php', 0644);
            
            $success = '✅ Configuration repaired successfully! Your data is safe. Redirecting...';
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 2000);</script>";
            
        } catch (Exception $e) {
            $error = 'Repair failed: ' . $e->getMessage();
            // Restore backup if exists
            $backup_files = glob('config.php.backup.*');
            if (!empty($backup_files)) {
                $latest_backup = end($backup_files);
                copy($latest_backup, 'config.php');
            }
        }
        
    } else {
        // INSTALL MODE: Full installation
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        $site_url = rtrim($_POST['site_url'] ?? '', '/');
        $site_name = $_POST['site_name'] ?? 'HYLS';
        $site_description = $_POST['site_description'] ?? 'Professional Link Shortener and Bio Link Platform';
        $site_keywords = $_POST['site_keywords'] ?? 'link shortener, bio link, url shortener, hypechats';
        $app_id = $_POST['app_id'] ?? '';
        $app_secret = $_POST['app_secret'] ?? '';
        $ga_tracking_id = $_POST['ga_tracking_id'] ?? '';
        $smtp_host = $_POST['smtp_host'] ?? '';
        $smtp_port = $_POST['smtp_port'] ?? '587';
        $smtp_user = $_POST['smtp_user'] ?? '';
        $smtp_pass = $_POST['smtp_pass'] ?? '';
        $smtp_from = $_POST['smtp_from'] ?? '';
        
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
              `earnings` decimal(10,2) DEFAULT 0.00,
              `is_admin` tinyint(1) DEFAULT 0,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `username` (`username`),
              UNIQUE KEY `email` (`email`),
              UNIQUE KEY `hype_id` (`hype_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `short_links` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `short_code` varchar(10) NOT NULL,
              `original_url` text NOT NULL,
              `title` varchar(255) DEFAULT NULL,
              `clicks` int(11) DEFAULT 0,
              `earnings` decimal(10,2) DEFAULT 0.00,
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
              `facebook_enabled` tinyint(1) DEFAULT 1,
              `instagram` varchar(255) DEFAULT NULL,
              `instagram_enabled` tinyint(1) DEFAULT 1,
              `twitter` varchar(255) DEFAULT NULL,
              `twitter_enabled` tinyint(1) DEFAULT 1,
              `linkedin` varchar(255) DEFAULT NULL,
              `linkedin_enabled` tinyint(1) DEFAULT 1,
              `youtube` varchar(255) DEFAULT NULL,
              `youtube_enabled` tinyint(1) DEFAULT 1,
              `tiktok` varchar(255) DEFAULT NULL,
              `tiktok_enabled` tinyint(1) DEFAULT 1,
              `github` varchar(255) DEFAULT NULL,
              `github_enabled` tinyint(1) DEFAULT 1,
              `pinterest` varchar(255) DEFAULT NULL,
              `pinterest_enabled` tinyint(1) DEFAULT 1,
              `snapchat` varchar(255) DEFAULT NULL,
              `snapchat_enabled` tinyint(1) DEFAULT 1,
              `discord` varchar(255) DEFAULT NULL,
              `discord_enabled` tinyint(1) DEFAULT 1,
              `twitch` varchar(255) DEFAULT NULL,
              `twitch_enabled` tinyint(1) DEFAULT 1,
              `telegram` varchar(255) DEFAULT NULL,
              `telegram_enabled` tinyint(1) DEFAULT 1,
              `whatsapp` varchar(255) DEFAULT NULL,
              `whatsapp_enabled` tinyint(1) DEFAULT 1,
              `spotify` varchar(255) DEFAULT NULL,
              `spotify_enabled` tinyint(1) DEFAULT 1,
              `reddit` varchar(255) DEFAULT NULL,
              `reddit_enabled` tinyint(1) DEFAULT 1,
              `website` varchar(255) DEFAULT NULL,
              `website_enabled` tinyint(1) DEFAULT 1,
              `email` varchar(100) DEFAULT NULL,
              `email_enabled` tinyint(1) DEFAULT 1,
              `phone` varchar(20) DEFAULT NULL,
              `phone_enabled` tinyint(1) DEFAULT 1,
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

            CREATE TABLE IF NOT EXISTS `advertisements` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `description` text DEFAULT NULL,
              `url` varchar(255) NOT NULL,
              `image_url` varchar(255) DEFAULT NULL,
              `cta_text` varchar(50) DEFAULT 'Visit Now',
              `is_active` tinyint(1) DEFAULT 1,
              `position` int(11) DEFAULT 0,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $pdo->exec($sql);
            
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
            $stmt->execute([$admin_username, $admin_email, $hashed_password]);
            
            $settings = [
                ['site_url', $site_url],
                ['site_name', $site_name],
                ['site_description', $site_description],
                ['site_keywords', $site_keywords],
                ['app_id', $app_id],
                ['app_secret', $app_secret],
                ['ga_tracking_id', $ga_tracking_id],
                ['smtp_host', $smtp_host],
                ['smtp_port', $smtp_port],
                ['smtp_user', $smtp_user],
                ['smtp_pass', $smtp_pass],
                ['smtp_from', $smtp_from],
                ['theme_color', '#6366f1'],
                ['ads_enabled', '1'],
                ['ads_duration', '5'],
                ['earning_per_click', '0.001'],
                ['min_payout', '10.00']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $setting) {
                $stmt->execute($setting);
            }
            
            // Insert default HypeChats advertisement
            $stmt = $pdo->prepare("INSERT INTO advertisements (title, description, url, cta_text, position) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                'Visit HypeChats',
                'The Best Social Platform for Creators. Connect with millions of users, share your content, and grow your audience.',
                'https://hypechats.com',
                'Visit HypeChats Now',
                1
            ]);
            
            $config_content = "<?php
define('DB_HOST', " . var_export($db_host, true) . ");
define('DB_NAME', " . var_export($db_name, true) . ");
define('DB_USER', " . var_export($db_user, true) . ");
define('DB_PASS', " . var_export($db_pass, true) . ");
define('SITE_URL', " . var_export($site_url, true) . ");
define('SITE_NAME', " . var_export($site_name, true) . ");
define('SITE_DESCRIPTION', " . var_export($site_description, true) . ");
define('SITE_KEYWORDS', " . var_export($site_keywords, true) . ");
define('APP_ID', " . var_export($app_id, true) . ");
define('APP_SECRET', " . var_export($app_secret, true) . ");
define('GA_TRACKING_ID', " . var_export($ga_tracking_id, true) . ");
define('SMTP_HOST', " . var_export($smtp_host, true) . ");
define('SMTP_PORT', " . var_export($smtp_port, true) . ");
define('SMTP_USER', " . var_export($smtp_user, true) . ");
define('SMTP_PASS', " . var_export($smtp_pass, true) . ");
define('SMTP_FROM', " . var_export($smtp_from, true) . ");
";
            
            file_put_contents('config.php', $config_content);
            chmod('config.php', 0644);
            
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0755, true);
            if (!is_dir('uploads/bio')) mkdir('uploads/bio', 0755, true);
            if (!is_dir('uploads/ads')) mkdir('uploads/ads', 0755, true);
            
            // Create .htaccess for uploads
            $htaccess_uploads = "Options -Indexes\n<FilesMatch \"\\.(jpg|jpeg|png|gif|ico)$\">\n    Order allow,deny\n    Allow from all\n</FilesMatch>";
            file_put_contents('uploads/.htaccess', $htaccess_uploads);
            
            $success = '✅ Installation completed successfully! Redirecting to login...';
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 2000);</script>";
            
        } catch (Exception $e) {
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYLS <?= $mode === 'repair' ? 'Repair' : 'Installation' ?></title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            overflow-y: auto;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            padding: 40px;
            margin: 20px auto;
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
        .mode-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .mode-badge.repair {
            background: #dbeafe;
            color: #1e40af;
        }
        .mode-badge.install {
            background: #d1fae5;
            color: #065f46;
        }
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .info-box.warning {
            background: #fef3c7;
            border-color: #fde68a;
        }
        .info-box h3 {
            color: #0c4a6e;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .info-box.warning h3 {
            color: #78350f;
        }
        .info-box p {
            color: #075985;
            font-size: 13px;
            line-height: 1.6;
        }
        .info-box.warning p {
            color: #92400e;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            color: #075985;
            font-size: 13px;
            margin: 5px 0;
        }
        .info-box.warning li {
            color: #92400e;
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
        input[type="password"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        input:focus, textarea:focus {
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
        .section-title:first-of-type {
            margin-top: 0;
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
            margin-top: 10px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        button.secondary {
            background: #e2e8f0;
            color: #475569;
        }
        button.secondary:hover {
            background: #cbd5e1;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .mode-switch {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .mode-switch a {
            color: #6366f1;
            text-decoration: none;
            font-size: 14px;
        }
        .mode-switch a:hover {
            text-decoration: underline;
        }
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔗 HYLS</h1>
            <p>HypeLink Shortener - <?= $mode === 'repair' ? 'Repair Wizard' : 'Installation Wizard' ?></p>
            <span class="mode-badge <?= $mode ?>">
                <?= $mode === 'repair' ? '🔧 REPAIR MODE' : '🚀 NEW INSTALLATION' ?>
            </span>
        </div>

        <?php if ($mode === 'repair' && $database_exists): ?>
            <div class="info-box">
                <h3>✅ Existing Installation Detected</h3>
                <p>We found an existing HYLS installation with data. This repair mode will:</p>
                <ul>
                    <li>✅ Update your config.php file</li>
                    <li>✅ Keep all your existing data (users, links, settings)</li>
                    <li>✅ Test database connectivity</li>
                    <li>✅ Create automatic backup of current config</li>
                    <li>❌ NOT delete or modify your database</li>
                </ul>
                <p><strong>Your data is safe!</strong></p>
            </div>
        <?php elseif ($mode === 'repair' && $existing_config && !$database_exists): ?>
            <div class="info-box warning">
                <h3>⚠️ Configuration Found, Database Missing</h3>
                <p>We found a config.php file, but couldn't connect to the database. This could mean:</p>
                <ul>
                    <li>Database credentials are incorrect</li>
                    <li>Database server is not running</li>
                    <li>Database was deleted</li>
                </ul>
                <p>You can repair the configuration below, or switch to full installation mode.</p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="<?= $mode === 'repair' ? 'repair' : 'install' ?>">
            
            <div class="section-title">📊 Database Configuration</div>
            
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="<?= htmlspecialchars($config_data['db_host'] ?? 'localhost') ?>" required>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" value="<?= htmlspecialchars($config_data['db_name'] ?? '') ?>" required>
                <?php if ($mode === 'install'): ?>
                    <div class="help-text">Database will be created if it doesn't exist</div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($config_data['db_user'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" value="<?= htmlspecialchars($config_data['db_pass'] ?? '') ?>">
                </div>
            </div>

            <?php if ($mode === 'install'): ?>
            <div class="section-title">👤 Admin Account</div>

            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="admin_username" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" required>
                </div>

                <div class="form-group">
                    <label>Admin Password</label>
                    <input type="password" name="admin_password" required>
                </div>
            </div>
            <?php endif; ?>

            <div class="section-title">⚙️ Site Configuration</div>

            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="<?= htmlspecialchars($config_data['site_name'] ?? 'HYLS') ?>" required>
            </div>

            <div class="form-group">
                <label>Site URL</label>
                <input type="text" name="site_url" value="<?= htmlspecialchars($config_data['site_url'] ?? '') ?>" placeholder="https://yourdomain.com" required>
                <div class="help-text">Your website URL (without trailing slash)</div>
            </div>

            <div class="form-group">
                <label>Site Description</label>
                <textarea name="site_description" placeholder="Professional Link Shortener and Bio Link Platform"><?= htmlspecialchars($config_data['site_description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Site Keywords</label>
                <input type="text" name="site_keywords" value="<?= htmlspecialchars($config_data['site_keywords'] ?? '') ?>" placeholder="link shortener, bio link, url shortener">
            </div>

            <div class="section-title">🔐 HypeChats OAuth</div>

            <div class="form-row">
                <div class="form-group">
                    <label>App ID</label>
                    <input type="text" name="app_id" value="<?= htmlspecialchars($config_data['app_id'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>App Secret</label>
                    <input type="password" name="app_secret" value="<?= htmlspecialchars($config_data['app_secret'] ?? '') ?>" required>
                </div>
            </div>

            <div class="section-title">📧 SMTP Configuration (Optional)</div>

            <div class="form-row">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?= htmlspecialchars($config_data['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                </div>

                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?= htmlspecialchars($config_data['smtp_port'] ?? '587') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>SMTP Username</label>
                <input type="text" name="smtp_user" value="<?= htmlspecialchars($config_data['smtp_user'] ?? '') ?>" placeholder="your-email@gmail.com">
            </div>

            <div class="form-group">
                <label>SMTP Password</label>
                <input type="password" name="smtp_pass" value="<?= htmlspecialchars($config_data['smtp_pass'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>From Email Address</label>
                <input type="email" name="smtp_from" value="<?= htmlspecialchars($config_data['smtp_from'] ?? '') ?>" placeholder="noreply@yourdomain.com">
            </div>

            <div class="section-title">📈 Google Analytics (Optional)</div>

            <div class="form-group">
                <label>Tracking ID</label>
                <input type="text" name="ga_tracking_id" value="<?= htmlspecialchars($config_data['ga_tracking_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                <div class="help-text">Leave empty to skip Google Analytics</div>
            </div>

            <button type="submit">
                <?= $mode === 'repair' ? '🔧 Repair Configuration' : '🚀 Install HYLS' ?>
            </button>
        </form>

        <?php if ($existing_config): ?>
        <div class="mode-switch">
            <?php if ($mode === 'repair'): ?>
                <a href="?mode=install">⚠️ Switch to Full Installation (will reset database)</a>
            <?php else: ?>
                <a href="?mode=repair">🔧 Switch to Repair Mode (keep existing data)</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
