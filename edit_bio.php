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

// Get user's bio profile
$stmt = $db->prepare("SELECT bp.*, u.username FROM bio_profiles bp JOIN users u ON bp.user_id = u.id WHERE bp.user_id = ?");
$stmt->execute([$user_id]);
$bio_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bio_profile) {
    // Create default profile
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("INSERT INTO bio_profiles (user_id, username, display_name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->execute([$user_id, $user['username'], $user['username']]);
    
    header('Location: edit_bio.php');
    exit;
}

// Get social links
$stmt = $db->prepare("SELECT * FROM bio_social_links WHERE bio_profile_id = ? ORDER BY display_order ASC");
$stmt->execute([$bio_profile['id']]);
$social_links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get gallery images
$stmt = $db->prepare("SELECT * FROM bio_gallery WHERE bio_profile_id = ? ORDER BY display_order ASC LIMIT 6");
$stmt->execute([$bio_profile['id']]);
$gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get custom links
$stmt = $db->prepare("SELECT * FROM bio_custom_links WHERE bio_profile_id = ? ORDER BY display_order ASC");
$stmt->execute([$bio_profile['id']]);
$custom_links = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// All 29 social platforms
$social_platforms = [
    'instagram' => ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'color' => '#E4405F', 'base_url' => 'https://instagram.com/'],
    'facebook' => ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'color' => '#1877F2', 'base_url' => 'https://facebook.com/'],
    'twitter' => ['name' => 'Twitter/X', 'icon' => 'fab fa-x-twitter', 'color' => '#000000', 'base_url' => 'https://twitter.com/'],
    'tiktok' => ['name' => 'TikTok', 'icon' => 'fab fa-tiktok', 'color' => '#000000', 'base_url' => 'https://tiktok.com/@'],
    'youtube' => ['name' => 'YouTube', 'icon' => 'fab fa-youtube', 'color' => '#FF0000', 'base_url' => 'https://youtube.com/@'],
    'linkedin' => ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'color' => '#0A66C2', 'base_url' => 'https://linkedin.com/in/'],
    'github' => ['name' => 'GitHub', 'icon' => 'fab fa-github', 'color' => '#181717', 'base_url' => 'https://github.com/'],
    'twitch' => ['name' => 'Twitch', 'icon' => 'fab fa-twitch', 'color' => '#9146FF', 'base_url' => 'https://twitch.tv/'],
    'discord' => ['name' => 'Discord', 'icon' => 'fab fa-discord', 'color' => '#5865F2', 'base_url' => 'https://discord.gg/'],
    'reddit' => ['name' => 'Reddit', 'icon' => 'fab fa-reddit', 'color' => '#FF4500', 'base_url' => 'https://reddit.com/u/'],
    'snapchat' => ['name' => 'Snapchat', 'icon' => 'fab fa-snapchat', 'color' => '#FFFC00', 'base_url' => 'https://snapchat.com/add/'],
    'telegram' => ['name' => 'Telegram', 'icon' => 'fab fa-telegram', 'color' => '#26A5E4', 'base_url' => 'https://t.me/'],
    'whatsapp' => ['name' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'color' => '#25D366', 'base_url' => 'https://wa.me/'],
    'line' => ['name' => 'LINE', 'icon' => 'fab fa-line', 'color' => '#00B900', 'base_url' => 'https://line.me/ti/p/'],
    'wechat' => ['name' => 'WeChat', 'icon' => 'fab fa-weixin', 'color' => '#7BB32E', 'base_url' => ''],
    'pinterest' => ['name' => 'Pinterest', 'icon' => 'fab fa-pinterest', 'color' => '#E60023', 'base_url' => 'https://pinterest.com/'],
    'spotify' => ['name' => 'Spotify', 'icon' => 'fab fa-spotify', 'color' => '#1DB954', 'base_url' => 'https://open.spotify.com/user/'],
    'soundcloud' => ['name' => 'SoundCloud', 'icon' => 'fab fa-soundcloud', 'color' => '#FF3300', 'base_url' => 'https://soundcloud.com/'],
    'medium' => ['name' => 'Medium', 'icon' => 'fab fa-medium', 'color' => '#000000', 'base_url' => 'https://medium.com/@'],
    'behance' => ['name' => 'Behance', 'icon' => 'fab fa-behance', 'color' => '#1769FF', 'base_url' => 'https://behance.net/'],
    'dribbble' => ['name' => 'Dribbble', 'icon' => 'fab fa-dribbble', 'color' => '#EA4C89', 'base_url' => 'https://dribbble.com/'],
    'patreon' => ['name' => 'Patreon', 'icon' => 'fab fa-patreon', 'color' => '#FF424D', 'base_url' => 'https://patreon.com/'],
    'onlyfans' => ['name' => 'OnlyFans', 'icon' => 'fas fa-fire', 'color' => '#00AFF0', 'base_url' => 'https://onlyfans.com/'],
    'cashapp' => ['name' => 'Cash App', 'icon' => 'fas fa-dollar-sign', 'color' => '#00D64F', 'base_url' => 'https://cash.app/$'],
    'venmo' => ['name' => 'Venmo', 'icon' => 'fas fa-money-bill-wave', 'color' => '#3D95CE', 'base_url' => 'https://venmo.com/'],
    'paypal' => ['name' => 'PayPal', 'icon' => 'fab fa-paypal', 'color' => '#00457C', 'base_url' => 'https://paypal.me/'],
    'email' => ['name' => 'Email', 'icon' => 'fas fa-envelope', 'color' => '#EA4335', 'base_url' => 'mailto:'],
    'website' => ['name' => 'Website', 'icon' => 'fas fa-globe', 'color' => '#6366F1', 'base_url' => 'https://'],
    'custom' => ['name' => 'Custom Link', 'icon' => 'fas fa-link', 'color' => '#64748B', 'base_url' => '']
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Bio Link - <?= SITE_NAME ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #22c55e; margin-bottom: 10px; }
        .bio-url { background: #1e293b; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; }
        .bio-url input { flex: 1; padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; }
        .bio-url button { padding: 10px 20px; background: #3b82f6; border: none; border-radius: 6px; color: white; cursor: pointer; }
        .success { background: #22c55e; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #ef4444; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .section { background: #1e293b; padding: 25px; border-radius: 12px; margin-bottom: 20px; }
        .section h2 { color: #22c55e; margin-bottom: 15px; font-size: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #94a3b8; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #22c55e; color: white; }
        .btn-primary:hover { background: #16a34a; }
        .btn-secondary { background: #3b82f6; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .image-upload { display: flex; gap: 10px; align-items: center; }
        .image-preview { width: 100px; height: 100px; border-radius: 8px; object-fit: cover; border: 2px solid #334155; }
        
        /* Social Media Grid */
        .social-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .social-card { background: #0f172a; padding: 15px; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s; border: 2px solid #334155; }
        .social-card:hover { border-color: #3b82f6; transform: translateY(-2px); }
        .social-card.selected { border-color: #22c55e; background: #1e293b; }
        .social-card i { font-size: 32px; margin-bottom: 8px; }
        .social-card .name { font-size: 13px; font-weight: 600; }
        
        /* Added Social Links */
        .added-links { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
        .added-link { background: #0f172a; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .added-link .info { flex: 1; }
        .added-link .platform { font-weight: 600; color: #22c55e; margin-bottom: 4px; }
        .added-link .label { color: #94a3b8; font-size: 13px; }
        .added-link .username { color: #e2e8f0; }
        .added-link .actions { display: flex; gap: 8px; }
        
        /* Gallery Grid */
        .gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .gallery-slot { aspect-ratio: 1; border: 2px dashed #334155; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; background: #0f172a; position: relative; overflow: hidden; }
        .gallery-slot:hover { border-color: #3b82f6; }
        .gallery-slot img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-slot .upload-icon { font-size: 32px; color: #64748b; }
        .gallery-slot .delete-btn { position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.3s; }
        .gallery-slot:hover .delete-btn { opacity: 1; }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1e293b; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; }
        .modal-content h3 { color: #22c55e; margin-bottom: 20px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
        
        @media (max-width: 768px) {
            .social-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-edit"></i> Edit Bio Link</h1>
        <p style="color: #94a3b8; margin-bottom: 20px;">Customize your bio link page</p>
        
        <div class="bio-url">
            <input type="text" value="<?= SITE_URL ?>/bio.php?u=<?= htmlspecialchars($bio_profile['username']) ?>" readonly onclick="this.select()">
            <button onclick="copyBioUrl()"><i class="fas fa-copy"></i> Copy</button>
            <a href="/bio.php?u=<?= htmlspecialchars($bio_profile['username']) ?>" target="_blank" class="btn btn-secondary"><i class="fas fa-external-link-alt"></i> View</a>
        </div>
        
        <?php if ($success): ?>
        <div class="success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Basic Profile -->
        <form action="biolink_save.php" method="POST" enctype="multipart/form-data" class="section">
            <input type="hidden" name="action" value="save_profile">
            <h2><i class="fas fa-user"></i> Basic Profile</h2>
            
            <div class="form-group">
                <label>Username (Bio URL)</label>
                <input type="text" name="username" value="<?= htmlspecialchars($bio_profile['username']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Display Name</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($bio_profile['display_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio"><?= htmlspecialchars($bio_profile['bio'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Profile Picture</label>
                <div class="image-upload">
                    <?php if ($bio_profile['profile_picture']): ?>
                    <img src="<?= htmlspecialchars($bio_profile['profile_picture']) ?>" class="image-preview">
                    <?php endif; ?>
                    <input type="file" name="profile_picture" accept="image/*">
                </div>
            </div>
            
            <div class="form-group">
                <label>Cover Image</label>
                <div class="image-upload">
                    <?php if ($bio_profile['cover_image']): ?>
                    <img src="<?= htmlspecialchars($bio_profile['cover_image']) ?>" class="image-preview">
                    <?php endif; ?>
                    <input type="file" name="cover_image" accept="image/*">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
        </form>
        
        <!-- Social Media Links -->
        <div class="section">
            <h2><i class="fas fa-share-alt"></i> Social Media Links</h2>
            <p style="color: #94a3b8; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Add multiple accounts per platform!</p>
            
            <div class="social-grid">
                <?php foreach ($social_platforms as $platform => $data): ?>
                <div class="social-card" onclick="openSocialModal('<?= $platform ?>')" style="color: <?= $data['color'] ?>">
                    <i class="<?= $data['icon'] ?>"></i>
                    <div class="name"><?= $data['name'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($social_links): ?>
            <div class="added-links">
                <?php foreach ($social_links as $link): ?>
                <div class="added-link">
                    <div class="info">
                        <div class="platform"><i class="<?= $social_platforms[$link['platform']]['icon'] ?? 'fas fa-link' ?>"></i> <?= $social_platforms[$link['platform']]['name'] ?? $link['platform'] ?></div>
                        <div class="label"><?= htmlspecialchars($link['label']) ?> - @<?= htmlspecialchars($link['username']) ?></div>
                        <div class="username" style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($link['url']) ?></div>
                    </div>
                    <div class="actions">
                        <span style="color: #94a3b8;"><i class="fas fa-mouse-pointer"></i> <?= number_format($link['clicks'] ?? 0) ?> clicks</span>
                        <a href="biolink_save.php?action=delete_social&id=<?= $link['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this account?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Image Gallery -->
        <div class="section">
            <h2><i class="fas fa-images"></i> Image Gallery (Max 6)</h2>
            <p style="color: #94a3b8; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Upload up to 6 images to showcase</p>
            
            <form action="biolink_save.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_gallery">
                <div class="gallery-grid">
                    <?php 
                    for ($i = 0; $i < 6; $i++): 
                        $image = $gallery_images[$i] ?? null;
                    ?>
                    <div class="gallery-slot">
                        <?php if ($image): ?>
                            <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="Gallery">
                            <div class="delete-btn" onclick="if(confirm('Delete image?')) window.location='biolink_save.php?action=delete_gallery&id=<?= $image['id'] ?>'"><i class="fas fa-times"></i></div>
                        <?php else: ?>
                            <div class="upload-icon"><i class="fas fa-plus"></i></div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                <input type="file" name="gallery_images[]" accept="image/*" multiple style="margin-top: 15px;">
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;"><i class="fas fa-upload"></i> Upload Images</button>
            </form>
        </div>
        
        <!-- Custom Links -->
        <div class="section">
            <h2><i class="fas fa-link"></i> Custom Links</h2>
            <button onclick="openCustomLinkModal()" class="btn btn-secondary"><i class="fas fa-plus"></i> Add Custom Link</button>
            
            <?php if ($custom_links): ?>
            <div class="added-links" style="margin-top: 20px;">
                <?php foreach ($custom_links as $link): ?>
                <div class="added-link">
                    <div class="info">
                        <div class="platform"><i class="fas fa-link"></i> <?= htmlspecialchars($link['title']) ?></div>
                        <div class="username"><?= htmlspecialchars($link['url']) ?></div>
                        <?php if ($link['description']): ?>
                        <div class="label"><?= htmlspecialchars($link['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <span style="color: #94a3b8;"><i class="fas fa-mouse-pointer"></i> <?= number_format($link['clicks'] ?? 0) ?> clicks</span>
                        <a href="biolink_save.php?action=delete_custom&id=<?= $link['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this link?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Social Modal -->
    <div id="socialModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-share-alt"></i> Add Social Account</h3>
            <form action="biolink_save.php" method="POST">
                <input type="hidden" name="action" value="add_social">
                <input type="hidden" name="platform" id="modal_platform">
                
                <div class="form-group">
                    <label>Label (e.g., Personal, Business, Main)</label>
                    <input type="text" name="label" placeholder="Personal" required>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="modal_username" placeholder="" required>
                </div>
                
                <div class="form-group">
                    <label>Full URL</label>
                    <input type="url" name="url" id="modal_url" placeholder="" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Account</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Custom Link Modal -->
    <div id="customLinkModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-link"></i> Add Custom Link</h3>
            <form action="biolink_save.php" method="POST">
                <input type="hidden" name="action" value="add_custom_link">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" placeholder="My Portfolio" required>
                </div>
                
                <div class="form-group">
                    <label>URL</label>
                    <input type="url" name="url" placeholder="https://" required>
                </div>
                
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input type="text" name="description" placeholder="Check out my work">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Link</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const platforms = <?= json_encode($social_platforms) ?>;
        
        function openSocialModal(platform) {
            const modal = document.getElementById('socialModal');
            document.getElementById('modal_platform').value = platform;
            document.getElementById('modal_username').placeholder = '@' + platform;
            document.getElementById('modal_url').placeholder = platforms[platform].base_url + 'username';
            modal.classList.add('active');
        }
        
        function openCustomLinkModal() {
            document.getElementById('customLinkModal').classList.add('active');
        }
        
        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }
        
        function copyBioUrl() {
            const input = document.querySelector('.bio-url input');
            input.select();
            document.execCommand('copy');
            alert('Bio URL copied!');
        }
        
        // Auto-fill URL when username changes
        document.getElementById('modal_username')?.addEventListener('input', function(e) {
            const platform = document.getElementById('modal_platform').value;
            const username = e.target.value.replace('@', '');
            if (platforms[platform] && username) {
                document.getElementById('modal_url').value = platforms[platform].base_url + username;
            }
        });
    </script>
</body>
</html>