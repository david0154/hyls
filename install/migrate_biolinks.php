<?php
/**
 * Bio Links Migration Script
 * Adds bio links tables to existing HYLS installations
 */

define('MIGRATION_RUNNING', true);

// Load database connection
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/db.php';
    
    try {
        $db = new Database();
        
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Bio Links Migration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #0f172a; color: #e2e8f0; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 12px; }
        h1 { color: #22c55e; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        .log { background: #0f172a; padding: 15px; border-radius: 8px; margin: 10px 0; font-family: monospace; font-size: 13px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔗 Bio Links Migration</h1>
        <p>Adding bio links feature to your HYLS installation...</p>
        <div class='log'>";
        
        $migrations_run = [];
        $migrations_failed = [];
        
        // Migration 1: Create bio_profiles table
        try {
            $sql = "CREATE TABLE IF NOT EXISTS bio_profiles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                username VARCHAR(50) UNIQUE NOT NULL,
                display_name VARCHAR(100),
                bio TEXT,
                profile_picture TEXT,
                cover_image TEXT,
                theme VARCHAR(50) DEFAULT 'default',
                custom_css TEXT,
                is_active TINYINT(1) DEFAULT 1,
                views INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_username (username),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->query($sql);
            echo "<span class='success'>✅ Created bio_profiles table</span><br>";
            $migrations_run[] = 'bio_profiles table';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to create bio_profiles: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'bio_profiles: ' . $e->getMessage();
        }
        
        // Migration 2: Create bio_social_links table
        try {
            $sql = "CREATE TABLE IF NOT EXISTS bio_social_links (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bio_profile_id INT NOT NULL,
                platform VARCHAR(50) NOT NULL,
                label VARCHAR(100),
                username VARCHAR(255),
                url TEXT NOT NULL,
                icon VARCHAR(100),
                display_order INT DEFAULT 0,
                is_visible TINYINT(1) DEFAULT 1,
                clicks INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bio_profile_id) REFERENCES bio_profiles(id) ON DELETE CASCADE,
                INDEX idx_profile_platform (bio_profile_id, platform),
                INDEX idx_display_order (display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->query($sql);
            echo "<span class='success'>✅ Created bio_social_links table</span><br>";
            $migrations_run[] = 'bio_social_links table';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to create bio_social_links: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'bio_social_links: ' . $e->getMessage();
        }
        
        // Migration 3: Create bio_custom_links table
        try {
            $sql = "CREATE TABLE IF NOT EXISTS bio_custom_links (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bio_profile_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                url TEXT NOT NULL,
                description TEXT,
                thumbnail TEXT,
                icon VARCHAR(100),
                display_order INT DEFAULT 0,
                is_visible TINYINT(1) DEFAULT 1,
                clicks INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bio_profile_id) REFERENCES bio_profiles(id) ON DELETE CASCADE,
                INDEX idx_profile_order (bio_profile_id, display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->query($sql);
            echo "<span class='success'>✅ Created bio_custom_links table</span><br>";
            $migrations_run[] = 'bio_custom_links table';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to create bio_custom_links: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'bio_custom_links: ' . $e->getMessage();
        }
        
        // Migration 4: Create bio_gallery table
        try {
            $sql = "CREATE TABLE IF NOT EXISTS bio_gallery (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bio_profile_id INT NOT NULL,
                image_url TEXT NOT NULL,
                title VARCHAR(255),
                description TEXT,
                link_url TEXT,
                display_order INT DEFAULT 0,
                is_visible TINYINT(1) DEFAULT 1,
                clicks INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bio_profile_id) REFERENCES bio_profiles(id) ON DELETE CASCADE,
                INDEX idx_profile_gallery (bio_profile_id, display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->query($sql);
            echo "<span class='success'>✅ Created bio_gallery table</span><br>";
            $migrations_run[] = 'bio_gallery table';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to create bio_gallery: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'bio_gallery: ' . $e->getMessage();
        }
        
        // Migration 5: Create bio_analytics table
        try {
            $sql = "CREATE TABLE IF NOT EXISTS bio_analytics (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bio_profile_id INT NOT NULL,
                link_type ENUM('social', 'custom', 'gallery', 'profile') NOT NULL,
                link_id INT,
                visitor_ip VARCHAR(45),
                user_agent TEXT,
                referrer TEXT,
                country VARCHAR(2),
                city VARCHAR(100),
                device_type VARCHAR(50),
                browser VARCHAR(50),
                clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (bio_profile_id) REFERENCES bio_profiles(id) ON DELETE CASCADE,
                INDEX idx_profile_analytics (bio_profile_id, clicked_at),
                INDEX idx_link_type (link_type, link_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->query($sql);
            echo "<span class='success'>✅ Created bio_analytics table</span><br>";
            $migrations_run[] = 'bio_analytics table';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to create bio_analytics: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'bio_analytics: ' . $e->getMessage();
        }
        
        // Migration 6: Create bio_platforms table
        try {
            $sql = "CREATE TABLE IF NOT EXISTS bio_platforms (
                id INT PRIMARY KEY AUTO_INCREMENT,
                platform_key VARCHAR(50) UNIQUE NOT NULL,
                platform_name VARCHAR(100) NOT NULL,
                icon_class VARCHAR(100),
                base_url TEXT,
                color_hex VARCHAR(7),
                is_active TINYINT(1) DEFAULT 1,
                display_order INT DEFAULT 0,
                INDEX idx_platform_key (platform_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $db->query($sql);
            echo "<span class='success'>✅ Created bio_platforms table</span><br>";
            $migrations_run[] = 'bio_platforms table';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to create bio_platforms: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'bio_platforms: ' . $e->getMessage();
        }
        
        // Migration 7: Insert social media platforms
        try {
            $platforms = [
                ['instagram', 'Instagram', 'fab fa-instagram', 'https://instagram.com/', '#E4405F', 1],
                ['facebook', 'Facebook', 'fab fa-facebook', 'https://facebook.com/', '#1877F2', 2],
                ['twitter', 'Twitter/X', 'fab fa-twitter', 'https://twitter.com/', '#1DA1F2', 3],
                ['tiktok', 'TikTok', 'fab fa-tiktok', 'https://tiktok.com/@', '#000000', 4],
                ['youtube', 'YouTube', 'fab fa-youtube', 'https://youtube.com/', '#FF0000', 5],
                ['linkedin', 'LinkedIn', 'fab fa-linkedin', 'https://linkedin.com/in/', '#0A66C2', 6],
                ['github', 'GitHub', 'fab fa-github', 'https://github.com/', '#181717', 7],
                ['twitch', 'Twitch', 'fab fa-twitch', 'https://twitch.tv/', '#9146FF', 8],
                ['discord', 'Discord', 'fab fa-discord', 'https://discord.gg/', '#5865F2', 9],
                ['reddit', 'Reddit', 'fab fa-reddit', 'https://reddit.com/u/', '#FF4500', 10],
                ['snapchat', 'Snapchat', 'fab fa-snapchat', 'https://snapchat.com/add/', '#FFFC00', 11],
                ['telegram', 'Telegram', 'fab fa-telegram', 'https://t.me/', '#26A5E4', 12],
                ['whatsapp', 'WhatsApp', 'fab fa-whatsapp', 'https://wa.me/', '#25D366', 13],
                ['line', 'LINE', 'fab fa-line', 'https://line.me/ti/p/', '#00B900', 14],
                ['wechat', 'WeChat', 'fab fa-weixin', '', '#09B83E', 15],
                ['pinterest', 'Pinterest', 'fab fa-pinterest', 'https://pinterest.com/', '#E60023', 16],
                ['spotify', 'Spotify', 'fab fa-spotify', 'https://open.spotify.com/user/', '#1DB954', 17],
                ['soundcloud', 'SoundCloud', 'fab fa-soundcloud', 'https://soundcloud.com/', '#FF3300', 18],
                ['medium', 'Medium', 'fab fa-medium', 'https://medium.com/@', '#000000', 19],
                ['behance', 'Behance', 'fab fa-behance', 'https://behance.net/', '#1769FF', 20],
                ['dribbble', 'Dribbble', 'fab fa-dribbble', 'https://dribbble.com/', '#EA4C89', 21],
                ['patreon', 'Patreon', 'fab fa-patreon', 'https://patreon.com/', '#FF424D', 22],
                ['onlyfans', 'OnlyFans', 'fas fa-fire', 'https://onlyfans.com/', '#00AFF0', 23],
                ['cashapp', 'Cash App', 'fas fa-dollar-sign', 'https://cash.app/', '#00C244', 24],
                ['venmo', 'Venmo', 'fab fa-cc-venmo', 'https://venmo.com/', '#3D95CE', 25],
                ['paypal', 'PayPal', 'fab fa-paypal', 'https://paypal.me/', '#00457C', 26],
                ['email', 'Email', 'fas fa-envelope', 'mailto:', '#EA4335', 27],
                ['website', 'Website', 'fas fa-globe', '', '#0078D4', 28],
                ['custom', 'Custom Link', 'fas fa-link', '', '#6366F1', 99]
            ];
            
            $stmt = $db->prepare("
                INSERT INTO bio_platforms (platform_key, platform_name, icon_class, base_url, color_hex, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    platform_name=VALUES(platform_name),
                    icon_class=VALUES(icon_class),
                    base_url=VALUES(base_url),
                    color_hex=VALUES(color_hex)
            ");
            
            foreach ($platforms as $platform) {
                $stmt->execute($platform);
            }
            
            echo "<span class='success'>✅ Inserted " . count($platforms) . " social media platforms</span><br>";
            $migrations_run[] = 'social media platforms';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to insert platforms: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'platforms data: ' . $e->getMessage();
        }
        
        // Create uploads directory for bio link images
        try {
            $upload_dirs = [
                __DIR__ . '/../uploads/bio',
                __DIR__ . '/../uploads/bio/profiles',
                __DIR__ . '/../uploads/bio/covers',
                __DIR__ . '/../uploads/bio/gallery'
            ];
            
            foreach ($upload_dirs as $dir) {
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
            
            echo "<span class='success'>✅ Created upload directories</span><br>";
            $migrations_run[] = 'upload directories';
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed to create directories: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'directories: ' . $e->getMessage();
        }
        
        echo "</div>";
        echo "<h2>Migration Summary</h2>";
        echo "<p class='success'>✅ Successful: " . count($migrations_run) . "</p>";
        echo "<p class='error'>❌ Failed: " . count($migrations_failed) . "</p>";
        
        if (count($migrations_failed) === 0) {
            echo "<h3 class='success'>🎉 Bio Links feature installed successfully!</h3>";
            echo "<p>You can now create bio links in your dashboard.</p>";
        } else {
            echo "<h3 class='error'>⚠️ Some migrations failed</h3>";
            echo "<p>Check the errors above and try again.</p>";
        }
        
        echo "<p><a href='/dashboard.php' style='display: inline-block; padding: 12px 24px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;'>🏠 Go to Dashboard</a></p>";
        echo "</div></body></html>";
        
    } catch (Exception $e) {
        echo "<div class='container'><h1 class='error'>❌ Migration Error</h1>";
        echo "<p>" . $e->getMessage() . "</p></div></body></html>";
    }
} else {
    echo "<div class='container'><h1 class='error'>❌ Config Not Found</h1>";
    echo "<p>Cannot find config.php. Please ensure HYLS is properly installed.</p></div></body></html>";
}
?>