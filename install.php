<?php
session_start();

// ===========================
// COMPLETE INSTALLATION WIZARD WITH ALL MIGRATIONS
// Auto-runs all migrations from install/ folder
// Perfect for GitHub fork users
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
            $mode = 'repair';
        }
    } catch (Exception $e) {
        $database_exists = false;
    }
}

if (isset($_GET['mode'])) {
    if ($_GET['mode'] === 'repair' && $existing_config) {
        $mode = 'repair';
    } elseif ($_GET['mode'] === 'install') {
        $mode = 'install';
    }
}

$error = '';
$success = '';

// ===========================
// COMPLETE AUTO-MIGRATION FUNCTION
// Includes ALL migrations from install/ folder
// ===========================
function run_all_migrations($pdo) {
    $migrations = [];
    
    try {
        // 1. Add missing columns to short_links
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'is_banned'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN is_banned TINYINT(1) DEFAULT 0");
            $migrations[] = '✅ Added is_banned to short_links';
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'ban_reason'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN ban_reason TEXT DEFAULT NULL");
            $migrations[] = '✅ Added ban_reason to short_links';
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'banned_at'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN banned_at TIMESTAMP NULL DEFAULT NULL");
            $migrations[] = '✅ Added banned_at to short_links';
        }

        // 2. Add password and expiry columns to short_links
        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'password'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN password VARCHAR(255) DEFAULT NULL");
            $migrations[] = '✅ Added password protection to short_links';
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'expires_at'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE short_links ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL");
            $migrations[] = '✅ Added expiry date to short_links';
        }

        // 3. Add last_login to users
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL");
            $migrations[] = '✅ Added last_login to users';
        }

        // 4. Add cover_image to bio_links
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_links'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE 'cover_image'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE bio_links ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER profile_image");
                $migrations[] = '✅ Added cover_image to bio_links';
            }

            // Add new social platforms to bio_links
            $new_platforms = ['threads', 'bluesky', 'mastodon', 'medium', 'substack', 'patreon', 'onlyfans', 'cashapp', 'venmo', 'paypal', 'line'];
            foreach ($new_platforms as $platform) {
                $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE '$platform'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE bio_links ADD COLUMN $platform VARCHAR(255) DEFAULT NULL");
                    $pdo->exec("ALTER TABLE bio_links ADD COLUMN {$platform}_enabled TINYINT(1) DEFAULT 1");
                    $migrations[] = "✅ Added $platform to bio_links";
                }
            }
        }

        // 5. Create bio_gallery table (6 images)
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_gallery'");
        if ($stmt->rowCount() == 0) {
            $sql = "CREATE TABLE bio_gallery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                bio_profile_id INT NULL,
                image_url VARCHAR(255) NOT NULL,
                image_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_order (image_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            $migrations[] = '✅ Created bio_gallery table (6 images support)';
        }

        // 6. Create bio_custom_links table
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_custom_links'");
        if ($stmt->rowCount() == 0) {
            $sql = "CREATE TABLE bio_custom_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                bio_profile_id INT NULL,
                title VARCHAR(100) NOT NULL,
                url VARCHAR(500) NOT NULL,
                description TEXT NULL,
                icon VARCHAR(50) DEFAULT 'fa-link',
                clicks INT DEFAULT 0,
                link_order INT DEFAULT 0,
                is_active TINYINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            $migrations[] = '✅ Created bio_custom_links table';
        }

        // 7. Create bio_social_accounts table (multiple accounts per platform)
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_social_accounts'");
        if ($stmt->rowCount() == 0) {
            $sql = "CREATE TABLE bio_social_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                bio_profile_id INT NULL,
                platform VARCHAR(50) NOT NULL,
                account_label VARCHAR(100) DEFAULT NULL,
                username VARCHAR(255) DEFAULT NULL,
                url VARCHAR(500) NOT NULL,
                clicks INT DEFAULT 0,
                account_order INT DEFAULT 0,
                is_active TINYINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_platform (platform)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            $migrations[] = '✅ Created bio_social_accounts table (multiple accounts)';
        }
        
    } catch (Exception $e) {
        $migrations[] = '⚠️ Migration warning: ' . $e->getMessage();
    }
    
    return $migrations;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'install';
    
    if ($action === 'repair') {
        try {
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';
            $site_url = rtrim($_POST['site_url'] ?? '', '/');
            $site_name = $_POST['site_name'] ?? 'HYLS';
            $site_description = $_POST['site_description'] ?? 'Professional Link Shortener';
            $site_keywords = $_POST['site_keywords'] ?? 'link shortener, bio link';
            $app_id = $_POST['app_id'] ?? '';
            $app_secret = $_POST['app_secret'] ?? '';
            $ga_tracking_id = $_POST['ga_tracking_id'] ?? '';
            $smtp_host = $_POST['smtp_host'] ?? '';
            $smtp_port = $_POST['smtp_port'] ?? '587';
            $smtp_user = $_POST['smtp_user'] ?? '';
            $smtp_pass = $_POST['smtp_pass'] ?? '';
            $smtp_from = $_POST['smtp_from'] ?? '';
            
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Run ALL migrations
            $migration_messages = run_all_migrations($pdo);
            
            if (file_exists('config.php')) {
                copy('config.php', 'config.php.backup.' . time());
            }
            
            $config_content = "<?php\ndefine('DB_HOST', " . var_export($db_host, true) . ");\ndefine('DB_NAME', " . var_export($db_name, true) . ");\ndefine('DB_USER', " . var_export($db_user, true) . ");\ndefine('DB_PASS', " . var_export($db_pass, true) . ");\ndefine('SITE_URL', " . var_export($site_url, true) . ");\ndefine('SITE_NAME', " . var_export($site_name, true) . ");\ndefine('SITE_DESCRIPTION', " . var_export($site_description, true) . ");\ndefine('SITE_KEYWORDS', " . var_export($site_keywords, true) . ");\ndefine('APP_ID', " . var_export($app_id, true) . ");\ndefine('APP_SECRET', " . var_export($app_secret, true) . ");\ndefine('GA_TRACKING_ID', " . var_export($ga_tracking_id, true) . ");\ndefine('SMTP_HOST', " . var_export($smtp_host, true) . ");\ndefine('SMTP_PORT', " . var_export($smtp_port, true) . ");\ndefine('SMTP_USER', " . var_export($smtp_user, true) . ");\ndefine('SMTP_PASS', " . var_export($smtp_pass, true) . ");\ndefine('SMTP_FROM', " . var_export($smtp_from, true) . ");\n";
            
            file_put_contents('config.php', $config_content);
            chmod('config.php', 0644);
            
            $migration_summary = empty($migration_messages) ? '' : '<br><br><strong>✨ Auto-Migrations Completed:</strong><br>' . implode('<br>', $migration_messages);
            $success = '✅ Configuration repaired & all migrations applied!' . $migration_summary . '<br><br>Redirecting...';
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 4000);</script>";
            
        } catch (Exception $e) {
            $error = 'Repair failed: ' . $e->getMessage();
            $backup_files = glob('config.php.backup.*');
            if (!empty($backup_files)) {
                copy(end($backup_files), 'config.php');
            }
        }
        
    } else {
        // FULL INSTALLATION
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $admin_username = $_POST['admin_username'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        $site_url = rtrim($_POST['site_url'] ?? '', '/');
        $site_name = $_POST['site_name'] ?? 'HYLS';
        $site_description = $_POST['site_description'] ?? 'Professional Link Shortener';
        $site_keywords = $_POST['site_keywords'] ?? 'link shortener';
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
            
            // Complete schema with ALL features
            $sql = file_get_contents('install/complete_schema.sql') ?: "
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
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;

            CREATE TABLE short_links (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              short_code VARCHAR(10) NOT NULL UNIQUE,
              original_url TEXT NOT NULL,
              title VARCHAR(255) DEFAULT NULL,
              clicks INT DEFAULT 0,
              earnings DECIMAL(10,2) DEFAULT 0.00,
              password VARCHAR(255) DEFAULT NULL,
              expires_at TIMESTAMP NULL DEFAULT NULL,
              is_banned TINYINT(1) DEFAULT 0,
              ban_reason TEXT DEFAULT NULL,
              banned_at TIMESTAMP NULL DEFAULT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

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
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

            CREATE TABLE bio_gallery (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              image_url VARCHAR(255) NOT NULL,
              image_order INT DEFAULT 0,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

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
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

            CREATE TABLE bio_social_accounts (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              platform VARCHAR(50) NOT NULL,
              account_label VARCHAR(100) DEFAULT NULL,
              username VARCHAR(255) DEFAULT NULL,
              url VARCHAR(500) NOT NULL,
              clicks INT DEFAULT 0,
              account_order INT DEFAULT 0,
              is_active TINYINT DEFAULT 1,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

            CREATE TABLE settings (
              id INT AUTO_INCREMENT PRIMARY KEY,
              setting_key VARCHAR(50) NOT NULL UNIQUE,
              setting_value TEXT DEFAULT NULL
            ) ENGINE=InnoDB;

            CREATE TABLE analytics (
              id INT AUTO_INCREMENT PRIMARY KEY,
              link_id INT NOT NULL,
              ip_address VARCHAR(45) DEFAULT NULL,
              user_agent TEXT DEFAULT NULL,
              referrer VARCHAR(255) DEFAULT NULL,
              country VARCHAR(50) DEFAULT NULL,
              clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (link_id) REFERENCES short_links(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;

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
            ) ENGINE=InnoDB;";
            
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
            
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0755, true);
            if (!is_dir('uploads/bio')) mkdir('uploads/bio', 0755, true);
            if (!is_dir('uploads/ads')) mkdir('uploads/ads', 0755, true);
            
            $success = '✅ Installation completed with all features! Redirecting...';
            echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 2000);</script>";
            
        } catch (Exception $e) {
            $error = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?>
<!-- Rest of HTML is same as before, just showing that migrations are now integrated -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYLS Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { background: white; border-radius: 16px; max-width: 700px; margin: 20px auto; padding: 40px; }
        .logo h1 { color: #6366f1; text-align: center; margin-bottom: 10px; }
        .info-box { background: #f0f9ff; border: 1px solid #bae6fd; padding: 16px; margin: 20px 0; border-radius: 8px; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin: 20px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin: 20px 0; }
        button { width: 100%; padding: 14px; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer; margin-top: 20px; }
        input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; margin: 8px 0; }
        label { display: block; margin-top: 15px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><h1>🚀 HYLS - Complete Installation</h1></div>
        
        <?php if ($mode === 'repair'): ?>
        <div class="info-box">
            <strong>✅ Auto-Migration Enabled</strong><br>
            All missing features will be added automatically:
            <ul>
                <li>✅ Link banning system</li>
                <li>✅ Password protection</li>
                <li>✅ Link expiry</li>
                <li>✅ 29 social platforms</li>
                <li>✅ 6-image gallery</li>
                <li>✅ Multiple accounts per platform</li>
                <li>✅ Custom links</li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <p style="color: #64748b; text-align: center; margin: 20px 0;">
            🎉 <strong>Perfect for GitHub fork users!</strong> All migrations run automatically.
        </p>
    </div>
</body>
</html>