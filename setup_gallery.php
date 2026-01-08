<?php
// setup_gallery.php - Run this ONCE to create gallery table
// Visit: https://hyls.space/setup_gallery.php

require_once 'config.php';
require_once 'includes/db.php';

try {
    $db = new Database();
    $messages = [];
    
    // Create bio_gallery table
    $sql = "CREATE TABLE IF NOT EXISTS `bio_gallery` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `image_url` VARCHAR(255) NOT NULL,
      `image_order` INT(11) DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      KEY `image_order` (`image_order`),
      CONSTRAINT `bio_gallery_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    $messages[] = '✅ bio_gallery table created!';
    
    // Add all _enabled columns
    $platforms = [
        'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok',
        'github', 'pinterest', 'snapchat', 'discord', 'twitch', 'telegram',
        'whatsapp', 'spotify', 'reddit', 'website', 'email', 'phone',
        'threads', 'bluesky', 'mastodon', 'medium', 'substack', 'patreon',
        'onlyfans', 'cashapp', 'venmo', 'paypal', 'line'
    ];
    
    foreach ($platforms as $platform) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM bio_links LIKE '{$platform}_enabled'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE bio_links ADD COLUMN {$platform}_enabled TINYINT(1) DEFAULT 1");
                $messages[] = "✅ Added {$platform}_enabled column";
            }
        } catch (Exception $e) {
            // Column might already exist or platform not in table
        }
    }
    
    // Create uploads directory
    if (!is_dir('uploads/bio/gallery')) {
        mkdir('uploads/bio/gallery', 0755, true);
        $messages[] = '✅ Created uploads/bio/gallery folder';
    }
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Gallery Setup Complete</title>
    <style>
        body { font-family: Arial; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; padding: 40px; border-radius: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #6366f1; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin: 10px 0; }
        a { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✅ Setup Complete!</h1>";
    
    foreach ($messages as $msg) {
        echo "<div class='success'>$msg</div>";
    }
    
    echo "<a href='biolink.php'>➡️ Go to Bio Link</a>
    </div>
</body>
</html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Error</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #fee2e2; }
        .error { background: white; padding: 40px; border-radius: 20px; max-width: 600px; margin: 0 auto; color: #991b1b; }
    </style>
</head>
<body>
    <div class='error'>
        <h1>❌ Error</h1>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
    </div>
</body>
</html>";
}
?>