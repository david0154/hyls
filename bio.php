<?php
// bio.php - Bio link display page with enhanced design
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$bio = null;
$ad = null;
$settings = [];

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

    $stmt = $db->prepare("SELECT b.*, u.username as user_username FROM bio_links b JOIN users u ON b.user_id = u.id WHERE u.username = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    $stmt->execute([$username]);
    $bio = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$bio) {
        header('Location: index.php');
        exit;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .container {
            max-width: 700px;
            width: 100%;
            z-index: 1;
        }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2),
                        0 0 1px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, rgba(<?= htmlspecialchars($bio['theme_color']) ?>, 0.1), rgba(<?= htmlspecialchars($adjusted_color) ?>, 0.1));
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
            font-size: 24px;
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
        
        .social-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .social-btn:hover {
            transform: translateY(-8px) scale(1.1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
            filter: brightness(1.1);
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
            position: relative;
            overflow: hidden;
        }
        
        .contact-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.2);
            transition: left 0.3s;
            z-index: 0;
        }
        
        .contact-btn:hover::before {
            left: 100%;
        }
        
        .contact-btn span {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
        }
        
        .contact-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25);
            filter: brightness(1.05);
        }
        
        .contact-btn i {
            font-size: 18px;
        }
        
        <?php if ($ad): ?>
        .ad-banner {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            animation: slideUp 0.6s ease-out;
        }
        .ad-banner h3 {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 22px;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .ad-banner p {
            color: #64748b;
            margin-bottom: 16px;
            line-height: 1.6;
            font-size: 14px;
        }
        .ad-banner a {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
        .ad-banner a:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }
        <?php endif; ?>
        
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
            .profile-card {
                padding: 35px 25px;
                border-radius: 25px;
            }
            .display-name {
                font-size: 26px;
            }
            .profile-image-wrapper {
                width: 120px;
                height: 120px;
            }
            .social-btn {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .profile-card {
                padding: 25px 18px;
            }
            .display-name {
                font-size: 22px;
            }
            .bio-text {
                font-size: 14px;
                padding: 15px;
            }
            .badge-container {
                gap: 6px;
            }
            .badge {
                font-size: 12px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($ad): ?>
        <div class="ad-banner">
            <h3><?= htmlspecialchars($ad['title']) ?></h3>
            <p><?= htmlspecialchars($ad['description']) ?></p>
            <a href="<?= htmlspecialchars($ad['url']) ?>" target="_blank" rel="noopener">
                <?= htmlspecialchars($ad['cta_text']) ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>

        <div class="profile-card">
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
            
            <?php
            $socials = [
                'facebook' => 'fab fa-facebook-f',
                'instagram' => 'fab fa-instagram',
                'twitter' => 'fab fa-x-twitter',
                'linkedin' => 'fab fa-linkedin-in',
                'youtube' => 'fab fa-youtube',
                'tiktok' => 'fab fa-tiktok',
                'github' => 'fab fa-github',
                'pinterest' => 'fab fa-pinterest-p',
                'snapchat' => 'fab fa-snapchat-ghost',
                'discord' => 'fab fa-discord',
                'twitch' => 'fab fa-twitch',
                'telegram' => 'fab fa-telegram',
                'whatsapp' => 'fab fa-whatsapp',
                'spotify' => 'fab fa-spotify',
                'reddit' => 'fab fa-reddit-alien',
                'website' => 'fas fa-globe'
            ];
            
            $has_socials = false;
            foreach ($socials as $platform => $icon) {
                if (!empty($bio[$platform]) && ($bio[$platform . '_enabled'] ?? 1)) {
                    $has_socials = true;
                    break;
                }
            }
            
            if ($has_socials):
            ?>
            <div class="divider"></div>
            <div class="section-title"><i class="fas fa-share-alt"></i> Connect</div>
            <div class="social-links">
                <?php
                foreach ($socials as $platform => $icon) {
                    $enabled = $bio[$platform . '_enabled'] ?? 1;
                    $url = $bio[$platform] ?? '';
                    
                    if (!empty($url) && $enabled) {
                        if ($platform === 'website' && !preg_match('/^https?:\/\//i', $url)) {
                            $url = 'https://' . $url;
                        }
                        echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="social-btn" title="' . ucfirst($platform) . '"><i class="' . htmlspecialchars($icon) . '"></i></a>';
                    }
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php 
            $has_email = !empty($bio['email']) && ($bio['email_enabled'] ?? 1);
            $has_phone = !empty($bio['phone']) && ($bio['phone_enabled'] ?? 1);
            if ($has_email || $has_phone): 
            ?>
            <div class="divider"></div>
            <div class="section-title"><i class="fas fa-address-card"></i> Contact</div>
            <div class="contact-links">
                <?php if ($has_email): ?>
                <a href="mailto:<?= htmlspecialchars($bio['email']) ?>" class="contact-btn">
                    <span><i class="fas fa-envelope"></i> Email Me</span>
                </a>
                <?php endif; ?>
                
                <?php if ($has_phone): ?>
                <a href="tel:<?= htmlspecialchars($bio['phone']) ?>" class="contact-btn">
                    <span><i class="fas fa-phone"></i> Call Me</span>
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

    <script>
        // Smooth page load animation
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '1';
        });
    </script>
</body>
</html>