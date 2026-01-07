<?php
/**
 * Database Migration Script
 * Adds missing columns and tables for new features
 */

// Only run if called from update.php or directly
if (!defined('MIGRATION_RUNNING')) {
    define('MIGRATION_RUNNING', true);
}

// Load database connection
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/db.php';
    
    try {
        $db = new Database();
        $migrations_run = [];
        $migrations_failed = [];
        
        echo "<br><strong>Running Database Migrations:</strong><br>";
        
        // Migration 1: Add google_id column to users table
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'google_id'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL AFTER email");
                $db->query("ALTER TABLE users ADD INDEX idx_google_id (google_id)");
                $migrations_run[] = 'Added google_id column to users table';
                echo "✅ Added google_id column<br>";
            } else {
                echo "ℹ️ google_id column already exists<br>";
            }
        } catch (Exception $e) {
            $migrations_failed[] = 'google_id column: ' . $e->getMessage();
            echo "❌ Failed to add google_id column: " . $e->getMessage() . "<br>";
        }
        
        // Migration 2: Add profile_picture column to users table
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE users ADD COLUMN profile_picture TEXT NULL AFTER google_id");
                $migrations_run[] = 'Added profile_picture column to users table';
                echo "✅ Added profile_picture column<br>";
            } else {
                echo "ℹ️ profile_picture column already exists<br>";
            }
        } catch (Exception $e) {
            $migrations_failed[] = 'profile_picture column: ' . $e->getMessage();
            echo "❌ Failed to add profile_picture column: " . $e->getMessage() . "<br>";
        }
        
        // Migration 3: Add email_verified column to users table
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER email");
                $migrations_run[] = 'Added email_verified column to users table';
                echo "✅ Added email_verified column<br>";
            } else {
                echo "ℹ️ email_verified column already exists<br>";
            }
        } catch (Exception $e) {
            $migrations_failed[] = 'email_verified column: ' . $e->getMessage();
            echo "❌ Failed to add email_verified column: " . $e->getMessage() . "<br>";
        }
        
        // Migration 4: Add last_login column to users table
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'last_login'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER updated_at");
                $migrations_run[] = 'Added last_login column to users table';
                echo "✅ Added last_login column<br>";
            } else {
                echo "ℹ️ last_login column already exists<br>";
            }
        } catch (Exception $e) {
            $migrations_failed[] = 'last_login column: ' . $e->getMessage();
            echo "❌ Failed to add last_login column: " . $e->getMessage() . "<br>";
        }
        
        // Migration 5: Make password nullable for Google OAuth users
        try {
            $db->query("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
            $migrations_run[] = 'Made password column nullable';
            echo "✅ Made password column nullable<br>";
        } catch (Exception $e) {
            $migrations_failed[] = 'password nullable: ' . $e->getMessage();
            echo "❌ Failed to make password nullable: " . $e->getMessage() . "<br>";
        }
        
        // Migration 6: Add hypechats_id column for HypeChats integration
        try {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'hypechats_id'");
            if (!$stmt->fetch()) {
                $db->query("ALTER TABLE users ADD COLUMN hypechats_id VARCHAR(255) NULL AFTER google_id");
                $db->query("ALTER TABLE users ADD INDEX idx_hypechats_id (hypechats_id)");
                $migrations_run[] = 'Added hypechats_id column to users table';
                echo "✅ Added hypechats_id column<br>";
            } else {
                echo "ℹ️ hypechats_id column already exists<br>";
            }
        } catch (Exception $e) {
            $migrations_failed[] = 'hypechats_id column: ' . $e->getMessage();
            echo "❌ Failed to add hypechats_id column: " . $e->getMessage() . "<br>";
        }
        
        // Log migrations
        if (!empty($migrations_run)) {
            error_log("Migrations completed: " . implode(', ', $migrations_run));
        }
        if (!empty($migrations_failed)) {
            error_log("Migrations failed: " . implode(', ', $migrations_failed));
        }
        
        echo "<br><strong>Migration Summary:</strong><br>";
        echo "✅ Successful: " . count($migrations_run) . "<br>";
        echo "❌ Failed: " . count($migrations_failed) . "<br>";
        
    } catch (Exception $e) {
        echo "❌ Migration error: " . $e->getMessage() . "<br>";
        error_log("Migration error: " . $e->getMessage());
    }
} else {
    echo "❌ Config file not found. Cannot run migrations.<br>";
}
?>