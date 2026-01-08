# ⚡ Quick Fix: Add Crop & Video to LIVE biolink.php

## 🔴 Problem
- biolink.php doesn't show crop option after upload
- No video management section
- biolink_enhanced.php has database errors

## ✅ Solution
Run these SQL commands to add missing features, then replace biolink.php

---

## Step 1: Run SQL (via phpMyAdmin or MySQL command)

```sql
-- Add crop columns to bio_gallery (if missing)
ALTER TABLE bio_gallery 
ADD COLUMN IF NOT EXISTS crop_x INT DEFAULT 0 AFTER image_order,
ADD COLUMN IF NOT EXISTS crop_y INT DEFAULT 0 AFTER crop_x,
ADD COLUMN IF NOT EXISTS crop_width INT DEFAULT NULL AFTER crop_y,
ADD COLUMN IF NOT EXISTS crop_height INT DEFAULT NULL AFTER crop_width,
ADD COLUMN IF NOT EXISTS original_width INT DEFAULT NULL AFTER crop_height,
ADD COLUMN IF NOT EXISTS original_height INT DEFAULT NULL AFTER original_width;

-- Create video table (using user_id directly - no bio_profiles needed)
CREATE TABLE IF NOT EXISTS bio_social_videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  platform VARCHAR(50) NOT NULL,
  video_url TEXT NOT NULL,
  embed_code TEXT NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  thumbnail_url VARCHAR(500) DEFAULT NULL,
  display_order INT DEFAULT 0,
  autoplay TINYINT(1) DEFAULT 1,
  views INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_platform (platform),
  INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create uploads/images directory (run this on server)
-- mkdir -p uploads/images && chmod 755 uploads/images
```

---

## Step 2: Download and Replace biolink.php

### Option A: Download from GitHub
```bash
# Backup current file
cp biolink.php biolink.php.backup.$(date +%Y%m%d)

# Download patched version
wget https://raw.githubusercontent.com/david0154/hyls/main/biolink_PATCHED.php -O biolink.php

# Or use curl
curl https://raw.githubusercontent.com/david0154/hyls/main/biolink_PATCHED.php -o biolink.php
```

### Option B: Manual Replace
1. Download [biolink_PATCHED.php](https://github.com/david0154/hyls/blob/main/biolink_PATCHED.php)
2. Rename to `biolink.php`
3. Upload to your server (replace existing)

---

## Step 3: Test

1. **Go to**: https://hyls.space/biolink.php
2. **Upload an image** (12MB max now!)
3. **See crop tool** appear below gallery
4. **Scroll down** to see "Social Media Videos" section
5. **Add a video** (YouTube, Facebook, Instagram, TikTok, etc.)

---

## 🎯 Features Added

### Image Crop Tool
- Visual canvas-based editor
- Click & drag to select area
- Real-time coordinates
- Reset button
- Saves to database
- **12MB file limit** (up from 5MB)

### Video Management
- Add videos from:
  - ✅ YouTube
  - ✅ Facebook
  - ✅ Instagram
  - ✅ TikTok
  - ✅ Vimeo
  - ✅ Dailymotion
- Video title & description
- Autoplay toggle
- Delete videos
- Video preview in dashboard
- Display order management

---

## 🛡️ Safety Features

### Zero Breaking Changes:
1. **Checks if columns exist** before using them
2. **Checks if video table exists** before showing section
3. **Falls back gracefully** if features not available
4. **Keeps all existing code** untouched
5. **Works with current bio_links table**

### What if SQL fails?
- Crop tool won't show (but upload still works)
- Video section won't show (but social links work)
- Everything else functions normally

---

## 📝 Verify Installation

### Check Crop Columns:
```sql
SHOW COLUMNS FROM bio_gallery LIKE 'crop_%';
```
Should show: `crop_x`, `crop_y`, `crop_width`, `crop_height`

### Check Video Table:
```sql
SHOW TABLES LIKE 'bio_social_videos';
```
Should return: `bio_social_videos`

### Check Directory:
```bash
ls -la uploads/images
```
Should exist with 755 permissions

---

## 🐛 Troubleshooting

### Crop tool not showing?
**Cause**: Crop columns missing
**Fix**: Run Step 1 SQL again

### Video section not showing?
**Cause**: Table not created
**Fix**: Run Step 1 SQL again

### Upload fails with "12MB too large"?
**Cause**: PHP upload limits
**Fix**: Edit `php.ini`:
```ini
upload_max_filesize = 12M
post_max_size = 13M
```

### "Column 'user_id' doesn't exist in bio_social_videos"?
**Cause**: Old migration used `bio_profile_id`
**Fix**: Run this:
```sql
ALTER TABLE bio_social_videos 
ADD COLUMN user_id INT NOT NULL AFTER id,
ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
```

---

## 🚀 Expected Behavior

### After Image Upload:
1. Image appears in gallery grid
2. **NEW**: "Crop Images" section appears below
3. Select image from dropdown
4. Canvas loads with image
5. Click & drag to crop
6. Click "Save Crop Settings"

### Video Section:
1. New card appears: "Social Media Videos"
2. Shows existing videos (if any)
3. Form to add new video:
   - Platform dropdown
   - Video URL input
   - Title (optional)
   - Description (optional)
   - Autoplay checkbox
4. Click "Add Video"
5. Video appears with embed preview
6. Delete button on each video

---

## 🔗 URLs After Install

**Dashboard**: https://hyls.space/biolink.php
**Public Bio**: https://hyls.space/bio/USERNAME

Videos will show on public bio automatically!

---

## 📚 Files Modified

### NO changes needed to:
- `bio.php` (public view)
- Database structure (only additions)
- Any other files

### ONLY changed:
- `biolink.php` (replaced with patched version)

---

## ⚡ One-Command Install

```bash
# Backup, download SQL, run migration, replace file
cp biolink.php biolink.php.backup && \
mysql -u YOUR_USER -p YOUR_DATABASE < <(cat << 'EOF'
ALTER TABLE bio_gallery 
ADD COLUMN IF NOT EXISTS crop_x INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS crop_y INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS crop_width INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS crop_height INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS original_width INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS original_height INT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS bio_social_videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  platform VARCHAR(50) NOT NULL,
  video_url TEXT NOT NULL,
  embed_code TEXT NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  thumbnail_url VARCHAR(500) DEFAULT NULL,
  display_order INT DEFAULT 0,
  autoplay TINYINT(1) DEFAULT 1,
  views INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
EOF
) && \
mkdir -p uploads/images && chmod 755 uploads/images && \
wget https://raw.githubusercontent.com/david0154/hyls/main/biolink_PATCHED.php -O biolink.php

echo "✅ Installation complete! Visit https://hyls.space/biolink.php"
```

---

## 📞 Support

If issues persist:
1. Check error logs: `/var/log/apache2/error.log` or `tail -f storage/logs/laravel.log`
2. Enable debug in biolink.php (already enabled)
3. Check browser console for JavaScript errors
4. Verify database columns exist

---

**💜 Made by David Studioz**
**✅ Zero Downtime | ✅ No Breaking Changes | ✅ Safe for Live**
