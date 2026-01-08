# Database Migration Guide for HYLS Video Features

## Overview
This guide will help you add the social media video embedding and enhanced image features to your HYLS database.

## Prerequisites
- MySQL/MariaDB database access
- Command line access or phpMyAdmin
- Backup your database before proceeding!

---

## Method 1: Using MySQL Command Line (Recommended)

### Step 1: Backup Your Database
```bash
mysqldump -u your_username -p your_database_name > hyls_backup_$(date +%Y%m%d).sql
```

### Step 2: Navigate to Project Directory
```bash
cd /path/to/hyls
```

### Step 3: Run Migration SQL File
```bash
mysql -u your_username -p your_database_name < social_videos_table.sql
```

You'll be prompted for your password. After entering it, the migration will run.

### Step 4: Verify Tables Were Created
```bash
mysql -u your_username -p your_database_name
```

Then run:
```sql
SHOW TABLES LIKE 'bio_social_videos';
DESCRIBE bio_social_videos;
DESCRIBE bio_gallery;
DESCRIBE bio_profiles;
```

You should see the new tables and updated columns.

---

## Method 2: Using phpMyAdmin

### Step 1: Backup Database
1. Open phpMyAdmin
2. Select your HYLS database
3. Click "Export" tab
4. Click "Go" to download backup

### Step 2: Run Migration
1. Click "SQL" tab
2. Open `social_videos_table.sql` file in a text editor
3. Copy all SQL content
4. Paste into SQL query box in phpMyAdmin
5. Click "Go" button

### Step 3: Verify Migration
Check that these tables/columns exist:
- Table: `bio_social_videos` (new)
- Column in `bio_gallery`: `crop_x`, `crop_y`, `crop_width`, `crop_height`, `original_width`, `original_height`
- Column in `bio_profiles`: `cover_crop_x`, `cover_crop_y`, `cover_crop_width`, `cover_crop_height`

---

## Method 3: Using PHP Migration Script

Create a file `run_migration.php` in your HYLS root:

```php
<?php
require_once 'config.php';
require_once 'includes/db.php';

$db = new Database();

// Read SQL file
$sql = file_get_contents('social_videos_table.sql');

// Split into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

echo "<h2>Running Database Migration</h2>";
echo "<pre>";

$success = 0;
$errors = 0;

foreach ($queries as $query) {
    if (empty($query)) continue;
    
    try {
        $db->pdo->exec($query);
        $success++;
        echo "✓ Query executed successfully\n";
    } catch (PDOException $e) {
        $errors++;
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "  Query: " . substr($query, 0, 100) . "...\n\n";
    }
}

echo "\n";
echo "=================================\n";
echo "Migration Complete!\n";
echo "Successful queries: $success\n";
echo "Failed queries: $errors\n";
echo "=================================\n";
echo "</pre>";

// Verify tables
echo "<h3>Verification:</h3>";
echo "<pre>";

try {
    $stmt = $db->pdo->query("SHOW TABLES LIKE 'bio_social_videos'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'bio_social_videos' exists\n";
    } else {
        echo "✗ Table 'bio_social_videos' NOT found\n";
    }
    
    $stmt = $db->pdo->query("SHOW COLUMNS FROM bio_gallery LIKE 'crop_x'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'crop_x' added to bio_gallery\n";
    } else {
        echo "✗ Column 'crop_x' NOT found in bio_gallery\n";
    }
    
    $stmt = $db->pdo->query("SHOW COLUMNS FROM bio_profiles LIKE 'cover_crop_x'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'cover_crop_x' added to bio_profiles\n";
    } else {
        echo "✗ Column 'cover_crop_x' NOT found in bio_profiles\n";
    }
} catch (PDOException $e) {
    echo "Error during verification: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><strong>Important:</strong> Delete this file (run_migration.php) after migration is complete!</p>";
?>
```

### Run the Migration:
1. Save the file as `run_migration.php` in HYLS root
2. Open browser: `http://yoursite.com/run_migration.php`
3. Check results
4. **DELETE** `run_migration.php` after success

---

## Method 4: Manual SQL Execution

If you prefer to run queries one by one:

### Query 1: Create bio_social_videos Table
```sql
CREATE TABLE IF NOT EXISTS `bio_social_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bio_profile_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `video_url` text NOT NULL,
  `embed_code` text NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `autoplay` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bio_profile_id` (`bio_profile_id`),
  KEY `platform` (`platform`),
  FOREIGN KEY (`bio_profile_id`) REFERENCES `bio_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Query 2: Add Crop Columns to bio_gallery
```sql
ALTER TABLE `bio_gallery` 
  ADD COLUMN `crop_x` int(11) DEFAULT 0,
  ADD COLUMN `crop_y` int(11) DEFAULT 0,
  ADD COLUMN `crop_width` int(11) DEFAULT NULL,
  ADD COLUMN `crop_height` int(11) DEFAULT NULL,
  ADD COLUMN `original_width` int(11) DEFAULT NULL,
  ADD COLUMN `original_height` int(11) DEFAULT NULL;
```

