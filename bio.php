<?php
// bio.php - Bio link display page
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
    // Check if Database class exists
    if (!class_exists('Database')) {
        throw new Exception('Database class not found');
    }
    
    $db = new Database();
    
    // Get username from URL parameter
    $username = $_GET['u'] ?? '';
    $username = trim($username);

    if (empty($username)) {
        header('Location: index.php');
        exit;
    }

    // Get bio link data - use PDO::FETCH_ASSOC to get only associative keys
    $stmt = $db->prepare("SELECT b.*, u.username as user_username FROM bio_links b JOIN users u ON b.user_id = u.id WHERE u.username = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    $stmt->execute([$username]);
    $bio = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$bio) {
        // Bio not found, redirect to home
        header('Location: index.php');
        exit;
    }

    // Update views counter
    try {
        $update_stmt = $db->prepare("UPDATE bio_links SET views = views + 1 WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->execute([$bio['id']]);
        }
    } catch (Exception $e) {
        error_log("Failed to update views: " . $e->getMessage());
    }

    // Get settings
    $settings = getSettings($db);
    if (!$settings) {
        $settings = [];
    }

    // Get active advertisements
    $ad_stmt = $db->prepare("SELECT * FROM advertisements WHERE is_active = 1 ORDER BY position ASC LIMIT 1");
    if ($ad_stmt) {
        $ad_stmt->execute();
        $ad = $ad_stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    // Set default theme color if not set
    if (empty($bio['theme_color'])) {
        $bio['theme_color'] = '#6366f1';
    }
    
} catch (Exception $e) {
    error_log("Bio Page Error: " . $e->getMessage());
    die('<div style="padding: 40px; text-align: center; font-family: sans-serif;"><h1>Error Loading Bio</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></div>');
}

// If we got here without a bio, something is wrong
if (!$bio) {
    header('Location: index.php');
    exit;
}

