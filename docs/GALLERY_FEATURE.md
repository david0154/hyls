# 📸 Gallery Feature Documentation

## Overview

The gallery feature allows users to upload up to **6 images** to their bio link page. Images are displayed in a responsive grid layout and can be easily managed through the bio link editor.

---

## ✨ Features

### 1. **Upload Multiple Images**
- Upload up to 6 images at once
- Supported formats: JPG, JPEG, PNG, GIF, WebP
- Maximum file size: 5MB per image
- Automatic validation and error handling

### 2. **Responsive Grid Layout**
- **Desktop:** 3 columns × 2 rows
- **Tablet:** 2 columns × 3 rows
- **Mobile:** 2 columns × 3 rows
- Square aspect ratio (1:1) maintained

### 3. **Image Management**
- Delete individual images with confirmation
- Visual slot indicators for available spaces
- Real-time counter showing "X/6 images"
- Automatic file cleanup on deletion

### 4. **Click Tracking** (Coming Soon)
- Track clicks on individual gallery images
- Analytics dashboard integration
- View most popular images

---

## 🗄️ Database Schema

### Table: `bio_gallery`

```sql
CREATE TABLE bio_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    image_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_order (image_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Fields Explained:

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Unique image identifier |
| `user_id` | INT | Links to users table |
| `image_url` | VARCHAR(255) | Relative path to image file |
| `image_order` | INT | Display order (0-5) |
| `created_at` | TIMESTAMP | Upload timestamp |

---

## 📂 File Structure

```
project-root/
├── uploads/
│   └── bio/
│       └── gallery/
│           ├── gallery_123_1234567890_abc123.jpg
│           ├── gallery_123_1234567891_def456.png
│           └── gallery_123_1234567892_ghi789.webp
│
├── biolink_enhanced.php (Gallery UI)
└── biolink_handler.php (Upload/Delete logic)
```

### Naming Convention:
```
gallery_{user_id}_{timestamp}_{unique_id}.{extension}

Example:
gallery_42_1705564800_abc123def.jpg
         ^    ^            ^         ^
      user_id|         unique_id    ext
           timestamp
```

---

## 🚀 Usage Guide

### For Users:

#### **Uploading Images**

1. Navigate to Bio Link Editor (`/biolink_enhanced.php`)
2. Scroll to "Image Gallery" section
3. Click the file input or drag-and-drop
4. Select 1-6 images (up to remaining slots)
5. Click "Upload Images"
6. Wait for success message

#### **Deleting Images**

1. Hover over image in gallery
2. Click the **×** button in top-right corner
3. Confirm deletion in popup
4. Image removed from display and database

#### **Viewing on Bio Page**

Gallery appears on public bio page:
- URL: `https://yoursite.com/bio/{username}`
- Click images to view full-size (if lightbox enabled)
- Responsive on all devices

---

## 💻 Developer Guide

### Upload Handler

**File:** `biolink_handler.php`

```php
function uploadGalleryImage($file, $user_id) {
    $upload_dir = __DIR__ . '/uploads/bio/gallery/';
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file type
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Invalid file type'];
    }
    
    // Validate file size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File too large (max 5MB)'];
    }
    
    // Generate unique filename
    $filename = 'gallery_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
    $path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => '/uploads/bio/gallery/' . $filename];
    }
    
    return ['error' => 'Upload failed'];
}
```

### Delete Handler

```php
if (isset($_GET['delete_gallery'])) {
    $image_id = (int)$_GET['delete_gallery'];
    
    // Get image path
    $stmt = $db->prepare(
        "SELECT image_url FROM bio_gallery WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$image_id, $user_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Delete from database
        $stmt = $db->prepare(
            "DELETE FROM bio_gallery WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$image_id, $user_id]);
        
        // Delete physical file
        $file_path = __DIR__ . $image['image_url'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }
}
```

### Gallery Display (Frontend)

