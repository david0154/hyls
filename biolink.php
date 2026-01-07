<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$user = getUserById($db, $user_id);
$bio_link = getUserBioLink($db, $user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = $_POST['display_name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $theme_color = $_POST['theme_color'] ?? '#6366f1';
    $facebook = $_POST['facebook'] ?? '';
    $instagram = $_POST['instagram'] ?? '';
    $twitter = $_POST['twitter'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $youtube = $_POST['youtube'] ?? '';
    $tiktok = $_POST['tiktok'] ?? '';
    $github = $_POST['github'] ?? '';
    $website = $_POST['website'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    $profile_image = $bio_link['profile_image'] ?? null;
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $profile_image = $upload_path;
        }
    }
    
    if ($bio_link) {
        $stmt = $db->prepare("UPDATE bio_links SET display_name=?, bio=?, profile_image=?, theme_color=?, facebook=?, instagram=?, twitter=?, linkedin=?, youtube=?, tiktok=?, github=?, website=?, email=?, phone=? WHERE user_id=?");
        $stmt->execute([$display_name, $bio, $profile_image, $theme_color, $facebook, $instagram, $twitter, $linkedin, $youtube, $tiktok, $github, $website, $email, $phone, $user_id]);
    } else {
        $stmt = $db->prepare("INSERT INTO bio_links (user_id, username, display_name, bio, profile_image, theme_color, facebook, instagram, twitter, linkedin, youtube, tiktok, github, website, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $user['username'], $display_name, $bio, $profile_image, $theme_color, $facebook, $instagram, $twitter, $linkedin, $youtube, $tiktok, $github, $website, $email, $phone]);
    }
    
    $_SESSION['success'] = 'Bio link updated successfully!';
    header('Location: biolink.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bio Link - HYLS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .navbar {
            background: white;
            padding: 16px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #6366f1;
            text-decoration: none;
        }
        .btn-back {
            padding: 8px 16px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .main-content {
            padding: 40px 20px;
        }
        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .page-subtitle {
            color: #64748b;
            margin-bottom: 40px;
        }
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1e293b;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .color-picker-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .color-preview {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        input[type="color"] {
            padding: 4px;
            cursor: pointer;
        }
        .profile-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 16px;
        }
        .social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .help-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="dashboard.php" class="logo">🔗 HYLS</a>
                <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <h1 class="page-title">Edit Your Bio Link</h1>
        <p class="page-subtitle">Create a beautiful landing page for all your links</p>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-card">
                <h2 class="section-title">Basic Information</h2>
                
                <?php if ($bio_link && $bio_link['profile_image']): ?>
                <img src="<?= htmlspecialchars($bio_link['profile_image']) ?>" alt="Profile" class="profile-preview">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_image" accept="image/*">
                    <div class="help-text">Upload a square image for best results (JPG, PNG)</div>
                </div>

                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($bio_link['display_name'] ?? '') ?>" placeholder="Your Name">
                </div>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" placeholder="Tell people about yourself..."><?= htmlspecialchars($bio_link['bio'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Theme Color</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="theme_color" value="<?= htmlspecialchars($bio_link['theme_color'] ?? '#6366f1') ?>" id="themeColor">
                        <div class="color-preview" id="colorPreview"></div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h2 class="section-title">Social Media Links</h2>
                <div class="social-grid">
                    <div class="form-group">
                        <label>📘 Facebook</label>
                        <input type="url" name="facebook" value="<?= htmlspecialchars($bio_link['facebook'] ?? '') ?>" placeholder="https://facebook.com/username">
                    </div>

                    <div class="form-group">
                        <label>📷 Instagram</label>
                        <input type="url" name="instagram" value="<?= htmlspecialchars($bio_link['instagram'] ?? '') ?>" placeholder="https://instagram.com/username">
                    </div>

                    <div class="form-group">
                        <label>🐦 Twitter</label>
                        <input type="url" name="twitter" value="<?= htmlspecialchars($bio_link['twitter'] ?? '') ?>" placeholder="https://twitter.com/username">
                    </div>

                    <div class="form-group">
                        <label>💼 LinkedIn</label>
                        <input type="url" name="linkedin" value="<?= htmlspecialchars($bio_link['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                    </div>

                    <div class="form-group">
                        <label>🎥 YouTube</label>
                        <input type="url" name="youtube" value="<?= htmlspecialchars($bio_link['youtube'] ?? '') ?>" placeholder="https://youtube.com/@username">
                    </div>

                    <div class="form-group">
                        <label>🎵 TikTok</label>
                        <input type="url" name="tiktok" value="<?= htmlspecialchars($bio_link['tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@username">
                    </div>

                    <div class="form-group">
                        <label>💻 GitHub</label>
                        <input type="url" name="github" value="<?= htmlspecialchars($bio_link['github'] ?? '') ?>" placeholder="https://github.com/username">
                    </div>

                    <div class="form-group">
                        <label>🌐 Website</label>
                        <input type="url" name="website" value="<?= htmlspecialchars($bio_link['website'] ?? '') ?>" placeholder="https://yourwebsite.com">
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h2 class="section-title">Contact Information</h2>
                <div class="social-grid">
                    <div class="form-group">
                        <label>📧 Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($bio_link['email'] ?? '') ?>" placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label>📱 Phone</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($bio_link['phone'] ?? '') ?>" placeholder="+1234567890">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">💾 Save Bio Link</button>
        </form>
    </div>

    <script>
        const colorInput = document.getElementById('themeColor');
        const colorPreview = document.getElementById('colorPreview');
        
        function updateColorPreview() {
            colorPreview.style.backgroundColor = colorInput.value;
        }
        
        colorInput.addEventListener('input', updateColorPreview);
        updateColorPreview();
    </script>
</body>
</html>
