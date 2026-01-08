# 📝 Guide: Add Image Crop & Video Management to biolink.php

This guide shows exactly where to add the **Image Crop Tool** and **Video Management** features to your existing `biolink.php` file.

---

## 🎯 Part 1: Add Video Management Section (After Gallery Section)

### Location: Insert AFTER the Gallery Section (around line 150)

```php
<!-- ADD THIS ENTIRE SECTION AFTER THE GALLERY CARD -->

<!-- Social Videos Section -->
<div class="card">
    <h2><i class="fas fa-video"></i> Social Media Videos (<?= count($social_videos) ?>)</h2>
    
    <?php if ($success && (strpos($success, 'Video') !== false || strpos($success, 'video') !== false)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="info-box">
        <strong>🎬 Add Video Embeds</strong><br>
        Embed videos from YouTube, Facebook, Instagram, TikTok, Vimeo, and more!
    </div>
    
    <!-- Video List -->
    <?php if (!empty($social_videos)): ?>
    <div style="margin: 20px 0;">
        <?php foreach ($social_videos as $video): ?>
        <div class="social-section" style="position: relative;">
            <a href="?delete_video=<?= $video['id'] ?>" 
               onclick="return confirm('Delete this video?')" 
               style="position: absolute; top: 15px; right: 15px; background: #ef4444; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none;">
                <i class="fas fa-times"></i>
            </a>
            <h3><i class="fab fa-<?= $video['platform'] ?>"></i> <?= ucfirst($video['platform']) ?> Video</h3>
            <p><strong>Title:</strong> <?= htmlspecialchars($video['title']) ?></p>
            <?php if ($video['description']): ?>
            <p><strong>Description:</strong> <?= htmlspecialchars($video['description']) ?></p>
            <?php endif; ?>
            <p><strong>Autoplay:</strong> <?= $video['autoplay'] ? 'Yes' : 'No' ?></p>
            <div style="margin-top: 15px; border-radius: 12px; overflow: hidden; aspect-ratio: 16/9;">
                <?= $video['embed_code'] ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Add Video Form -->
    <form method="POST" style="margin-top: 20px;">
        <h3 style="margin-bottom: 15px;">Add New Video</h3>
        
        <div class="form-group">
            <label><i class="fas fa-globe"></i> Platform</label>
            <select name="video_platform" required style="width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px;">
                <option value="">Select Platform</option>
                <option value="youtube">YouTube</option>
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="tiktok">TikTok</option>
                <option value="vimeo">Vimeo</option>
                <option value="dailymotion">Dailymotion</option>
            </select>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-link"></i> Video URL</label>
            <input type="url" name="video_url" required placeholder="Paste full video URL here">
            <small style="color: #64748b; display: block; margin-top: 8px;">
                Examples:<br>
                • YouTube: https://youtube.com/watch?v=VIDEO_ID<br>
                • Facebook: https://facebook.com/username/videos/VIDEO_ID<br>
                • Instagram: https://instagram.com/p/VIDEO_ID<br>
                • TikTok: https://tiktok.com/@username/video/VIDEO_ID
            </small>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-heading"></i> Video Title</label>
            <input type="text" name="video_title" placeholder="Optional: Title for this video">
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-align-left"></i> Description (Optional)</label>
            <textarea name="video_description" placeholder="Add a description..." rows="3"></textarea>
        </div>
        
        <div class="toggle-group">
            <input type="checkbox" name="autoplay" id="autoplay" value="1" checked>
            <label for="autoplay">Enable Autoplay</label>
        </div>
        
        <button type="submit" name="add_video" class="btn-primary" style="margin-top: 15px;">
            <i class="fas fa-plus"></i> Add Video
        </button>
    </form>
</div>
```

---

## 🖼️ Part 2: Add Crop Tool to Gallery Section

### Location: Add INSIDE the existing Gallery Section (after gallery grid)

