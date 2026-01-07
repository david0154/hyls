<?php
// DEBUG SCRIPT - Remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';
require_once 'includes/db.php';

$db = new Database();

echo "<h1>🔍 Bio Links Debug</h1>";

// Check 1: Users table
echo "<h2>1️⃣ Users in Database:</h2>";
$stmt = $db->prepare("SELECT id, username, email FROM users LIMIT 10");
$stmt->execute();
$users = $stmt->fetchAll();
if ($users) {
    echo "<pre>";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ No users found!</p>";
}

// Check 2: Bio links table
echo "<h2>2️⃣ Bio Links in Database:</h2>";
$stmt = $db->prepare("SELECT id, user_id, username, display_name, bio FROM bio_links LIMIT 10");
$stmt->execute();
$bios = $stmt->fetchAll();
if ($bios) {
    echo "<pre>";
    foreach ($bios as $bio) {
        echo "ID: {$bio['id']}, User ID: {$bio['user_id']}, Username: {$bio['username']}, Display: {$bio['display_name']}, Bio: " . substr($bio['bio'] ?? '', 0, 30) . "...\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ No bio links found in database!</p>";
}

// Check 3: Specific David bio
echo "<h2>3️⃣ Looking for David's Bio:</h2>";
$stmt = $db->prepare("SELECT * FROM bio_links WHERE username = 'David'");
$stmt->execute();
$david_bio = $stmt->fetch();
if ($david_bio) {
    echo "<p style='color: green;'>✅ Found David's bio!</p>";
    echo "<pre>";
    print_r($david_bio);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ David's bio not found!</p>";
}

// Check 4: Current session
echo "<h2>4️⃣ Session Info:</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ Logged in as User ID: {$_SESSION['user_id']}</p>";
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        echo "<p>Username: {$user['username']}</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Not logged in</p>";
}

// Check 5: Test bio.php query
echo "<h2>5️⃣ Testing bio.php Query (David):</h2>";
try {
    $stmt = $db->prepare("SELECT b.*, u.username FROM bio_links b JOIN users u ON b.user_id = u.id WHERE u.username = ?");
    $stmt->execute(['David']);
    $result = $stmt->fetch();
    if ($result) {
        echo "<p style='color: green;'>✅ Query works!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Query returns no results</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Check 6: File permissions
echo "<h2>6️⃣ Directory Permissions:</h2>";
$dirs = ['uploads', 'uploads/bio', 'uploads/profiles'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<p>✅ $dir exists (permissions: $perms)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ $dir does NOT exist</p>";
    }
}

echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ REMOVE THIS FILE AFTER DEBUGGING!</strong></p>";
echo "<p>Delete: debug_bio.php</p>";
?>