// Get the adjusted color for gradient - use adjustColor from functions.php
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
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(SITE_URL) ?>/assets/favicon.ico">
    <!-- Font Awesome CDN for social media icons -->
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        /* Animated background with multiple layers */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(0, 0, 0, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
            animation: moveGradient 15s ease infinite;
        }
        
        @keyframes moveGradient {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, 20px); }
        }
        
        .container {
            max-width: 700px;
            width: 100%;
            z-index: 1;
        }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.25),
                0 0 1px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            animation: slideUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-image-wrapper {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>);
            border-radius: 50%;
            padding: 5px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 0 10px rgba(255, 255, 255, 0.5),
                inset -5px -5px 15px rgba(0, 0, 0, 0.2);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .profile-image-wrapper::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: linear-gradient(45deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>, <?= htmlspecialchars($bio['theme_color']) ?>);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.5;
            animation: rotateGlow 3s linear infinite;
        }
        
        @keyframes rotateGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .display-name {
            font-size: 36px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeInText 0.8s ease-out 0.2s both;
        }
        
        @keyframes fadeInText {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .username {
            color: #64748b;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 500;
            animation: fadeInText 0.8s ease-out 0.3s both;
        }
        
        .views {
            display: inline-block;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
            color: <?= htmlspecialchars($bio['theme_color']) ?>;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            margin-bottom: 20px;
            font-weight: 700;
            border: 2px solid <?= htmlspecialchars($bio['theme_color']) ?>;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.2);
            animation: fadeInText 0.8s ease-out 0.4s both;
        }
        
        .bio-text {
            color: #334155;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 15px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
            padding: 25px;
            border-radius: 20px;
            border-left: 5px solid <?= htmlspecialchars($bio['theme_color']) ?>;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.03);
            animation: fadeInText 0.8s ease-out 0.5s both;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
            padding: 30px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color()) ?>);
            color: white;
            text-decoration: none;
            font-size: 28px;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.2),
                inset -2px -2px 5px rgba(0, 0, 0, 0.1);
            border: 3px solid white;
            position: relative;
            overflow: hidden;
        }
        
        .social-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }
        
        .social-btn:hover::before {
            left: 100%;
        }
        
        .social-btn:hover {
            transform: translateY(-12px) scale(1.15);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                inset -2px -2px 8px rgba(0, 0, 0, 0.15);
            filter: brightness(1.15);
        }
        
        .social-btn:active {
            transform: translateY(-8px) scale(1.1);
        }
        
        .contact-links {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 25px;
        }
        
        .contact-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 28px;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?>, <?= htmlspecialchars($adjusted_color) ?>);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 15px;
            box-shadow: 
                0 10px 25px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            border: 2px solid white;
            position: relative;
            overflow: hidden;
        }
        
        .contact-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .contact-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .contact-btn:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }
        
        .contact-btn i {
            font-size: 20px;
            position: relative;
            z-index: 1;
        }
        
        .powered-by {
            text-align: center;
            margin-top: 30px;
        }
        .powered-by a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: rgba(255, 255, 255, 0.95);
            color: <?= htmlspecialchars($bio['theme_color']) ?>;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 700;
            transition: all 0.3s;
            font-size: 14px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }
        .powered-by a:hover {
            background: white;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            .profile-card {
                padding: 35px 25px;
                border-radius: 25px;
            }
            .display-name {
                font-size: 28px;
            }
            .profile-image-wrapper {
                width: 130px;
                height: 130px;
            }
            .social-btn {
                width: 58px;
                height: 58px;
                font-size: 24px;
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
            .social-btn {
                width: 52px;
                height: 52px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <div class="profile-image-wrapper">
                <?php if (!empty($bio['profile_image'])): ?>
                <img src="<?= htmlspecialchars(SITE_URL) ?>/<?= htmlspecialchars($bio['profile_image']) ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($bio['display_name'] ?? $bio['username']) ?>&size=150&background=<?= str_replace('#', '', htmlspecialchars($bio['theme_color'])) ?>&color=fff&bold=true&font-size=0.5" alt="Profile" class="profile-image">
                <?php endif; ?>
            </div>
            
            <h1 class="display-name"><?= htmlspecialchars($bio['display_name'] ?? $bio['username']) ?></h1>
            <p class="username">@<?= htmlspecialchars($bio['username']) ?></p>
            <div class="views"><i class="fas fa-eye"></i> <?= number_format($bio['views'] ?? 0) ?> views</div>
            
            <?php if (!empty($bio['bio'])): ?>
            <p class="bio-text"><?= nl2br(htmlspecialchars($bio['bio'])) ?></p>
            <?php endif; ?>
            
            <div class="social-links">
                <?php
                // Social media icons mapping with Font Awesome classes
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
                
                foreach ($socials as $platform => $icon) {
                    $enabled_key = $platform . '_enabled';
                    $enabled = $bio[$enabled_key] ?? 1;
                    $url = $bio[$platform] ?? '';
                    
                    if (!empty($url) && $enabled) {
                        // Add protocol if missing for website
                        if ($platform === 'website' && !preg_match('/^https?:\/\//i', $url)) {
                            $url = 'https://' . $url;
                        }
                        echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="social-btn" title="' . ucfirst($platform) . '"><i class="' . htmlspecialchars($icon) . '"></i></a>';
                    }
                }
                ?>
            </div>
            
            <?php 
            $has_email = !empty($bio['email']) && ($bio['email_enabled'] ?? 1);
            $has_phone = !empty($bio['phone']) && ($bio['phone_enabled'] ?? 1);
            if ($has_email || $has_phone): 
            ?>
            <div class="contact-links">
                <?php if ($has_email): ?>
                <a href="mailto:<?= htmlspecialchars($bio['email']) ?>" class="contact-btn">
                    <i class="fas fa-envelope"></i> Email Me
                </a>
                <?php endif; ?>
                
                <?php if ($has_phone): ?>
                <a href="tel:<?= htmlspecialchars($bio['phone']) ?>" class="contact-btn">
                    <i class="fas fa-phone"></i> Call Me
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