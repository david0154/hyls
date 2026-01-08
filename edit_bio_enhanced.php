<?php
/**
 * Enhanced Bio Editor with Video and Advanced Image Features
 * This adds video embedding and image crop functionality to edit_bio.php
 */

// Add this section to your existing edit_bio.php after the Custom Links section

// Get social videos
$stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
$stmt->execute([$bio_profile['id']]);
$social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$video_platforms = [
    'auto' => ['name' => 'Auto Detect', 'icon' => 'fas fa-magic'],
    'youtube' => ['name' => 'YouTube', 'icon' => 'fab fa-youtube', 'color' => '#FF0000'],
    'facebook' => ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'color' => '#1877F2'],
    'instagram' => ['name' => 'Instagram Reel', 'icon' => 'fab fa-instagram', 'color' => '#E4405F'],
    'tiktok' => ['name' => 'TikTok', 'icon' => 'fab fa-tiktok', 'color' => '#000000'],
    'vimeo' => ['name' => 'Vimeo', 'icon' => 'fab fa-vimeo', 'color' => '#1AB7EA'],
    'dailymotion' => ['name' => 'Dailymotion', 'icon' => 'fas fa-play-circle', 'color' => '#0066DC'],
    'twitter' => ['name' => 'Twitter/X', 'icon' => 'fab fa-x-twitter', 'color' => '#000000'],
    'twitch' => ['name' => 'Twitch', 'icon' => 'fab fa-twitch', 'color' => '#9146FF']
];
?>

<!-- Add this HTML section after Custom Links section in edit_bio.php -->

<!-- Social Media Videos Section -->
<div class="section" id="videos">
    <h2><i class="fas fa-video"></i> Social Media Videos</h2>
    <p style="color: #94a3b8; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Embed videos from YouTube, Facebook, Instagram, TikTok and more!</p>
    
    <button onclick="openVideoModal()" class="btn btn-secondary"><i class="fas fa-plus"></i> Add Video</button>
    
    <?php if ($social_videos): ?>
    <div class="added-links" style="margin-top: 20px;">
        <?php foreach ($social_videos as $video): ?>
        <div class="added-link video-item">
            <div class="info">
                <div class="platform">
                    <i class="<?= $video_platforms[$video['platform']]['icon'] ?? 'fas fa-video' ?>"></i> 
                    <?= ucfirst($video['platform']) ?>
                    <?php if ($video['autoplay']): ?>
                    <span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">AUTOPLAY</span>
                    <?php endif; ?>
                </div>
                <?php if ($video['title']): ?>
                <div class="platform" style="color: #e2e8f0; font-weight: normal; margin-top: 4px;"><?= htmlspecialchars($video['title']) ?></div>
                <?php endif; ?>
                <div class="username" style="font-size: 12px; color: #64748b; margin-top: 4px;"><?= htmlspecialchars($video['video_url']) ?></div>
                <?php if ($video['description']): ?>
                <div class="label" style="margin-top: 4px;"><?= htmlspecialchars($video['description']) ?></div>
                <?php endif; ?>
            </div>
            <div class="actions">
                <span style="color: #94a3b8;"><i class="fas fa-eye"></i> <?= number_format($video['views'] ?? 0) ?> views</span>
                <button onclick="toggleAutoplay(<?= $video['id'] ?>, <?= $video['autoplay'] ? 0 : 1 ?>)" class="btn btn-secondary" style="padding: 8px 12px;">
                    <i class="fas fa-<?= $video['autoplay'] ? 'pause' : 'play' ?>"></i>
                </button>
                <a href="biolink_video.php?action=delete_video&id=<?= $video['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this video?')"><i class="fas fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Video Modal -->
<div id="videoModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-video"></i> Add Social Media Video</h3>
        <form action="biolink_video.php" method="POST">
            <input type="hidden" name="action" value="add_video">
            
            <div class="form-group">
                <label>Platform</label>
                <select name="platform" class="form-control" style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0;">
                    <?php foreach ($video_platforms as $key => $platform): ?>
                    <option value="<?= $key ?>"><?= $platform['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Video URL <span style="color: #22c55e;">*</span></label>
                <input type="url" name="video_url" placeholder="https://youtube.com/watch?v=..." required>
                <small style="color: #94a3b8; display: block; margin-top: 4px;">
                    Paste the full video URL from YouTube, Facebook, Instagram, TikTok, etc.
                </small>
            </div>
            
            <div class="form-group">
                <label>Title (optional)</label>
                <input type="text" name="title" placeholder="My Latest Video">
            </div>
            
            <div class="form-group">
                <label>Description (optional)</label>
                <input type="text" name="description" placeholder="Check out my latest content!">
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="autoplay" value="1" checked style="width: auto;">
                    <span>Enable Autoplay (muted)</span>
                </label>
                <small style="color: #94a3b8; display: block; margin-top: 4px;">
                    Videos will autoplay when visitors view your bio page
                </small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Video</button>
            </div>
        </form>
    </div>
</div>

<script>
function openVideoModal() {
    document.getElementById('videoModal').classList.add('active');
}

function toggleAutoplay(videoId, autoplay) {
    fetch('biolink_video.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_autoplay&id=${videoId}&autoplay=${autoplay}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}
</script>

<!-- Add image cropper CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

<!-- Add image cropper JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
.video-item {
    border-left: 4px solid #3b82f6;
}

.cropper-modal {
    background: rgba(0,0,0,0.9) !important;
}

.crop-container {
    max-height: 500px;
    margin: 20px 0;
}

#cropImage {
    max-width: 100%;
}
</style>