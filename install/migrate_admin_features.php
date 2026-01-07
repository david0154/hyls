<?php
/**
 * Admin Features Migration
 * Adds ban/block columns to users and links tables
 */

define('MIGRATION_RUNNING', true);

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/db.php';
    
    try {
        $db = new Database();
        
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Features Migration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #0f172a; color: #e2e8f0; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 12px; }
        h1 { color: #22c55e; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .log { background: #0f172a; padding: 15px; border-radius: 8px; margin: 10px 0; font-family: monospace; font-size: 13px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔒 Admin Features Migration</h1>
        <p>Adding ban/block functionality...</p>
        <div class='log'>";
        
        $migrations_run = [];
        $migrations_failed = [];
        
        // User ban columns
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_banned'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER is_admin");
                echo "<span class='success'>✅ Added is_banned column to users</span><br>";
                $migrations_run[] = 'users.is_banned';
            } else {
                echo "<span class='success'>ℹ️ users.is_banned already exists</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'users.is_banned';
        }
        
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'banned_at'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE users ADD COLUMN banned_at DATETIME NULL AFTER is_banned");
                echo "<span class='success'>✅ Added banned_at column to users</span><br>";
                $migrations_run[] = 'users.banned_at';
            } else {
                echo "<span class='success'>ℹ️ users.banned_at already exists</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'users.banned_at';
        }
        
        // Link block columns
        try {
            $stmt = $db->query("SHOW COLUMNS FROM links LIKE 'is_blocked'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE links ADD COLUMN is_blocked TINYINT(1) DEFAULT 0 AFTER clicks");
                echo "<span class='success'>✅ Added is_blocked column to links</span><br>";
                $migrations_run[] = 'links.is_blocked';
            } else {
                echo "<span class='success'>ℹ️ links.is_blocked already exists</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'links.is_blocked';
        }
        
        try {
            $stmt = $db->query("SHOW COLUMNS FROM links LIKE 'blocked_at'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE links ADD COLUMN blocked_at DATETIME NULL AFTER is_blocked");
                echo "<span class='success'>✅ Added blocked_at column to links</span><br>";
                $migrations_run[] = 'links.blocked_at';
            } else {
                echo "<span class='success'>ℹ️ links.blocked_at already exists</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'links.blocked_at';
        }
        
        try {
            $stmt = $db->query("SHOW COLUMNS FROM links LIKE 'blocked_reason'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE links ADD COLUMN blocked_reason TEXT NULL AFTER blocked_at");
                echo "<span class='success'>✅ Added blocked_reason column to links</span><br>";
                $migrations_run[] = 'links.blocked_reason';
            } else {
                echo "<span class='success'>ℹ️ links.blocked_reason already exists</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Failed: " . $e->getMessage() . "</span><br>";
            $migrations_failed[] = 'links.blocked_reason';
        }
        
        echo "</div>";
        echo "<h2>Migration Summary</h2>";
        echo "<p class='success'>✅ Successful: " . count($migrations_run) . "</p>";
        echo "<p class='error'>❌ Failed: " . count($migrations_failed) . "</p>";
        
        if (count($migrations_failed) === 0) {
            echo "<h3 class='success'>🎉 Admin features installed successfully!</h3>";
            echo "<p>You can now ban/unban users and block/unblock links from admin panel.</p>";
        }
        
        echo "<p><a href='/admin/users.php' style='display: inline-block; padding: 12px 24px; background: #22c55e; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; margin-right: 10px;'>👥 Manage Users</a>";
        echo "<a href='/admin/links.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;'>🔗 Manage Links</a></p>";
        echo "</div></body></html>";
        
    } catch (Exception $e) {
        echo "<div class='container'><h1 class='error'>❌ Migration Error</h1>";
        echo "<p>" . $e->getMessage() . "</p></div></body></html>";
    }
} else {
    echo "<div class='container'><h1 class='error'>❌ Config Not Found</h1></div></body></html>";
}
?>