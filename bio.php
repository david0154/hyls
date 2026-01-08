<?php
// bio.php - Bio link display page with FIXED BLOCKING & AD DISPLAY
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
        
        /* Advertisement */
        .ad-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.5);
            animation: fadeIn 0.8s ease-out;
            transition: transform 0.3s;
        }
        .ad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        .ad-badge {
            display: inline-block;
            background: rgba(251, 191, 36, 0.2);
            color: #d97706;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        .ad-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        .ad-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .ad-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .ad-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        .ad-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
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
        .social-btn.blocked {
            background: linear-gradient(135deg, #94a3b8, #64748b);
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
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
        .social-btn:not(.blocked):hover::before { width: 300px; height: 300px; }
        .social-btn:not(.blocked):hover {
            transform: translateY(-8px) scale(1.1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
            filter: brightness(1.1);
        }
        .social-btn .custom-icon {
            font-size: 16px;
            font-weight: 900;
            line-height: 1;
        }
        .blocked-overlay {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            border: 2px solid white;
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
        .contact-btn.blocked {
            background: linear-gradient(135deg, #94a3b8, #64748b);
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
        .contact-btn.blocked::after {
            content: 'Link Blocked';
            margin-left: 8px;
            font-size: 11px;
            background: #ef4444;
            padding: 2px 8px;
            border-radius: 8px;
        }
        .contact-btn:not(.blocked):hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25);
            filter: brightness(1.05);
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
        }
        @media (max-width: 480px) {
            .profile-card { padding: 25px 18px; }
            .display-name { font-size: 22px; }
            .bio-text { font-size: 14px; padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($ad && !empty($ad['url'])): ?>
        <div class="ad-card">
            <span class="ad-badge"><i class="fas fa-ad"></i> Sponsored</span>
            <?php if (!empty($ad['image_url'])): ?>
            <img src="<?= htmlspecialchars($ad['image_url']) ?>" alt="<?= htmlspecialchars($ad['title']) ?>" class="ad-image">
            <?php endif; ?>
            <h3 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h3>
            <?php if (!empty($ad['description'])): ?>
            <p class="ad-description"><?= htmlspecialchars($ad['description']) ?></p>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($ad['url']) ?>" target="_blank" rel="noopener sponsored" class="ad-cta">
                <?= htmlspecialchars($ad['cta_text'] ?? 'Visit Now') ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
        
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
            // ALL 29 PLATFORMS WITH PROPER BLOCKING
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
            $has_blocked_socials = false;
            foreach ($socials as $platform => $data) {
                $url = $bio[$platform] ?? '';
                if (!empty($url)) {
                    $has_socials = true;
                    $enabled_col = $platform . '_enabled';
                    $is_blocked = isset($bio[$enabled_col]) && $bio[$enabled_col] == 0;
                    if ($is_blocked) {
                        $has_blocked_socials = true;
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
                        $enabled_col = $platform . '_enabled';
                        $is_blocked = isset($bio[$enabled_col]) && $bio[$enabled_col] == 0;
                        
                        if ($platform === 'website' && !preg_match('/^https?:\/\//i', $url)) {
                            $url = 'https://' . $url;
                        }
                        
                        $icon_html = '';
                        if (!empty($data['icon'])) {
                            $icon_html = '<i class="' . htmlspecialchars($data['icon']) . '"></i>';
                        } else if (!empty($data['text'])) {
                            $icon_html = '<span class="custom-icon">' . htmlspecialchars($data['text']) . '</span>';
                        }
                        
                        $blocked_class = $is_blocked ? ' blocked' : '';
                        $href = $is_blocked ? '#' : htmlspecialchars($url);
                        $target = $is_blocked ? '' : 'target="_blank" rel="noopener"';
                        $title = $is_blocked ? 'Link Blocked' : ucfirst($platform);
                        
                        echo '<a href="' . $href . '" ' . $target . ' class="social-btn' . $blocked_class . '" title="' . $title . '">';
                        echo $icon_html;
                        if ($is_blocked) {
                            echo '<span class="blocked-overlay"><i class="fas fa-ban"></i></span>';
                        }
                        echo '</a>';
                    }
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php 
            $has_email = !empty($bio['email']);
            $has_phone = !empty($bio['phone']);
            $email_blocked = $has_email && isset($bio['email_enabled']) && $bio['email_enabled'] == 0;
            $phone_blocked = $has_phone && isset($bio['phone_enabled']) && $bio['phone_enabled'] == 0;
            
            if ($has_email || $has_phone): 
            ?>
            <div class="divider"></div>
            <div class="section-title"><i class="fas fa-address-card"></i> Contact</div>
            <div class="contact-links">
                <?php if ($has_email): ?>
                <a href="<?= $email_blocked ? '#' : 'mailto:' . htmlspecialchars($bio['email']) ?>" class="contact-btn<?= $email_blocked ? ' blocked' : '' ?>">
                    <i class="fas fa-envelope"></i> <?= $email_blocked ? 'Email (Blocked)' : 'Email Me' ?>
                </a>
                <?php endif; ?>
                
                <?php if ($has_phone): ?>
                <a href="<?= $phone_blocked ? '#' : 'tel:' . htmlspecialchars($bio['phone']) ?>" class="contact-btn<?= $phone_blocked ? ' blocked' : '' ?>">
                    <i class="fas fa-phone"></i> <?= $phone_blocked ? 'Call (Blocked)' : 'Call Me' ?>
                </a>
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