```php
<!-- ADD THIS AFTER THE gallery-grid div -->

<!-- Image Crop Tool -->
<?php if (!empty($gallery_images)): ?>
<div style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #e2e8f0;">
    <h3 style="margin-bottom: 20px;"><i class="fas fa-crop"></i> Crop Images</h3>
    
    <div class="form-group">
        <label>Select Image to Crop</label>
        <select id="cropImageSelect" style="width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px;">
            <option value="">Choose an image...</option>
            <?php foreach ($gallery_images as $img): ?>
            <option value="<?= $img['id'] ?>" 
                    data-url="<?= htmlspecialchars($img['image_url']) ?>"
                    data-crop-x="<?= $img['crop_x'] ?? 0 ?>"
                    data-crop-y="<?= $img['crop_y'] ?? 0 ?>"
                    data-crop-width="<?= $img['crop_width'] ?? $img['original_width'] ?>"
                    data-crop-height="<?= $img['crop_height'] ?? $img['original_height'] ?>"
                    data-original-width="<?= $img['original_width'] ?>"
                    data-original-height="<?= $img['original_height'] ?>">
                Image #<?= $img['id'] ?> (<?= $img['original_width'] ?>x<?= $img['original_height'] ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div id="cropContainer" style="display: none; margin-top: 20px;">
        <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
            <p style="color: #64748b; margin-bottom: 10px;">
                <strong>How to crop:</strong> Click and drag on the image to select the area you want to display.
            </p>
            <div style="position: relative; max-width: 100%; overflow: auto; border: 2px solid #e2e8f0; border-radius: 12px; background: white;">
                <canvas id="cropCanvas" style="max-width: 100%; height: auto; cursor: crosshair;"></canvas>
            </div>
        </div>
        
        <form method="POST" id="cropForm">
            <input type="hidden" name="image_id" id="cropImageId">
            <input type="hidden" name="crop_x" id="cropX">
            <input type="hidden" name="crop_y" id="cropY">
            <input type="hidden" name="crop_width" id="cropWidth">
            <input type="hidden" name="crop_height" id="cropHeight">
            
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <strong>Crop Area:</strong>
                    <span id="cropInfo" style="color: #6366f1;">Select an area</span>
                </div>
                <button type="button" onclick="resetCrop()" style="padding: 10px 20px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
            
            <button type="submit" name="save_crop" class="btn-primary">
                <i class="fas fa-save"></i> Save Crop Settings
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
```

---

## 📜 Part 3: Add JavaScript for Crop Tool

### Location: Add BEFORE the closing `</body>` tag

```javascript
<script>
// Image Crop Tool
let canvas, ctx, img;
let isDragging = false;
let startX, startY, currentX, currentY;
let cropData = { x: 0, y: 0, width: 0, height: 0 };
let scale = 1;

document.getElementById('cropImageSelect')?.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (!option.value) {
        document.getElementById('cropContainer').style.display = 'none';
        return;
    }
    
    const imageUrl = option.dataset.url;
    const imageId = option.value;
    const cropX = parseInt(option.dataset.cropX) || 0;
    const cropY = parseInt(option.dataset.cropY) || 0;
    const cropWidth = parseInt(option.dataset.cropWidth);
    const cropHeight = parseInt(option.dataset.cropHeight);
    const originalWidth = parseInt(option.dataset.originalWidth);
    const originalHeight = parseInt(option.dataset.originalHeight);
    
    document.getElementById('cropImageId').value = imageId;
    document.getElementById('cropContainer').style.display = 'block';
    
    canvas = document.getElementById('cropCanvas');
    ctx = canvas.getContext('2d');
    img = new Image();
    
    img.onload = function() {
        // Scale image to fit canvas max width (800px)
        const maxWidth = 800;
        scale = maxWidth / img.width;
        if (scale > 1) scale = 1; // Don't upscale
        
        canvas.width = img.width * scale;
        canvas.height = img.height * scale;
        
        // Set initial crop to saved values
        cropData = {
            x: cropX * scale,
            y: cropY * scale,
            width: cropWidth * scale,
            height: cropHeight * scale
        };
        
        drawImage();
    };
    
    img.src = imageUrl;
});

function drawImage() {
    if (!ctx || !img) return;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    
    // Draw semi-transparent overlay
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Clear crop area (show original image)
    if (cropData.width > 0 && cropData.height > 0) {
        ctx.clearRect(cropData.x, cropData.y, cropData.width, cropData.height);
        ctx.drawImage(img, cropData.x / scale, cropData.y / scale, cropData.width / scale, cropData.height / scale,
                      cropData.x, cropData.y, cropData.width, cropData.height);
        
        // Draw crop border
        ctx.strokeStyle = '#6366f1';
        ctx.lineWidth = 3;
        ctx.strokeRect(cropData.x, cropData.y, cropData.width, cropData.height);
    }
    
    updateCropInfo();
}

canvas?.addEventListener('mousedown', function(e) {
    const rect = canvas.getBoundingClientRect();
    startX = e.clientX - rect.left;
    startY = e.clientY - rect.top;
    isDragging = true;
});

canvas?.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    
    const rect = canvas.getBoundingClientRect();
    currentX = e.clientX - rect.left;
    currentY = e.clientY - rect.top;
    
    cropData.x = Math.min(startX, currentX);
    cropData.y = Math.min(startY, currentY);
    cropData.width = Math.abs(currentX - startX);
    cropData.height = Math.abs(currentY - startY);
    
    drawImage();
});

canvas?.addEventListener('mouseup', function() {
    isDragging = false;
    
    // Update hidden form fields with original scale values
    document.getElementById('cropX').value = Math.round(cropData.x / scale);
    document.getElementById('cropY').value = Math.round(cropData.y / scale);
    document.getElementById('cropWidth').value = Math.round(cropData.width / scale);
    document.getElementById('cropHeight').value = Math.round(cropData.height / scale);
});

function updateCropInfo() {
    if (cropData.width > 0 && cropData.height > 0) {
        const origX = Math.round(cropData.x / scale);
        const origY = Math.round(cropData.y / scale);
        const origW = Math.round(cropData.width / scale);
        const origH = Math.round(cropData.height / scale);
        document.getElementById('cropInfo').textContent = 
            `X: ${origX}, Y: ${origY}, Width: ${origW}, Height: ${origH}`;
    } else {
        document.getElementById('cropInfo').textContent = 'Select an area';
    }
}

function resetCrop() {
    if (!img) return;
    cropData = { x: 0, y: 0, width: canvas.width, height: canvas.height };
    drawImage();
    
    document.getElementById('cropX').value = 0;
    document.getElementById('cropY').value = 0;
    document.getElementById('cropWidth').value = Math.round(img.width);
    document.getElementById('cropHeight').value = Math.round(img.height);
}
</script>
```

