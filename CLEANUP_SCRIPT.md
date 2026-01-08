# 🧹 HYLS Repository Cleanup Script

## Project Structure After Cleanup

```
hyls/
├── 📁 admin/              # Admin panel files
├── 📁 assets/             # CSS, JS, images, favicon
├── 📁 docs/               # Documentation files
├── 📁 includes/           # Core PHP classes
│   ├── db.php
│   ├── functions.php
│   ├── image_processor.php
│   ├── mailer.php
│   └── video_embed.php
├── 📁 install/            # Installation scripts & SQL files
│   ├── install.php
│   ├── create_gallery_table.sql
│   ├── fix_cover_image.sql
│   └── social_videos_table.sql
├── 📁 uploads/            # User uploaded files (auto-created)
│   ├── bio/
│   └── qr/
├── 📄 .htaccess          # Apache configuration
├── 📄 ad-page.php        # Advertisement management
├── 📄 auth.php           # Authentication handler
├── 📄 bio.php            # Public bio page display
├── 📄 biolink.php        # Bio link management (admin)
├── 📄 config.sample.php  # Sample configuration file
├── 📄 dashboard.php      # User dashboard
├── 📄 delete_link.php    # Link deletion handler
├── 📄 edit_bio.php       # Bio editing interface
├── 📄 google-auth.php    # Google OAuth handler
├── 📄 index.php          # Landing page
├── 📄 LICENSE            # MIT License
├── 📄 login.php          # Login page
├── 📄 logout.php         # Logout handler
├── 📄 r.php              # URL redirect handler
├── 📄 README.md          # Main documentation
├── 📄 shorten.php        # URL shortening handler
├── 📄 update.php         # Database update script
└── 📄 UPGRADE_INSTRUCTIONS.md
```

## 🗑️ Files to Delete

### Duplicate Biolink Files (Keep only `biolink.php`)
```bash
# Already deleted:
# ✅ biolink_COMPLETE.php
# ✅ biolink_PATCHED.php
# ✅ biolink_ULTIMATE.php

# Still to delete:
rm biolink_enhanced.php
rm bio_with_videos.php
rm biolink_video.php
```

### Duplicate Edit Files
```bash
rm edit_bio_enhanced.php
```

### Duplicate README Files
```bash
rm readme.md  # Keep README.md (uppercase)
rm README_BIOLINK_COMPLETE.md
rm README_ENHANCEMENTS.md
```

### Outdated Documentation
```bash
rm BIOLINK_CROP_VIDEO_GUIDE.md
rm CLEANUP_GUIDE.md
rm DATABASE_MIGRATION_GUIDE.md
rm INSTALL_CROP_VIDEO.md
rm QUICK_FIX_LIVE.md
```

### Temporary/Setup Files (One-time use)
```bash
rm create_assets.php
rm fix_database.php
rm migrate.php
rm setup_gallery.php
rm setup_gallery_simple.php
```

### Move SQL Files to Install Folder
```bash
# Move these files:
mv create_gallery_table.sql install/
mv fix_cover_image.sql install/
mv social_videos_table.sql install/
```

## 🔧 Manual Cleanup Steps

### Step 1: Delete Duplicate Files
```bash
# Clone the repository
git clone https://github.com/david0154/hyls.git
cd hyls

# Delete duplicate biolink files
git rm biolink_enhanced.php bio_with_videos.php biolink_video.php

# Delete duplicate edit files
git rm edit_bio_enhanced.php

# Delete duplicate README files
git rm readme.md README_BIOLINK_COMPLETE.md README_ENHANCEMENTS.md

# Delete outdated documentation
git rm BIOLINK_CROP_VIDEO_GUIDE.md CLEANUP_GUIDE.md DATABASE_MIGRATION_GUIDE.md
git rm INSTALL_CROP_VIDEO.md QUICK_FIX_LIVE.md

# Delete temporary setup files
git rm create_assets.php fix_database.php migrate.php
git rm setup_gallery.php setup_gallery_simple.php

# Commit deletion
git commit -m "Clean up repository - remove duplicate and outdated files"
git push origin main
```

### Step 2: Organize SQL Files
```bash
# Create install directory if it doesn't exist
mkdir -p install

# Move SQL files
git mv create_gallery_table.sql install/
git mv fix_cover_image.sql install/
git mv social_videos_table.sql install/

# Move install.php if needed
git mv install.php install/

# Commit changes
git commit -m "Organize SQL and installation files into install directory"
git push origin main
```

### Step 3: Create .gitignore
```bash
# Create .gitignore file
cat > .gitignore << 'EOF'
# Configuration files
config.php

# User uploads
uploads/*
!uploads/.gitkeep

# Log files
*.log
error_log

# System files
.DS_Store
Thumbs.db

# IDE files
.vscode/
.idea/
*.swp
*.swo
*~

# Temporary files
*.tmp
*.bak
*.backup

# Composer
/vendor/
composer.lock
EOF

# Create .gitkeep for uploads directory
mkdir -p uploads/bio uploads/qr
touch uploads/.gitkeep
touch uploads/bio/.gitkeep
touch uploads/qr/.gitkeep

# Commit
git add .gitignore uploads/.gitkeep uploads/bio/.gitkeep uploads/qr/.gitkeep
git commit -m "Add .gitignore and preserve upload directories"
git push origin main
```

