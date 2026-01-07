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

// Get or create bio profile
function getBioProfileId($db, $user_id) {
    $stmt = $db->prepare("SELECT id FROM bio_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    return $profile['id'] ?? null;
}

// Handle file upload
function uploadBioImage($file, $type = 'profiles') {
    $upload_dir = __DIR__ . '/uploads/bio/' . $type . '/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        return ['error' => 'File too large. Max 5MB.'];
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $ext;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => '/uploads/bio/' . $type . '/' . $new_filename];
    }
    
    return ['error' => 'Failed to upload file.'];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // Save basic profile
        case 'save_profile':
            $username = trim($_POST['username']);
            $display_name = trim($_POST['display_name']);
            $bio = trim($_POST['bio']);
            
            // Check username availability
            $stmt = $db->prepare("SELECT id FROM bio_profiles WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Username already taken!';
                header('Location: biolink_editor.php');
                exit;
            }
            
            // Handle file uploads
            $profile_picture = null;
            $cover_image = null;
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $result = uploadBioImage($_FILES['profile_picture'], 'profiles');
                if (isset($result['success'])) {
                    $profile_picture = $result['success'];
                } else {
                    $_SESSION['error'] = $result['error'];
                    header('Location: biolink_editor.php');
                    exit;
                }
            }
            
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $result = uploadBioImage($_FILES['cover_image'], 'covers');
                if (isset($result['success'])) {
                    $cover_image = $result['success'];
                } else {
                    $_SESSION['error'] = $result['error'];
                    header('Location: biolink_editor.php');
                    exit;
                }
            }
            
            // Check if profile exists
            $bio_profile_id = getBioProfileId($db, $user_id);
            
            if ($bio_profile_id) {
                // Update existing profile
                $sql = "UPDATE bio_profiles SET username = ?, display_name = ?, bio = ?, updated_at = NOW()";
                $params = [$username, $display_name, $bio];
                
                if ($profile_picture) {
                    $sql .= ", profile_picture = ?";
                    $params[] = $profile_picture;
                }
                
                if ($cover_image) {
                    $sql .= ", cover_image = ?";
                    $params[] = $cover_image;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $bio_profile_id;
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
            } else {
                // Create new profile
                $stmt = $db->prepare("
                    INSERT INTO bio_profiles 
                    (user_id, username, display_name, bio, profile_picture, cover_image, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$user_id, $username, $display_name, $bio, $profile_picture, $cover_image]);
            }
            
            $_SESSION['success'] = 'Profile saved successfully!';
            header('Location: biolink_editor.php');
            exit;
            
        // Add social media link
        case 'add_social':
            $bio_profile_id = getBioProfileId($db, $user_id);
            
            if (!$bio_profile_id) {
                $_SESSION['error'] = 'Please save your profile first!';
                header('Location: biolink_editor.php');
                exit;
            }
            
            $platform = $_POST['platform'];
            $label = trim($_POST['label']);
            $username = trim($_POST['username']);
            $url = trim($_POST['url']);
            
            // Get display order
            $stmt = $db->prepare("SELECT MAX(display_order) as max_order FROM bio_social_links WHERE bio_profile_id = ?");
            $stmt->execute([$bio_profile_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $display_order = ($result['max_order'] ?? 0) + 1;
            
            $stmt = $db->prepare("
                INSERT INTO bio_social_links 
                (bio_profile_id, platform, label, username, url, display_order, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$bio_profile_id, $platform, $label, $username, $url, $display_order]);
            
            $_SESSION['success'] = 'Social media account added!';
            header('Location: biolink_editor.php');
            exit;
            
        // Delete social media link
        case 'delete_social':
            $id = (int)$_GET['id'];
            $bio_profile_id = getBioProfileId($db, $user_id);
            
            $stmt = $db->prepare("DELETE FROM bio_social_links WHERE id = ? AND bio_profile_id = ?");
            $stmt->execute([$id, $bio_profile_id]);
            
            $_SESSION['success'] = 'Social media account deleted!';
            header('Location: biolink_editor.php');
            exit;
            
        // Save gallery images
        case 'save_gallery':
            $bio_profile_id = getBioProfileId($db, $user_id);
            
            if (!$bio_profile_id) {
                $_SESSION['error'] = 'Please save your profile first!';
                header('Location: biolink_editor.php');
                exit;
            }
            
            if (isset($_FILES['gallery_images'])) {
                $uploaded_count = 0;
                
                // Count existing images
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM bio_gallery WHERE bio_profile_id = ?");
                $stmt->execute([$bio_profile_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $existing_count = $result['count'];
                
                foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                    if ($existing_count + $uploaded_count >= 6) {
                        break; // Max 6 images
                    }
                    
                    if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['gallery_images']['name'][$key],
                            'tmp_name' => $tmp_name,
                            'size' => $_FILES['gallery_images']['size'][$key]
                        ];
                        
                        $result = uploadBioImage($file, 'gallery');
                        
                        if (isset($result['success'])) {
                            // Get display order
                            $stmt = $db->prepare("SELECT MAX(display_order) as max_order FROM bio_gallery WHERE bio_profile_id = ?");
                            $stmt->execute([$bio_profile_id]);
                            $order_result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $display_order = ($order_result['max_order'] ?? 0) + 1;
                            
                            $stmt = $db->prepare("
                                INSERT INTO bio_gallery 
                                (bio_profile_id, image_url, display_order, created_at) 
                                VALUES (?, ?, ?, NOW())
                            ");
                            $stmt->execute([$bio_profile_id, $result['success'], $display_order]);
                            $uploaded_count++;
                        }
                    }
                }
                
                if ($uploaded_count > 0) {
                    $_SESSION['success'] = "$uploaded_count image(s) uploaded successfully!";
                } else {
                    $_SESSION['error'] = 'No images were uploaded.';
                }
            }
            
            header('Location: biolink_editor.php');
            exit;
            
        // Delete gallery image
        case 'delete_gallery':
            $id = (int)$_GET['id'];
            $bio_profile_id = getBioProfileId($db, $user_id);
            
            // Get image path for deletion
            $stmt = $db->prepare("SELECT image_url FROM bio_gallery WHERE id = ? AND bio_profile_id = ?");
            $stmt->execute([$id, $bio_profile_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($image) {
                // Delete from database
                $stmt = $db->prepare("DELETE FROM bio_gallery WHERE id = ? AND bio_profile_id = ?");
                $stmt->execute([$id, $bio_profile_id]);
                
                // Delete physical file
                $file_path = __DIR__ . $image['image_url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $_SESSION['success'] = 'Image deleted!';
            }
            
            header('Location: biolink_editor.php');
            exit;
            
        // Add custom link
        case 'add_custom_link':
            $bio_profile_id = getBioProfileId($db, $user_id);
            
            if (!$bio_profile_id) {
                $_SESSION['error'] = 'Please save your profile first!';
                header('Location: biolink_editor.php');
                exit;
            }
            
            $title = trim($_POST['title']);
            $url = trim($_POST['url']);
            $description = trim($_POST['description']);
            
            // Get display order
            $stmt = $db->prepare("SELECT MAX(display_order) as max_order FROM bio_custom_links WHERE bio_profile_id = ?");
            $stmt->execute([$bio_profile_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $display_order = ($result['max_order'] ?? 0) + 1;
            
            $stmt = $db->prepare("
                INSERT INTO bio_custom_links 
                (bio_profile_id, title, url, description, display_order, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$bio_profile_id, $title, $url, $description, $display_order]);
            
            $_SESSION['success'] = 'Custom link added!';
            header('Location: biolink_editor.php');
            exit;
            
        // Delete custom link
        case 'delete_custom':
            $id = (int)$_GET['id'];
            $bio_profile_id = getBioProfileId($db, $user_id);
            
            $stmt = $db->prepare("DELETE FROM bio_custom_links WHERE id = ? AND bio_profile_id = ?");
            $stmt->execute([$id, $bio_profile_id]);
            
            $_SESSION['success'] = 'Custom link deleted!';
            header('Location: biolink_editor.php');
            exit;
            
        default:
            $_SESSION['error'] = 'Invalid action!';
            header('Location: biolink_editor.php');
            exit;
    }
    
} catch (Exception $e) {
    error_log("Bio Link Save Error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    header('Location: biolink_editor.php');
    exit;
}
?>