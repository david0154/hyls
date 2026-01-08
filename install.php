<?php
session_start();

// ===========================
// COMPLETE INSTALLATION WIZARD WITH AUTO-MIGRATIONS
// Includes: cover_image, 29 platforms, 6-image gallery
// Auto-fixes existing databases
// ===========================

$mode = 'install';
$existing_config = false;
$database_exists = false;
$config_data = [];
$migration_messages = [];

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
    
    try {
        $pdo = new PDO("mysql:host={$config_data['db_host']};dbname={$config_data['db_name']};charset=utf8mb4", 
                       $config_data['db_user'], $config_data['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            $database_exists = true;
            $mode = 'migrate';
        }
    } catch (Exception $e) {
        $database_exists = false;
    }
}

if (isset($_GET['mode'])) {
    if ($_GET['mode'] === 'migrate' && $existing_config) {
        $mode = 'migrate';
    } elseif ($_GET['mode'] === 'install') {
        $mode = 'install';
    }
}

$error = '';
$success = '';

// ===========================
// AUTO-MIGRATION: Adds ALL missing columns & tables
// ===========================
function run_all_migrations($pdo) {
    $migrations = [];
    
    try {
        // 1. short_links: Add ban system
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'is_banned'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER clicks");
            $migrations[] = '✅ Added is_banned to short_links';
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'ban_reason'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN ban_reason TEXT DEFAULT NULL AFTER is_banned");
            $migrations[] = '✅ Added ban_reason to short_links';
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'banned_at'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN banned_at TIMESTAMP NULL DEFAULT NULL AFTER ban_reason");
            $migrations[] = '✅ Added banned_at to short_links';
        }

        // 2. short_links: Add password & expiry
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'password'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER original_url");
            $migrations[] = '✅ Added password to short_links';
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'expires_at'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL AFTER password");
            $migrations[] = '✅ Added expires_at to short_links';
        }

        // 3. users: Add last_login
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER is_admin");
            $migrations[] = '✅ Added last_login to users';
        }

        // 4. bio_links: Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_links'");
        if ($stmt->rowCount() > 0) {
            
            // Add cover_image
            $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE 'cover_image'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE bio_links ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER profile_image");
                $migrations[] = '✅ Added cover_image to bio_links';
            }

            // Add ALL 29 social platforms
            $all_platforms = [
                'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 
                'github', 'pinterest', 'snapchat', 'discord', 'twitch', 'telegram', 
                'whatsapp', 'spotify', 'reddit', 'website', 'email', 'phone',
                'threads', 'bluesky', 'mastodon', 'medium', 'substack', 'patreon',
                'onlyfans', 'cashapp', 'venmo', 'paypal', 'line'
            ];
            
            foreach ($all_platforms as $platform) {
                // Add platform URL column
                $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE '$platform'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE bio_links ADD COLUMN $platform VARCHAR(500) DEFAULT NULL");
                    $migrations[] = "✅ Added $platform to bio_links";
                }
                
                // Add platform enabled column
                $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE '{$platform}_enabled'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE bio_links ADD COLUMN {$platform}_enabled TINYINT(1) DEFAULT 1");
                }
            }
        }

        // 5. Create bio_gallery table (6 images support)
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_gallery'");
        if ($stmt->rowCount() == 0) {
            $sql = "CREATE TABLE bio_gallery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                image_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_order (image_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            $migrations[] = '✅ Created bio_gallery table (6 images)';
        }

        // 6. Create bio_custom_links table
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_custom_links'");
        if ($stmt->rowCount() == 0) {
            $sql = "CREATE TABLE bio_custom_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(100) NOT NULL,
                url VARCHAR(500) NOT NULL,
                description TEXT NULL,
                icon VARCHAR(50) DEFAULT 'fa-link',
                clicks INT DEFAULT 0,
                link_order INT DEFAULT 0,
                is_active TINYINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_active (user_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            $migrations[] = '✅ Created bio_custom_links table';
        }

        // 7. Create directories
        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
            $migrations[] = '✅ Created uploads directory';
        }
        if (!is_dir('uploads/bio')) {
            mkdir('uploads/bio', 0755, true);
            $migrations[] = '✅ Created uploads/bio directory';
        }
        if (!is_dir('uploads/bio/gallery')) {
            mkdir('uploads/bio/gallery', 0755, true);
            $migrations[] = '✅ Created uploads/bio/gallery directory';
        }
        
    } catch (Exception $e) {
        $migrations[] = '⚠️ Migration error: ' . $e->getMessage();
    }
    
    return $migrations;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'install';
    
    if ($action === 'migrate') {
        // DATABASE MIGRATION MODE
        try {
            $pdo = new PDO("mysql:host={$config_data['db_host']};dbname={$config_data['db_name']};charset=utf8mb4", 
                           $config_data['db_user'], $config_data['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Run ALL migrations
            $migration_messages = run_all_migrations($pdo);
            
            $migration_summary = empty($migration_messages) 
                ? '<strong>All features already up to date!</strong>' 
                : '<strong>✨ Migrations Applied:</strong><br>' . implode('<br>', $migration_messages);
            
            $success = '✅ Database updated successfully!<br><br>' . $migration_summary . '<br><br>Redirecting to dashboard...';
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 4000);</script>";
            
        } catch (Exception $e) {
            $error = 'Migration failed: ' . $e->getMessage();
        }
        
    } else {
        // FULL FRESH INSTALLATION
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        $site_url = rtrim($_POST['site_url'] ?? '', '/');
        $site_name = $_POST['site_name'] ?? 'HYLS';
        $site_description = $_POST['site_description'] ?? 'Professional Link Shortener & Bio Link Tool';
        $site_keywords = $_POST['site_keywords'] ?? 'link shortener, bio link, url shortener';
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
            
            // COMPLETE DATABASE SCHEMA WITH ALL FEATURES
            $sql = "
            CREATE TABLE users (
              id INT AUTO_INCREMENT PRIMARY KEY,
              hype_id VARCHAR(100) DEFAULT NULL,
              username VARCHAR(50) NOT NULL UNIQUE,
              email VARCHAR(100) NOT NULL UNIQUE,
              password VARCHAR(255) DEFAULT NULL,
              first_name VARCHAR(50) DEFAULT NULL,
              last_name VARCHAR(50) DEFAULT NULL,
              profile_picture VARCHAR(255) DEFAULT NULL,
              access_token VARCHAR(255) DEFAULT NULL,
              earnings DECIMAL(10,2) DEFAULT 0.00,
              is_admin TINYINT(1) DEFAULT 0,
              last_login TIMESTAMP NULL DEFAULT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_username (username),
              INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE short_links (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              short_code VARCHAR(10) NOT NULL UNIQUE,
              original_url TEXT NOT NULL,
              title VARCHAR(255) DEFAULT NULL,
              password VARCHAR(255) DEFAULT NULL,
              expires_at TIMESTAMP NULL DEFAULT NULL,
              clicks INT DEFAULT 0,
              earnings DECIMAL(10,2) DEFAULT 0.00,
              is_banned TINYINT(1) DEFAULT 0,
              ban_reason TEXT DEFAULT NULL,
              banned_at TIMESTAMP NULL DEFAULT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
              INDEX idx_short_code (short_code),
              INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE bio_links (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              username VARCHAR(50) NOT NULL UNIQUE,
              display_name VARCHAR(100) DEFAULT NULL,
              bio TEXT DEFAULT NULL,
              profile_image VARCHAR(255) DEFAULT NULL,
              cover_image VARCHAR(255) DEFAULT NULL,
              theme_color VARCHAR(7) DEFAULT '#6366f1',
              views INT DEFAULT 0,
              facebook VARCHAR(500) DEFAULT NULL,
              facebook_enabled TINYINT(1) DEFAULT 1,
              instagram VARCHAR(500) DEFAULT NULL,
              instagram_enabled TINYINT(1) DEFAULT 1,
              twitter VARCHAR(500) DEFAULT NULL,
              twitter_enabled TINYINT(1) DEFAULT 1,
              threads VARCHAR(500) DEFAULT NULL,
              threads_enabled TINYINT(1) DEFAULT 1,
              tiktok VARCHAR(500) DEFAULT NULL,
              tiktok_enabled TINYINT(1) DEFAULT 1,
              youtube VARCHAR(500) DEFAULT NULL,
              youtube_enabled TINYINT(1) DEFAULT 1,
              linkedin VARCHAR(500) DEFAULT NULL,
              linkedin_enabled TINYINT(1) DEFAULT 1,
              github VARCHAR(500) DEFAULT NULL,
              github_enabled TINYINT(1) DEFAULT 1,
              discord VARCHAR(500) DEFAULT NULL,
              discord_enabled TINYINT(1) DEFAULT 1,
              twitch VARCHAR(500) DEFAULT NULL,
              twitch_enabled TINYINT(1) DEFAULT 1,
              telegram VARCHAR(500) DEFAULT NULL,
              telegram_enabled TINYINT(1) DEFAULT 1,
              whatsapp VARCHAR(500) DEFAULT NULL,
              whatsapp_enabled TINYINT(1) DEFAULT 1,
              spotify VARCHAR(500) DEFAULT NULL,
              spotify_enabled TINYINT(1) DEFAULT 1,
              reddit VARCHAR(500) DEFAULT NULL,
              reddit_enabled TINYINT(1) DEFAULT 1,
              snapchat VARCHAR(500) DEFAULT NULL,
              snapchat_enabled TINYINT(1) DEFAULT 1,
              pinterest VARCHAR(500) DEFAULT NULL,
              pinterest_enabled TINYINT(1) DEFAULT 1,
              medium VARCHAR(500) DEFAULT NULL,
              medium_enabled TINYINT(1) DEFAULT 1,
              substack VARCHAR(500) DEFAULT NULL,
              substack_enabled TINYINT(1) DEFAULT 1,
              patreon VARCHAR(500) DEFAULT NULL,
              patreon_enabled TINYINT(1) DEFAULT 1,
              onlyfans VARCHAR(500) DEFAULT NULL,
              onlyfans_enabled TINYINT(1) DEFAULT 1,
              bluesky VARCHAR(500) DEFAULT NULL,
              bluesky_enabled TINYINT(1) DEFAULT 1,
              mastodon VARCHAR(500) DEFAULT NULL,
              mastodon_enabled TINYINT(1) DEFAULT 1,
              line VARCHAR(500) DEFAULT NULL,
              line_enabled TINYINT(1) DEFAULT 1,
              cashapp VARCHAR(500) DEFAULT NULL,
              cashapp_enabled TINYINT(1) DEFAULT 1,
              venmo VARCHAR(500) DEFAULT NULL,
              venmo_enabled TINYINT(1) DEFAULT 1,
              paypal VARCHAR(500) DEFAULT NULL,
              paypal_enabled TINYINT(1) DEFAULT 1,
              website VARCHAR(500) DEFAULT NULL,
              website_enabled TINYINT(1) DEFAULT 1,
              email VARCHAR(200) DEFAULT NULL,
              email_enabled TINYINT(1) DEFAULT 1,
              phone VARCHAR(20) DEFAULT NULL,
              phone_enabled TINYINT(1) DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
              INDEX idx_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE bio_gallery (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              image_url VARCHAR(500) NOT NULL,
              image_order INT DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
              INDEX idx_user_id (user_id),
              INDEX idx_order (image_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE bio_custom_links (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              title VARCHAR(100) NOT NULL,
              url VARCHAR(500) NOT NULL,
              description TEXT NULL,
              icon VARCHAR(50) DEFAULT 'fa-link',
              clicks INT DEFAULT 0,
              link_order INT DEFAULT 0,
              is_active TINYINT DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
              INDEX idx_user_active (user_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE settings (
              id INT AUTO_INCREMENT PRIMARY KEY,
              setting_key VARCHAR(50) NOT NULL UNIQUE,
              setting_value TEXT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE analytics (
              id INT AUTO_INCREMENT PRIMARY KEY,
              link_id INT NOT NULL,
              ip_address VARCHAR(45) DEFAULT NULL,
              user_agent TEXT DEFAULT NULL,
              referrer VARCHAR(255) DEFAULT NULL,
              country VARCHAR(50) DEFAULT NULL,
              clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (link_id) REFERENCES short_links(id) ON DELETE CASCADE,
              INDEX idx_link_id (link_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE advertisements (
              id INT AUTO_INCREMENT PRIMARY KEY,
              title VARCHAR(255) NOT NULL,
              description TEXT DEFAULT NULL,
              url VARCHAR(255) NOT NULL,
              image_url VARCHAR(255) DEFAULT NULL,
              cta_text VARCHAR(50) DEFAULT 'Visit Now',
              is_active TINYINT(1) DEFAULT 1,
              position INT DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $pdo->exec($sql);
            
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 1)");
            $stmt->execute([$admin_username, $admin_email, $hashed_password]);
            
            $settings = [
                ['site_url', $site_url],
                ['site_name', $site_name],
                ['site_description', $site_description],
                ['site_keywords', $site_keywords],
            ];
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $setting) {
                $stmt->execute($setting);
            }
            
            $config_content = "<?php\ndefine('DB_HOST', " . var_export($db_host, true) . ");\ndefine('DB_NAME', " . var_export($db_name, true) . ");\ndefine('DB_USER', " . var_export($db_user, true) . ");\ndefine('DB_PASS', " . var_export($db_pass, true) . ");\ndefine('SITE_URL', " . var_export($site_url, true) . ");\ndefine('SITE_NAME', " . var_export($site_name, true) . ");\ndefine('SITE_DESCRIPTION', " . var_export($site_description, true) . ");\ndefine('SITE_KEYWORDS', " . var_export($site_keywords, true) . ");\ndefine('APP_ID', " . var_export($app_id, true) . ");\ndefine('APP_SECRET', " . var_export($app_secret, true) . ");\ndefine('GA_TRACKING_ID', " . var_export($ga_tracking_id, true) . ");\ndefine('SMTP_HOST', " . var_export($smtp_host, true) . ");\ndefine('SMTP_PORT', " . var_export($smtp_port, true) . ");\ndefine('SMTP_USER', " . var_export($smtp_user, true) . ");\ndefine('SMTP_PASS', " . var_export($smtp_pass, true) . ");\ndefine('SMTP_FROM', " . var_export($smtp_from, true) . ");\n";
            
            file_put_contents('config.php', $config_content);
            chmod('config.php', 0644);
            
            // Create upload directories
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0755, true);
            if (!is_dir('uploads/bio')) mkdir('uploads/bio', 0755, true);
            if (!is_dir('uploads/bio/gallery')) mkdir('uploads/bio/gallery', 0755, true);
            if (!is_dir('uploads/ads')) mkdir('uploads/ads', 0755, true);
            
            $success = '✅ HYLS installed successfully with all features!<br><br>✨ Includes:<br>- 29 social platforms<br>- 6-image gallery<br>- Cover images<br>- Link banning<br>- Password protection<br><br>Redirecting...';
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 3000);</script>";
            
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
    <title>HYLS - Installation & Migration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { 
            background: white;
            border-radius: 20px;
            max-width: 700px;
            width: 100%;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { 
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 36px;
            margin-bottom: 8px;
        }
        .logo p { color: #64748b; font-size: 14px; }
        .info-box { 
            background: #f0f9ff;
            border: 2px solid #bae6fd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 12px;
        }
        .info-box ul { margin-left: 20px; margin-top: 10px; }
        .info-box li { margin: 5px 0; }
        .success { 
            background: #d1fae5;
            color: #065f46;
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
        }
        .error { 
            background: #fee2e2;
            color: #991b1b;
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid #ef4444;
        }
        .form-group { margin: 20px 0; }
        label { 
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"] { 
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }
        input:focus { 
            outline: none;
            border-color: #6366f1;
        }
        button { 
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        .migrate-btn {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        small { color: #64748b; }
        .feature-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #6366f1;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🚀 HYLS</h1>
            <p>Complete Link Shortener & Bio Link Platform</p>
        </div>
        
        <?php if ($mode === 'migrate'): ?>
        <div class="info-box">
            <strong>✅ Database Detected - Migration Mode</strong><br>
            Click below to update your database with all new features:
            <div style="margin-top: 12px;">
                <span class="feature-badge">✅ Cover Image</span>
                <span class="feature-badge">✅ 29 Platforms</span>
                <span class="feature-badge">✅ 6 Images Gallery</span>
                <span class="feature-badge">✅ Link Banning</span>
                <span class="feature-badge">✅ Password Links</span>
                <span class="feature-badge">✅ Link Expiry</span>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="migrate">
            <button type="submit" class="migrate-btn">
                ✨ Update Database Now (Auto-Migration)
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #64748b;">
            <small>Safe to run multiple times - only adds missing features</small>
        </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <?php if ($mode === 'install'): ?>
        <form method="POST">
            <input type="hidden" name="action" value="install">
            
            <div class="info-box">
                <strong>🎉 Fresh Installation</strong><br>
                Complete setup with all features included!
            </div>
            
            <h3 style="color: #1e293b; margin: 30px 0 15px;">Database Configuration</h3>
            
            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="db_name" required>
            </div>
            
            <div class="form-group">
                <label>Database Username</label>
                <input type="text" name="db_user" required>
            </div>
            
            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="db_pass">
            </div>
            
            <h3 style="color: #1e293b; margin: 30px 0 15px;">Admin Account</h3>
            
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
            
            <h3 style="color: #1e293b; margin: 30px 0 15px;">Site Configuration</h3>
            
            <div class="form-group">
                <label>Site URL (without trailing slash)</label>
                <input type="text" name="site_url" value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>" required>
                <small>Example: https://yourdomain.com</small>
            </div>
            
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="HYLS" required>
            </div>
            
            <button type="submit">
                🚀 Install HYLS (Complete Setup)
            </button>
        </form>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 30px; color: #94a3b8; font-size: 13px;">
            💜 Made with love by David Studioz
        </p>
    </div>
</body>
</html>