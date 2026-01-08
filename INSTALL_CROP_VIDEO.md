# 🚀 Add Crop Tool & Video Management to Live biolink.php

## ✅ What This Adds

**Image Crop Tool:**
- Visual canvas-based crop editor
- Click & drag to select area
- Real-time coordinates display
- Reset to full image
- Saves crop settings to database
- **12MB upload limit** (up from 5MB)

**Video Management:**
- Add videos from YouTube, Facebook, Instagram, TikTok, Vimeo, Dailymotion
- Video title & description
- Autoplay toggle
- Delete videos
- Video preview in dashboard
- Display order management

**Your Existing Features (Already Working):**
- ✅ Profile Picture Upload
- ✅ Cover Image Upload  
- ✅ 6 Image Gallery
- ✅ 29 Social Platforms
- ✅ Theme Color

---

## 📋 Step 1: Run Migration (NO phpMyAdmin needed!)

**Visit this URL in your browser:**
```
https://hyls.space/install.php?mode=migrate
```

**Click:** "✨ Update Database Now (Auto-Migration)"

This will add:
- `crop_x`, `crop_y`, `crop_width`, `crop_height` columns to `bio_gallery`
- `original_width`, `original_height` columns
- `bio_social_videos` table with `user_id` column
- `uploads/images` directory

**Safe to run multiple times!** Only adds missing features.

---

## 📥 Step 2: Download Complete File

I've prepared your complete file here:

