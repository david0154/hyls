<?php
// biolink_fixed_cover.php - FIXED VERSION with proper cover image handling
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

    // FIRST: Ensure cover_image column exists
    try {
        $stmt = $db->query("SHOW COLUMNS FROM bio_links LIKE 'cover_image'");
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$col) {
            error_log("cover_image column missing, adding it...");
            $db->query("ALTER TABLE `bio_links` ADD COLUMN `cover_image` VARCHAR(255) DEFAULT '' AFTER `profile_image`");
            error_log("cover_image column added successfully");
        }
    } catch (Exception $e) {
        error_log("Error checking/adding cover_image column: " . $e->getMessage());
    }

    // Get or create bio link
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
                    
                    if ($files['size'][$i] > 5 * 1024 * 1024) {
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
        
        // Create uploads directory if it doesn't exist
        if (!is_dir('uploads/bio')) {
            mkdir('uploads/bio', 0755, true);
        }
        
        // Handle profile image upload
        $profile_image = $bio['profile_image'] ?? '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/bio/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    if ($profile_image && file_exists($profile_image)) {
                        @unlink($profile_image);
                    }
                    $profile_image = $upload_path;
                    error_log("Profile image uploaded: $profile_image");
                }
            }
        }
        
        // Handle cover image upload - FIXED VERSION
        $cover_image = $bio['cover_image'] ?? '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            error_log("Cover image upload detected");
            error_log("File name: " . $_FILES['cover_image']['name']);
            error_log("File size: " . $_FILES['cover_image']['size']);
            
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['cover_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'cover_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/bio/' . $new_filename;
                
                error_log("Attempting to move file to: $upload_path");
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    // Delete old cover if exists
                    if ($cover_image && file_exists($cover_image)) {
                        @unlink($cover_image);
                        error_log("Deleted old cover: $cover_image");
                    }
                    $cover_image = $upload_path;
                    error_log("✅ Cover image uploaded successfully: $cover_image");
                } else {
                    error_log("❌ Failed to move cover image");
                }
            } else {
                error_log("Invalid file type: $ext");
            }
        } else {
            error_log("No cover image upload or error: " . ($_FILES['cover_image']['error'] ?? 'not set'));
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
                
                // CRITICAL: Add cover_image if column exists
                if (in_array('cover_image', $all_db_columns)) {
                    $data['cover_image'] = $cover_image;
                    error_log("Adding cover_image to data: $cover_image");
                } else {
                    error_log("⚠️ WARNING: cover_image column not found in database!");
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
                error_log("Cover image in DB: " . ($bio['cover_image'] ?? 'not set'));
                
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
<!-- REST OF HTML IS SAME AS biolink_final.php -->
<!-- Copy the entire HTML section from biolink_final.php here -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bio Link - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/assets/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same CSS as biolink_final.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* ... copy all CSS ... */
    </style>
</head>
<body>
    <!-- Same HTML body as biolink_final.php -->
    <!-- Include navbar, forms, etc. -->
    <p style="text-align: center; padding: 20px;">Copy complete HTML from biolink_final.php</p>
</body>
</html>