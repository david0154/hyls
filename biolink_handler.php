<?php
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

// Handle file upload for gallery
function uploadGalleryImage($file, $user_id) {
    $upload_dir = __DIR__ . '/uploads/bio/gallery/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Invalid file type'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        return ['error' => 'File too large (max 5MB)'];
    }
    
    $new_filename = 'gallery_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => '/uploads/bio/gallery/' . $new_filename];
    }
    
    return ['error' => 'Upload failed'];
}

try {
    // Handle gallery image upload
    if (isset($_FILES['gallery_images'])) {
        $uploaded_count = 0;
        $errors = [];
        
        // Count existing images
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM bio_gallery WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing_count = $result['count'];
        
        $files = $_FILES['gallery_images'];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($existing_count + $uploaded_count >= 6) {
                $errors[] = "Maximum 6 images allowed";
                break;
            }
            
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $result = uploadGalleryImage($file, $user_id);
                
                if (isset($result['success'])) {
                    // Get max order
                    $stmt = $db->prepare("SELECT COALESCE(MAX(image_order), 0) as max_order FROM bio_gallery WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $new_order = $order_result['max_order'] + 1;
                    
                    // Insert into database
                    $stmt = $db->prepare("INSERT INTO bio_gallery (user_id, image_url, image_order, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $result['success'], $new_order]);
                    $uploaded_count++;
                } else {
                    $errors[] = $result['error'];
                }
            }
        }
        
        if ($uploaded_count > 0) {
            $_SESSION['success'] = "$uploaded_count image(s) uploaded successfully!";
        }
        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', array_unique($errors));
        }
        
        header('Location: biolink.php');
        exit;
    }
    
    // Handle gallery image delete
    if (isset($_GET['delete_gallery'])) {
        $image_id = (int)$_GET['delete_gallery'];
        
        // Get image path
        $stmt = $db->prepare("SELECT image_url FROM bio_gallery WHERE id = ? AND user_id = ?");
        $stmt->execute([$image_id, $user_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // Delete from database
            $stmt = $db->prepare("DELETE FROM bio_gallery WHERE id = ? AND user_id = ?");
            $stmt->execute([$image_id, $user_id]);
            
            // Delete physical file
            $file_path = __DIR__ . $image['image_url'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            
            $_SESSION['success'] = 'Image deleted successfully!';
        }
        
        header('Location: biolink.php');
        exit;
    }
    
    // Handle social media account addition
    if (isset($_POST['add_social_account'])) {
        $platform = $_POST['platform'];
        $label = trim($_POST['account_label']);
        $username = trim($_POST['account_username']);
        $url = trim($_POST['account_url']);
        
        // Get max order
        $stmt = $db->prepare("SELECT COALESCE(MAX(account_order), 0) as max_order FROM bio_social_accounts WHERE user_id = ? AND platform = ?");
        $stmt->execute([$user_id, $platform]);
        $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_order = $order_result['max_order'] + 1;
        
        $stmt = $db->prepare("INSERT INTO bio_social_accounts (user_id, platform, account_label, username, url, account_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$user_id, $platform, $label, $username, $url, $new_order]);
        
        $_SESSION['success'] = 'Social account added successfully!';
        header('Location: biolink.php');
        exit;
    }
    
    // Handle social account delete
    if (isset($_GET['delete_social'])) {
        $account_id = (int)$_GET['delete_social'];
        
        $stmt = $db->prepare("DELETE FROM bio_social_accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$account_id, $user_id]);
        
        $_SESSION['success'] = 'Social account deleted successfully!';
        header('Location: biolink.php');
        exit;
    }
    
    // Handle social account toggle
    if (isset($_GET['toggle_social'])) {
        $account_id = (int)$_GET['toggle_social'];
        
        $stmt = $db->prepare("UPDATE bio_social_accounts SET is_active = NOT is_active WHERE id = ? AND user_id = ?");
        $stmt->execute([$account_id, $user_id]);
        
        $_SESSION['success'] = 'Social account visibility toggled!';
        header('Location: biolink.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Biolink Handler Error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    header('Location: biolink.php');
    exit;
}
?>