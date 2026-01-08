# HYLS Enhancement Features

## New Features Added

### 1. Social Media Video Embedding with Autoplay

#### Supported Platforms:
- YouTube
- Facebook
- Instagram (Reels/Videos)
- TikTok
- Vimeo
- Dailymotion
- Twitter/X
- Twitch

#### Features:
- **Auto-detection**: Automatically detects video platform from URL
- **Autoplay support**: Videos can autoplay (muted) on bio page load
- **Multiple videos**: Add unlimited videos from different platforms
- **Video management**: Easy add, delete, and reorder videos
- **View tracking**: Track video views on your bio page
- **Toggle autoplay**: Enable/disable autoplay per video

#### How to Use:
1. Go to "Edit Bio Link" page
2. Scroll to "Social Media Videos" section
3. Click "Add Video" button
4. Select platform (or use Auto Detect)
5. Paste video URL
6. Add optional title and description
7. Check "Enable Autoplay" if desired
8. Click "Add Video"

#### Supported URL Formats:

**YouTube:**
- https://www.youtube.com/watch?v=VIDEO_ID
- https://youtu.be/VIDEO_ID

**Facebook:**
- https://www.facebook.com/username/videos/VIDEO_ID
- https://fb.watch/VIDEO_ID

**Instagram:**
- https://www.instagram.com/p/POST_ID/
- https://www.instagram.com/reel/REEL_ID/

**TikTok:**
- https://www.tiktok.com/@username/video/VIDEO_ID

**Vimeo:**
- https://vimeo.com/VIDEO_ID

### 2. Enhanced Image Upload with Crop (12MB Limit)

#### Features:
- **12MB file size limit** per image
- **Image cropping**: Crop images before uploading
- **Cover image**: Set a banner/cover image for bio page
- **Gallery**: Upload up to 6 gallery images
- **Supported formats**: JPG, PNG, GIF, WebP
- **Auto-optimization**: Images are optimized for web display

#### How to Use:
1. Go to "Edit Bio Link" page
2. Under "Basic Profile" section:
   - Upload Profile Picture (optional cropping)
   - Upload Cover Image (with crop dimensions)
3. Under "Image Gallery" section:
   - Upload up to 6 images
   - Each can be cropped individually
   - Max 12MB per image

### 3. Installation Instructions

#### Step 1: Run Database Migration
```bash
php migrate.php
```

Or manually run the SQL file:
```bash
mysql -u your_username -p your_database < social_videos_table.sql
```

#### Step 2: Verify File Structure
Ensure these new files exist:
- `includes/video_embed.php`
- `includes/image_processor.php`
- `biolink_video.php`
- `social_videos_table.sql`

#### Step 3: Create Upload Directory
```bash
mkdir -p uploads/images
chmod 755 uploads/images
```

#### Step 4: Update edit_bio.php
Add the enhanced video section from `edit_bio_enhanced.php` to your existing `edit_bio.php` file.

Or replace entire file:
```bash
cp edit_bio_enhanced.php edit_bio.php
```

### 4. Display Videos on Bio Page

Add this code to your `bio.php` or `biolink.php` file where you want videos to display:

```php
<?php
// Get social videos
$stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
$stmt->execute([$bio_profile['id']]);
$social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($social_videos): ?>
<div class="social-videos-section">
    <h3>Latest Videos</h3>
    <div class="videos-grid">
        <?php foreach ($social_videos as $video): ?>
        <div class="video-card" data-platform="<?= htmlspecialchars($video['platform']) ?>">
            <?php if ($video['title']): ?>
            <h4><?= htmlspecialchars($video['title']) ?></h4>
            <?php endif; ?>
            
            <div class="video-embed">
                <?= $video['embed_code'] ?>
            </div>
            
            <?php if ($video['description']): ?>
            <p><?= htmlspecialchars($video['description']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
```

### 5. CSS for Video Display

```css
.social-videos-section {
    margin: 30px 0;
}

.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.video-card {
    background: #1e293b;
    border-radius: 12px;
    padding: 15px;
    overflow: hidden;
}

.video-card h4 {
    color: #22c55e;
    margin-bottom: 10px;
}

.video-embed {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
    border-radius: 8px;
}

.video-embed iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.video-card p {
    margin-top: 10px;
    color: #94a3b8;
    font-size: 14px;
}
```

### 6. API Endpoints

**Add Video:**
```
POST /biolink_video.php
action=add_video
video_url=https://youtube.com/watch?v=...
platform=youtube (or auto)
title=Optional Title
description=Optional Description
autoplay=1 (or 0)
```

**Delete Video:**
```
GET /biolink_video.php?action=delete_video&id=VIDEO_ID
```

**Toggle Autoplay:**
```
POST /biolink_video.php
action=toggle_autoplay
id=VIDEO_ID
autoplay=1 (or 0)
```

### 7. Security Notes

- All image uploads are validated for type and size
- Video URLs are sanitized before embed generation
- SQL injection protection via prepared statements
- XSS protection via htmlspecialchars()
- File upload directory has restricted permissions

### 8. Browser Compatibility

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support (autoplay may require user interaction)
- Mobile browsers: Full support with touch controls

### 9. Known Limitations

- Instagram embeds may not autoplay due to platform restrictions
- Facebook videos require public visibility
- Autoplay is muted by default (browser requirement)
- Some platforms may block embedding in certain regions

### 10. Troubleshooting

**Videos not displaying:**
- Check if video is public/embeddable
- Verify URL format is correct
- Check browser console for errors

**Images not uploading:**
- Verify upload directory permissions (755)
- Check PHP upload_max_filesize setting
- Ensure GD library is installed

**Autoplay not working:**
- Autoplay requires muted audio (browser policy)
- Some platforms don't support autoplay
- Check browser autoplay settings

## Support

For issues or questions, please open an issue on GitHub:
https://github.com/david0154/hyls/issues

## Credits

Developed by David Studioz
Enhancements added: January 2026