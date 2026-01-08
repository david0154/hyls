<?php
// setup_gallery_simple.php - Simple gallery table setup
// Visit: https://hyls.space/setup_gallery_simple.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Gallery Setup</title>
    <style>
        body { font-family: Arial; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; padding: 40px; border-radius: 20px; max-width: 700px; margin: 0 auto; }
        h1 { color: #6366f1; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin: 10px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin: 10px 0; }
        .info { background: #e0f2fe; color: #075985; padding: 12px; border-radius: 8px; margin: 10px 0; }
        pre { background: #f8fafc; padding: 12px; border-radius: 8px; overflow-x: auto; }
        a { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🛠️ Gallery Setup</h1>";

try {
    // Step 1: Check if config exists
    if (!file_exists('config.php')) {
        throw new Exception('config.php not found!');
    }
    echo "<div class='success'>✅ config.php found</div>";
    
    require_once 'config.php';
    echo "<div class='success'>✅ config.php loaded</div>";
    
    // Step 2: Connect to database using PDO directly
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='success'>✅ Database connected</div>";
    
    $messages = [];
    
    // Step 3: Create bio_gallery table
    $sql = "CREATE TABLE IF NOT EXISTS `bio_gallery` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `image_url` VARCHAR(255) NOT NULL,
      `image_order` INT(11) DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    $messages[] = '✅ bio_gallery table created/verified';
    
    // Step 4: Check current bio_links structure
    $stmt = $pdo->query("SHOW COLUMNS FROM bio_links");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    $messages[] = '📋 Found ' . count($existing_columns) . ' columns in bio_links';
    
    // Step 5: Add missing _enabled columns
    $platforms = [
        'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok',
        'github', 'pinterest', 'snapchat', 'discord', 'twitch', 'telegram',
        'whatsapp', 'spotify', 'reddit', 'website', 'email', 'phone',
        'threads', 'bluesky', 'mastodon', 'medium', 'substack', 'patreon',
        'onlyfans', 'cashapp', 'venmo', 'paypal', 'line'
    ];
    
    $added = 0;
    $skipped = 0;
    
    foreach ($platforms as $platform) {
        $enabled_col = $platform . '_enabled';
        
        // Only add _enabled if base column exists
        if (in_array($platform, $existing_columns)) {
            if (!in_array($enabled_col, $existing_columns)) {
                try {
                    $pdo->exec("ALTER TABLE bio_links ADD COLUMN `$enabled_col` TINYINT(1) DEFAULT 1");
                    $messages[] = "✅ Added $enabled_col";
                    $added++;
                } catch (Exception $e) {
                    $messages[] = "⚠️ Could not add $enabled_col: " . $e->getMessage();
                }
            } else {
                $skipped++;
            }
        }
    }
    
    $messages[] = "🎯 Added $added new columns, $skipped already existed";
    
    // Step 6: Create uploads directory
    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
    if (!is_dir('uploads/bio')) mkdir('uploads/bio', 0755, true);
    if (!is_dir('uploads/bio/gallery')) {
        mkdir('uploads/bio/gallery', 0755, true);
        $messages[] = '✅ Created uploads/bio/gallery folder';
    } else {
        $messages[] = '✅ uploads/bio/gallery already exists';
    }
    
    // Show results
    foreach ($messages as $msg) {
        echo "<div class='info'>$msg</div>";
    }
    
    echo "<div class='success' style='margin-top: 20px; padding: 20px;'>
        <h2>✅ Setup Complete!</h2>
        <p>Your gallery is ready to use.</p>
        <a href='biolink.php'>➡️ Go to Bio Link</a>
        <a href='setup_gallery_simple.php?verify=1' style='background: #10b981; margin-left: 10px;'>🔍 Verify Setup</a>
    </div>";
    
    // Verification mode
    if (isset($_GET['verify'])) {
        echo "<h2 style='margin-top: 30px;'>🔍 Verification</h2>";
        
        // Check gallery table
        $stmt = $pdo->query("SHOW TABLES LIKE 'bio_gallery'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ bio_gallery table exists</div>";
            
            $stmt = $pdo->query("DESCRIBE bio_gallery");
            echo "<div class='info'><strong>Table structure:</strong><pre>";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo $row['Field'] . " - " . $row['Type'] . "\n";
            }
            echo "</pre></div>";
        } else {
            echo "<div class='error'>❌ bio_gallery table not found</div>";
        }
        
        // Check _enabled columns
        $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE '%_enabled'");
        $enabled_cols = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $enabled_cols[] = $row['Field'];
        }
        echo "<div class='success'>✅ Found " . count($enabled_cols) . " _enabled columns</div>";
        echo "<div class='info'><strong>Enabled columns:</strong><pre>" . implode("\n", $enabled_cols) . "</pre></div>";
        
        // Check folders
        if (is_dir('uploads/bio/gallery') && is_writable('uploads/bio/gallery')) {
            echo "<div class='success'>✅ uploads/bio/gallery is writable</div>";
        } else {
            echo "<div class='error'>❌ uploads/bio/gallery is not writable</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h2>❌ Error</h2>
        <p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>
        <p>File: " . htmlspecialchars($e->getFile()) . "<br>
        Line: " . $e->getLine() . "</p>
    </div>";
    
    echo "<h3>Troubleshooting:</h3>
    <ol>
        <li>Check if config.php has correct database credentials</li>
        <li>Make sure database user has CREATE/ALTER permissions</li>
        <li>Try running the SQL manually in phpMyAdmin</li>
    </ol>";
}

echo "</div>
</body>
</html>";
?>