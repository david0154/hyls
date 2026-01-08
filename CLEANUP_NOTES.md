# HYLS - Code Cleanup Notes

## Files Recommended for Removal

These files are unused, redundant, or development artifacts that should be removed to clean up the codebase:

### Duplicate/Redundant Files

1. **biolink_fixed_cover.php**
   - Reason: Duplicate functionality of bio.php
   - Action: DELETE - Use bio.php instead

2. **readme.md** (lowercase)
   - Reason: Duplicate of README.md
   - Action: DELETE - Keep README.md only

### Debug/Development Files

3. **check_cover_upload.php**
   - Reason: Debug/test file for cover image uploads
   - Action: DELETE - Development testing file

4. **create_assets.php**
   - Reason: One-time setup script
   - Action: DELETE - Not needed after initial setup

5. **fix_database.php**
   - Reason: Debug/fix utility
   - Action: DELETE - Use install.php migration instead

### Raw SQL Files (Use Migrations Instead)

6. **create_gallery_table.sql**
   - Reason: Raw SQL - should be in migration system
   - Action: DELETE - Already in install.php migrations

7. **fix_cover_image.sql**
   - Reason: Raw SQL patch file
   - Action: DELETE - Already in install.php migrations

### Redundant Setup Files

8. **setup_gallery.php**
   - Reason: Gallery setup is handled by install.php
   - Action: DELETE - Use install.php instead

9. **setup_gallery_simple.php**
   - Reason: Duplicate of setup_gallery.php
   - Action: DELETE - Use install.php instead

## Recommended Cleanup Command

```bash
# Remove all unnecessary files
rm -f biolink_fixed_cover.php \
      readme.md \
      check_cover_upload.php \
      create_assets.php \
      fix_database.php \
      create_gallery_table.sql \
      fix_cover_image.sql \
      setup_gallery.php \
      setup_gallery_simple.php
```

## Why This Cleanup Matters

- **Security**: Removes potential debug endpoints
- **Maintenance**: Easier to navigate codebase
- **Confusion**: Prevents using outdated files
- **Repository Size**: Smaller, cleaner repo

## Migration Strategy

All database changes should go through:
- `install.php` - For fresh installations
- `install.php?mode=migrate` - For updating existing databases

This ensures:
- ✅ Version controlled schema changes
- ✅ Idempotent operations (safe to run multiple times)
- ✅ Automatic migration tracking
- ✅ No manual SQL file execution needed

## After Cleanup

Your clean file structure:
```
hyls/
├── admin/
├── assets/
├── includes/
├── install/
├── uploads/
├── auth.php
├── bio.php          # Single bio page (fixed version)
├── config.php
├── dashboard.php
├── edit_bio.php
├── index.php
├── install.php      # Handles all migrations
├── login.php
├── r.php
├── shorten.php
└── README.md        # Single README (uppercase)
```

---

**Date:** January 2026  
**Author:** David Studioz  
**Purpose:** Code quality and security improvement
