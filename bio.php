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
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, <?= htmlspecialchars($bio['theme_color']) ?> 0%, <?= htmlspecialchars($adjusted_color) ?> 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 680px;
            width: 100%;
        }
        .profile-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .display-name {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .username {
            color: #64748b;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .views {
            display: inline-block;
            background: #f1f5f9;
            color: #64748b;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .bio-text {
            color: #334155;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .social-links {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: <?= htmlspecialchars($bio['theme_color']) ?>;
            color: white;
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s;
        }
        .social-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .contact-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }
        .contact-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            background: <?= htmlspecialchars($bio['theme_color']) ?>;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        <?php if ($ad): ?>
        .ad-banner {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .ad-banner h3 {
            color: #6366f1;
            font-size: 20px;
            margin-bottom: 8px;
        }
        .ad-banner p {
            color: #64748b;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .ad-banner a {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .ad-banner a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
        }
        <?php endif; ?>
        .powered-by {
            text-align: center;
            margin-top: 20px;
        }
        .powered-by a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.9);
            color: #6366f1;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }
        .powered-by a:hover {
            background: white;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .profile-card {
                padding: 30px 20px;
            }
            .display-name {
                font-size: 24px;
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
                <?= htmlspecialchars($ad['cta_text']) ?> →
            </a>
        </div>
        <?php endif; ?>

        <div class="profile-card">
            <?php if (!empty($bio['profile_image'])): ?>
            <img src="<?= htmlspecialchars(SITE_URL) ?>/<?= htmlspecialchars($bio['profile_image']) ?>" alt="Profile" class="profile-image">
            <?php else: ?>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($bio['display_name'] ?? $bio['username']) ?>&size=120&background=6366f1&color=fff" alt="Profile" class="profile-image">
            <?php endif; ?>
            
            <h1 class="display-name"><?= htmlspecialchars($bio['display_name'] ?? $bio['username']) ?></h1>
            <p class="username">@<?= htmlspecialchars($bio['username']) ?></p>
            <div class="views">👁️ <?= number_format($bio['views'] ?? 0) ?> views</div>
            
            <?php if (!empty($bio['bio'])): ?>
            <p class="bio-text"><?= nl2br(htmlspecialchars($bio['bio'])) ?></p>
            <?php endif; ?>
            
            <div class="social-links">
                <?php
                $socials = [
                    'facebook' => '👥',
                    'instagram' => '📷',
                    'twitter' => '🐦',
                    'linkedin' => '💼',
                    'youtube' => '🎥',
                    'tiktok' => '🎵',
                    'github' => '💻',
                    'pinterest' => '📌',
                    'snapchat' => '👻',
                    'discord' => '🎮',
                    'twitch' => '🟣',
                    'telegram' => '✈️',
                    'whatsapp' => '💬',
                    'spotify' => '🎶',
                    'reddit' => '🗣️',
                    'website' => '🌐'
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
                        echo '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="social-btn" title="' . ucfirst($platform) . '">' . $icon . '</a>';
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
                    📧 Email Me
                </a>
                <?php endif; ?>
                
                <?php if ($has_phone): ?>
                <a href="tel:<?= htmlspecialchars($bio['phone']) ?>" class="contact-btn">
                    📱 Call Me
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="powered-by">
            <a href="<?= htmlspecialchars(SITE_URL) ?>" target="_blank" rel="noopener">
                🔗 Powered by <?= htmlspecialchars(SITE_NAME) ?>
            </a>
        </div>
    </div>
</body>
</html>