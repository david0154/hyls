<?php
// bio.php - Bio link display page with VIDEOS, gallery, and ad display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$bio = null;
$ad = null;
$settings = [];
$gallery_images = [];
$social_videos = []; // NEW: For video embeds

try {
    if (!class_exists('Database')) {
        throw new Exception('Database class not found');
    }
    
    $db = new Database();
    
    $username = $_GET['u'] ?? '';
    $username = trim($username);

    if (empty($username)) {
        header('Location: index.php');
        exit;
    }

    $stmt = $db->prepare("SELECT b.*, u.username as user_username, u.id as user_id FROM bio_links b JOIN users u ON b.user_id = u.id WHERE u.username = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    $stmt->execute([$username]);
    $bio = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$bio) {
        header('Location: index.php');
        exit;
    }

    // Get gallery images
    try {
        $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
        $stmt->execute([$bio['user_id']]);
        $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Gallery error: " . $e->getMessage());
    }
    
    // NEW: Get social media videos
    try {
        // First, get bio_profile_id from user_id
        $stmt = $db->prepare("SELECT id FROM bio_profiles WHERE user_id = ?");
        $stmt->execute([$bio['user_id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
            $stmt->execute([$profile['id']]);
            $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update views for each video
            if (!empty($social_videos)) {
                $update_stmt = $db->prepare("UPDATE bio_social_videos SET views = views + 1 WHERE id = ?");
                foreach ($social_videos as $video) {
                    $update_stmt->execute([$video['id']]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Video error: " . $e->getMessage());
    }

    try {
        $update_stmt = $db->prepare("UPDATE bio_links SET views = views + 1 WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->execute([$bio['id']]);
        }
    } catch (Exception $e) {
        error_log("Failed to update views: " . $e->getMessage());
    }

    $settings = getSettings($db);
    if (!$settings) {
        $settings = [];
    }

    // Get active advertisement
    $ad_stmt = $db->prepare("SELECT * FROM advertisements WHERE is_active = 1 ORDER BY position ASC LIMIT 1");
    if ($ad_stmt) {
        $ad_stmt->execute();
        $ad = $ad_stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    if (empty($bio['theme_color'])) {
        $bio['theme_color'] = '#6366f1';
    }
    
} catch (Exception $e) {
    error_log("Bio Page Error: " . $e->getMessage());
    die('<div style="padding: 40px; text-align: center; font-family: sans-serif;"><h1>Error Loading Bio</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></div>');
}

if (!$bio) {
    header('Location: index.php');
    exit;
}

$adjusted_color = function_exists('adjustColor') ? adjustColor($bio['theme_color'], -20) : $bio['theme_color'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($bio['display_name'] ?? $bio['username']) ?> - <?= htmlspecialchars(SITE_NAME) ?></title>
    <meta name="description" content="<?= htmlspecialchars(substr($bio['bio'] ?? '', 0, 160)) ?>">
    <meta name="keywords" content="<?= htmlspecialchars(SITE_KEYWORDS ?? '') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($bio['display_name'] ?? $bio['username']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(substr($bio['bio'] ?? '', 0, 160)) ?>">
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(SITE_URL) ?>/assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if (!empty($settings['ga_tracking_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($settings['ga_tracking_id']) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($settings['ga_tracking_id']) ?>');
    </script>
    <?php endif; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?> 0%, <?= htmlspecialchars($adjusted_color) ?> 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
        .container { max-width: 700px; width: 100%; z-index: 1; }
        
        /* Cover Image */
        .cover-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 30px 30px 0 0;
            margin-bottom: -60px;
        }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2), 0 0 1px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .profile-image-wrapper {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>);
            border-radius: 50%;
            padding: 4px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.8s ease-out;
        }
        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .display-name {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .username {
            color: #64748b;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 500;
        }
        .badge-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99, 102, 241, 0.1);
            color: <?= htmlspecialchars($bio['theme_color']) ?>;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid <?= htmlspecialchars($bio['theme_color']) ?>;
        }
        .bio-text {
            color: #334155;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 15px;
            background: rgba(0, 0, 0, 0.02);
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid <?= htmlspecialchars($bio['theme_color']) ?>;
        }
        
        /* NEW: Social Media Videos Section */
        .videos-section {
            margin: 30px 0;
        }
        .videos-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        .video-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 2px solid <?= htmlspecialchars($bio['theme_color']) ?>;
        }
        .video-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .video-platform-icon {
            font-size: 24px;
            color: <?= htmlspecialchars($bio['theme_color']) ?>;
        }
        .video-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            flex: 1;
        }
        .video-badge {
            background: <?= htmlspecialchars($bio['theme_color']) ?>;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .video-embed {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            background: #000;
            margin: 12px 0;
        }
        .video-embed iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        .video-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-top: 12px;
        }
        .video-stats {
            display: flex;
            gap: 15px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0,0,0,0.1);
            font-size: 13px;
            color: #94a3b8;
        }
        .video-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Advertisement */
        .ad-container {
            background: linear-gradient(135deg, #fff5f5, #fffbeb);
            border: 2px dashed #fbbf24;
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
            transition: transform 0.3s;
        }
        .ad-container:hover { transform: scale(1.02); }
        .ad-label {
            font-size: 11px;
            color: #f59e0b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .ad-image {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin: 10px 0;
        }
        .ad-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin: 10px 0;
        }
        .ad-description {
            font-size: 14px;
            color: #64748b;
            margin: 10px 0;
            line-height: 1.6;
        }
        .ad-cta {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            margin-top: 10px;
            transition: transform 0.3s;
        }
        .ad-cta:hover { transform: translateY(-2px); }
        
        /* Gallery */
        .gallery-section {
            margin: 25px 0;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        .gallery-item {
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .gallery-item:hover { transform: scale(1.05); }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
            margin: 25px 0;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 25px;
            margin-bottom: 16px;
        }
        .social-links {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>);
            color: white;
            text-decoration: none;
            font-size: 22px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 2px solid white;
            position: relative;
            overflow: hidden;
        }
        .social-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .social-btn:hover::before { width: 300px; height: 300px; }
        .social-btn:hover {
            transform: translateY(-8px) scale(1.1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
            filter: brightness(1.1);
        }
        
        /* Blocked link styling */
        .social-btn.blocked {
            background: #e2e8f0;
            cursor: not-allowed;
            opacity: 0.5;
            position: relative;
        }
        .social-btn.blocked::after {
            content: '🚫';
            position: absolute;
            font-size: 20px;
        }
        .social-btn.blocked:hover {
            transform: none;
            filter: none;
        }
        
        .social-btn .custom-icon {
            font-size: 16px;
            font-weight: 900;
            line-height: 1;
        }
        
        .contact-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 16px;
            animation: fadeIn 0.8s ease-out 0.4s both;
        }
        .contact-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s;
            font-size: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            border: 2px solid white;
        }
        .contact-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25);
            filter: brightness(1.05);
        }
        .contact-btn.blocked {
            background: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }
        .contact-btn.blocked:hover {
            transform: none;
            filter: none;
        }
        .powered-by {
            text-align: center;
            margin-top: 25px;
            animation: fadeIn 0.8s ease-out 0.6s both;
        }
        .powered-by a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.9);
            color: <?= htmlspecialchars($bio['theme_color']) ?>;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s;
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid white;
        }
        .powered-by a:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        @media (max-width: 768px) {
            .profile-card { padding: 35px 25px; border-radius: 25px; }
            .display-name { font-size: 26px; }
            .profile-image-wrapper { width: 120px; height: 120px; }
            .social-btn { width: 50px; height: 50px; font-size: 20px; }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
            .cover-image { height: 150px; }
            .video-embed { padding-bottom: 75%; } /* Adjust for mobile */
        }
        @media (max-width: 480px) {
            .profile-card { padding: 25px 18px; }
            .display-name { font-size: 22px; }
            .bio-text { font-size: 14px; padding: 15px; }
            .video-card { padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <?php if (!empty($bio['cover_image'])): ?>
            <img src="<?= htmlspecialchars(SITE_URL) ?>/<?= htmlspecialchars($bio['cover_image']) ?>" alt="Cover" class="cover-image" loading="lazy">
            <?php endif; ?>
            
            <div class="profile-image-wrapper">
                <?php if (!empty($bio['profile_image'])): ?>
                <img src="<?= htmlspecialchars(SITE_URL) ?>/<?= htmlspecialchars($bio['profile_image']) ?>" alt="Profile" class="profile-image" loading="lazy">
                <?php else: ?>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($bio['display_name'] ?? $bio['username']) ?>&size=140&background=<?= str_replace('#', '', htmlspecialchars($bio['theme_color'])) ?>&color=fff&bold=true" alt="Profile" class="profile-image">
                <?php endif; ?>
            </div>
            
            <h1 class="display-name"><?= htmlspecialchars($bio['display_name'] ?? $bio['username']) ?></h1>
            <p class="username">@<?= htmlspecialchars($bio['username']) ?></p>
            
            <div class="badge-container">
                <span class="badge"><i class="fas fa-eye"></i> <?= number_format($bio['views'] ?? 0) ?> views</span>
            </div>
            
            <?php if (!empty($bio['bio'])): ?>
            <p class="bio-text"><?= nl2br(htmlspecialchars($bio['bio'])) ?></p>
            <?php endif; ?>
            
            <?php if ($ad): ?>
            <div class="ad-container">
                <div class="ad-label">📢 Sponsored</div>
                <?php if (!empty($ad['image_url'])): ?>
                <img src="<?= htmlspecialchars($ad['image_url']) ?>" alt="<?= htmlspecialchars($ad['title']) ?>" class="ad-image">
                <?php endif; ?>
                <div class="ad-title"><?= htmlspecialchars($ad['title']) ?></div>
                <?php if (!empty($ad['description'])): ?>
                <div class="ad-description"><?= htmlspecialchars($ad['description']) ?></div>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($ad['url']) ?>" target="_blank" rel="noopener sponsored" class="ad-cta">
                    <?= htmlspecialchars($ad['cta_text'] ?? 'Visit Now') ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php // NEW: Display Social Media Videos ?>
            <?php if (!empty($social_videos)): ?>
            <div class="divider"></div>
            <div class="section-title"><i class="fas fa-video"></i> Latest Videos</div>
            <div class="videos-section">
                <div class="videos-grid">
                    <?php 
                    $platform_icons = [
                        'youtube' => 'fab fa-youtube',
                        'facebook' => 'fab fa-facebook',
                        'instagram' => 'fab fa-instagram',
                        'tiktok' => 'fab fa-tiktok',
                        'vimeo' => 'fab fa-vimeo',
                        'dailymotion' => 'fas fa-play-circle',
                        'twitter' => 'fab fa-x-twitter',
                        'twitch' => 'fab fa-twitch'
                    ];
                    
                    foreach ($social_videos as $video): 
                    ?>
                    <div class="video-card" data-platform="<?= htmlspecialchars($video['platform']) ?>">
                        <?php if (!empty($video['title']) || !empty($video['platform'])): ?>
                        <div class="video-card-header">
                            <i class="video-platform-icon <?= $platform_icons[$video['platform']] ?? 'fas fa-video' ?>"></i>
                            <?php if (!empty($video['title'])): ?>
                            <div class="video-title"><?= htmlspecialchars($video['title']) ?></div>
                            <?php endif; ?>
                            <?php if ($video['autoplay']): ?>
                            <span class="video-badge">Autoplay</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="video-embed">
                            <?= $video['embed_code'] ?>
                        </div>
                        
                        <?php if (!empty($video['description'])): ?>
                        <div class="video-description"><?= nl2br(htmlspecialchars($video['description'])) ?></div>
                        <?php endif; ?>
                        
                        <div class="video-stats">
                            <div class="video-stat">
                                <i class="fas fa-eye"></i>
                                <span><?= number_format($video['views'] ?? 0) ?> views</span>
                            </div>
                            <div class="video-stat">
                                <i class="<?= $platform_icons[$video['platform']] ?? 'fas fa-video' ?>"></i>
                                <span><?= ucfirst($video['platform']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($gallery_images)): ?>
            <div class="divider"></div>
            <div class="section-title"><i class="fas fa-images"></i> Gallery</div>
            <div class="gallery-section">
                <div class="gallery-grid">
                    <?php foreach ($gallery_images as $img): ?>
                    <div class="gallery-item">
                        <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Gallery" loading="lazy">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            // ALL 29 PLATFORMS WITH PROPER ICONS
            $socials = [
                'facebook' => ['icon' => 'fab fa-facebook-f', 'text' => ''],
                'instagram' => ['icon' => 'fab fa-instagram', 'text' => ''],
                'twitter' => ['icon' => 'fab fa-x-twitter', 'text' => ''],
                'threads' => ['icon' => 'fab fa-threads', 'text' => '@'],
                'tiktok' => ['icon' => 'fab fa-tiktok', 'text' => ''],
                'youtube' => ['icon' => 'fab fa-youtube', 'text' => ''],
                'linkedin' => ['icon' => 'fab fa-linkedin-in', 'text' => ''],
                'github' => ['icon' => 'fab fa-github', 'text' => ''],
                'discord' => ['icon' => 'fab fa-discord', 'text' => ''],
                'twitch' => ['icon' => 'fab fa-twitch', 'text' => ''],
                'telegram' => ['icon' => 'fab fa-telegram', 'text' => ''],
                'whatsapp' => ['icon' => 'fab fa-whatsapp', 'text' => ''],
                'spotify' => ['icon' => 'fab fa-spotify', 'text' => ''],
                'reddit' => ['icon' => 'fab fa-reddit-alien', 'text' => ''],
                'snapchat' => ['icon' => 'fab fa-snapchat-ghost', 'text' => ''],
                'pinterest' => ['icon' => 'fab fa-pinterest-p', 'text' => ''],
                'medium' => ['icon' => 'fab fa-medium', 'text' => ''],
                'substack' => ['icon' => 'fas fa-newspaper', 'text' => ''],
                'patreon' => ['icon' => 'fab fa-patreon', 'text' => ''],
                'onlyfans' => ['icon' => '', 'text' => 'OF'],
                'bluesky' => ['icon' => 'fas fa-cloud', 'text' => ''],
                'mastodon' => ['icon' => 'fab fa-mastodon', 'text' => ''],
                'line' => ['icon' => 'fab fa-line', 'text' => ''],
                'cashapp' => ['icon' => 'fas fa-dollar-sign', 'text' => ''],
                'venmo' => ['icon' => '', 'text' => 'V'],
                'paypal' => ['icon' => 'fab fa-paypal', 'text' => ''],
                'website' => ['icon' => 'fas fa-globe', 'text' => '']
            ];
            
            $has_socials = false;
            $has_enabled_socials = false;
            foreach ($socials as $platform => $data) {
                if (!empty($bio[$platform])) {
                    $has_socials = true;
                    // Check if enabled (default to true if column doesn't exist)
                    $enabled = isset($bio[$platform . '_enabled']) ? ($bio[$platform . '_enabled'] == 1) : true;
                    if ($enabled) {
                        $has_enabled_socials = true;
                        break;
                    }
                }
            }
            
            if ($has_socials):
            ?>
            <div class="divider"></div>
            <div class="section-title"><i class="fas fa-share-alt"></i> Connect</div>
            <div class="social-links">
                <?php
                foreach ($socials as $platform => $data) {
                    $url = $bio[$platform] ?? '';
                    
                    if (!empty($url)) {
                        // Check if link is enabled (default to true if column doesn't exist)
                        $enabled = isset($bio[$platform . '_enabled']) ? ($bio[$platform . '_enabled'] == 1) : true;
                        
                        if ($platform === 'website' && !preg_match('/^https?:\/\//i', $url)) {
                            $url = 'https://' . $url;
                        }
                        
                        $icon_html = '';
                        if (!empty($data['icon'])) {
                            $icon_html = '<i class="' . htmlspecialchars($data['icon']) . '"></i>';
                        } else if (!empty($data['text'])) {
                            $icon_html = '<span class="custom-icon">' . htmlspecialchars($data['text']) . '</span>';
                        }
                        
                        if ($enabled) {
                            echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="social-btn" title="' . ucfirst($platform) . '">' . $icon_html . '</a>';
                        } else {
                            // Show blocked link with disabled styling
                            echo '<span class="social-btn blocked" title="' . ucfirst($platform) . ' (Blocked)">' . $icon_html . '</span>';
                        }
                    }
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php 
            $has_email = !empty($bio['email']);
            $has_phone = !empty($bio['phone']);
            $email_enabled = isset($bio['email_enabled']) ? ($bio['email_enabled'] == 1) : true;
            $phone_enabled = isset($bio['phone_enabled']) ? ($bio['phone_enabled'] == 1) : true;
            
            if ($has_email || $has_phone): 
            ?>
            <div class="divider"></div>
            <div class="section-title"><i class="fas fa-address-card"></i> Contact</div>
            <div class="contact-links">
                <?php if ($has_email): ?>
                    <?php if ($email_enabled): ?>
                    <a href="mailto:<?= htmlspecialchars($bio['email']) ?>" class="contact-btn">
                        <i class="fas fa-envelope"></i> Email Me
                    </a>
                    <?php else: ?>
                    <span class="contact-btn blocked" title="Email Blocked">
                        <i class="fas fa-envelope"></i> Email (Blocked)
                    </span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($has_phone): ?>
                    <?php if ($phone_enabled): ?>
                    <a href="tel:<?= htmlspecialchars($bio['phone']) ?>" class="contact-btn">
                        <i class="fas fa-phone"></i> Call Me
                    </a>
                    <?php else: ?>
                    <span class="contact-btn blocked" title="Phone Blocked">
                        <i class="fas fa-phone"></i> Phone (Blocked)
                    </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="powered-by">
            <a href="<?= htmlspecialchars(SITE_URL) ?>" target="_blank" rel="noopener">
                <i class="fas fa-link"></i> Powered by <?= htmlspecialchars(SITE_NAME) ?>
            </a>
        </div>
    </div>
</body>
</html>