<?php
// biolink_final.php - COMPLETE WORKING VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("=== Biolink page loaded ===");

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


    // ===  AUTO-DETECTION (SAFE FOR SHARED HOSTING) ===
    $has_video_table = false;
    $has_crop_columns = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'bio_social_videos'");
        $has_video_table = ($stmt->rowCount() > 0);

        $stmt = $db->query("SHOW COLUMNS FROM bio_gallery LIKE 'crop_%'");
        $has_crop_columns = ($stmt->rowCount() > 0);
    } catch (Exception $e) {
        error_log("Feature detection: " . $e->getMessage());
    }

    // === SOCIAL VIDEOS ===
    $social_videos = [];
    if ($has_video_table) {
        try {
            $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE user_id = ? ORDER BY display_order ASC");
            $stmt->execute([$user_id]);
            $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Videos error: " . $e->getMessage());
        }
    }

    // Video delete
    if (isset($_GET['delete_video']) && $has_video_table) {
        try {
            $stmt = $db->prepare("DELETE FROM bio_social_videos WHERE id = ? AND user_id = ?");
            $stmt->execute([(int)$_GET['delete_video'], $user_id]);
            header('Location: biolink.php');
            exit;
        } catch (Exception $e) {
            $error = 'Video delete failed';
        }
    }

    // Video add
    if (isset($_POST['add_video']) && $has_video_table) {
        $platform = $_POST['video_platform'] ?? '';
        $url = trim($_POST['video_url'] ?? '');
        $title = $_POST['video_title'] ?? '';
        $desc = $_POST['video_description'] ?? '';
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;

        if (!empty($url)) {
            $embed = '';
            $thumb = '';

            switch($platform) {
                case 'youtube':
                    if (preg_match('/(?:youtube\\.com\\/watch\\?v=|youtu\\.be\\/)([^&?]+)/', $url, $m)) {
                        $vid = $m[1];
                        $ap = $autoplay ? '1' : '0';
                        $embed = '<iframe width="560" height="315" src="https://www.youtube.com/embed/'.$vid.'?autoplay='.$ap.'" frameborder="0" allowfullscreen></iframe>';
                        $thumb = 'https://img.youtube.com/vi/'.$vid.'/maxresdefault.jpg';
                    }
                    break;
                case 'facebook':
                    $embed = '<iframe src="https://www.facebook.com/plugins/video.php?href='.urlencode($url).'" width="560" height="315" frameborder="0" allowfullscreen></iframe>';
                    break;
                case 'instagram':
                    $embed = '<iframe src="'.$url.'embed" width="400" height="480" frameborder="0"></iframe>';
                    break;
                case 'tiktok':
                    if (preg_match('/video\\/(\\d+)/', $url, $m)) {
                        $embed = '<iframe src="https://www.tiktok.com/embed/'.$m[1].'" width="340" height="700" frameborder="0"></iframe>';
                    }
                    break;
                case 'vimeo':
                    if (preg_match('/vimeo\\.com\\/(\\d+)/', $url, $m)) {
                        $embed = '<iframe src="https://player.vimeo.com/video/'.$m[1].'?autoplay='.$autoplay.'" width="640" height="360" frameborder="0" allowfullscreen></iframe>';
                    }
                    break;
                case 'dailymotion':
                    if (preg_match('/dailymotion\\.com\\/video\\/([^_]+)/', $url, $m)) {
                        $embed = '<iframe src="https://www.dailymotion.com/embed/video/'.$m[1].'?autoplay='.$autoplay.'" width="640" height="360" frameborder="0" allowfullscreen></iframe>';
                    }
                    break;
            }

            if ($embed) {
                $stmt = $db->prepare("SELECT COALESCE(MAX(display_order), 0)+1 FROM bio_social_videos WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $order = $stmt->fetchColumn();

                $stmt = $db->prepare("INSERT INTO bio_social_videos (user_id, platform, video_url, embed_code, title, description, thumbnail_url, display_order, autoplay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $platform, $url, $embed, $title, $desc, $thumb, $order, $autoplay]);
                $success = 'Video added successfully!';
                header('Location: biolink.php');
                exit;
            } else {
                $error = 'Invalid video URL format';
            }
        }
    }

    // ===  IMAGE CROP SAVE ===
    if (isset($_POST['save_crop'])) {
        $image_id = (int)$_POST['image_id'];
        $crop_x = (int)$_POST['crop_x'];
        $crop_y = (int)$_POST['crop_y'];
        $crop_width = (int)$_POST['crop_width'];
        $crop_height = (int)$_POST['crop_height'];

        try {
            $stmt = $db->query("SHOW COLUMNS FROM bio_gallery LIKE 'crop_%'");
            if ($stmt->rowCount() === 0) {
                // Auto-create crop columns
                $db->exec("ALTER TABLE bio_gallery ADD COLUMN crop_x INT DEFAULT 0");
                $db->exec("ALTER TABLE bio_gallery ADD COLUMN crop_y INT DEFAULT 0");
                $db->exec("ALTER TABLE bio_gallery ADD COLUMN crop_width INT DEFAULT 0");
                $db->exec("ALTER TABLE bio_gallery ADD COLUMN crop_height INT DEFAULT 0");
            }

            $stmt = $db->prepare("UPDATE bio_gallery SET crop_x = ?, crop_y = ?, crop_width = ?, crop_height = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$crop_x, $crop_y, $crop_width, $crop_height, $image_id, $user_id]);
            $success = '✅ Image cropped successfully!';

            // Refresh gallery
            $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
            $stmt->execute([$user_id]);
            $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $error = 'Crop failed: ' . $e->getMessage();
            error_log("Crop error: " . $e->getMessage());
        }
    }


    // Get gallery images
    $gallery_images = [];
    try {
        $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
        $stmt->execute([$user_id]);
        $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Gallery table error: " . $e->getMessage());
    }

    // Handle gallery delete
    if (isset($_GET['delete_gallery'])) {
        $image_id = (int)$_GET['delete_gallery'];
        
        try {
            $stmt = $db->prepare("SELECT image_url FROM bio_gallery WHERE id = ? AND user_id = ?");
            $stmt->execute([$image_id, $user_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($image) {
                $stmt = $db->prepare("DELETE FROM bio_gallery WHERE id = ? AND user_id = ?");
                $stmt->execute([$image_id, $user_id]);
                
                $file_path = __DIR__ . $image['image_url'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
                
                $success = 'Image deleted successfully!';
                
                // Refresh gallery
                $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
                $stmt->execute([$user_id]);
                $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $error = 'Delete failed: ' . $e->getMessage();
            error_log("Gallery delete error: " . $e->getMessage());
        }
    }

    // Handle gallery upload
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        $uploaded_count = 0;
        $errors = [];
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM bio_gallery WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $existing_count = $result['count'];
            
            $files = $_FILES['gallery_images'];
            
            if (!is_dir('uploads/bio/gallery')) {
                mkdir('uploads/bio/gallery', 0755, true);
            }
            
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($existing_count + $uploaded_count >= 6) {
                    $errors[] = "Maximum 6 images allowed";
                    break;
                }
                
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    
                    if (!in_array($ext, $allowed)) {
                        $errors[] = 'Invalid file type: ' . $files['name'][$i];
                        continue;
                    }
                    
                    if ($files['size'][$i] > 12 * 1024 * 1024) {  // ✅ 12MB LIMIT
                        $errors[] = 'File too large: ' . $files['name'][$i];
                        continue;
                    }
                    
                    $new_filename = 'gallery_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = 'uploads/bio/gallery/' . $new_filename;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
                        $stmt = $db->prepare("SELECT COALESCE(MAX(image_order), 0) as max_order FROM bio_gallery WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $new_order = $order_result['max_order'] + 1;
                        
                        $stmt = $db->prepare("INSERT INTO bio_gallery (user_id, image_url, image_order, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$user_id, '/' . $upload_path, $new_order]);
                        $uploaded_count++;
                    }
                }
            }
            
            if ($uploaded_count > 0) {
                $success = "$uploaded_count image(s) uploaded successfully!";
            }
            if (!empty($errors)) {
                $error = implode(', ', array_unique($errors));
            }
            
            // Refresh gallery images
            $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
            $stmt->execute([$user_id]);
            $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = 'Upload failed: ' . $e->getMessage();
            error_log("Gallery upload error: " . $e->getMessage());
        }
    }

    // MAIN FORM SUBMISSION
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['gallery_images']) && !isset($_GET['delete_gallery'])) {
        error_log("=== Processing bio form submission ===");
        
        $username = $current_user['username'];
        $display_name = $_POST['display_name'] ?? '';
        $bio_text = $_POST['bio'] ?? '';
        $theme_color = $_POST['theme_color'] ?? '#6366f1';
        
        // ALL 29 Social media fields
        $socials = [
            'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 
            'github', 'pinterest', 'snapchat', 'discord', 'twitch', 'telegram', 
            'whatsapp', 'spotify', 'reddit', 'website', 'email', 'phone',
            'threads', 'bluesky', 'mastodon', 'medium', 'substack', 'patreon',
            'onlyfans', 'cashapp', 'venmo', 'paypal', 'line'
        ];
        
        // CRITICAL: Collect ALL POST data
        error_log("POST data: " . print_r($_POST, true));
        
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
                // Get existing columns from database
                $stmt = $db->query("SHOW COLUMNS FROM bio_links");
                $all_db_columns = [];
                while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $all_db_columns[] = $col['Field'];
                }
                
                error_log("Database columns: " . implode(', ', $all_db_columns));
                
                // Build data array with ALL values
                $data = [
                    'username' => $username,
                    'display_name' => $display_name,
                    'bio' => $bio_text,
                    'theme_color' => $theme_color,
                    'profile_image' => $profile_image
                ];
                
                // Add cover_image if column exists
                if (in_array('cover_image', $all_db_columns)) {
                    $data['cover_image'] = $cover_image;
                }
                
                // Add social data - CHECK EACH PLATFORM
                foreach ($socials as $social) {
                    // Get URL value (empty if not provided)
                    $url_value = trim($_POST[$social] ?? '');
                    
                    // Get enabled state - CRITICAL: Default to 0 if not checked
                    $enabled_value = isset($_POST[$social . '_enabled']) ? 1 : 0;
                    
                    error_log("$social: URL='$url_value' Enabled=$enabled_value");
                    
                    // Add URL if column exists
                    if (in_array($social, $all_db_columns)) {
                        $data[$social] = $url_value;
                    }
                    
                    // Add enabled state if column exists
                    if (in_array($social . '_enabled', $all_db_columns)) {
                        $data[$social . '_enabled'] = $enabled_value;
                    }
                }
                
                if ($bio) {
                    // UPDATE existing bio
                    $set_parts = [];
                    $values = [];
                    
                    foreach ($data as $key => $value) {
                        $set_parts[] = "`$key` = ?";
                        $values[] = $value;
                    }
                    
                    $values[] = $user_id; // WHERE user_id = ?
                    
                    $sql = "UPDATE bio_links SET " . implode(', ', $set_parts) . " WHERE user_id = ?";
                    error_log("UPDATE SQL: $sql");
                    error_log("UPDATE VALUES: " . print_r($values, true));
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($values);
                    
                } else {
                    // INSERT new bio
                    $data['user_id'] = $user_id;
                    
                    $columns = array_keys($data);
                    $placeholders = array_fill(0, count($data), '?');
                    $values = array_values($data);
                    
                    $sql = "INSERT INTO bio_links (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    error_log("INSERT SQL: $sql");
                    error_log("INSERT VALUES: " . print_r($values, true));
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($values);
                }
                
                $success = 'Bio link saved successfully! View it at: ' . SITE_URL . '/bio/' . $username;
                
                // Refresh bio data
                $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $bio = $stmt->fetch();
                
                error_log("Bio saved successfully");
                
            } catch (Exception $e) {
                $error = 'Failed to save bio link: ' . $e->getMessage();
                error_log("Bio Link Save Error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
        }
    }

    $settings = getSettings($db);
    if (!$settings) {
        $settings = [];
    }
} catch (Exception $e) {
    error_log("Biolink Page Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    die("An error occurred: " . $e->getMessage());
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
        .navbar nav { display: flex; gap: 24px; }
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
        .navbar nav a:hover::after { width: 100%; }
        .navbar nav a:hover { color: #6366f1; }
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
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.25), 0 0 1px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.6);
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
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
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        .info-box strong { color: #6366f1; font-size: 16px; }
        .info-box a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 700;
            word-break: break-all;
            transition: all 0.3s;
        }
        .info-box a:hover { opacity: 0.8; }
        
        /* GALLERY STYLES */
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .gallery-item:hover { transform: scale(1.05); }
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
            transition: all 0.3s;
        }
        .gallery-slot:hover {
            border-color: #6366f1;
            background: #f0f4ff;
        }
        .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ef4444;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 10;
        }
        .gallery-item:hover .delete-btn { opacity: 1; }
        .delete-btn:hover { background: #dc2626; }
        
        .form-group { margin-bottom: 20px; position: relative; z-index: 1; }
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
        .form-group input[type="file"] { padding: 10px; cursor: pointer; }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus {
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
        .btn-primary:hover::before { width: 300px; height: 300px; }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
        }
        .btn-primary:active { transform: translateY(-1px); }
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
        .social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .container { padding: 0 10px; }
            .card { padding: 25px 15px; }
            .card h2 { font-size: 22px; }
            .navbar nav { gap: 12px; }
            .social-section { padding: 16px; }
            .social-grid { grid-template-columns: 1fr; }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .card { padding: 20px 12px; }
            .card h2 { font-size: 20px; }
            .navbar h1 { font-size: 20px; }
            .navbar nav { flex-direction: column; gap: 8px; }
        }

        /* ✅ VIDEO STYLES */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .video-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .video-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .video-embed {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            background: #000;
        }
        .video-embed iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .video-info {
            padding: 15px;
        }
        .video-info h4 {
            margin: 0 0 10px 0;
            color: #1e293b;
            font-size: 16px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .share-btn, .copy-btn {
            padding: 6px 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .share-btn:hover, .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .copy-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .copy-btn:hover {
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .delete-video-btn {
            color: #ef4444;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: color 0.3s;
        }
        .delete-video-btn:hover {
            color: #dc2626;
        }

        /* ✅ CROP MODAL */
        .crop-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .crop-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }
        .crop-canvas {
            max-width: 100%;
            border: 3px solid #6366f1;
            border-radius: 12px;
            cursor: crosshair;
            display: block;
        }
        .crop-buttons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn-crop-save {
            padding: 12px 30px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-crop-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }
        .btn-crop-cancel {
            padding: 12px 30px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-crop-cancel:hover {
            background: #dc2626;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 10001;
            animation: slideIn 0.3s;
            font-weight: 600;
        }
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
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

        <!-- ✅ SOCIAL VIDEOS SECTION -->
        <?php if ($has_video_table): ?>
        <div class="card" style="animation-delay: 0.1s;">
            <h2><i class="fas fa-video"></i> Social Videos</h2>
            <p style="color:#64748b;margin-bottom:20px;">📺 YouTube · Facebook · Instagram · TikTok · Vimeo · Dailymotion</p>

            <?php if (!empty($social_videos)): ?>
            <div class="video-grid">
                <?php foreach ($social_videos as $video): ?>
                <div class="video-item">
                    <div class="video-embed"><?= $video['embed_code'] ?></div>
                    <div class="video-info">
                        <h4><?= htmlspecialchars($video['title'] ?: 'Untitled Video') ?></h4>
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-top: 10px;">
                            <span class="badge"><?= ucfirst($video['platform']) ?></span>
                            <button onclick="shareVideo('<?= htmlspecialchars(addslashes($video['video_url'])) ?>', '<?= htmlspecialchars(addslashes($video['title'] ?: 'Video')) ?>')" class="share-btn">
                                <i class="fas fa-share-alt"></i> Share
                            </button>
                            <button onclick="copyLink('<?= htmlspecialchars(addslashes($video['video_url'])) ?>')" class="copy-btn">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                            <a href="?delete_video=<?= $video['id'] ?>" onclick="return confirm('Delete this video?')" class="delete-video-btn">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" style="margin-top: 30px; border-top: 2px solid #e2e8f0; padding-top: 30px;">
                <h3 style="margin-bottom: 20px; color: #1e293b;"><i class="fas fa-plus-circle"></i> Add New Video</h3>
                <div class="form-group">
                    <label><i class="fas fa-video"></i> Platform</label>
                    <select name="video_platform" required style="width:100%; padding:14px; border:2px solid #e2e8f0; border-radius:12px; font-size:14px; background: white;">
                        <option value="youtube">YouTube</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="tiktok">TikTok</option>
                        <option value="vimeo">Vimeo</option>
                        <option value="dailymotion">Dailymotion</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-link"></i> Video URL</label>
                    <input type="url" name="video_url" placeholder="https://youtube.com/watch?v=..." required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Title (Optional)</label>
                    <input type="text" name="video_title" placeholder="Video title">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description (Optional)</label>
                    <textarea name="video_description" placeholder="Video description"></textarea>
                </div>
                <div class="toggle-group">
                    <input type="checkbox" name="autoplay" id="autoplay">
                    <label for="autoplay">Enable autoplay</label>
                </div>
                <button type="submit" name="add_video" class="btn-primary"><i class="fas fa-plus"></i> Add Video</button>
            </form>
        </div>
        <?php endif; ?>


        <!-- Gallery Section -->
        <div class="card">
            <h2><i class="fas fa-images"></i> Image Gallery (<?= count($gallery_images) ?>/6)</h2>
            
            <?php if ($success && (strpos($success, 'image') !== false || strpos($success, 'deleted') !== false)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error && strpos($error, 'image') !== false): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="gallery-grid">
                <?php foreach ($gallery_images as $img): ?>
                <div class="gallery-item">
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Gallery image">
                    <a href="?delete_gallery=<?= $img['id'] ?>" 
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
            <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                <div class="form-group">
                    <label><i class="fas fa-upload"></i> Upload Images (Max 12MB each)</label>
                    <input type="file" name="gallery_images[]" accept="image/*" multiple required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-upload"></i> Upload Images (<?= 6 - count($gallery_images) ?> slots available)
                </button>
            </form>
            <?php else: ?>
            <p style="color: #64748b; text-align: center; margin-top: 20px;">
                <i class="fas fa-info-circle"></i> Gallery full (6/6 images). Delete an image to add new ones.
            </p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2><i class="fas fa-id-card"></i> Bio Link Settings</h2>
            
            <?php if ($success && strpos($success, 'image') === false && strpos($success, 'deleted') === false): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error && strpos($error, 'image') === false): ?>
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
                        <label><i class="fas fa-panorama"></i> Cover Image</label>
                        <input type="file" name="cover_image" accept="image/*">
                        <?php if (!empty($bio['cover_image'])): ?>
                            <small style="color: #64748b; display: block; margin-top: 8px;"><i class="fas fa-info-circle"></i> Current: <?= basename($bio['cover_image']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-palette"></i> Theme Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" name="theme_color" value="<?= htmlspecialchars($bio['theme_color'] ?? '#6366f1') ?>">
                        <span id="colorValue" style="font-weight: 700; color: #6366f1;"></span>
                    </div>
                </div>
                
                <h2 style="margin-top: 40px; margin-bottom: 24px;"><i class="fas fa-globe"></i> Social Media Links (29 Platforms)</h2>
                <p style="color: #64748b; margin-bottom: 20px;">💡 <strong>Tip:</strong> Uncheck "Show on bio page" to hide any platform!</p>
                
                <div class="social-grid">
                <?php
                $all_platforms = [
                    'facebook' => ['icon' => 'fab fa-facebook-f', 'label' => 'Facebook', 'placeholder' => 'https://facebook.com/username', 'color' => '#1877f2'],
                    'instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/username', 'color' => '#e4405f'],
                    'twitter' => ['icon' => 'fab fa-x-twitter', 'label' => 'X (Twitter)', 'placeholder' => 'https://twitter.com/username', 'color' => '#000000'],
                    'threads' => ['icon' => 'fab fa-threads', 'label' => 'Threads', 'placeholder' => 'https://threads.net/@username', 'color' => '#000000'],
                    'tiktok' => ['icon' => 'fab fa-tiktok', 'label' => 'TikTok', 'placeholder' => 'https://tiktok.com/@username', 'color' => '#000000'],
                    'youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube', 'placeholder' => 'https://youtube.com/@username', 'color' => '#ff0000'],
                    'linkedin' => ['icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn', 'placeholder' => 'https://linkedin.com/in/username', 'color' => '#0a66c2'],
                    'github' => ['icon' => 'fab fa-github', 'label' => 'GitHub', 'placeholder' => 'https://github.com/username', 'color' => '#333333'],
                    'discord' => ['icon' => 'fab fa-discord', 'label' => 'Discord', 'placeholder' => 'https://discord.gg/invite', 'color' => '#5865f2'],
                    'twitch' => ['icon' => 'fab fa-twitch', 'label' => 'Twitch', 'placeholder' => 'https://twitch.tv/username', 'color' => '#9146ff'],
                    'reddit' => ['icon' => 'fab fa-reddit-alien', 'label' => 'Reddit', 'placeholder' => 'https://reddit.com/u/username', 'color' => '#ff4500'],
                    'snapchat' => ['icon' => 'fab fa-snapchat', 'label' => 'Snapchat', 'placeholder' => 'https://snapchat.com/add/username', 'color' => '#fffc00'],
                    'pinterest' => ['icon' => 'fab fa-pinterest-p', 'label' => 'Pinterest', 'placeholder' => 'https://pinterest.com/username', 'color' => '#e60023'],
                    'telegram' => ['icon' => 'fab fa-telegram-plane', 'label' => 'Telegram', 'placeholder' => 'https://t.me/username', 'color' => '#26a5e4'],
                    'whatsapp' => ['icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp', 'placeholder' => 'https://wa.me/1234567890', 'color' => '#25d366'],
                    'spotify' => ['icon' => 'fab fa-spotify', 'label' => 'Spotify', 'placeholder' => 'https://open.spotify.com/user/username', 'color' => '#1db954'],
                    'medium' => ['icon' => 'fab fa-medium', 'label' => 'Medium', 'placeholder' => 'https://medium.com/@username', 'color' => '#000000'],
                    'substack' => ['icon' => 'fas fa-newspaper', 'label' => 'Substack', 'placeholder' => 'https://username.substack.com', 'color' => '#ff6719'],
                    'patreon' => ['icon' => 'fab fa-patreon', 'label' => 'Patreon', 'placeholder' => 'https://patreon.com/username', 'color' => '#ff424d'],
                    'onlyfans' => ['icon' => 'fas fa-user-lock', 'label' => 'OnlyFans', 'placeholder' => 'https://onlyfans.com/username', 'color' => '#00aff0'],
                    'bluesky' => ['icon' => 'fas fa-cloud', 'label' => 'Bluesky', 'placeholder' => 'https://bsky.app/profile/username', 'color' => '#1185fe'],
                    'mastodon' => ['icon' => 'fab fa-mastodon', 'label' => 'Mastodon', 'placeholder' => 'https://mastodon.social/@username', 'color' => '#6364ff'],
                    'line' => ['icon' => 'fab fa-line', 'label' => 'LINE', 'placeholder' => 'https://line.me/ti/p/username', 'color' => '#00c300'],
                    'cashapp' => ['icon' => 'fas fa-dollar-sign', 'label' => 'Cash App', 'placeholder' => 'https://cash.app/$username', 'color' => '#00d632'],
                    'venmo' => ['icon' => 'fas fa-money-bill-wave', 'label' => 'Venmo', 'placeholder' => 'https://venmo.com/username', 'color' => '#3d95ce'],
                    'paypal' => ['icon' => 'fab fa-paypal', 'label' => 'PayPal', 'placeholder' => 'https://paypal.me/username', 'color' => '#00457c'],
                    'website' => ['icon' => 'fas fa-globe', 'label' => 'Website', 'placeholder' => 'https://yourwebsite.com', 'color' => '#6366f1'],
                    'email' => ['icon' => 'fas fa-envelope', 'label' => 'Email', 'placeholder' => 'your@email.com', 'color' => '#ea4335'],
                    'phone' => ['icon' => 'fas fa-phone', 'label' => 'Phone', 'placeholder' => '+1234567890', 'color' => '#34a853'],
                ];
                
                foreach ($all_platforms as $key => $platform):
                    $value = $bio[$key] ?? '';
                    $enabled = isset($bio[$key . '_enabled']) ? ($bio[$key . '_enabled'] == 1) : true;
                ?>
                <div class="social-section">
                    <h3 style="color: <?= $platform['color'] ?>;"><i class="<?= $platform['icon'] ?>"></i> <?= $platform['label'] ?></h3>
                    <div class="form-group">
                        <input type="<?= $key === 'email' ? 'email' : ($key === 'phone' ? 'tel' : 'url') ?>" 
                               name="<?= $key ?>" 
                               value="<?= htmlspecialchars($value) ?>" 
                               placeholder="<?= $platform['placeholder'] ?>">
                        <div class="toggle-group">
                            <input type="checkbox" 
                                   name="<?= $key ?>_enabled" 
                                   id="<?= $key ?>_enabled" 
                                   value="1"
                                   <?= $enabled ? 'checked' : '' ?>>
                            <label for="<?= $key ?>_enabled">Show on bio page</label>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
        
        // Debug: Log form data before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('=== Form submitting ===');
            const formData = new FormData(this);
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
        });
    </script>


    <!-- ✅ CROP TOOL + VIDEO SHARE FUNCTIONS -->
    <script>
    // === IMAGE CROP TOOL WITH SAVE ===
    let cropData = { x: 0, y: 0, width: 0, height: 0, imageId: 0 };

    document.querySelectorAll('.gallery-item img').forEach(img => {
        // Add hint on hover
        img.title = 'Double-click to crop this image';
        img.style.cursor = 'pointer';

        img.addEventListener('dblclick', function() {
            const imgSrc = this.src;
            const imgId = this.closest('.gallery-item').dataset.imageId || 0;

            const modal = document.createElement('div');
            modal.className = 'crop-modal';
            modal.innerHTML = `
                <div class="crop-container">
                    <h3 style="margin-bottom:20px; text-align:center;"><i class="fas fa-crop"></i> Crop Image</h3>
                    <p style="color:#64748b;margin-bottom:15px; text-align:center;">💡 Click and drag to select crop area</p>
                    <canvas id="cropCanvas" class="crop-canvas"></canvas>
                    <div class="crop-buttons">
                        <button onclick="saveCropToServer()" class="btn-crop-save">
                            <i class="fas fa-check"></i> Save Crop
                        </button>
                        <button onclick="this.closest('.crop-modal').remove()" class="btn-crop-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            const canvas = document.getElementById('cropCanvas');
            const ctx = canvas.getContext('2d');
            const image = new Image();
            image.crossOrigin = 'anonymous';
            image.src = imgSrc;

            image.onload = function() {
                const maxWidth = window.innerWidth * 0.8;
                const maxHeight = window.innerHeight * 0.6;
                let width = image.width;
                let height = image.height;

                if (width > maxWidth) {
                    height = (height * maxWidth) / width;
                    width = maxWidth;
                }
                if (height > maxHeight) {
                    width = (width * maxHeight) / height;
                    height = maxHeight;
                }

                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(image, 0, 0, width, height);

                let isDrawing = false;
                let startX, startY;

                canvas.addEventListener('mousedown', e => {
                    isDrawing = true;
                    const rect = canvas.getBoundingClientRect();
                    startX = e.clientX - rect.left;
                    startY = e.clientY - rect.top;
                });

                canvas.addEventListener('mousemove', e => {
                    if (!isDrawing) return;
                    const rect = canvas.getBoundingClientRect();
                    const currentX = e.clientX - rect.left;
                    const currentY = e.clientY - rect.top;

                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(image, 0, 0, width, height);

                    // Draw selection rectangle
                    ctx.strokeStyle = '#6366f1';
                    ctx.lineWidth = 3;
                    ctx.strokeRect(startX, startY, currentX - startX, currentY - startY);
                    ctx.fillStyle = 'rgba(99, 102, 241, 0.1)';
                    ctx.fillRect(startX, startY, currentX - startX, currentY - startY);

                    // Store crop data
                    cropData = {
                        x: Math.round(startX),
                        y: Math.round(startY),
                        width: Math.round(currentX - startX),
                        height: Math.round(currentY - startY),
                        imageId: imgId
                    };
                });

                canvas.addEventListener('mouseup', () => {
                    isDrawing = false;
                });
            };
        });
    });

    // Add imageId to gallery items
    document.querySelectorAll('.gallery-item').forEach((item, index) => {
        const deleteLink = item.querySelector('a[href*="delete_gallery"]');
        if (deleteLink) {
            const id = new URLSearchParams(deleteLink.search).get('delete_gallery');
            item.dataset.imageId = id;
        }
    });

    // Save crop to server
    function saveCropToServer() {
        if (cropData.width === 0 || cropData.height === 0) {
            showNotification('⚠️ Please select a crop area first!', '#f59e0b');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="save_crop" value="1">
            <input type="hidden" name="image_id" value="${cropData.imageId}">
            <input type="hidden" name="crop_x" value="${cropData.x}">
            <input type="hidden" name="crop_y" value="${cropData.y}">
            <input type="hidden" name="crop_width" value="${cropData.width}">
            <input type="hidden" name="crop_height" value="${cropData.height}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // ===  VIDEO SHARE FUNCTIONS ===
    function shareVideo(url, title) {
        if (navigator.share) {
            navigator.share({
                title: title,
                text: 'Check out this video: ' + title,
                url: url
            }).then(() => {
                showNotification('✅ Shared successfully!', '#10b981');
            }).catch(err => {
                if (err.name !== 'AbortError') {
                    copyLink(url);
                }
            });
        } else {
            copyLink(url);
        }
    }

    function copyLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('✅ Link copied to clipboard!', '#10b981');
        }).catch(err => {
            // Fallback for older browsers
            const temp = document.createElement('textarea');
            temp.value = url;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            showNotification('✅ Link copied!', '#10b981');
        });
    }

    // Show notification
    function showNotification(message, color) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.innerHTML = message;
        notification.style.background = color;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s';
            setTimeout(() => notification.remove(), 300);
        }, 2500);
    }
    </script>
</body>
</html>