<?php
/**
 * Bio Link Advanced Features Migration
 * Adds: 
 * - bio_gallery table (6 images)
 * - bio_custom_links table (unlimited custom links)
 * - bio_social_accounts table (multiple accounts per platform)
 * - cover_image column to bio_links
 */

require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = new Database();
    $success = [];
    $errors = [];

    echo "<!DOCTYPE html>
<html>
<head>
    <title>Bio Link Advanced Migration</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h1 { color: #333; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🚀 Bio Link Advanced Features Migration</h1>";

    // 1. Add cover_image column to bio_links if not exists
    echo "<div class='info'>📝 Checking bio_links table...</div>";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM bio_links LIKE 'cover_image'");
        if ($stmt->rowCount() == 0) {
            $db->query("ALTER TABLE bio_links ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER profile_image");
            $success[] = "✅ Added 'cover_image' column to bio_links table";
        } else {
            $success[] = "✓ 'cover_image' column already exists";
        }
    } catch (Exception $e) {
        $errors[] = "❌ Failed to add cover_image: " . $e->getMessage();
    }

    // 2. Create bio_gallery table (6 images max)
    echo "<div class='info'>📝 Creating bio_gallery table...</div>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS bio_gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            bio_profile_id INT NULL,
            image_url VARCHAR(255) NOT NULL,
            image_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_order (image_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql);
        $success[] = "✅ Created bio_gallery table (for 6 images)";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "✓ bio_gallery table already exists";
        } else {
            $errors[] = "❌ Failed to create bio_gallery: " . $e->getMessage();
        }
    }

    // 3. Create bio_custom_links table
    echo "<div class='info'>📝 Creating bio_custom_links table...</div>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS bio_custom_links (
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
            INDEX idx_active (is_active),
            INDEX idx_order (link_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql);
        $success[] = "✅ Created bio_custom_links table (unlimited custom links)";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "✓ bio_custom_links table already exists";
        } else {
            $errors[] = "❌ Failed to create bio_custom_links: " . $e->getMessage();
        }
    }

    // 4. Create bio_social_accounts table (multiple accounts per platform)
    echo "<div class='info'>📝 Creating bio_social_accounts table...</div>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS bio_social_accounts (
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
            INDEX idx_platform (platform),
            INDEX idx_active (is_active),
            INDEX idx_order (account_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql);
        $success[] = "✅ Created bio_social_accounts table (multiple accounts per platform)";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $success[] = "✓ bio_social_accounts table already exists";
        } else {
            $errors[] = "❌ Failed to create bio_social_accounts: " . $e->getMessage();
        }
    }

    // Display results
    echo "<h2>Migration Results:</h2>";
    
    foreach ($success as $msg) {
        echo "<div class='success'>$msg</div>";
    }
    
    foreach ($errors as $msg) {
        echo "<div class='error'>$msg</div>";
    }

    if (empty($errors)) {
        echo "<div class='success'>
            <h3>✅ Migration Completed Successfully!</h3>
            <p><strong>New Features Available:</strong></p>
            <ul>
                <li>✅ <strong>Cover Image</strong> - Added to bio_links table</li>
                <li>✅ <strong>6-Image Gallery</strong> - bio_gallery table created</li>
                <li>✅ <strong>Custom Links</strong> - bio_custom_links table created</li>
                <li>✅ <strong>Multiple Social Accounts</strong> - bio_social_accounts table created</li>
            </ul>
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Visit <a href='../biolink.php'>biolink.php</a> to use the new features</li>
                <li>Upload cover image and profile picture</li>
                <li>Add up to 6 gallery images</li>
                <li>Create custom links with icons</li>
                <li>Add multiple accounts per social platform</li>
            </ol>
        </div>";
    } else {
        echo "<div class='error'>
            <h3>⚠️ Migration completed with errors</h3>
            <p>Some features may not be available. Check error messages above.</p>
        </div>";
    }

    echo "<div class='info'>
        <strong>Database Tables Created/Updated:</strong><br>
        <code>bio_links</code> - Added cover_image column<br>
        <code>bio_gallery</code> - For 6 image uploads<br>
        <code>bio_custom_links</code> - For custom links<br>
        <code>bio_social_accounts</code> - For multiple social accounts
    </div>";

} catch (Exception $e) {
    echo "<div class='error'>
        <h3>❌ Fatal Error</h3>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}

echo "</body></html>";
?>