```php
<?php
// Get gallery images
$stmt = $db->prepare(
    "SELECT * FROM bio_gallery 
     WHERE user_id = ? 
     ORDER BY image_order ASC 
     LIMIT 6"
);
$stmt->execute([$user_id]);
$gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="gallery-grid">
    <?php foreach ($gallery_images as $img): ?>
    <div class="gallery-item">
        <img src="<?= htmlspecialchars($img['image_url']) ?>" 
             alt="Gallery image">
        <a href="biolink_handler.php?delete_gallery=<?= $img['id'] ?>" 
           onclick="return confirm('Delete this image?')" 
           class="delete-btn">
            <i class="fas fa-times"></i>
        </a>
    </div>
    <?php endforeach; ?>
    
    <!-- Empty slots -->
    <?php for ($i = count($gallery_images); $i < 6; $i++): ?>
    <div class="gallery-slot">
        <i class="fas fa-plus"></i>
    </div>
    <?php endfor; ?>
</div>
```

---

## 🎨 CSS Styling

```css
/* Gallery Grid */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin: 20px 0;
}

/* Gallery Item */
.gallery-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 12px;
    overflow: hidden;
    background: #f3f4f6;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

/* Delete Button */
.delete-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #ef4444;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    text-decoration: none;
}

.gallery-item:hover .delete-btn {
    opacity: 1;
}

/* Empty Slot */
.gallery-slot {
    aspect-ratio: 1;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}

.gallery-slot i {
    font-size: 32px;
    color: #cbd5e1;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

---

## ⚙️ Configuration

### Customizing Limits

Edit `biolink_handler.php`:

```php
// Change max file size (default: 5MB)
if ($file['size'] > 10 * 1024 * 1024) { // 10MB
    return ['error' => 'File too large (max 10MB)'];
}

// Change allowed formats
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

// Change max gallery images (default: 6)
if ($existing_count + $uploaded_count >= 12) { // 12 images
    break;
}
```

### Image Optimization (Optional)

Add image compression using GD or ImageMagick:

```php
function optimizeImage($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagepng($image, $destination, 9 - round($quality / 10));
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            imagegif($image, $destination);
            break;
    }
    
    imagedestroy($image);
}
```

---

## 🐛 Troubleshooting

### Issue: "Upload failed"

**Cause:** Directory permissions

**Solution:**
```bash
chmod 755 uploads/bio/gallery/
chown www-data:www-data uploads/bio/gallery/
```

### Issue: "File too large"

**Cause:** PHP upload limits

**Solution:** Edit `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 10
```

### Issue: Images not displaying

**Cause:** Incorrect path or permissions

**Solution:**
```php
// Check if file exists
if (!file_exists(__DIR__ . $image['image_url'])) {
    echo "File not found: " . $image['image_url'];
}

// Check permissions
echo substr(sprintf('%o', fileperms($file)), -4);
```

### Issue: "Maximum 6 images" not enforced

**Cause:** Missing validation

**Solution:**
```php
// Check BEFORE upload
$stmt = $db->prepare("SELECT COUNT(*) as count FROM bio_gallery WHERE user_id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] >= 6) {
    throw new Exception('Maximum 6 images allowed');
}
```

---

## 📊 Analytics (Future Enhancement)

### Track Image Clicks

```php
// Add clicks column to bio_gallery
ALTER TABLE bio_gallery ADD COLUMN clicks INT DEFAULT 0;

// Track click
if (isset($_GET['track_gallery'])) {
    $image_id = (int)$_GET['track_gallery'];
    $stmt = $db->prepare(
        "UPDATE bio_gallery SET clicks = clicks + 1 WHERE id = ?"
    );
    $stmt->execute([$image_id]);
}

// Wrap image with tracking link
<a href="biolink_handler.php?track_gallery=<?= $img['id'] ?>" 
   target="_blank">
    <img src="<?= $img['image_url'] ?>">
