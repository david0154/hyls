<?php
// biolink.php - Bio link management page
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get or create bio link
$stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
$stmt->execute([$user_id]);
$bio = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
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
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'bio_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/bio/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Delete old image
                if ($profile_image && file_exists($profile_image)) {
                    unlink($profile_image);
                }
                $profile_image = $upload_path;
            }
        }
    }
    
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
            // Create new bio
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
        
        $success = 'Bio link updated successfully!';
        
        // Refresh bio data
        $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $bio = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Failed to update bio link: ' . $e->getMessage();
    }
}

$settings = getSettings($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bio Link - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/assets/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            color: #6366f1;
            font-size: 24px;
        }
        .navbar nav a {
            color: #64748b;
            text-decoration: none;
            margin-left: 24px;
            font-weight: 600;
        }
        .navbar nav a:hover {
            color: #6366f1;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .card h2 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 24px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="url"],
        .form-group input[type="color"],
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
        }
        .toggle-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }
        .toggle-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .toggle-group label {
            margin: 0;
            cursor: pointer;
        }
        .social-section {
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .social-section h3 {
            color: #6366f1;
            font-size: 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .preview-link {
            display: inline-block;
            padding: 10px 20px;
            background: #f1f5f9;
            color: #6366f1;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 12px;
        }
        .preview-link:hover {
            background: #e2e8f0;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
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
            <h2>📱 Bio Link Settings</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($bio): ?>
                <a href="<?= SITE_URL ?>/bio/<?= htmlspecialchars($bio['username']) ?>" target="_blank" class="preview-link">
                    👁️ Preview Bio Link
                </a>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top: 24px;">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($bio['username'] ?? '') ?>" required pattern="[a-zA-Z0-9_-]+" title="Only letters, numbers, underscore and hyphen allowed">
                </div>
                
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($bio['display_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" placeholder="Tell people about yourself..."><?= htmlspecialchars($bio['bio'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Profile Image</label>
                        <input type="file" name="profile_image" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>Theme Color</label>
                        <input type="color" name="theme_color" value="<?= htmlspecialchars($bio['theme_color'] ?? '#6366f1') ?>">
                    </div>
                </div>
                
                <h2 style="margin-top: 32px; margin-bottom: 24px;">🌐 Social Media Links</h2>
                
                <?php
                $social_platforms = [
                    'facebook' => ['icon' => '📘', 'label' => 'Facebook', 'placeholder' => 'https://facebook.com/username'],
                    'instagram' => ['icon' => '📷', 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/username'],
                    'twitter' => ['icon' => '🐦', 'label' => 'X (Twitter)', 'placeholder' => 'https://twitter.com/username'],
                    'linkedin' => ['icon' => '💼', 'label' => 'LinkedIn', 'placeholder' => 'https://linkedin.com/in/username'],
                    'youtube' => ['icon' => '📹', 'label' => 'YouTube', 'placeholder' => 'https://youtube.com/@username'],
                    'tiktok' => ['icon' => '🎵', 'label' => 'TikTok', 'placeholder' => 'https://tiktok.com/@username'],
                    'github' => ['icon' => '💻', 'label' => 'GitHub', 'placeholder' => 'https://github.com/username'],
                    'pinterest' => ['icon' => '📌', 'label' => 'Pinterest', 'placeholder' => 'https://pinterest.com/username'],
                    'snapchat' => ['icon' => '👻', 'label' => 'Snapchat', 'placeholder' => 'https://snapchat.com/add/username'],
                    'discord' => ['icon' => '🎮', 'label' => 'Discord', 'placeholder' => 'username#1234 or Discord server invite'],
                    'twitch' => ['icon' => '🎬', 'label' => 'Twitch', 'placeholder' => 'https://twitch.tv/username'],
                    'telegram' => ['icon' => '✈️', 'label' => 'Telegram', 'placeholder' => 'https://t.me/username'],
                    'whatsapp' => ['icon' => '💬', 'label' => 'WhatsApp', 'placeholder' => 'https://wa.me/1234567890'],
                    'spotify' => ['icon' => '🎧', 'label' => 'Spotify', 'placeholder' => 'https://open.spotify.com/user/username'],
                    'reddit' => ['icon' => '🔴', 'label' => 'Reddit', 'placeholder' => 'https://reddit.com/u/username'],
                    'website' => ['icon' => '🌐', 'label' => 'Website', 'placeholder' => 'https://yourwebsite.com'],
                ];
                
                foreach ($social_platforms as $key => $platform):
                    $value = $bio[$key] ?? '';
                    $enabled = ($bio[$key . '_enabled'] ?? 1) == 1;
                ?>
                <div class="social-section">
                    <h3><?= $platform['icon'] ?> <?= $platform['label'] ?></h3>
                    <div class="form-group">
                        <input type="url" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>" placeholder="<?= $platform['placeholder'] ?>">
                        <div class="toggle-group">
                            <input type="checkbox" name="<?= $key ?>_enabled" id="<?= $key ?>_enabled" <?= $enabled ? 'checked' : '' ?>>
                            <label for="<?= $key ?>_enabled">Show on bio page</label>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <h2 style="margin-top: 32px; margin-bottom: 24px;">📞 Contact Information</h2>
                
                <div class="social-section">
                    <h3>📧 Email</h3>
                    <div class="form-group">
                        <input type="email" name="email" value="<?= htmlspecialchars($bio['email'] ?? '') ?>" placeholder="your@email.com">
                        <div class="toggle-group">
                            <input type="checkbox" name="email_enabled" id="email_enabled" <?= ($bio['email_enabled'] ?? 1) ? 'checked' : '' ?>>
                            <label for="email_enabled">Show on bio page</label>
                        </div>
                    </div>
                </div>
                
                <div class="social-section">
                    <h3>📱 Phone</h3>
                    <div class="form-group">
                        <input type="tel" name="phone" value="<?= htmlspecialchars($bio['phone'] ?? '') ?>" placeholder="+1234567890">
                        <div class="toggle-group">
                            <input type="checkbox" name="phone_enabled" id="phone_enabled" <?= ($bio['phone_enabled'] ?? 1) ? 'checked' : '' ?>>
                            <label for="phone_enabled">Show on bio page</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">💾 Save Bio Link</button>
            </form>
        </div>
    </div>
</body>
</html>