**Download:** [biolink_COMPLETE.php](https://github.com/david0154/hyls/blob/main/biolink_COMPLETE.php)

OR use command line:
```bash
wget https://raw.githubusercontent.com/david0154/hyls/main/biolink_COMPLETE.php
```

---

## 🔄 Step 3: Backup Current File

```bash
cp biolink.php biolink.php.backup_$(date +%Y%m%d_%H%M%S)
```

---

## 📝 Step 4: Add NEW Code to Your biolink.php

Since the file is complete and working, I'll show you EXACTLY what to add:

### A. After line 50 (after `$bio = $stmt->fetch();`), ADD:

```php
// NEW: Check if crop columns exist
$has_crop_columns = false;
try {
    $stmt = $db->query("SHOW COLUMNS FROM bio_gallery LIKE 'crop_x'");
    $has_crop_columns = ($stmt->rowCount() > 0);
} catch (Exception $e) {
    error_log("Crop columns check: " . $e->getMessage());
}

// NEW: Check if video table exists
$has_video_table = false;
try {
    $stmt = $db->query("SHOW TABLES LIKE 'bio_social_videos'");
    $has_video_table = ($stmt->rowCount() > 0);
} catch (Exception $e) {
    error_log("Video table check: " . $e->getMessage());
}
```

### B. After `$gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);`, ADD:

```php
// NEW: Get social videos (if table exists)
$social_videos = [];
if ($has_video_table) {
    try {
        $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE user_id = ? ORDER BY display_order ASC");
        $stmt->execute([$user_id]);
        $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Social videos error: " . $e->getMessage());
    }
}
```

### C. Before `// Handle gallery delete`, ADD:

```php
// NEW: Handle video delete
if (isset($_GET['delete_video']) && $has_video_table) {
    $video_id = (int)$_GET['delete_video'];
    try {
        $stmt = $db->prepare("DELETE FROM bio_social_videos WHERE id = ? AND user_id = ?");
        $stmt->execute([$video_id, $user_id]);
        $success = 'Video deleted successfully!';
        header('Location: biolink.php');
        exit;
    } catch (Exception $e) {
        $error = 'Delete failed: ' . $e->getMessage();
    }
}

// NEW: Handle video add
if (isset($_POST['add_video']) && $has_video_table) {
    $platform = $_POST['video_platform'] ?? '';
    $video_url = trim($_POST['video_url'] ?? '');
    $video_title = $_POST['video_title'] ?? '';
    $video_desc = $_POST['video_description'] ?? '';
    $autoplay = isset($_POST['autoplay']) ? 1 : 0;
    
    if (!empty($video_url)) {
        $embed_code = '';
        $thumbnail = '';
        
        if ($platform === 'youtube') {
            preg_match('/(?:youtube\\.com\\/watch\\?v=|youtu\\.be\\/)([^&?]+)/', $video_url, $matches);
            if (isset($matches[1])) {
                $video_id_match = $matches[1];
                $autoplay_param = $autoplay ? '1' : '0';
                $embed_code = '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $video_id_match . '?autoplay=' . $autoplay_param . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                $thumbnail = 'https://img.youtube.com/vi/' . $video_id_match . '/maxresdefault.jpg';
            }
        } elseif ($platform === 'facebook') {
            $embed_code = '<iframe src="https://www.facebook.com/plugins/video.php?href=' . urlencode($video_url) . '&show_text=0&width=560" width="100%" height="100%" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>';
        } elseif ($platform === 'instagram') {
            $embed_code = '<iframe src="' . $video_url . 'embed" width="100%" height="100%" frameborder="0" scrolling="no" allowtransparency="true"></iframe>';
        } elseif ($platform === 'tiktok') {
            preg_match('/video\\/(\\d+)/', $video_url, $matches);
            if (isset($matches[1])) {
                $embed_code = '<iframe src="https://www.tiktok.com/embed/' . $matches[1] . '" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>';
            }
        } elseif ($platform === 'vimeo') {
            preg_match('/vimeo\\.com\\/(\\d+)/', $video_url, $matches);
            if (isset($matches[1])) {
                $embed_code = '<iframe src="https://player.vimeo.com/video/' . $matches[1] . '?autoplay=' . $autoplay . '" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
            }
        } elseif ($platform === 'dailymotion') {
            preg_match('/dailymotion\\.com\\/video\\/([^_]+)/', $video_url, $matches);
            if (isset($matches[1])) {
                $embed_code = '<iframe frameborder="0" width="100%" height="100%" src="https://www.dailymotion.com/embed/video/' . $matches[1] . '?autoplay=' . $autoplay . '" allowfullscreen allow="autoplay"></iframe>';
            }
        }
        
        if ($embed_code) {
            try {
                $stmt = $db->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM bio_social_videos WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_order = $order_result['max_order'] + 1;
                
                $stmt = $db->prepare("INSERT INTO bio_social_videos (user_id, platform, video_url, embed_code, title, description, thumbnail_url, display_order, autoplay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $platform, $video_url, $embed_code, $video_title, $video_desc, $thumbnail, $new_order, $autoplay]);
                $success = 'Video added successfully!';
                header('Location: biolink.php');
                exit;
            } catch (Exception $e) {
                $error = 'Failed to add video: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid video URL for selected platform';
        }
    }
}

// NEW: Handle image crop save
if (isset($_POST['save_crop']) && $has_crop_columns) {
    $image_id = (int)$_POST['image_id'];
    $crop_x = (int)$_POST['crop_x'];
    $crop_y = (int)$_POST['crop_y'];
    $crop_width = (int)$_POST['crop_width'];
    $crop_height = (int)$_POST['crop_height'];
    
    try {
        $stmt = $db->prepare("UPDATE bio_gallery SET crop_x = ?, crop_y = ?, crop_width = ?, crop_height = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$crop_x, $crop_y, $crop_width, $crop_height, $image_id, $user_id]);
        $success = 'Crop settings saved!';
        
        // Refresh gallery
        $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
        $stmt->execute([$user_id]);
        $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Crop save failed: ' . $e->getMessage();
    }
}
```

### D. Change line `if ($files['size'][$i] > 5 * 1024 * 1024)` TO:

```php
// NEW: 12MB limit (was 5MB)
if ($files['size'][$i] > 12 * 1024 * 1024) {
    $errors[] = 'File too large (max 12MB): ' . $files['name'][$i];
    continue;
}
```

### E. Replace the gallery INSERT section with:

```php
// NEW: Add crop columns if available
if ($has_crop_columns) {
    list($width, $height) = @getimagesize($upload_path);
    if (!$width) $width = 1920;
    if (!$height) $height = 1080;
    
    $stmt = $db->prepare("INSERT INTO bio_gallery (user_id, image_url, image_order, crop_x, crop_y, crop_width, crop_height, original_width, original_height, created_at) VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, '/' . $upload_path, $new_order, $width, $height, $width, $height]);
} else {
    $stmt = $db->prepare("INSERT INTO bio_gallery (user_id, image_url, image_order, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, '/' . $upload_path, $new_order]);
}
```

### F. Update MAIN FORM check line from:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['gallery_images']) && !isset($_GET['delete_gallery'])) {
```
TO:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['gallery_images']) && !isset($_GET['delete_gallery']) && !isset($_POST['add_video']) && !isset($_POST['save_crop'])) {
```

---

## 🎨 Step 5: Add HTML Sections

### A. After the Gallery Card `</div>`, BEFORE Bio Link Settings card, ADD:

```html
<!-- Video Management Section -->
<?php if ($has_video_table): ?>
<div class="card">
    <h2><i class="fas fa-video"></i> Social Media Videos (<?= count($social_videos) ?>)</h2>
    
    <?php if ($success && strpos($success, 'Video') !== false): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (count($social_videos) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <?php foreach ($social_videos as $video): ?>
            <div style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; background: white;">
                <div style="aspect-ratio: 16/9; background: #f1f5f9; border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
                    <?= $video['embed_code'] ?>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #1e293b;"><?= ucfirst($video['platform']) ?></strong>
                        <?php if ($video['title']): ?>
                            <br><small style="color: #64748b;"><?= htmlspecialchars($video['title']) ?></small>
                        <?php endif; ?>
                    </div>
                    <a href="?delete_video=<?= $video['id'] ?>" onclick="return confirm('Delete this video?')" 
                       style="background: #ef4444; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 12px;">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: #64748b; text-align: center; margin: 20px 0;">
            <i class="fas fa-info-circle"></i> No videos added yet. Add your first video below!
        </p>
    <?php endif; ?>
    
    <h3 style="color: #6366f1; margin: 30px 0 20px;"><i class="fas fa-plus-circle"></i> Add New Video</h3>
    
    <form method="POST" style="position: relative; z-index: 1;">
        <input type="hidden" name="add_video" value="1">
        
        <div class="form-group">
            <label><i class="fas fa-tv"></i> Video Platform</label>
            <select name="video_platform" required style="width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: white;">
                <option value="">Select Platform</option>
                <option value="youtube">🎥 YouTube</option>
                <option value="facebook">📘 Facebook</option>
                <option value="instagram">📷 Instagram</option>
                <option value="tiktok">🎵 TikTok</option>
                <option value="vimeo">🎬 Vimeo</option>
                <option value="dailymotion">📹 Dailymotion</option>
            </select>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-link"></i> Video URL</label>
            <input type="url" name="video_url" placeholder="https://youtube.com/watch?v=..." required>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Title (Optional)</label>
                <input type="text" name="video_title" placeholder="Video title">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description (Optional)</label>
                <input type="text" name="video_description" placeholder="Short description">
            </div>
        </div>
        
        <div class="toggle-group" style="margin-bottom: 20px;">
            <input type="checkbox" name="autoplay" id="autoplay" value="1" checked>
            <label for="autoplay">Enable Autoplay</label>
        </div>
        
        <button type="submit" class="btn-primary">
            <i class="fas fa-plus-circle"></i> Add Video
        </button>
    </form>
</div>
<?php endif; ?>

<!-- Image Crop Tool -->
<?php if ($has_crop_columns && count($gallery_images) > 0): ?>
<div class="card">
    <h2><i class="fas fa-crop"></i> Crop Gallery Images</h2>
    
    <form method="POST" id="cropForm">
        <input type="hidden" name="save_crop" value="1">
        <input type="hidden" name="image_id" id="cropImageId">
        <input type="hidden" name="crop_x" id="cropX">
        <input type="hidden" name="crop_y" id="cropY">
        <input type="hidden" name="crop_width" id="cropWidth">
        <input type="hidden" name="crop_height" id="cropHeight">
        
        <div class="form-group">
            <label><i class="fas fa-image"></i> Select Image to Crop</label>
            <select id="imageToCrop" style="width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: white;">
                <option value="">Choose an image...</option>
                <?php foreach ($gallery_images as $img): ?>
                    <option value="<?= $img['id'] ?>" 
                            data-url="<?= htmlspecialchars($img['image_url']) ?>"
                            data-crop-x="<?= $img['crop_x'] ?? 0 ?>"
                            data-crop-y="<?= $img['crop_y'] ?? 0 ?>"
                            data-crop-width="<?= $img['crop_width'] ?? 0 ?>"
                            data-crop-height="<?= $img['crop_height'] ?? 0 ?>">
                        Image #<?= $img['id'] ?> (Current: <?= basename($img['image_url']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="cropContainer" style="display: none; margin: 20px 0;">
            <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <canvas id="cropCanvas" style="max-width: 100%; border: 2px solid #6366f1; border-radius: 8px; cursor: crosshair;"></canvas>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
                <div style="background: #f0f9ff; padding: 12px; border-radius: 8px; text-align: center;">
                    <strong style="color: #0369a1;">X:</strong> <span id="displayX">0</span>px
                </div>
                <div style="background: #f0f9ff; padding: 12px; border-radius: 8px; text-align: center;">
                    <strong style="color: #0369a1;">Y:</strong> <span id="displayY">0</span>px
                </div>
                <div style="background: #f0f9ff; padding: 12px; border-radius: 8px; text-align: center;">
                    <strong style="color: #0369a1;">Width:</strong> <span id="displayW">0</span>px
                </div>
                <div style="background: #f0f9ff; padding: 12px; border-radius: 8px; text-align: center;">
                    <strong style="color: #0369a1;">Height:</strong> <span id="displayH">0</span>px
                </div>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="resetCrop()" style="flex: 1; padding: 12px; background: #64748b; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-undo"></i> Reset to Full Image
                </button>
                <button type="submit" style="flex: 2; padding: 12px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save"></i> Save Crop Settings
                </button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>
```

### B. Before `</script>` closing tag at the bottom, ADD:

```javascript
// IMAGE CROP TOOL
let canvas, ctx, img;
let cropStart = null;
let cropEnd = null;
let currentCrop = {x: 0, y: 0, width: 0, height: 0};

document.getElementById('imageToCrop')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (!selected.value) {
        document.getElementById('cropContainer').style.display = 'none';
        return;
    }
    
    document.getElementById('cropImageId').value = selected.value;
    const imageUrl = selected.dataset.url;
    
    canvas = document.getElementById('cropCanvas');
    ctx = canvas.getContext('2d');
    img = new Image();
    
    img.onload = function() {
        const maxWidth = canvas.parentElement.offsetWidth - 40;
        const scale = Math.min(maxWidth / img.width, 600 / img.height, 1);
        
        canvas.width = img.width * scale;
        canvas.height = img.height * scale;
        
        // Load existing crop or full image
        const cropX = parseInt(selected.dataset.cropX) || 0;
        const cropY = parseInt(selected.dataset.cropY) || 0;
        const cropW = parseInt(selected.dataset.cropWidth) || img.width;
        const cropH = parseInt(selected.dataset.cropHeight) || img.height;
        
        currentCrop = {
            x: cropX * scale,
            y: cropY * scale,
            width: cropW * scale,
            height: cropH * scale
        };
        
        drawImage();
        document.getElementById('cropContainer').style.display = 'block';
    };
    
    img.src = imageUrl;
});

function drawImage() {
    if (!ctx || !img) return;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    
    if (currentCrop.width > 0) {
        ctx.strokeStyle = '#6366f1';
        ctx.lineWidth = 3;
        ctx.setLineDash([10, 5]);
        ctx.strokeRect(currentCrop.x, currentCrop.y, currentCrop.width, currentCrop.height);
        
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        ctx.fillRect(0, 0, canvas.width, currentCrop.y);
        ctx.fillRect(0, currentCrop.y, currentCrop.x, currentCrop.height);
        ctx.fillRect(currentCrop.x + currentCrop.width, currentCrop.y, canvas.width, currentCrop.height);
        ctx.fillRect(0, currentCrop.y + currentCrop.height, canvas.width, canvas.height);
    }
    
    const scale = img.width / canvas.width;
    document.getElementById('cropX').value = Math.round(currentCrop.x * scale);
    document.getElementById('cropY').value = Math.round(currentCrop.y * scale);
    document.getElementById('cropWidth').value = Math.round(currentCrop.width * scale);
    document.getElementById('cropHeight').value = Math.round(currentCrop.height * scale);
    
    document.getElementById('displayX').textContent = Math.round(currentCrop.x * scale);
    document.getElementById('displayY').textContent = Math.round(currentCrop.y * scale);
    document.getElementById('displayW').textContent = Math.round(currentCrop.width * scale);
    document.getElementById('displayH').textContent = Math.round(currentCrop.height * scale);
}

canvas?.addEventListener('mousedown', (e) => {
    const rect = canvas.getBoundingClientRect();
    cropStart = {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    };
});

canvas?.addEventListener('mousemove', (e) => {
    if (!cropStart) return;
    
    const rect = canvas.getBoundingClientRect();
    cropEnd = {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    };
    
    currentCrop = {
        x: Math.min(cropStart.x, cropEnd.x),
        y: Math.min(cropStart.y, cropEnd.y),
        width: Math.abs(cropEnd.x - cropStart.x),
        height: Math.abs(cropEnd.y - cropStart.y)
    };
    
    drawImage();
});

canvas?.addEventListener('mouseup', () => {
    cropStart = null;
});

function resetCrop() {
    if (!img) return;
    const scale = canvas.width / img.width;
    currentCrop = {
        x: 0,
        y: 0,
        width: img.width * scale,
        height: img.height * scale
    };
    drawImage();
}
```

---

## ✅ Step 6: Test

1. Visit: `https://hyls.space/biolink.php`
2. Upload an image → Crop tool should appear below gallery
3. Scroll to "Social Media Videos" section → Add a YouTube video
4. Check that profile picture and cover image uploads still work

---

## 🔄 OR: Easy Way - Use Complete File

Instead of manual edits, just replace your biolink.php with the complete version:

```bash
# Backup
cp biolink.php biolink.php.backup_$(date +%Y%m%d)

# Download complete file
wget https://raw.githubusercontent.com/david0154/hyls/main/biolink_COMPLETE.php -O biolink.php

# Done!
```

---

## 🛡️ Safety Features

- Checks if `crop_x`, `crop_y`, etc. columns exist before using
- Checks if `bio_social_videos` table exists before showing video section
- If features don't exist → sections hidden, no errors
- **All existing features keep working!**

---

## 🐛 Troubleshooting

### Crop tool not showing?
**Run:** `https://hyls.space/install.php?mode=migrate` again

### Video section not showing?
**Run:** `https://hyls.space/install.php?mode=migrate` again

### 12MB upload fails?
**Edit php.ini:**
```ini
upload_max_filesize = 12M
post_max_size = 13M
```

---

**💜 Made by David Studioz | ✅ Zero Breaking Changes | ✅ Safe for Live Sites**
