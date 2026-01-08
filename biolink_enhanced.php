<?php
// biolink_enhanced.php - WITH IMAGE CROP & VIDEO MANAGEMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("=== Biolink Enhanced page loaded ===");

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

    // Get bio profile (NEW TABLE for video support)
    $stmt = $db->prepare("SELECT * FROM bio_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $bio_profile = $stmt->fetch();
    
    if (!$bio_profile) {
        // Create profile if not exists
        $stmt = $db->prepare("INSERT INTO bio_profiles (user_id, username, display_name, theme_color) VALUES (?, ?, ?, '#6366f1')");
        $stmt->execute([$user_id, $current_user['username'], $current_user['username']]);
        $bio_profile_id = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT * FROM bio_profiles WHERE id = ?");
        $stmt->execute([$bio_profile_id]);
        $bio_profile = $stmt->fetch();
    }

    // Get or create bio link (OLD TABLE for compatibility)
    $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $bio = $stmt->fetch();

    // Get gallery images
    $gallery_images = [];
    try {
        $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
        $stmt->execute([$user_id]);
        $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Gallery table error: " . $e->getMessage());
    }

    // Get social videos
    $social_videos = [];
    try {
        $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
        $stmt->execute([$bio_profile['id']]);
        $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Social videos error: " . $e->getMessage());
    }

    // Handle video delete
    if (isset($_GET['delete_video'])) {
        $video_id = (int)$_GET['delete_video'];
        try {
            $stmt = $db->prepare("DELETE FROM bio_social_videos WHERE id = ? AND bio_profile_id = ?");
            $stmt->execute([$video_id, $bio_profile['id']]);
            $success = 'Video deleted successfully!';
            
            // Refresh videos
            $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
            $stmt->execute([$bio_profile['id']]);
            $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Delete failed: ' . $e->getMessage();
        }
    }

    // Handle video add
    if (isset($_POST['add_video'])) {
        $platform = $_POST['video_platform'] ?? '';
        $video_url = trim($_POST['video_url'] ?? '');
        $video_title = $_POST['video_title'] ?? '';
        $video_desc = $_POST['video_description'] ?? '';
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;
        
        if (!empty($video_url)) {
            // Extract video ID and create embed code
            $embed_code = '';
            $thumbnail = '';
            
            if ($platform === 'youtube') {
                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?]+)/', $video_url, $matches);
                if (isset($matches[1])) {
                    $video_id = $matches[1];
                    $embed_code = '<iframe src="https://www.youtube.com/embed/' . $video_id . '?autoplay=' . $autoplay . '" frameborder="0" allowfullscreen></iframe>';
                    $thumbnail = 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
                }
            } elseif ($platform === 'facebook') {
                $embed_code = '<iframe src="https://www.facebook.com/plugins/video.php?href=' . urlencode($video_url) . '&show_text=0&width=560" frameborder="0" allowfullscreen></iframe>';
            } elseif ($platform === 'instagram') {
                $embed_code = '<iframe src="' . $video_url . 'embed" frameborder="0" allowfullscreen></iframe>';
            } elseif ($platform === 'tiktok') {
                preg_match('/video\/(\d+)/', $video_url, $matches);
                if (isset($matches[1])) {
                    $embed_code = '<iframe src="https://www.tiktok.com/embed/' . $matches[1] . '" frameborder="0" allowfullscreen></iframe>';
                }
            } elseif ($platform === 'vimeo') {
                preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches);
                if (isset($matches[1])) {
                    $embed_code = '<iframe src="https://player.vimeo.com/video/' . $matches[1] . '?autoplay=' . $autoplay . '" frameborder="0" allowfullscreen></iframe>';
                }
            } elseif ($platform === 'dailymotion') {
                preg_match('/dailymotion\.com\/video\/([^_]+)/', $video_url, $matches);
                if (isset($matches[1])) {
                    $embed_code = '<iframe src="https://www.dailymotion.com/embed/video/' . $matches[1] . '?autoplay=' . $autoplay . '" frameborder="0" allowfullscreen></iframe>';
                }
            }
            
            if ($embed_code) {
                try {
                    $stmt = $db->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM bio_social_videos WHERE bio_profile_id = ?");
                    $stmt->execute([$bio_profile['id']]);
                    $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $new_order = $order_result['max_order'] + 1;
                    
                    $stmt = $db->prepare("INSERT INTO bio_social_videos (bio_profile_id, platform, video_url, embed_code, title, description, thumbnail_url, display_order, autoplay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$bio_profile['id'], $platform, $video_url, $embed_code, $video_title, $video_desc, $thumbnail, $new_order, $autoplay]);
                    $success = 'Video added successfully!';
                    
                    // Refresh videos
                    $stmt = $db->prepare("SELECT * FROM bio_social_videos WHERE bio_profile_id = ? ORDER BY display_order ASC");
                    $stmt->execute([$bio_profile['id']]);
                    $social_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $error = 'Failed to add video: ' . $e->getMessage();
                }
            } else {
                $error = 'Invalid video URL for selected platform';
            }
        }
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

    // Handle gallery upload with crop data
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        $uploaded_count = 0;
        $errors = [];
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM bio_gallery WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $existing_count = $result['count'];
            
            $files = $_FILES['gallery_images'];
            
            if (!is_dir('uploads/images')) {
                mkdir('uploads/images', 0755, true);
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
                    
                    // 12MB limit
                    if ($files['size'][$i] > 12 * 1024 * 1024) {
                        $errors[] = 'File too large (max 12MB): ' . $files['name'][$i];
                        continue;
                    }
                    
                    $new_filename = 'gallery_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = 'uploads/images/' . $new_filename;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
                        // Get image dimensions
                        list($width, $height) = getimagesize($upload_path);
                        
                        $stmt = $db->prepare("SELECT COALESCE(MAX(image_order), 0) as max_order FROM bio_gallery WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $new_order = $order_result['max_order'] + 1;
                        
                        // Insert with crop data (defaults to full image)
                        $stmt = $db->prepare("INSERT INTO bio_gallery (user_id, image_url, image_order, crop_x, crop_y, crop_width, crop_height, original_width, original_height, created_at) VALUES (?, ?, ?, 0, 0, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$user_id, '/' . $upload_path, $new_order, $width, $height, $width, $height]);
                        $uploaded_count++;
                    }
                }
            }
            
            if ($uploaded_count > 0) {
                $success = "$uploaded_count image(s) uploaded successfully! Use crop tool to adjust.";
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

    // Handle image crop save
    if (isset($_POST['save_crop'])) {
        $image_id = (int)$_POST['image_id'];
        $crop_x = (int)$_POST['crop_x'];
        $crop_y = (int)$_POST['crop_y'];
        $crop_width = (int)$_POST['crop_width'];
        $crop_height = (int)$_POST['crop_height'];
        
        try {
            $stmt = $db->prepare("UPDATE bio_gallery SET crop_x = ?, crop_y = ?, crop_width = ?, crop_height = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$crop_x, $crop_y, $crop_width, $crop_height, $image_id, $user_id]);
            $success = 'Crop settings saved!';
            
            // Refresh gallery
            $stmt = $db->prepare("SELECT * FROM bio_gallery WHERE user_id = ? ORDER BY image_order ASC LIMIT 6");
            $stmt->execute([$user_id]);
            $gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Crop save failed: ' . $e->getMessage();
        }
    }

    // MAIN FORM SUBMISSION (Social Links)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['gallery_images']) && !isset($_GET['delete_gallery']) && !isset($_POST['add_video']) && !isset($_POST['save_crop'])) {
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
        
        // Handle profile image upload
        $profile_image = $bio['profile_image'] ?? '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed) && $_FILES['profile_image']['size'] <= 12 * 1024 * 1024) {
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
            
            if (in_array($ext, $allowed) && $_FILES['cover_image']['size'] <= 12 * 1024 * 1024) {
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
                
                // Build data array
                $data = [
                    'username' => $username,
                    'display_name' => $display_name,
                    'bio' => $bio_text,
                    'theme_color' => $theme_color,
                    'profile_image' => $profile_image
                ];
                
                if (in_array('cover_image', $all_db_columns)) {
                    $data['cover_image'] = $cover_image;
                }
                
                // Add social data
                foreach ($socials as $social) {
                    $url_value = trim($_POST[$social] ?? '');
                    $enabled_value = isset($_POST[$social . '_enabled']) ? 1 : 0;
                    
                    if (in_array($social, $all_db_columns)) {
                        $data[$social] = $url_value;
                    }
                    
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
                    
                    $values[] = $user_id;
                    
                    $sql = "UPDATE bio_links SET " . implode(', ', $set_parts) . " WHERE user_id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($values);
                    
                } else {
                    // INSERT new bio
                    $data['user_id'] = $user_id;
                    
                    $columns = array_keys($data);
                    $placeholders = array_fill(0, count($data), '?');
                    $values = array_values($data);
                    
                    $sql = "INSERT INTO bio_links (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($values);
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
    die("An error occurred: " . $e->getMessage());
}
?>
<!-- Continue in next file due to length -->