</a>
```

---

## 🔐 Security Considerations

### 1. **File Type Validation**
```php
// Validate MIME type (not just extension)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed_mimes)) {
    throw new Exception('Invalid file type');
}
```

### 2. **User Isolation**
```php
// ALWAYS check user_id in queries
DELETE FROM bio_gallery WHERE id = ? AND user_id = ?;
// Never delete by id alone!
```

### 3. **Path Traversal Prevention**
```php
// Never use user input directly in paths
$filename = basename($file['name']); // Remove directory components
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename); // Sanitize
```

### 4. **Rate Limiting**
```php
// Limit uploads per user per hour
$stmt = $db->prepare(
    "SELECT COUNT(*) as count FROM bio_gallery 
     WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
);
$stmt->execute([$user_id]);
$result = $stmt->fetch();

if ($result['count'] >= 10) {
    throw new Exception('Upload limit exceeded. Try again later.');
}
```

---

## ✅ Testing Checklist

- [ ] Upload single image
- [ ] Upload multiple images (2-6)
- [ ] Try uploading 7th image (should block)
- [ ] Upload unsupported format (should reject)
- [ ] Upload file > 5MB (should reject)
- [ ] Delete image (file + DB entry removed)
- [ ] Check responsive layout on mobile
- [ ] Test with different image dimensions
- [ ] Verify permissions on uploads folder
- [ ] Test concurrent uploads by multiple users

---

## 📱 Mobile Optimization

### Touch-Friendly Delete
```css
@media (max-width: 768px) {
    .delete-btn {
        opacity: 1; /* Always visible on mobile */
        width: 40px; /* Larger touch target */
        height: 40px;
    }
}
```

### Responsive Upload Button
```css
.upload-btn {
    width: 100%;
    padding: 16px;
    font-size: 16px; /* Minimum for iOS */
    touch-action: manipulation; /* Prevent zoom on tap */
}
```

---

## 🚀 Performance Tips

1. **Lazy Load Images**
```html
<img src="<?= $img['image_url'] ?>" 
     loading="lazy" 
     decoding="async">
```

2. **Generate Thumbnails**
```php
function createThumbnail($source, $dest, $width = 400) {
    list($orig_width, $orig_height) = getimagesize($source);
    $ratio = $width / $orig_width;
    $height = $orig_height * $ratio;
    
    $thumb = imagecreatetruecolor($width, $height);
    $image = imagecreatefromjpeg($source);
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
    imagejpeg($thumb, $dest, 85);
}
```

3. **CDN Integration**
```php
// Store images on CDN
$cdn_url = 'https://cdn.yoursite.com';
$image_url = $cdn_url . '/bio/gallery/' . $filename;
```

---

## 📖 API Reference (Future)

### Upload Image
```http
POST /api/gallery/upload
Content-Type: multipart/form-data
Authorization: Bearer {token}

image: file
```

### Delete Image
```http
DELETE /api/gallery/{id}
Authorization: Bearer {token}
```

### List Images
```http
GET /api/gallery
Authorization: Bearer {token}

Response:
{
  "images": [
    {"id": 1, "url": "/uploads/...", "order": 0},
    {"id": 2, "url": "/uploads/...", "order": 1}
  ],
  "count": 2,
  "max": 6
}
```

---

## 🎯 Roadmap

- [ ] Image reordering (drag-and-drop)
- [ ] Image captions/descriptions
- [ ] Click tracking analytics
- [ ] Lightbox for full-size viewing
- [ ] Batch delete
- [ ] Image filters/effects
- [ ] Video support
- [ ] GIF optimization
- [ ] WebP conversion
- [ ] Cloud storage integration (S3, Cloudinary)

---

## 📞 Support

For issues or questions:
- GitHub Issues: https://github.com/david0154/hyls/issues
- Email: support@davidstudioz.com
- Documentation: /docs/

---

**Last Updated:** January 8, 2026  
**Version:** 1.0.0  
**Author:** David Studioz