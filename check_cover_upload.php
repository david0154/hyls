<?php
// check_cover_upload.php - Debug script to check cover image setup
require_once 'config.php';
require_once 'includes/db.php';

$db = new Database();

echo "<h2>Cover Image Upload Debug</h2>";

// Check if cover_image column exists
echo "<h3>1. Checking database structure:</h3>";
$stmt = $db->query("SHOW COLUMNS FROM bio_links LIKE 'cover_image'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);

if ($col) {
    echo "✅ <strong>cover_image</strong> column EXISTS<br>";
    echo "Type: {$col['Type']}<br>";
    echo "Null: {$col['Null']}<br>";
    echo "Default: {$col['Default']}<br>";
} else {
    echo "❌ <strong>cover_image</strong> column DOES NOT EXIST<br>";
    echo "<br><strong>FIX:</strong> Run this SQL in phpMyAdmin:<br>";
    echo "<code style='background: #f0f0f0; padding: 10px; display: block; margin-top: 10px;'>";
    echo "ALTER TABLE `bio_links` ADD COLUMN `cover_image` VARCHAR(255) DEFAULT '' AFTER `profile_image`;";
    echo "</code>";
}

// Check uploads directory
echo "<h3>2. Checking upload directories:</h3>";
$upload_dir = __DIR__ . '/uploads/bio';
if (is_dir($upload_dir)) {
    echo "✅ uploads/bio directory EXISTS<br>";
    if (is_writable($upload_dir)) {
        echo "✅ uploads/bio is WRITABLE<br>";
    } else {
        echo "❌ uploads/bio is NOT WRITABLE<br>";
        echo "<strong>FIX:</strong> Run: <code>chmod 755 uploads/bio</code><br>";
    }
} else {
    echo "❌ uploads/bio directory DOES NOT EXIST<br>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "✅ Created uploads/bio directory<br>";
    } else {
        echo "❌ Failed to create directory<br>";
    }
}

// Check existing cover images
echo "<h3>3. Existing cover images in database:</h3>";
$stmt = $db->query("SELECT u.username, b.cover_image FROM bio_links b JOIN users u ON b.user_id = u.id WHERE b.cover_image != '' AND b.cover_image IS NOT NULL");
$covers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($covers) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Cover Image Path</th><th>File Exists</th></tr>";
    foreach ($covers as $c) {
        $file_path = __DIR__ . '/' . $c['cover_image'];
        $exists = file_exists($file_path) ? '✅ Yes' : '❌ No';
        echo "<tr><td>{$c['username']}</td><td>{$c['cover_image']}</td><td>$exists</td></tr>";
    }
    echo "</table>";
} else {
    echo "No cover images found in database yet.<br>";
}

echo "<h3>4. Test form:</h3>";
echo "<p>Try uploading a cover image through <a href='biolink.php'>biolink.php</a></p>";
echo "<p>After uploading, refresh this page to see the results.</p>";
?>