<?php
/**
 * ===========================
 * DATABASE MIGRATION SCRIPT
 * ===========================
 * 
 * This script adds the missing 'is_banned' column to the short_links table.
 * Run this ONCE if you're upgrading from an older version.
 * 
 * Usage: https://yourdomain.com/migrate.php
 */

// Check if config.php exists
if (!file_exists('config.php')) {
    die('<h1>Error</h1><p>config.php not found. Please run install.php first.</p>');
}

require_once 'config.php';

$success_messages = [];
$error_messages = [];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if is_banned column exists in short_links table
    $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'is_banned'");
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("
            ALTER TABLE short_links 
            ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER earnings
        ");
        $success_messages[] = '✅ Added is_banned column to short_links table';
    } else {
        $success_messages[] = '✅ is_banned column already exists';
    }
    
    // Check if ban_reason column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'ban_reason'");
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("
            ALTER TABLE short_links 
            ADD COLUMN ban_reason TEXT DEFAULT NULL AFTER is_banned
        ");
        $success_messages[] = '✅ Added ban_reason column to short_links table';
    } else {
        $success_messages[] = '✅ ban_reason column already exists';
    }
    
    // Check if banned_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM short_links LIKE 'banned_at'");
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("
            ALTER TABLE short_links 
            ADD COLUMN banned_at TIMESTAMP NULL DEFAULT NULL AFTER ban_reason
        ");
        $success_messages[] = '✅ Added banned_at column to short_links table';
    } else {
        $success_messages[] = '✅ banned_at column already exists';
    }
    
    $success_messages[] = '🎉 <strong>Migration completed successfully!</strong>';
    $success_messages[] = '🚀 You can now use the link banning feature in the admin panel.';
    $success_messages[] = '🗑️ <strong>IMPORTANT:</strong> Delete this migrate.php file for security.';
    
} catch (PDOException $e) {
    $error_messages[] = '❌ Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $error_messages[] = '❌ Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - HYLS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #6366f1;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .logo p {
            color: #64748b;
            font-size: 14px;
        }
        .message-box {
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 15px;
            font-size: 14px;
            line-height: 1.6;
        }
        .success-box {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .error-box {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .message-box ul {
            list-style: none;
            padding: 0;
        }
        .message-box li {
            padding: 5px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            display: block;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            display: inline-block;
            margin-right: 10px;
        }
        .actions {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔧 HYLS Migration</h1>
            <p>Database Structure Update</p>
        </div>

        <?php if (!empty($success_messages)): ?>
            <div class="message-box success-box">
                <ul>
                    <?php foreach ($success_messages as $msg): ?>
                        <li><?= $msg ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_messages)): ?>
            <div class="message-box error-box">
                <ul>
                    <?php foreach ($error_messages as $msg): ?>
                        <li><?= $msg ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="actions">
            <a href="admin/index.php" class="btn btn-primary">👑 Go to Admin Panel</a>
            <br>
            <a href="index.php" class="btn btn-secondary">🏠 Go to Homepage</a>
        </div>
    </div>
</body>
</html>
