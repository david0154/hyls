# 🚀 Installation Guide - Gallery & Social Accounts

## Overview

This guide covers installing the new gallery and multiple social accounts features in your HYLS installation.

---

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx with mod_rewrite
- Writable `uploads/` directory
- GD or ImageMagick extension (optional, for image optimization)

---

## 🆕 Fresh Installation

### Step 1: Clone/Download Repository

```bash
git clone https://github.com/david0154/hyls.git
cd hyls
```

### Step 2: Configure Web Server

**Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

**Nginx**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### Step 3: Set Permissions

```bash
chmod 755 uploads/
chown -R www-data:www-data uploads/

# Create gallery directory
mkdir -p uploads/bio/gallery
chmod 755 uploads/bio/gallery
```

### Step 4: Run Installer

1. Navigate to: `http://yoursite.com/install.php`
2. Fill in database credentials
3. Create admin account
4. Click "Install"
5. Wait for success message

**All tables created automatically:**
- ✅ `users`
- ✅ `short_links`
- ✅ `bio_links`
- ✅ `bio_gallery` (NEW)
- ✅ `bio_social_accounts` (NEW)
- ✅ `bio_custom_links` (NEW)
- ✅ `analytics`
- ✅ `advertisements`
- ✅ `settings`

### Step 5: Verify Installation

```bash
# Check database tables
mysql -u your_user -p your_database -e "SHOW TABLES;"

# Should see:
# bio_gallery
# bio_social_accounts
# bio_custom_links
```

---

## 🔄 Upgrading Existing Installation

### Step 1: Backup Everything

```bash
# Backup database
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# Backup files
tar -czf hyls_backup_$(date +%Y%m%d).tar.gz /path/to/hyls/
```

### Step 2: Pull Latest Changes

```bash
cd /path/to/hyls
git fetch origin
git checkout feature/gallery-multiple-socials

# Or merge pull request #4 on GitHub
```

### Step 3: Run Auto-Migration

**Option A: Via Web Interface**
1. Navigate to: `http://yoursite.com/install.php?mode=repair`
2. Fill in existing database credentials
3. Click "Repair & Migrate"
4. Wait for success message

**Option B: Via Command Line**
```bash
php install/migrate.php
```

### Step 4: Verify Migration

```bash
# Check new tables exist
mysql -u user -p database -e "SHOW TABLES LIKE 'bio_%';"

# Should see:
# bio_links (existing)
# bio_gallery (NEW)
# bio_social_accounts (NEW)
# bio_custom_links (NEW)
```

### Step 5: Test Features

1. Login to dashboard
2. Navigate to Bio Link editor
3. Upload test image to gallery
4. Add test social account
5. Verify on public bio page

---

## 📦 Manual Migration (Alternative)

If auto-migration fails, run SQL manually:

### Create Gallery Table

```sql
CREATE TABLE IF NOT EXISTS bio_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    image_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_order (image_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Create Social Accounts Table

```sql
CREATE TABLE IF NOT EXISTS bio_social_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
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
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Create Custom Links Table

```sql
CREATE TABLE IF NOT EXISTS bio_custom_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
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
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Verify Tables

```sql
DESCRIBE bio_gallery;
DESCRIBE bio_social_accounts;
DESCRIBE bio_custom_links;
```

---

## ⚙️ Configuration

### PHP Settings

Edit `php.ini` or `.htaccess`:

```ini
; Upload limits
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 10

; Memory limit
memory_limit = 256M

; Execution time
max_execution_time = 300
```

### Apache .htaccess

```apache
# Upload limits
php_value upload_max_filesize 10M
php_value post_max_size 10M

# Security
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

---

## 🔐 Security Hardening

### 1. Protect Upload Directory

```apache
# uploads/.htaccess
<FilesMatch "\.(php|php5|php7|phtml|phar)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
```

### 2. File Permissions

```bash
# Set correct permissions
find uploads/ -type d -exec chmod 755 {} \;
find uploads/ -type f -exec chmod 644 {} \;

# Prevent execution
chmod 644 uploads/bio/gallery/*
```

### 3. Database User

```sql
-- Create dedicated database user with limited permissions
CREATE USER 'hyls_app'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON hyls_db.* TO 'hyls_app'@'localhost';
FLUSH PRIVILEGES;
```

---

## 🐛 Troubleshooting

### Issue: "Failed to create directory"

**Cause:** Permission denied

**Solution:**
```bash
chmod 755 uploads/
chown -R www-data:www-data uploads/
```

### Issue: "Upload failed"

**Cause:** PHP upload limits

**Solution:**
```ini
; Check current limits
php -i | grep upload_max_filesize

; Increase in php.ini
upload_max_filesize = 10M
post_max_size = 10M

; Restart web server
sudo systemctl restart apache2
```

### Issue: "Table doesn't exist"

**Cause:** Migration didn't run

**Solution:**
```bash
# Run migration again
php install/migrate.php

# Or visit
http://yoursite.com/install.php?mode=repair
```

### Issue: "Foreign key constraint fails"

**Cause:** Users table missing or incompatible

**Solution:**
```sql
-- Check users table exists
SHOW TABLES LIKE 'users';

-- Check structure
DESCRIBE users;

-- Recreate if needed
DROP TABLE IF EXISTS bio_gallery;
DROP TABLE IF EXISTS bio_social_accounts;
-- Then run migration again
```

---

## ✅ Post-Installation Checklist

- [ ] All database tables created
- [ ] Upload directories exist with correct permissions
- [ ] PHP upload limits configured
- [ ] Web server configured (rewrite rules)
- [ ] SSL certificate installed (recommended)
- [ ] Backup system in place
- [ ] Error logging enabled
- [ ] Gallery upload works
- [ ] Social account creation works
- [ ] Public bio page displays correctly
- [ ] Mobile responsive design verified

---

## 📊 Monitoring

### Check Error Logs

```bash
# PHP error log
tail -f /var/log/apache2/error.log

# Application log
tail -f logs/app.log
```

### Database Statistics

```sql
-- Check table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'your_database'
ORDER BY (data_length + index_length) DESC;

-- Check row counts
SELECT COUNT(*) FROM bio_gallery;
SELECT COUNT(*) FROM bio_social_accounts;
```

---

## 🚀 Performance Optimization

### Enable Caching

```php
// config.php
define('ENABLE_CACHE', true);
define('CACHE_DURATION', 3600); // 1 hour
```

### Database Indexing

```sql
-- Add indexes for performance
CREATE INDEX idx_user_active ON bio_social_accounts(user_id, is_active);
CREATE INDEX idx_user_order ON bio_gallery(user_id, image_order);
```

### CDN Setup (Optional)

```php
// config.php
define('CDN_URL', 'https://cdn.yoursite.com');
define('USE_CDN', true);

// Use in templates
$image_url = USE_CDN ? CDN_URL . $path : $path;
```

---

## 📞 Support

If you encounter issues:

1. Check [Troubleshooting](#troubleshooting) section
2. Review error logs
3. Search [GitHub Issues](https://github.com/david0154/hyls/issues)
4. Create new issue with:
   - Error message
   - PHP version
   - Database version
   - Steps to reproduce

---

## 🎉 Success!

Your HYLS installation now has:
- ✅ 6-image gallery
- ✅ Multiple social accounts per platform
- ✅ Fixed checkbox toggles
- ✅ Click tracking
- ✅ Mobile-responsive design

**Next Steps:**
- Customize theme colors
- Add your social accounts
- Upload gallery images
- Share your bio link!

---

**Last Updated:** January 8, 2026  
**Version:** 1.0.0  
**Author:** David Studioz