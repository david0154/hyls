<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['goto']) || empty($_GET['goto'])) {
    header('Location: index.php');
    exit;
}

$db = new Database();
$settings = getSettings($db);

$goto_url = $_GET['goto'];
$ads_enabled = $settings['ads_enabled'] ?? 1;
$ads_duration = $settings['ads_duration'] ?? 5;
$promo_enabled = $settings['promo_enabled'] ?? 1;
$promo_url = $settings['promo_url'] ?? 'https://hypechats.com';
$promo_title = $settings['promo_title'] ?? 'HypeChats - Connect & Chat';
$promo_description = $settings['promo_description'] ?? 'Join the fastest growing chat platform!';
$promo_logo = $settings['promo_logo'] ?? '';

// If ads disabled, redirect immediately
if (!$ads_enabled) {
    header('Location: ' . $goto_url);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Please Wait - HYLS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .ad-container {
            max-width: 600px;
            width: 100%;
        }

        .timer-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .timer-icon {
            font-size: 48px;
            color: #6366f1;
            margin-bottom: 20px;
        }

        .timer-text {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 20px;
        }

        .countdown {
            font-size: 64px;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 20px;
        }

        .skip-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #10b981;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            opacity: 0;
            pointer-events: none;
        }

        .skip-btn.active {
            opacity: 1;
            pointer-events: auto;
        }

        .skip-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .promo-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .promo-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }

        .promo-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .promo-description {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .promo-btn {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }

        .promo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .ad-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ad-placeholder {
            color: #94a3b8;
            font-size: 14px;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 10px;
            transition: width 1s linear;
        }

        @media (max-width: 640px) {
            .promo-card {
                padding: 30px 20px;
            }

            .promo-title {
                font-size: 22px;
            }

            .countdown {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
    <div class="ad-container">
        <!-- Timer Card -->
        <div class="timer-card">
            <div class="timer-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="timer-text">Your link is preparing...</div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressBar"></div>
            </div>
            <div class="countdown" id="countdown"><?php echo $ads_duration; ?></div>
            <a href="<?php echo htmlspecialchars($goto_url); ?>" class="skip-btn" id="skipBtn">
                <i class="fas fa-arrow-right"></i> Continue to Link
            </a>
        </div>

        <?php if ($promo_enabled): ?>
        <!-- Promotion Card -->
        <div class="promo-card">
            <?php if (!empty($promo_logo)): ?>
                <img src="<?php echo htmlspecialchars($promo_logo); ?>" alt="<?php echo htmlspecialchars($promo_title); ?>" class="promo-logo">
            <?php endif; ?>
            <h2 class="promo-title"><?php echo htmlspecialchars($promo_title); ?></h2>
            <p class="promo-description"><?php echo htmlspecialchars($promo_description); ?></p>
            <a href="<?php echo htmlspecialchars($promo_url); ?>" target="_blank" class="promo-btn">
                <i class="fas fa-external-link-alt"></i> Visit Now
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($settings['google_ads_code']) && $settings['google_ads_enabled']): ?>
        <!-- Google AdSense -->
        <div class="ad-section">
            <?php echo $settings['google_ads_code']; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($settings['juicy_ads_code']) && $settings['juicy_ads_enabled']): ?>
        <!-- Juicy Ads -->
        <div class="ad-section">
            <?php echo $settings['juicy_ads_code']; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($settings['custom_ads_code']) && $settings['custom_ads_enabled']): ?>
        <!-- Custom Ads -->
        <div class="ad-section">
            <?php echo $settings['custom_ads_code']; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const duration = <?php echo $ads_duration; ?>;
        const gotoUrl = <?php echo json_encode($goto_url); ?>;
        let timeLeft = duration;

        const countdownEl = document.getElementById('countdown');
        const skipBtn = document.getElementById('skipBtn');
        const progressBar = document.getElementById('progressBar');

        // Update progress bar
        function updateProgress() {
            const progress = ((duration - timeLeft) / duration) * 100;
            progressBar.style.width = progress + '%';
        }

        // Countdown timer
        const timer = setInterval(() => {
            timeLeft--;
            countdownEl.textContent = timeLeft;
            updateProgress();

            if (timeLeft <= 0) {
                clearInterval(timer);
                skipBtn.classList.add('active');
                countdownEl.textContent = '✓';
                // Auto redirect
                setTimeout(() => {
                    window.location.href = gotoUrl;
                }, 500);
            }
        }, 1000);

        // Initialize progress
        updateProgress();
    </script>
</body>
</html>