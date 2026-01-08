# 🧹 HYLS Cleanup Guide

This guide lists unused and redundant files that should be removed from the codebase for better organization.

## 🗑️ Files to Remove

### Duplicate/Redundant Files

1. **biolink_fixed_cover.php**
   - Duplicate of bio.php functionality
   - Was created during cover image fix testing
   - Safe to delete

2. **readme.md** (lowercase)
   - Duplicate of README.md
   - GitHub recognizes README.md (uppercase)
   - Safe to delete

### Debug/Test Files

3. **check_cover_upload.php**
   - Debug file for testing cover uploads
   - Not needed in production
   - Safe to delete

4. **fix_database.php**
   - One-time database fix script
   - Functionality now in install.php migrations
   - Safe to delete after running once

### Setup/Migration Files (Keep or Remove Based on Usage)

5. **create_assets.php**
   - One-time asset directory creator
   - Safe to delete after initial setup

6. **setup_gallery.php**
   - Gallery setup script
   - Functionality now in install.php
   - Safe to delete

7. **setup_gallery_simple.php**
   - Simplified gallery setup
   - Redundant with install.php
   - Safe to delete

### Raw SQL Files (Move to docs/ or remove)

8. **create_gallery_table.sql**
   - SQL for gallery table
   - Already in install.php schema
   - Move to docs/ or delete

9. **fix_cover_image.sql**
   - SQL patch for cover image
   - Already in install.php migrations
   - Move to docs/ or delete

## ✅ Safe Removal Commands

### Remove All Unused Files at Once

```bash
# Navigate to your hyls directory
cd /path/to/hyls

# Remove duplicate functionality
rm -f biolink_fixed_cover.php
rm -f readme.md

# Remove debug/test files
rm -f check_cover_upload.php
rm -f fix_database.php

# Remove setup scripts (if already installed)
rm -f create_assets.php
rm -f setup_gallery.php
rm -f setup_gallery_simple.php

# Move SQL files to docs or remove
mkdir -p docs/sql
mv create_gallery_table.sql docs/sql/
mv fix_cover_image.sql docs/sql/
# OR delete them: rm -f create_gallery_table.sql fix_cover_image.sql

# Commit changes
git add .
git commit -m "Clean up unused and duplicate files"
git push
```

### Remove Files One by One (Safer)

```bash
# Duplicate files
git rm biolink_fixed_cover.php
git commit -m "Remove duplicate biolink_fixed_cover.php"

git rm readme.md
git commit -m "Remove duplicate readme.md (keeping README.md)"

# Debug files
git rm check_cover_upload.php
git commit -m "Remove debug file check_cover_upload.php"

git rm fix_database.php
git commit -m "Remove fix_database.php (functionality in install.php)"

# Setup files
git rm create_assets.php setup_gallery.php setup_gallery_simple.php
git commit -m "Remove redundant setup scripts"

# SQL files
git rm create_gallery_table.sql fix_cover_image.sql
git commit -m "Remove raw SQL files (schemas in install.php)"

git push
```

## 📝 Files to Keep

### Essential Core Files
- `install.php` - Installation wizard with all migrations
- `bio.php` - Bio link display (NOW FIXED)
- `biolink.php` - Bio link editor/creator
- `edit_bio.php` - Bio link editing
- `r.php` - Link redirect handler
- `dashboard.php` - User dashboard
- `index.php` - Homepage
- `login.php` - Login page
- `auth.php` - HypeChats OAuth
- `google-auth.php` - Google OAuth
- `update.php` - Auto-update system
- `migrate.php` - Database migration runner

### Configuration
- `config.php` - Main configuration (generated)
- `config.sample.php` - Sample configuration
- `.htaccess` - Apache rewrite rules

### Directories
- `admin/` - Admin panel
- `assets/` - CSS, JS, images
- `includes/` - Core PHP classes
- `uploads/` - User uploads
- `docs/` - Documentation

## ⚠️ Important Notes

1. **Before Deleting:**
   - Make sure you have backups
   - Check if any custom code references these files
   - Test your site after removal

2. **After Deleting:**
   - Clear any PHP opcache: `service php-fpm restart`
   - Test all major features
   - Check error logs for missing file warnings

3. **SQL Files:**
   - Consider moving to `docs/sql/` for reference
   - Don't delete if you haven't run migrations yet

## ✅ Verification

After cleanup, verify your site works:

```bash
# Check for broken links
grep -r "biolink_fixed_cover" .
grep -r "check_cover_upload" .
grep -r "fix_database" .
grep -r "setup_gallery" .

# If no results, files aren't referenced - safe to delete!
```

## 🚀 Next Steps

After cleanup:
1. Test all features
2. Check bio pages work correctly
3. Verify links are properly blocked when disabled
4. Confirm ads display on bio pages
5. Test admin panel functionality

---

**Created:** January 8, 2026  
**Version:** 1.0  
**Part of:** HYLS Comprehensive Fixes
