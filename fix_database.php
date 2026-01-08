<?php
// fix_database.php - Run this ONCE to fix all database issues
// Visit: https://hyls.space/fix_database.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Fix</title>
    <style>
        body { font-family: Arial; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; padding: 40px; border-radius: 20px; max-width: 800px; margin: 0 auto; }
        h1 { color: #6366f1; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin: 10px 0; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin: 10px 0; }
        .info { background: #e0f2fe; color: #075985; padding: 12px; border-radius: 8px; margin: 10px 0; }
        pre { background: #f1f5f9; padding: 12px; border-radius: 8px; overflow-x: auto; font-size: 12px; }
        a { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
<h1>🛠️ Database Fix Tool</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='success'>✅ Connected to database: " . DB_NAME . "</div>";
    
    $messages = [];
    
    // 1. DROP and recreate bio_gallery table with correct structure
    echo "<h2>Step 1: Fix Gallery Table</h2>";
    
    try {
        $pdo->exec("DROP TABLE IF EXISTS bio_gallery");
        $messages[] = '🗑️ Dropped old bio_gallery table';
    } catch (Exception $e) {
        $messages[] = '⚠️ bio_gallery didn\'t exist';
    }
    
    $sql = "CREATE TABLE `bio_gallery` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `image_url` VARCHAR(500) NOT NULL,
      `image_order` INT(11) DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_image_order` (`image_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $messages[] = '✅ Created bio_gallery table with correct structure';
    
    // 2. Check bio_links structure
    echo "<h2>Step 2: Check bio_links Table</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM bio_links");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    $messages[] = '📋 Found ' . count($existing_columns) . ' columns in bio_links';
    
    // 3. Add missing base columns
    echo "<h2>Step 3: Add Missing Columns</h2>";
    
    $base_columns = [
        ['name' => 'threads', 'type' => 'VARCHAR(500)'],
        ['name' => 'bluesky', 'type' => 'VARCHAR(500)'],
        ['name' => 'mastodon', 'type' => 'VARCHAR(500)'],
        ['name' => 'medium', 'type' => 'VARCHAR(500)'],
        ['name' => 'substack', 'type' => 'VARCHAR(500)'],
        ['name' => 'patreon', 'type' => 'VARCHAR(500)'],
        ['name' => 'onlyfans', 'type' => 'VARCHAR(500)'],
        ['name' => 'cashapp', 'type' => 'VARCHAR(500)'],
        ['name' => 'venmo', 'type' => 'VARCHAR(500)'],
        ['name' => 'paypal', 'type' => 'VARCHAR(500)'],
        ['name' => 'line', 'type' => 'VARCHAR(500)']
    ];
    
    foreach ($base_columns as $col) {
        if (!in_array($col['name'], $existing_columns)) {
            try {
                $pdo->exec("ALTER TABLE bio_links ADD COLUMN `{$col['name']}` {$col['type']} DEFAULT '' AFTER phone");
                $messages[] = "✅ Added {$col['name']} column";
            } catch (Exception $e) {
                $messages[] = "⚠️ Could not add {$col['name']}: " . $e->getMessage();
            }
        }
    }
    
    // 4. Add ALL _enabled columns
    echo "<h2>Step 4: Add ALL _enabled Columns</h2>";
    
    $all_platforms = [
        'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok',
        'github', 'pinterest', 'snapchat', 'discord', 'twitch', 'telegram',
        'whatsapp', 'spotify', 'reddit', 'website', 'email', 'phone',
        'threads', 'bluesky', 'mastodon', 'medium', 'substack', 'patreon',
        'onlyfans', 'cashapp', 'venmo', 'paypal', 'line'
    ];
    
    $added_enabled = 0;
    
    foreach ($all_platforms as $platform) {
        $enabled_col = $platform . '_enabled';
        
        // Check if base column exists first
        if (in_array($platform, $existing_columns) || in_array($platform, array_column($base_columns, 'name'))) {
            if (!in_array($enabled_col, $existing_columns)) {
                try {
                    $pdo->exec("ALTER TABLE bio_links ADD COLUMN `$enabled_col` TINYINT(1) DEFAULT 1 AFTER `$platform`");
                    $messages[] = "✅ Added $enabled_col";
                    $added_enabled++;
                } catch (Exception $e) {
                    $messages[] = "⚠️ Could not add $enabled_col: " . $e->getMessage();
                }
            }
        }
    }
    
    $messages[] = "🎯 Added $added_enabled new _enabled columns";
    
    // 5. Create uploads directories
    echo "<h2>Step 5: Create Upload Directories</h2>";
    
    $dirs = ['uploads', 'uploads/bio', 'uploads/bio/gallery'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $messages[] = "✅ Created $dir/";
        } else {
            $messages[] = "✔️ $dir/ already exists";
        }
    }
    
    // Show all messages
    foreach ($messages as $msg) {
        echo "<div class='info'>$msg</div>";
    }
    
    // 6. Verify setup
    echo "<h2 style='margin-top: 30px;'>🔍 Verification</h2>";
    
    // Check gallery table
    $stmt = $pdo->query("DESCRIBE bio_gallery");
    echo "<div class='success'>✅ bio_gallery table structure:</div>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo str_pad($row['Field'], 20) . " | " . $row['Type'] . "\n";
    }
    echo "</pre>";
    
    // Check _enabled columns
    $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE '%_enabled'");
    $enabled_count = 0;
    $enabled_list = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $enabled_count++;
        $enabled_list[] = $row['Field'];
    }
    
    echo "<div class='success'>✅ Found $enabled_count _enabled columns:</div>";
    echo "<pre>" . implode("\n", $enabled_list) . "</pre>";
    
    // Check if threads exists
    $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE 'threads'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ threads column exists</div>";
    } else {
        echo "<div class='error'>❌ threads column missing!</div>";
    }
    
    // Check if threads_enabled exists
    $stmt = $pdo->query("SHOW COLUMNS FROM bio_links LIKE 'threads_enabled'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ threads_enabled column exists</div>";
    } else {
        echo "<div class='error'>❌ threads_enabled column missing!</div>";
    }
    
    echo "<div class='success' style='margin-top: 30px; padding: 20px; border: 2px solid #10b981;'>
        <h2 style='color: #065f46; margin: 0 0 10px 0;'>✅ Setup Complete!</h2>
        <p><strong>Your database is now ready.</strong></p>
        <p>All 29 platforms with _enabled columns are configured.</p>
        <a href='biolink.php' style='background: #10b981;'>➡️ Go to Bio Link</a>
        <a href='fix_database.php' style='background: #6366f1; margin-left: 10px;'>🔄 Refresh This Page</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h2>❌ Error</h2>
        <p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>
        <p>File: " . htmlspecialchars($e->getFile()) . "<br>
        Line: " . $e->getLine() . "</p>
        <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>";
}

echo "</div>
</body>
</html>";
?>