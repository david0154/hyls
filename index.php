<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = new Database();
$settings = getSettings($db);

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYLS - Free Link Shortener & Bio Links</title>
    <?php if (!empty($settings['ga_tracking_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $settings['ga_tracking_id'] ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= $settings['ga_tracking_id'] ?>');
    </script>
    <?php endif; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #1e293b;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #6366f1;
            text-decoration: none;
        }
        .nav-buttons a {
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            margin-left: 12px;
            transition: all 0.3s;
        }
        .btn-login {
            color: #6366f1;
            background: transparent;
        }
        .btn-login:hover {
            background: #f1f5f9;
        }
        .btn-signup {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .hero {
            text-align: center;
            padding: 100px 20px;
            color: white;
        }
        .hero h1 {
            font-size: 56px;
            margin-bottom: 24px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }
        .hero p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
        }
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .cta-buttons a {
            padding: 16px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s;
        }
        .cta-primary {
            background: white;
            color: #6366f1;
        }
        .cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .cta-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        .cta-secondary:hover {
            background: rgba(255,255,255,0.3);
        }
        .features {
            background: white;
            padding: 80px 20px;
        }
        .features h2 {
            text-align: center;
            font-size: 42px;
            margin-bottom: 60px;
            color: #1e293b;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        .feature-card {
            text-align: center;
            padding: 40px;
            border-radius: 16px;
            background: #f8fafc;
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: #1e293b;
        }
        .feature-card p {
            color: #64748b;
            line-height: 1.6;
        }
        .stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 20px;
            text-align: center;
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        .stat-item h3 {
            font-size: 48px;
            margin-bottom: 8px;
        }
        .stat-item p {
            opacity: 0.9;
        }
        .footer {
            background: #1e293b;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .footer p {
            opacity: 0.8;
        }
        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" class="logo">🔗 HYLS</a>
                <div class="nav-buttons">
                    <a href="login.php" class="btn-login">Login</a>
                    <!-- HypeChats button HIDDEN - Using regular login now -->
                    <a href="login.php" class="btn-signup">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <h1>Shorten Links. Share Everything.</h1>
            <p>Create short links, bio pages, and QR codes - all in one powerful platform</p>
            <div class="cta-buttons">
                <!-- HypeChats button HIDDEN - Using regular login now -->
                <a href="login.php" class="cta-primary">Get Started Free</a>
                <a href="#features" class="cta-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="container">
            <h2>Everything You Need</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔗</div>
                    <h3>Link Shortening</h3>
                    <p>Transform long URLs into short, shareable links with custom codes and detailed analytics</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👤</div>
                    <h3>Bio Link Pages</h3>
                    <p>Create beautiful landing pages to showcase all your links, social media, and content</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics Dashboard</h3>
                    <p>Track clicks, visitors, and engagement with detailed real-time analytics</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎨</div>
                    <h3>Custom Branding</h3>
                    <p>Personalize your links and bio pages with custom themes and colors</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔐</div>
                    <h3>Secure Access</h3>
                    <p>Protected with industry-standard security and password protection options</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Lightning Fast</h3>
                    <p>Optimized for speed with instant redirects and minimal load times</p>
                </div>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="container">
            <h2>Trusted by Thousands</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?= number_format(getTotalLinks($db)) ?>+</h3>
                    <p>Links Created</p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format(getTotalClicks($db)) ?>+</h3>
                    <p>Total Clicks</p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format(getTotalUsers($db)) ?>+</h3>
                    <p>Happy Users</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 HYLS - HypeLink Shortener. All rights reserved.</p>
            <p style="margin-top: 12px; font-size: 14px;">
                <a href="privacy.php">Privacy Policy</a> • 
                <a href="terms.php">Terms of Service</a> • 
                <a href="contact.php">Contact</a>
            </p>
        </div>
    </footer>
</body>
</html>