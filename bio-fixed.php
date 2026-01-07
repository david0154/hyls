<?php
// Enhanced Bio Page - FIXED VERSION
// All content visible with beautiful design

// Get username from URL
$username = isset($_GET['user']) ? htmlspecialchars($_GET['user']) : 'default';

// Sample bio data (in production, fetch from database)
$bioData = [
    'name' => 'Your Name',
    'bio' => 'I love creating amazing things online! 🚀',
    'image' => 'https://via.placeholder.com/150/6366f1/ffffff?text=Profile',
    'theme_color' => '#6366f1',
    'views' => 1234,
    'social' => [
        ['name' => 'Facebook', 'url' => 'https://facebook.com', 'icon' => 'fab fa-facebook'],
        ['name' => 'Instagram', 'url' => 'https://instagram.com', 'icon' => 'fab fa-instagram'],
        ['name' => 'Twitter', 'url' => 'https://twitter.com', 'icon' => 'fab fa-twitter'],
        ['name' => 'LinkedIn', 'url' => 'https://linkedin.com', 'icon' => 'fab fa-linkedin'],
        ['name' => 'YouTube', 'url' => 'https://youtube.com', 'icon' => 'fab fa-youtube'],
        ['name' => 'TikTok', 'url' => 'https://tiktok.com', 'icon' => 'fab fa-tiktok'],
        ['name' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'fab fa-github'],
        ['name' => 'Discord', 'url' => 'https://discord.com', 'icon' => 'fab fa-discord'],
    ],
    'email' => 'contact@example.com',
    'phone' => '+1234567890'
];

// Extract theme color
$themeColor = $bioData['theme_color'] ?? '#6366f1';
$themeDark = '#4f46e5';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $bioData['name']; ?> - Bio Link</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: <?php echo $themeColor; ?>;
            --primary-dark: <?php echo $themeDark; ?>;
            --primary-light: <?php echo $themeColor; ?>20;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 700px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px 30px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s ease-out;
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
            z-index: 20;
            animation: float 3s ease-in-out infinite 0.3s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .profile-image-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: conic-gradient(from 0deg, var(--primary), var(--primary-dark), var(--primary));
            animation: rotate 4s linear infinite;
            z-index: -1;
            filter: blur(15px);
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            position: relative;
            z-index: 2;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .display-name {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            animation: fadeIn 0.6s ease-out 0.2s backwards;
        }

        .bio-text {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 20px;
            line-height: 1.6;
            animation: fadeIn 0.6s ease-out 0.3s backwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .social-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin: 30px 0;
            z-index: 15;
        }

        .social-btn {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: 3px solid white;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out 0.4s backwards;
        }

        .social-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        .social-btn:hover {
            transform: translateY(-12px) scale(1.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .social-btn:active {
            transform: translateY(-8px) scale(1.05);
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-effect 0.6s ease-out;
            pointer-events: none;
        }

        @keyframes ripple-effect {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .contact-section {
            margin: 30px 0;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .contact-btn {
            flex: 1;
            min-width: 150px;
            padding: 15px 25px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.6s ease-out 0.5s backwards;
        }

        .contact-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .contact-btn i {
            font-size: 18px;
        }

        .view-counter {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            animation: fadeIn 0.6s ease-out 0.6s backwards;
        }

        .view-counter i {
            color: var(--primary);
            margin-right: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-card {
                padding: 30px 20px;
                border-radius: 25px;
            }

            .profile-image-wrapper {
                width: 120px;
                height: 120px;
                margin-bottom: 20px;
            }

            .display-name {
                font-size: 26px;
            }

            .bio-text {
                font-size: 14px;
            }

            .social-btn {
                width: 58px;
                height: 58px;
                font-size: 20px;
            }

            .contact-btn {
                flex: 1 1 100%;
                min-width: auto;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .profile-card {
                padding: 25px 15px;
                border-radius: 20px;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            }

            .profile-image-wrapper {
                width: 100px;
                height: 100px;
                margin-bottom: 15px;
            }

            .display-name {
                font-size: 22px;
                margin-bottom: 8px;
            }

            .bio-text {
                font-size: 13px;
                margin-bottom: 15px;
            }

            .social-links {
                gap: 10px;
                margin: 20px 0;
            }

            .social-btn {
                width: 52px;
                height: 52px;
                font-size: 18px;
            }

            .contact-section {
                margin: 20px 0;
                gap: 10px;
            }

            .contact-btn {
                font-size: 14px;
                padding: 12px 15px;
            }
        }

        /* Utilities */
        .hidden {
            display: none !important;
        }

        .visible {
            display: block !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <!-- Profile Image -->
            <div class="profile-image-wrapper">
                <img src="<?php echo $bioData['image']; ?>" alt="<?php echo $bioData['name']; ?>" class="profile-image">
            </div>

            <!-- Display Name -->
            <h1 class="display-name"><?php echo $bioData['name']; ?></h1>

            <!-- Bio Text -->
            <p class="bio-text"><?php echo $bioData['bio']; ?></p>

            <!-- Social Links -->
            <div class="social-links">
                <?php foreach ($bioData['social'] as $social): ?>
                    <a href="<?php echo $social['url']; ?>" 
                       title="<?php echo $social['name']; ?>" 
                       class="social-btn"
                       target="_blank" 
                       rel="noopener noreferrer">
                        <i class="<?php echo $social['icon']; ?>"></i>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Contact Buttons -->
            <div class="contact-section">
                <a href="mailto:<?php echo $bioData['email']; ?>" class="contact-btn">
                    <i class="fas fa-envelope"></i>
                    <span>Email Me</span>
                </a>
                <a href="tel:<?php echo $bioData['phone']; ?>" class="contact-btn">
                    <i class="fas fa-phone"></i>
                    <span>Call Me</span>
                </a>
            </div>

            <!-- View Counter -->
            <div class="view-counter">
                <i class="fas fa-eye"></i>
                <?php echo number_format($bioData['views']); ?> views
            </div>
        </div>
    </div>

    <script>
        // Add ripple effect on button click
        document.querySelectorAll('.social-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Track page views
        function trackView() {
            // In production, send to database
            console.log('Page viewed');
        }

        // Track view on page load
        window.addEventListener('load', trackView);
    </script>
</body>
</html>