<?php
// r.php - URL redirect handler with ads and earnings
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

try {
    $code = $_GET['c'] ?? '';
    $code = trim($code);

    if (empty($code)) {
        header('Location: index.php');
        exit;
    }

    $db = new Database();
    $stmt = $db->prepare("SELECT * FROM short_links WHERE short_code = ?");
    if (!$stmt) {
        throw new Exception('Database error');
    }
    
    $stmt->execute([$code]);
    $link = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$link) {
        header('Location: index.php');
        exit;
    }

    // Track analytics
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';

    try {
        $analytics_stmt = $db->prepare("INSERT INTO analytics (link_id, ip_address, user_agent, referrer) VALUES (?, ?, ?, ?)");
        if ($analytics_stmt) {
            $analytics_stmt->execute([$link['id'], $ip, $user_agent, $referrer]);
        }
    } catch (Exception $e) {
        error_log("Analytics insert error: " . $e->getMessage());
    }

    // Update clicks
    try {
        $db->prepare("UPDATE short_links SET clicks = clicks + 1 WHERE id = ?")->execute([$link['id']]);
    } catch (Exception $e) {
        error_log("Click update error: " . $e->getMessage());
    }

    // Calculate and add earnings
    $settings = getSettings($db);
    if ($settings) {
        $earning_per_click = floatval($settings['earning_per_click'] ?? 0.001);
        try {
            $db->prepare("UPDATE short_links SET earnings = earnings + ? WHERE id = ?")->execute([$earning_per_click, $link['id']]);
            $db->prepare("UPDATE users SET earnings = earnings + ? WHERE id = ?")->execute([$earning_per_click, $link['user_id']]);
        } catch (Exception $e) {
            error_log("Earnings update error: " . $e->getMessage());
        }
    }

    $ads_enabled = $settings['ads_enabled'] ?? 1;
    $ads_duration = intval($settings['ads_duration'] ?? 5);

    // Get active advertisement
    $ad = null;
    try {
        $ad_stmt = $db->prepare("SELECT * FROM advertisements WHERE is_active = 1 ORDER BY position ASC LIMIT 1");
        if ($ad_stmt) {
            $ad_stmt->execute();
            $ad = $ad_stmt->fetch(\PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Ad fetch error: " . $e->getMessage());
    }

    // If ads enabled and ad found, show ad page
    if ($ads_enabled && $ad):
        $original_url = htmlspecialchars($link['original_url']);
        $js_url = htmlspecialchars(addslashes($link['original_url']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting... - <?= htmlspecialchars(SITE_NAME) ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .ad-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .ad-logo {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .ad-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .ad-title {
            color: #6366f1;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .ad-description {
            color: #334155;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .btn-visit {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-visit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .redirect-info {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
        }
        .redirect-info h3 {
            font-size: 20px;
            margin-bottom: 12px;
        }
        .countdown {
            font-size: 48px;
            font-weight: 700;
            margin: 20px 0;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 16px;
        }
        .progress-fill {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.1s linear;
        }
        .skip-btn {
            display: inline-block;
            padding: 10px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 16px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .skip-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="ad-box">
            <?php if (!empty($ad['image_url'])): ?>
            <img src="<?= htmlspecialchars($ad['image_url']) ?>" alt="<?= htmlspecialchars($ad['title']) ?>" class="ad-image">
            <?php else: ?>
            <div class="ad-logo">💬</div>
            <?php endif; ?>
            <h2 class="ad-title"><?= htmlspecialchars($ad['title']) ?></h2>
            <?php if (!empty($ad['description'])): ?>
            <p class="ad-description"><?= nl2br(htmlspecialchars($ad['description'])) ?></p>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($ad['url']) ?>" target="_blank" rel="noopener" class="btn-visit">
                <?= htmlspecialchars($ad['cta_text']) ?> →
            </a>
        </div>
        
        <div class="redirect-info">
            <h3>🔗 Redirecting to your destination...</h3>
            <div class="countdown" id="countdown"><?= $ads_duration ?></div>
            <p>You will be redirected automatically</p>
            <div class="progress-bar">
                <div class="progress-fill" id="progress"></div>
            </div>
            <button class="skip-btn" id="skipBtn" style="display:none;" onclick="skipAd(); return false;">
                Skip Ad →
            </button>
        </div>
    </div>

    <script>
        const targetUrl = "<?= $js_url ?>";
        let timeLeft = <?= $ads_duration ?>;
        const countdown = document.getElementById('countdown');
        const progress = document.getElementById('progress');
        const skipBtn = document.getElementById('skipBtn');
        const totalTime = <?= $ads_duration ?>;
        
        function skipAd() {
            window.location.href = targetUrl;
        }
        
        const timer = setInterval(() => {
            timeLeft--;
            countdown.textContent = timeLeft;
            
            const progressPercent = ((totalTime - timeLeft) / totalTime) * 100;
            progress.style.width = progressPercent + '%';
            
            if (timeLeft === Math.ceil(totalTime / 2)) {
                skipBtn.style.display = 'inline-block';
            }
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = targetUrl;
            }
        }, 1000);
    </script>
</body>
</html>
<?php else:
    // No ads, redirect immediately
    header('Location: ' . $link['original_url']);
    exit;
endif;

} catch (Exception $e) {
    error_log("R.php Error: " . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>