### Query 3: Add Crop Columns to bio_profiles
```sql
ALTER TABLE `bio_profiles`
  ADD COLUMN `cover_crop_x` int(11) DEFAULT 0,
  ADD COLUMN `cover_crop_y` int(11) DEFAULT 0,
  ADD COLUMN `cover_crop_width` int(11) DEFAULT NULL,
  ADD COLUMN `cover_crop_height` int(11) DEFAULT NULL;
```

---

## Post-Migration Steps

### 1. Create Upload Directory
```bash
mkdir -p uploads/images
chmod 755 uploads/images
chown www-data:www-data uploads/images  # Linux/Apache
# OR
chown nginx:nginx uploads/images        # Linux/Nginx
```

### 2. Update bio.php
Replace your current `bio.php` with `bio_with_videos.php`:
```bash
cp bio.php bio.php.backup
cp bio_with_videos.php bio.php
```

### 3. Test the Installation
1. Go to your bio editor page
2. Try adding a video (scroll to "Social Media Videos" section)
3. Try uploading an image
4. View your bio page to see videos displayed

---

## Troubleshooting

### Error: "Table 'bio_profiles' doesn't exist"
**Solution:** Your HYLS installation might use a different table structure. Check your database for the correct table name:
```sql
SHOW TABLES LIKE '%bio%';
```

Then modify the foreign key constraint accordingly.

### Error: "Duplicate column name"
**Solution:** Column already exists. You can skip that specific ALTER TABLE command.

### Error: "Cannot add foreign key constraint"
**Solution:** Remove the FOREIGN KEY line from the CREATE TABLE query:
```sql
-- Remove this line:
FOREIGN KEY (`bio_profile_id`) REFERENCES `bio_profiles` (`id`) ON DELETE CASCADE
```

### Error: "Access denied"
**Solution:** Your MySQL user needs proper permissions:
```sql
GRANT ALL PRIVILEGES ON your_database.* TO 'your_username'@'localhost';
FLUSH PRIVILEGES;
```

### Videos Not Showing on Bio Page
**Checklist:**
1. Check if `bio_social_videos` table has data:
   ```sql
   SELECT * FROM bio_social_videos;
   ```
2. Verify bio_profile_id matches between tables:
   ```sql
   SELECT bp.id, bp.user_id, u.username 
   FROM bio_profiles bp 
   JOIN users u ON bp.user_id = u.id;
   ```
3. Check browser console for JavaScript errors
4. Verify video URLs are public and embeddable

---

## Rollback Instructions

If something goes wrong, restore from backup:

### Using Command Line:
```bash
mysql -u your_username -p your_database_name < hyls_backup_20260108.sql
```

### Using phpMyAdmin:
1. Select database
2. Click "Import" tab
3. Choose your backup file
4. Click "Go"

---

## Verification Checklist

After migration, verify:

- [ ] Table `bio_social_videos` exists and is empty
- [ ] `bio_gallery` has new crop columns
- [ ] `bio_profiles` has new cover crop columns
- [ ] `uploads/images` directory exists with 755 permissions
- [ ] Can access edit bio page without errors
- [ ] Can add a test video
- [ ] Video displays on bio page
- [ ] Can upload images (under 12MB)
- [ ] No PHP errors in error logs

---

## Support

If you encounter issues:

1. Check error logs:
   - PHP error log (usually `/var/log/php/error.log`)
   - MySQL error log (usually `/var/log/mysql/error.log`)
   - Web server error log

2. Enable error display temporarily:
   ```php
   // Add to top of PHP file
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. Check database connection in `config.php`

4. Open an issue on GitHub: https://github.com/david0154/hyls/issues

---

## Complete Command Reference

### Quick Migration (Copy-Paste This)
```bash
# 1. Backup
mysqldump -u root -p hyls_db > backup.sql

# 2. Run Migration
mysql -u root -p hyls_db < social_videos_table.sql

# 3. Create Upload Directory
mkdir -p uploads/images && chmod 755 uploads/images

# 4. Update bio.php
cp bio.php bio.php.backup && cp bio_with_videos.php bio.php

# 5. Test
echo "Migration complete! Visit your site to test."
```

Replace `root` and `hyls_db` with your actual MySQL username and database name.

---

## Success!

If all steps completed successfully, you now have:
- ✅ Social media video embedding (8 platforms)
- ✅ Autoplay functionality
- ✅ Enhanced image upload (12MB limit)
- ✅ Image cropping capabilities
- ✅ Video view tracking
- ✅ Platform-specific styling

Enjoy your enhanced HYLS bio pages! 🎉