## 📋 Updated File Count

### Before Cleanup: ~50+ files
### After Cleanup: ~30 files

**Removed:** 20+ duplicate/outdated files
**Better organized:** SQL files moved to `/install`
**Added:** .gitignore for security

## 🚀 Benefits of Cleanup

1. ✅ **Cleaner Repository** - Easier to navigate
2. ✅ **Reduced Confusion** - No duplicate files
3. ✅ **Better Organization** - Logical file structure
4. ✅ **Improved Security** - .gitignore prevents config exposure
5. ✅ **Professional Structure** - Industry-standard layout
6. ✅ **Easier Maintenance** - Clear purpose for each file
7. ✅ **Faster Cloning** - Smaller repository size

## 📝 Core Files (Keep These)

### Essential PHP Files
- `index.php` - Landing page
- `login.php` - User authentication
- `dashboard.php` - User dashboard
- `biolink.php` - Bio link management
- `bio.php` - Public bio display
- `r.php` - URL redirection
- `shorten.php` - URL shortening
- `auth.php` - Auth handler
- `google-auth.php` - OAuth handler

### Configuration & Setup
- `config.sample.php` - Configuration template
- `install.php` - Initial installation
- `update.php` - Database updates

### Documentation
- `README.md` - Main documentation
- `LICENSE` - MIT License
- `UPGRADE_INSTRUCTIONS.md` - Upgrade guide

### Directories
- `admin/` - Admin panel
- `assets/` - Static resources
- `includes/` - Core classes
- `docs/` - Documentation
- `install/` - Installation files

## ⚠️ Important Notes

1. **Backup First**: Always backup before cleanup
2. **Test After**: Verify everything works after cleanup
3. **Config Safety**: Never commit `config.php` with real credentials
4. **Upload Safety**: Keep uploads directory structure

## 🔄 Automated Cleanup Script (Bash)

```bash
#!/bin/bash
# cleanup.sh - Automated repository cleanup

echo "🧹 Starting HYLS Repository Cleanup..."

# Delete duplicate biolink files
echo "Removing duplicate biolink files..."
git rm -f biolink_enhanced.php bio_with_videos.php biolink_video.php 2>/dev/null

# Delete duplicate edit files
echo "Removing duplicate edit files..."
git rm -f edit_bio_enhanced.php 2>/dev/null

# Delete duplicate README files
echo "Removing duplicate README files..."
git rm -f readme.md README_BIOLINK_COMPLETE.md README_ENHANCEMENTS.md 2>/dev/null

# Delete outdated documentation
echo "Removing outdated documentation..."
git rm -f BIOLINK_CROP_VIDEO_GUIDE.md CLEANUP_GUIDE.md DATABASE_MIGRATION_GUIDE.md 2>/dev/null
git rm -f INSTALL_CROP_VIDEO.md QUICK_FIX_LIVE.md 2>/dev/null

# Delete temporary files
echo "Removing temporary setup files..."
git rm -f create_assets.php fix_database.php migrate.php 2>/dev/null
git rm -f setup_gallery.php setup_gallery_simple.php 2>/dev/null

# Create install directory
echo "Organizing installation files..."
mkdir -p install

# Move SQL files
if [ -f "create_gallery_table.sql" ]; then
    git mv create_gallery_table.sql install/ 2>/dev/null
fi
if [ -f "fix_cover_image.sql" ]; then
    git mv fix_cover_image.sql install/ 2>/dev/null
fi
if [ -f "social_videos_table.sql" ]; then
    git mv social_videos_table.sql install/ 2>/dev/null
fi

# Create .gitignore if it doesn't exist
if [ ! -f ".gitignore" ]; then
    echo "Creating .gitignore..."
    cat > .gitignore << 'EOF'
config.php
uploads/*
!uploads/.gitkeep
*.log
error_log
.DS_Store
Thumbs.db
.vscode/
.idea/
*.swp
*.swo
*~
*.tmp
*.bak
*.backup
/vendor/
composer.lock
EOF
    git add .gitignore
fi

# Create upload directories
echo "Setting up upload directories..."
mkdir -p uploads/bio uploads/qr
touch uploads/.gitkeep uploads/bio/.gitkeep uploads/qr/.gitkeep
git add uploads/.gitkeep uploads/bio/.gitkeep uploads/qr/.gitkeep 2>/dev/null

# Commit all changes
echo "Committing cleanup changes..."
git commit -m "Repository cleanup: remove duplicates, organize structure, add .gitignore" 2>/dev/null

echo "✅ Cleanup complete!"
echo "📊 Run 'git status' to review changes"
echo "🚀 Run 'git push origin main' to upload changes"
```

## 📞 Support

If you encounter issues during cleanup:
1. Check git status: `git status`
2. Review changes: `git log --oneline`
3. Restore if needed: `git reset --hard HEAD~1`

---

**Last Updated:** January 8, 2026
**Maintained By:** David Studioz
