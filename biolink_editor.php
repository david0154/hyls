<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];

// Get or create bio profile
$stmt = $db->prepare("SELECT * FROM bio_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$bio_profile_id = $profile['id'] ?? null;

// Get all platforms
$stmt = $db->query("SELECT * FROM bio_platforms WHERE is_active = 1 ORDER BY display_order");
$platforms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get existing social links
$social_links = [];
if ($bio_profile_id) {
    $stmt = $db->prepare("SELECT * FROM bio_social_links WHERE bio_profile_id = ? ORDER BY display_order");
    $stmt->execute([$bio_profile_id]);
    $social_links = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get gallery images
$gallery_images = [];
if ($bio_profile_id) {
    $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE bio_profile_id = ? ORDER BY display_order LIMIT 6");
    $stmt->execute([$bio_profile_id]);
    $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get custom links
$custom_links = [];
if ($bio_profile_id) {
    $stmt = $db->prepare("SELECT * FROM bio_custom_links WHERE bio_profile_id = ? ORDER BY display_order");
    $stmt->execute([$bio_profile_id]);
    $custom_links = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bio Link - HYLS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/mobile.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #e2e8f0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #22c55e; margin-bottom: 30px; }
        h2 { color: #3b82f6; margin: 30px 0 15px; font-size: 20px; }
        .card { background: #1e293b; padding: 25px; border-radius: 12px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 500; }
        input[type="text"], input[type="url"], input[type="file"], textarea, select {
            width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155;
            border-radius: 8px; color: #e2e8f0; font-size: 15px;
        }
        textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background: #22c55e; color: white; }
        .btn-primary:hover { background: #16a34a; }
        .btn-secondary { background: #3b82f6; color: white; }
        .btn-secondary:hover { background: #2563eb; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-small { padding: 8px 16px; font-size: 13px; }
        
        /* Social Links */
        .social-link-item { background: #0f172a; padding: 20px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #334155; }
        .social-link-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .platform-badge { padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        /* Platform Grid */
        .platform-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 20px; }
        .platform-option { padding: 12px; background: #0f172a; border: 2px solid #334155; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.3s; }
        .platform-option:hover { border-color: #3b82f6; background: #1e293b; }
        .platform-option.selected { border-color: #22c55e; background: #1e293b; }
        .platform-option i { font-size: 24px; margin-bottom: 8px; }
        .platform-option span { display: block; font-size: 12px; }
        
        /* Gallery */
        .gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .gallery-item { position: relative; aspect-ratio: 1; background: #0f172a; border: 2px dashed #334155; border-radius: 8px; overflow: hidden; cursor: pointer; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-item .upload-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #64748b; }
        .gallery-item .remove-btn { position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; }
        
        /* Custom Link */
        .custom-link-item { background: #0f172a; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #334155; }
        
        .success { background: #22c55e; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #ef4444; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info { background: #3b82f6; color: white; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
        
        .back-link { display: inline-block; margin-bottom: 20px; color: #3b82f6; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        
        /* Drag handle */
        .drag-handle { cursor: move; color: #64748b; margin-right: 10px; }
        .drag-handle:hover { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <h1><i class="fas fa-link"></i> Edit Bio Link</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <!-- Basic Profile -->
        <div class="card">
            <h2><i class="fas fa-user"></i> Basic Profile</h2>
            <form action="biolink_save.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_profile">
                
                <div class="form-group">
                    <label>Bio Link Username (Your URL)</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($profile['username'] ?? $_SESSION['username']) ?>" required>
                    <small style="color: #64748b;">Your bio link: <?= SITE_URL ?>/bio/<strong><?= htmlspecialchars($profile['username'] ?? $_SESSION['username']) ?></strong></small>
                </div>
                
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($profile['display_name'] ?? '') ?>" placeholder="Your Full Name">
                </div>
                
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" placeholder="Tell people about yourself..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*">
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <small style="color: #22c55e;">✓ Current image uploaded</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Cover Image</label>
                        <input type="file" name="cover_image" accept="image/*">
                        <?php if (!empty($profile['cover_image'])): ?>
                            <small style="color: #22c55e;">✓ Current image uploaded</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
            </form>
        </div>
        
        <?php if ($bio_profile_id): ?>
        
        <!-- Social Media Links -->
        <div class="card">
            <h2><i class="fas fa-share-alt"></i> Social Media Links</h2>
            <p class="info"><i class="fas fa-info-circle"></i> Add multiple accounts for the same platform! You can add Instagram personal, business, etc.</p>
            
            <div id="social-links-container">
                <?php foreach ($social_links as $link): ?>
                    <?php 
                        $platform_info = array_filter($platforms, fn($p) => $p['platform_key'] === $link['platform']);
                        $platform_info = reset($platform_info);
                    ?>
                    <div class="social-link-item" data-id="<?= $link['id'] ?>">
                        <div class="social-link-header">
                            <div>
                                <i class="fas fa-grip-vertical drag-handle"></i>
                                <span class="platform-badge" style="background: <?= $platform_info['color_hex'] ?? '#6366f1' ?>;">
                                    <i class="<?= $platform_info['icon_class'] ?? 'fas fa-link' ?>"></i>
                                    <?= htmlspecialchars($link['label'] ?: $platform_info['platform_name']) ?>
                                </span>
                            </div>
                            <button type="button" class="btn btn-danger btn-small" onclick="deleteSocialLink(<?= $link['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="form-row">
                            <div>
                                <strong>Username:</strong> <?= htmlspecialchars($link['username']) ?>
                            </div>
                            <div>
                                <strong>Clicks:</strong> <?= $link['clicks'] ?>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <strong>URL:</strong> <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" style="color: #3b82f6;"><?= htmlspecialchars($link['url']) ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn btn-secondary" onclick="showAddSocialModal()"><i class="fas fa-plus"></i> Add Social Media Account</button>
        </div>
        
        <!-- Image Gallery -->
        <div class="card">
            <h2><i class="fas fa-images"></i> Image Gallery (Max 6)</h2>
            <p class="info"><i class="fas fa-info-circle"></i> Upload up to 6 images to showcase your work, products, or moments!</p>
            
            <form action="biolink_save.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_gallery">
                
                <div class="gallery-grid">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <?php $image = $gallery_images[$i] ?? null; ?>
                        <div class="gallery-item">
                            <?php if ($image): ?>
                                <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="Gallery Image">
                                <button type="button" class="remove-btn" onclick="deleteGalleryImage(<?= $image['id'] ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php else: ?>
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 32px; margin-bottom: 8px;"></i>
                                    <span>Click to Upload</span>
                                    <input type="file" name="gallery_images[]" accept="image/*" style="display: none;" onchange="this.parentElement.parentElement.style.borderColor='#22c55e';">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Gallery</button>
            </form>
        </div>
        
        <!-- Custom Links -->
        <div class="card">
            <h2><i class="fas fa-link"></i> Custom Links</h2>
            <p class="info"><i class="fas fa-info-circle"></i> Add links to your website, store, portfolio, or anything else!</p>
            
            <div id="custom-links-container">
                <?php foreach ($custom_links as $link): ?>
                    <div class="custom-link-item" data-id="<?= $link['id'] ?>">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <i class="fas fa-grip-vertical drag-handle"></i>
                                <strong><?= htmlspecialchars($link['title']) ?></strong>
                                <div style="font-size: 13px; color: #64748b; margin-top: 5px;">
                                    <i class="fas fa-link"></i> <?= htmlspecialchars($link['url']) ?>
                                </div>
                                <?php if ($link['description']): ?>
                                    <div style="font-size: 13px; color: #94a3b8; margin-top: 5px;">
                                        <?= htmlspecialchars($link['description']) ?>
                                    </div>
                                <?php endif; ?>
                                <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                                    Clicks: <?= $link['clicks'] ?>
                                </div>
                            </div>
                            <button type="button" class="btn btn-danger btn-small" onclick="deleteCustomLink(<?= $link['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn btn-secondary" onclick="showAddCustomLinkModal()"><i class="fas fa-plus"></i> Add Custom Link</button>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Add Social Media Modal -->
    <div id="addSocialModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
        <div style="max-width: 600px; margin: 50px auto; background: #1e293b; padding: 30px; border-radius: 12px;">
            <h2 style="margin-bottom: 20px;">Add Social Media Account</h2>
            
            <form action="biolink_save.php" method="POST">
                <input type="hidden" name="action" value="add_social">
                
                <div class="form-group">
                    <label>Select Platform</label>
                    <div class="platform-grid">
                        <?php foreach ($platforms as $platform): ?>
                            <div class="platform-option" onclick="selectPlatform('<?= $platform['platform_key'] ?>', '<?= htmlspecialchars($platform['base_url']) ?>')">
                                <i class="<?= $platform['icon_class'] ?>" style="color: <?= $platform['color_hex'] ?>;"></i>
                                <span><?= htmlspecialchars($platform['platform_name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="platform" id="selected_platform" required>
                </div>
                
                <div class="form-group">
                    <label>Label (Optional - e.g., "Personal", "Business")</label>
                    <input type="text" name="label" placeholder="Leave empty to use platform name">
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="social_username" required>
                </div>
                
                <div class="form-group">
                    <label>Full URL</label>
                    <input type="url" name="url" id="social_url" required placeholder="https://">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Account</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddSocialModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Custom Link Modal -->
    <div id="addCustomLinkModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; overflow-y: auto;">
        <div style="max-width: 600px; margin: 50px auto; background: #1e293b; padding: 30px; border-radius: 12px;">
            <h2 style="margin-bottom: 20px;">Add Custom Link</h2>
            
            <form action="biolink_save.php" method="POST">
                <input type="hidden" name="action" value="add_custom_link">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required placeholder="My Website, Shop, Portfolio, etc.">
                </div>
                
                <div class="form-group">
                    <label>URL</label>
                    <input type="url" name="url" required placeholder="https://">
                </div>
                
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" placeholder="Brief description..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Link</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddCustomLinkModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Platform selection
        function selectPlatform(key, baseUrl) {
            document.querySelectorAll('.platform-option').forEach(el => el.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            document.getElementById('selected_platform').value = key;
            
            // Auto-fill URL base
            const urlInput = document.getElementById('social_url');
            if (baseUrl && !urlInput.value) {
                urlInput.value = baseUrl;
            }
            
            // Auto-update URL when username changes
            document.getElementById('social_username').oninput = function() {
                if (baseUrl) {
                    urlInput.value = baseUrl + this.value;
                }
            };
        }
        
        // Modals
        function showAddSocialModal() {
            document.getElementById('addSocialModal').style.display = 'block';
        }
        
        function closeAddSocialModal() {
            document.getElementById('addSocialModal').style.display = 'none';
        }
        
        function showAddCustomLinkModal() {
            document.getElementById('addCustomLinkModal').style.display = 'block';
        }
        
        function closeAddCustomLinkModal() {
            document.getElementById('addCustomLinkModal').style.display = 'none';
        }
        
        // Delete functions
        function deleteSocialLink(id) {
            if (confirm('Delete this social media link?')) {
                window.location.href = 'biolink_save.php?action=delete_social&id=' + id;
            }
        }
        
        function deleteGalleryImage(id) {
            if (confirm('Delete this image?')) {
                window.location.href = 'biolink_save.php?action=delete_gallery&id=' + id;
            }
        }
        
        function deleteCustomLink(id) {
            if (confirm('Delete this link?')) {
                window.location.href = 'biolink_save.php?action=delete_custom&id=' + id;
            }
        }
        
        // Gallery upload click
        document.querySelectorAll('.gallery-item .upload-placeholder').forEach(item => {
            item.addEventListener('click', function() {
                this.querySelector('input[type="file"]').click();
            });
        });
    </script>
</body>
</html>