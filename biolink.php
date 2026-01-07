<?php
// biolink.php - Bio link management page
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $db = new Database();
    $user_id = $_SESSION['user_id'];
    $success = '';
    $error = '';

    // Get current user info
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
    
    if (!$current_user) {
        die("User not found");
    }

    // Get or create bio link
    $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $bio = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Use the actual username from users table
        $username = $current_user['username'];
        $display_name = $_POST['display_name'] ?? '';
        $bio_text = $_POST['bio'] ?? '';
        $theme_color = $_POST['theme_color'] ?? '#6366f1';
        
        // Social media fields
        $socials = [
            'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 
            'github', 'pinterest', 'snapchat', 'discord', 'twitch', 'telegram', 
            'whatsapp', 'spotify', 'reddit', 'website', 'email', 'phone'
        ];
        
        $social_data = [];
        foreach ($socials as $social) {
            $social_data[$social] = $_POST[$social] ?? '';
            $social_data[$social . '_enabled'] = isset($_POST[$social . '_enabled']) ? 1 : 0;
        }
        
        // Handle profile image upload
        $profile_image = $bio['profile_image'] ?? '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                // Create uploads directory if it doesn't exist
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                if (!is_dir('uploads/bio')) {
                    mkdir('uploads/bio', 0755, true);
                }
                
                $new_filename = 'bio_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/bio/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old image
                    if ($profile_image && file_exists($profile_image)) {
                        @unlink($profile_image);
                    }
                    $profile_image = $upload_path;
                } else {
                    $error = 'Failed to upload image. Please check directory permissions.';
                }
            } else {
                $error = 'Invalid image format. Allowed: JPG, PNG, GIF, WebP';
            }
        }
        
        if (empty($error)) {
            try {
                if ($bio) {
                    // Update existing bio
                    $sql = "UPDATE bio_links SET 
                            username = ?, display_name = ?, bio = ?, theme_color = ?, profile_image = ?,
                            facebook = ?, facebook_enabled = ?, instagram = ?, instagram_enabled = ?,
                            twitter = ?, twitter_enabled = ?, linkedin = ?, linkedin_enabled = ?,
                            youtube = ?, youtube_enabled = ?, tiktok = ?, tiktok_enabled = ?,
                            github = ?, github_enabled = ?, pinterest = ?, pinterest_enabled = ?,
                            snapchat = ?, snapchat_enabled = ?, discord = ?, discord_enabled = ?,
                            twitch = ?, twitch_enabled = ?, telegram = ?, telegram_enabled = ?,
                            whatsapp = ?, whatsapp_enabled = ?, spotify = ?, spotify_enabled = ?,
                            reddit = ?, reddit_enabled = ?, website = ?, website_enabled = ?,
                            email = ?, email_enabled = ?, phone = ?, phone_enabled = ?
                            WHERE user_id = ?";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $username, $display_name, $bio_text, $theme_color, $profile_image,
                        $social_data['facebook'], $social_data['facebook_enabled'],
                        $social_data['instagram'], $social_data['instagram_enabled'],
                        $social_data['twitter'], $social_data['twitter_enabled'],
                        $social_data['linkedin'], $social_data['linkedin_enabled'],
                        $social_data['youtube'], $social_data['youtube_enabled'],
                        $social_data['tiktok'], $social_data['tiktok_enabled'],
                        $social_data['github'], $social_data['github_enabled'],
                        $social_data['pinterest'], $social_data['pinterest_enabled'],
                        $social_data['snapchat'], $social_data['snapchat_enabled'],
                        $social_data['discord'], $social_data['discord_enabled'],
                        $social_data['twitch'], $social_data['twitch_enabled'],
                        $social_data['telegram'], $social_data['telegram_enabled'],
                        $social_data['whatsapp'], $social_data['whatsapp_enabled'],
                        $social_data['spotify'], $social_data['spotify_enabled'],
                        $social_data['reddit'], $social_data['reddit_enabled'],
                        $social_data['website'], $social_data['website_enabled'],
                        $social_data['email'], $social_data['email_enabled'],
                        $social_data['phone'], $social_data['phone_enabled'],
                        $user_id
                    ]);
                } else {
                    // Create new bio - MUST include username field
                    $sql = "INSERT INTO bio_links (
                            user_id, username, display_name, bio, theme_color, profile_image,
                            facebook, facebook_enabled, instagram, instagram_enabled,
                            twitter, twitter_enabled, linkedin, linkedin_enabled,
                            youtube, youtube_enabled, tiktok, tiktok_enabled,
                            github, github_enabled, pinterest, pinterest_enabled,
                            snapchat, snapchat_enabled, discord, discord_enabled,
                            twitch, twitch_enabled, telegram, telegram_enabled,
                            whatsapp, whatsapp_enabled, spotify, spotify_enabled,
                            reddit, reddit_enabled, website, website_enabled,
                            email, email_enabled, phone, phone_enabled
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $user_id, $username, $display_name, $bio_text, $theme_color, $profile_image,
                        $social_data['facebook'], $social_data['facebook_enabled'],
                        $social_data['instagram'], $social_data['instagram_enabled'],
                        $social_data['twitter'], $social_data['twitter_enabled'],
                        $social_data['linkedin'], $social_data['linkedin_enabled'],
                        $social_data['youtube'], $social_data['youtube_enabled'],
                        $social_data['tiktok'], $social_data['tiktok_enabled'],
                        $social_data['github'], $social_data['github_enabled'],
                        $social_data['pinterest'], $social_data['pinterest_enabled'],
                        $social_data['snapchat'], $social_data['snapchat_enabled'],
                        $social_data['discord'], $social_data['discord_enabled'],
                        $social_data['twitch'], $social_data['twitch_enabled'],
                        $social_data['telegram'], $social_data['telegram_enabled'],
                        $social_data['whatsapp'], $social_data['whatsapp_enabled'],
                        $social_data['spotify'], $social_data['spotify_enabled'],
                        $social_data['reddit'], $social_data['reddit_enabled'],
                        $social_data['website'], $social_data['website_enabled'],
                        $social_data['email'], $social_data['email_enabled'],
                        $social_data['phone'], $social_data['phone_enabled']
                    ]);
                }
                
                $success = 'Bio link saved successfully! View it at: ' . SITE_URL . '/bio/' . $username;
                
                // Refresh bio data
                $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $bio = $stmt->fetch();
                
            } catch (Exception $e) {
                $error = 'Failed to save bio link: ' . $e->getMessage();
                error_log("Bio Link Save Error: " . $e->getMessage());
            }
        }
    }

    $settings = getSettings($db);
    if (!$settings) {
        $settings = [];
    }
} catch (Exception $e) {
    error_log("Biolink Page Error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bio Link - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .navbar h1 {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            font-weight: 800;
        }
        
        .navbar nav {
            display: flex;
            gap: 24px;
        }
        
        .navbar nav a {
            color: #64748b;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            position: relative;
        }
        
        .navbar nav a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 3px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            transition: width 0.3s;
        }
        
        .navbar nav a:hover::after {
            width: 100%;
        }
        
        .navbar nav a:hover {
            color: #6366f1;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.25),
                0 0 1px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
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
        
        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .card h2 {
            color: #1e293b;
            font-size: 28px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 1;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            animation: slideDown 0.3s ease-out;
            position: relative;
            z-index: 1;
            border-left: 5px solid;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left-color: #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-left-color: #ef4444;
        }
        
        .info-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
            border: 2px solid #6366f1;
            color: #1e293b;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15);
            position: relative;
            z-index: 1;
        }
        
        .info-box strong {
            color: #6366f1;
            font-size: 16px;
        }
        
        .info-box a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 700;
            word-break: break-all;
            transition: all 0.3s;
        }
        
        .info-box a:hover {
            opacity: 0.8;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .form-group label {
            display: block;
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="url"],
        .form-group input[type="color"],
        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input[type="file"] {
            padding: 10px;
            cursor: pointer;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }
        
        .toggle-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            padding: 12px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
            border-radius: 10px;
            border-left: 4px solid #6366f1;
        }
        
        .toggle-group input[type="checkbox"] {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #6366f1;
        }
        
        .toggle-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 600;
            color: #6366f1;
            font-size: 14px;
        }
        
        .social-section {
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.08);
            position: relative;
            z-index: 1;
        }
        
        .social-section:hover {
            border-color: #6366f1;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.15);
            transform: translateY(-3px);
        }
        
        .social-section h3 {
            color: #6366f1;
            font-size: 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }
        
        .btn-primary {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        
        .btn-primary::before {
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
        
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
        }
        
        .preview-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            margin-top: 16px;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .preview-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(16, 185, 129, 0.4);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .color-input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-group input[type="color"] {
            width: 80px;
            height: 50px;
            border-radius: 10px;
            cursor: pointer;
            padding: 4px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 0 10px;
            }
            
            .card {
                padding: 25px 15px;
            }
            
            .card h2 {
                font-size: 22px;
            }
            
            .navbar nav {
                gap: 12px;
            }
            
            .social-section {
                padding: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 20px 12px;
            }
            
            .card h2 {
                font-size: 20px;
            }
            
            .navbar h1 {
                font-size: 20px;
            }
            
            .navbar nav {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🔗 <?= SITE_NAME ?></h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="biolink.php">Bio Link</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2><i class="fas fa-id-card"></i> Bio Link Settings</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="info-box">
                <strong>✨ Your bio link URL:</strong><br>
                <a href="<?= SITE_URL ?>/bio/<?= htmlspecialchars($current_user['username']) ?>" target="_blank">
                    <?= SITE_URL ?>/bio/<strong><?= htmlspecialchars($current_user['username']) ?></strong>
                </a>
            </div>
            
            <?php if ($bio): ?>
                <a href="<?= SITE_URL ?>/bio/<?= htmlspecialchars($current_user['username']) ?>" target="_blank" class="preview-link">
                    <i class="fas fa-eye"></i> View Your Bio Link
                </a>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top: 32px; position: relative; z-index: 1;">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($bio['display_name'] ?? $current_user['username']) ?>" placeholder="Your name">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Bio</label>
                    <textarea name="bio" placeholder="Tell people about yourself..."><?= htmlspecialchars($bio['bio'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Profile Image</label>
                        <input type="file" name="profile_image" accept="image/*">
                        <?php if (!empty($bio['profile_image'])): ?>
                            <small style="color: #64748b; display: block; margin-top: 8px;"><i class="fas fa-info-circle"></i> Current: <?= basename($bio['profile_image']) ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-palette"></i> Theme Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="theme_color" value="<?= htmlspecialchars($bio['theme_color'] ?? '#6366f1') ?>">
                            <span id="colorValue" style="font-weight: 700; color: #6366f1;"></span>
                        </div>
                    </div>
                </div>
                
                <h2 style="margin-top: 40px; margin-bottom: 24px;"><i class="fas fa-globe"></i> Social Media Links</h2>
                
                <?php
                $social_platforms = [
                    'facebook' => ['icon' => 'fab fa-facebook-f', 'label' => 'Facebook', 'placeholder' => 'https://facebook.com/username'],
                    'instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/username'],
                    'twitter' => ['icon' => 'fab fa-x-twitter', 'label' => 'X (Twitter)', 'placeholder' => 'https://twitter.com/username'],
                    'linkedin' => ['icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn', 'placeholder' => 'https://linkedin.com/in/username'],
                    'youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube', 'placeholder' => 'https://youtube.com/@username'],
                    'tiktok' => ['icon' => 'fab fa-tiktok', 'label' => 'TikTok', 'placeholder' => 'https://tiktok.com/@username'],
                    'github' => ['icon' => 'fab fa-github', 'label' => 'GitHub', 'placeholder' => 'https://github.com/username'],
                    'website' => ['icon' => 'fas fa-globe', 'label' => 'Website', 'placeholder' => 'https://yourwebsite.com'],
                ];
                
                foreach ($social_platforms as $key => $platform):
                    $value = $bio[$key] ?? '';
                    $enabled = ($bio[$key . '_enabled'] ?? 1) == 1;
                ?>
                <div class="social-section">
                    <h3><i class="<?= $platform['icon'] ?>"></i> <?= $platform['label'] ?></h3>
                    <div class="form-group">
                        <input type="url" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>" placeholder="<?= $platform['placeholder'] ?>">
                        <div class="toggle-group">
                            <input type="checkbox" name="<?= $key ?>_enabled" id="<?= $key ?>_enabled" <?= $enabled ? 'checked' : '' ?>>
                            <label for="<?= $key ?>_enabled">Show on bio page</label>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <h2 style="margin-top: 40px; margin-bottom: 24px;"><i class="fas fa-phone"></i> Contact Information</h2>
                
                <div class="social-section">
                    <h3><i class="fas fa-envelope"></i> Email</h3>
                    <div class="form-group">
                        <input type="email" name="email" value="<?= htmlspecialchars($bio['email'] ?? '') ?>" placeholder="your@email.com">
                        <div class="toggle-group">
                            <input type="checkbox" name="email_enabled" id="email_enabled" <?= ($bio['email_enabled'] ?? 1) ? 'checked' : '' ?>>
                            <label for="email_enabled">Show on bio page</label>
                        </div>
                    </div>
                </div>
                
                <div class="social-section">
                    <h3><i class="fas fa-phone"></i> Phone</h3>
                    <div class="form-group">
                        <input type="tel" name="phone" value="<?= htmlspecialchars($bio['phone'] ?? '') ?>" placeholder="+1234567890">
                        <div class="toggle-group">
                            <input type="checkbox" name="phone_enabled" id="phone_enabled" <?= ($bio['phone_enabled'] ?? 1) ? 'checked' : '' ?>>
                            <label for="phone_enabled">Show on bio page</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Bio Link</button>
            </form>
        </div>
    </div>
    
    <script>
        // Update color value display
        const colorInput = document.querySelector('input[type="color"]');
        const colorValue = document.getElementById('colorValue');
        
        function updateColorValue() {
            colorValue.textContent = colorInput.value.toUpperCase();
        }
        
        colorInput.addEventListener('change', updateColorValue);
        colorInput.addEventListener('input', updateColorValue);
        
        // Initialize
        updateColorValue();
    </script>
</body>
</html>