---

## ⚙️ Part 4: Add PHP Handlers (Top of File, After Database Init)

### Location: Add AFTER the bio/gallery queries (around line 50)

```php
// Get bio profile for video support
$stmt = $db->prepare("SELECT * FROM bio_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$bio_profile = $stmt->fetch();

if (!$bio_profile) {
    // Create profile if not exists
    $stmt = $db->prepare("INSERT INTO bio_profiles (user_id, username, display_name, theme_color) VALUES (?, ?, ?, '#6366f1')");
    $stmt->execute([$user_id, $current_user['username'], $current_user['username']]);
    $bio_profile_id = $db->lastInsertId();
    
    $stmt = $db->prepare("SELECT * FROM bio_profiles WHERE id = ?");
    $stmt->execute([$bio_profile_id]);
    $bio_profile = $stmt->fetch();
}

// Get social videos
$social_videos = [];
try {
    $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
    $stmt->execute([$bio_profile['id']]);
    $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Social videos error: " . $e->getMessage());
}

// Handle video delete
if (isset($_GET['delete_video'])) {
    $video_id = (int)$_GET['delete_video'];
    try {
        $stmt = $db->prepare("DELETE FROM bio_social_videos WHERE id = ? AND bio_profile_id = ?");
        $stmt->execute([$video_id, $bio_profile['id']]);
        $success = 'Video deleted successfully!';
        
        // Refresh videos
        $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
        $stmt->execute([$bio_profile['id']]);
        $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Delete failed: ' . $e->getMessage();
    }
}

// Handle video add
if (isset($_POST['add_video'])) {
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
                $video_id = $matches[1];
                $embed_code = '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $video_id . '?autoplay=' . $autoplay . '" frameborder="0" allowfullscreen></iframe>';
                $thumbnail = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
            }
        } elseif ($platform === 'facebook') {
            $embed_code = '<iframe src="https://www.facebook.com/plugins/video.php?href=' . urlencode($video_url) . '&show_text=0&width=560" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>';
        } elseif ($platform === 'instagram') {
            $embed_code = '<iframe src="' . $video_url . 'embed" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>';
        } elseif ($platform === 'tiktok') {
            preg_match('/video\\/(\\d+)/', $video_url, $matches);
            if (isset($matches[1])) {
                $embed_code = '<iframe src="https://www.tiktok.com/embed/' . $matches[1] . '" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>';
            }
        } elseif ($platform === 'vimeo') {
            preg_match('/vimeo\\.com\\/(\\d+)/', $video_url, $matches);
            if (isset($matches[1])) {
                $embed_code = '<iframe src="https://player.vimeo.com/video/' . $matches[1] . '?autoplay=' . $autoplay . '" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>';
            }
        } elseif ($platform === 'dailymotion') {
            preg_match('/dailymotion\\.com\\/video\\/([^_]+)/', $video_url, $matches);
            if (isset($matches[1])) {
                $embed_code = '<iframe src="https://www.dailymotion.com/embed/video/' . $matches[1] . '?autoplay=' . $autoplay . '" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>';
            }
        }
        
        if ($embed_code) {
            try {
                $stmt = $db->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM bio_social_videos WHERE bio_profile_id = ?");
                $stmt->execute([$bio_profile['id']]);
                $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_order = $order_result['max_order'] + 1;
                
                $stmt = $db->prepare("INSERT INTO bio_social_videos (bio_profile_id, platform, video_url, embed_code, title, description, thumbnail_url, display_order, autoplay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$bio_profile['id'], $platform, $video_url, $embed_code, $video_title, $video_desc, $thumbnail, $new_order, $autoplay]);
                $success = 'Video added successfully!';
                
                // Refresh videos
                $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
                $stmt->execute([$bio_profile['id']]);
                $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $error = 'Failed to add video: ' . $e->getMessage();
            }
        } else {
            $error = 'Invalid video URL for selected platform';
        }
    }
}

// Handle image crop save
if (isset($_POST['save_crop'])) {
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

---

## 🔄 Part 5: Update Gallery Upload to Support 12MB and Save Dimensions

### Location: Find the gallery upload section and update it:

```php
// Change this line:
if ($files['size'][$i] > 5 * 1024 * 1024) {

// To this:
if ($files['size'][$i] > 12 * 1024 * 1024) {
    $errors[] = 'File too large (max 12MB): ' . $files['name'][$i];
    continue;
}

// After move_uploaded_file, add:
if (move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
    // Get image dimensions
    list($width, $height) = getimagesize($upload_path);
    
    $stmt = $db->prepare("SELECT COALESCE(MAX(image_order), 0) as max_order FROM bio_gallery WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_order = $order_result['max_order'] + 1;
    
    // Update INSERT to include crop columns
    $stmt = $db->prepare("INSERT INTO bio_gallery (user_id, image_url, image_order, crop_x, crop_y, crop_width, crop_height, original_width, original_height, created_at) VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, '/' . $upload_path, $new_order, $width, $height, $width, $height]);
    $uploaded_count++;
}
```

---

## ✅ Summary of Changes

### What You're Adding:

1. **Video Management Section** (New Card)
   - Add/delete social media videos
   - Support for YouTube, Facebook, Instagram, TikTok, Vimeo, Dailymotion
   - Autoplay toggle
   - Video preview in dashboard

2. **Image Crop Tool** (Inside Gallery Card)
   - Visual canvas-based crop editor
   - Click and drag to select crop area
   - Real-time preview
   - Save crop coordinates to database

3. **PHP Handlers** (Top of file)
   - Video add/delete logic
   - Crop save logic
   - Bio profile creation for video support

4. **JavaScript** (Bottom of file)
   - Interactive crop tool
   - Canvas drawing
   - Mouse event handlers

5. **Upload Improvements**
   - 12MB file size limit (up from 5MB)
   - Save original image dimensions
   - Initialize crop to full image

---

## 🚀 Quick Installation Steps

1. **Backup** your current `biolink.php`
2. **Run migration** via `install.php?mode=migrate` (creates video tables & crop columns)
3. **Open** `biolink.php` in your editor
4. **Add sections** following the locations in this guide:
   - Part 4 (PHP) → After line 50
   - Part 1 (Videos) → After gallery card (~line 150)
   - Part 2 (Crop) → Inside gallery card
   - Part 3 (JavaScript) → Before `</body>`
   - Part 5 (Upload) → Update existing code
5. **Test** upload images and add videos!

---

## 📌 Notes

- **Database Migration Required**: Run `install.php?mode=migrate` first
- **Upload Directory**: Creates `uploads/images/` for 12MB images
- **Browser Support**: Works in all modern browsers with canvas support
- **Mobile**: Touch events not yet supported (desktop only for crop)

---

**💜 Made by David Studioz**
