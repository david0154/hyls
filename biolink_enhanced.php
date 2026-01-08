<?php
// biolink_enhanced.php - Complete bio link editor with gallery and multiple social accounts
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
    $success = $_SESSION['success'] ?? '';
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['success'], $_SESSION['error']);

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

    // Get gallery images
    $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
    $stmt->execute([$user_id]);
    $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get social accounts grouped by platform
    $stmt = $db->prepare("SELECT * FROM bio_social_accounts WHERE user_id = ? ORDER BY platform, account_order");
    $stmt->execute([$user_id]);
    $social_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $accounts_by_platform = [];
    foreach ($social_accounts as $account) {
        $accounts_by_platform[$account['platform']][] = $account;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_social_account'])) {
        $username = $current_user['username'];
        $display_name = $_POST['display_name'] ?? '';
        $bio_text = $_POST['bio'] ?? '';
        $theme_color = $_POST['theme_color'] ?? '#6366f1';
        
        // Handle profile image upload
        $profile_image = $bio['profile_image'] ?? '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if (!is_dir('uploads/bio')) {
                    mkdir('uploads/bio', 0755, true);
                }
                
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/bio/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    if ($profile_image && file_exists($profile_image)) {
                        @unlink($profile_image);
                    }
                    $profile_image = $upload_path;
                }
            }
        }
        
        // Handle cover image upload
        $cover_image = $bio['cover_image'] ?? '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['cover_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if (!is_dir('uploads/bio')) {
                    mkdir('uploads/bio', 0755, true);
                }
                
                $new_filename = 'cover_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/bio/' . $new_filename;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    if ($cover_image && file_exists($cover_image)) {
                        @unlink($cover_image);
                    }
                    $cover_image = $upload_path;
                }
            }
        }
        
        if (empty($error)) {
            try {
                if ($bio) {
                    // Update existing bio
                    $sql = "UPDATE bio_links SET username = ?, display_name = ?, bio = ?, theme_color = ?, profile_image = ?, cover_image = ? WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$username, $display_name, $bio_text, $theme_color, $profile_image, $cover_image, $user_id]);
                } else {
                    // Create new bio
                    $sql = "INSERT INTO bio_links (user_id, username, display_name, bio, theme_color, profile_image, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$user_id, $username, $display_name, $bio_text, $theme_color, $profile_image, $cover_image]);
                }
                
                $_SESSION['success'] = 'Bio link saved successfully! View it at: ' . SITE_URL . '/bio/' . $username;
                header('Location: biolink_enhanced.php');
                exit;
                
            } catch (Exception $e) {
                $error = 'Failed to save bio link: ' . $e->getMessage();
                error_log("Bio Link Save Error: " . $e->getMessage());
            }
        }
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
    <title>Bio Link Editor - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 28px;
        }
        .navbar nav { display: flex; gap: 24px; }
        .navbar nav a {
            color: #64748b;
            text-decoration: none;
            font-weight: 700;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 24px;
        }
        .card h2 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #7f1d1d; }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-slot {
            aspect-ratio: 1;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }
        .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ef4444;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
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
        }
        .social-section {
            border: 2px solid rgba(99, 102, 241, 0.2);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .account-item {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 8px;
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-sm {
            padding: 6px 12px;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
        }
        #socialModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
        }
        @media (max-width: 768px) {
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🔗 <?= SITE_NAME ?></h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="biolink_enhanced.php">Bio Link</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Gallery Section -->
        <div class="card">
            <h2><i class="fas fa-images"></i> Image Gallery (<?= count($gallery_images) ?>/6)</h2>
            
            <div class="gallery-grid">
                <?php foreach ($gallery_images as $img): ?>
                <div class="gallery-item">
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Gallery image">
                    <a href="biolink_handler.php?delete_gallery=<?= $img['id'] ?>" 
                       onclick="return confirm('Delete this image?')" 
                       class="delete-btn">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                <?php endforeach; ?>
                
                <?php for ($i = 0; $i < (6 - count($gallery_images)); $i++): ?>
                <div class="gallery-slot">
                    <i class="fas fa-plus" style="font-size: 32px; color: #cbd5e1;"></i>
                </div>
                <?php endfor; ?>
            </div>
            
            <?php if (count($gallery_images) < 6): ?>
            <form action="biolink_handler.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="gallery_images[]" accept="image/*" multiple required>
                <button type="submit" class="btn-primary" style="margin-top: 10px;">
                    <i class="fas fa-upload"></i> Upload Images (<?= 6 - count($gallery_images) ?> slots available)
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Profile Section -->
        <div class="card">
            <h2><i class="fas fa-user"></i> Profile Information</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($bio['display_name'] ?? $current_user['username']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="4"><?= htmlspecialchars($bio['bio'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" name="profile_image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>Cover Image</label>
                    <input type="file" name="cover_image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>Theme Color</label>
                    <input type="color" name="theme_color" value="<?= htmlspecialchars($bio['theme_color'] ?? '#6366f1') ?>">
                </div>
                
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Profile</button>
            </form>
        </div>

        <!-- Social Media Section -->
        <div class="card">
            <h2><i class="fas fa-globe"></i> Social Media Accounts</h2>
            
            <?php
            $platforms = [
                'instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram', 'color' => '#e4405f'],
                'twitter' => ['icon' => 'fab fa-x-twitter', 'label' => 'X (Twitter)', 'color' => '#000000'],
                'facebook' => ['icon' => 'fab fa-facebook-f', 'label' => 'Facebook', 'color' => '#1877f2'],
                'youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube', 'color' => '#ff0000'],
                'tiktok' => ['icon' => 'fab fa-tiktok', 'label' => 'TikTok', 'color' => '#000000'],
                'linkedin' => ['icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn', 'color' => '#0a66c2'],
                'github' => ['icon' => 'fab fa-github', 'label' => 'GitHub', 'color' => '#333333'],
                'spotify' => ['icon' => 'fab fa-spotify', 'label' => 'Spotify', 'color' => '#1db954'],
            ];
            
            foreach ($platforms as $key => $platform):
            ?>
            <div class="social-section">
                <h3 style="color: <?= $platform['color'] ?>;"><i class="<?= $platform['icon'] ?>"></i> <?= $platform['label'] ?></h3>
                
                <?php if (isset($accounts_by_platform[$key])): ?>
                    <?php foreach ($accounts_by_platform[$key] as $account): ?>
                    <div class="account-item">
                        <div>
                            <strong><?= htmlspecialchars($account['account_label']) ?></strong> - @<?= htmlspecialchars($account['username']) ?>
                            <br><small><?= number_format($account['clicks']) ?> clicks</small>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="biolink_handler.php?toggle_social=<?= $account['id'] ?>" 
                               class="btn-sm" 
                               style="background: <?= $account['is_active'] ? '#22c55e' : '#94a3b8' ?>;">
                                <?= $account['is_active'] ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>' ?>
                            </a>
                            <a href="biolink_handler.php?delete_social=<?= $account['id'] ?>" 
                               onclick="return confirm('Delete this account?')" 
                               class="btn-sm" style="background: #ef4444;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <button onclick="openModal('<?= $key ?>', '<?= $platform['label'] ?>')" class="btn-primary" style="margin-top: 10px;">
                    <i class="fas fa-plus"></i> Add <?= $platform['label'] ?> Account
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="socialModal">
        <div class="modal-content">
            <h3 id="modalTitle">Add Social Account</h3>
            <form action="biolink_handler.php" method="POST">
                <input type="hidden" name="add_social_account" value="1">
                <input type="hidden" name="platform" id="modalPlatform">
                
                <div class="form-group">
                    <label>Label (e.g., Personal, Business)</label>
                    <input type="text" name="account_label" required>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="account_username" required>
                </div>
                
                <div class="form-group">
                    <label>Full URL</label>
                    <input type="url" name="account_url" required>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeModal()" style="flex: 1; background: #94a3b8;" class="btn-primary">Cancel</button>
                    <button type="submit" class="btn-primary" style="flex: 1;">Add Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(platform, label) {
        document.getElementById('modalPlatform').value = platform;
        document.getElementById('modalTitle').textContent = 'Add ' + label + ' Account';
        document.getElementById('socialModal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('socialModal').style.display = 'none';
    }
    </script>
</body>
</html>