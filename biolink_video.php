<?php
/**
 * Social Media Video Management
 * Add, update, delete social media video embeds
 */

session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/video_embed.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];

// Get user's bio profile
$stmt = $db->prepare("SELECT id FROM bio_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Bio profile not found']);
    exit;
}

$profile_id = $profile['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_video':
        addVideo($db, $profile_id);
        break;
    case 'delete_video':
        deleteVideo($db, $profile_id);
        break;
    case 'update_order':
        updateOrder($db, $profile_id);
        break;
    case 'toggle_autoplay':
        toggleAutoplay($db, $profile_id);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

/**
 * Add video
 */
function addVideo($db, $profile_id) {
    $video_url = trim($_POST['video_url'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $autoplay = isset($_POST['autoplay']) ? (int)$_POST['autoplay'] : 1;
    
    if (empty($video_url)) {
        $_SESSION['error'] = 'Video URL is required';
        header('Location: edit_bio.php#videos');
        exit;
    }
    
    // Auto-detect platform if not provided
    if (empty($platform) || $platform === 'auto') {
        $platform = VideoEmbed::detectPlatform($video_url);
    }
    
    if ($platform === 'unknown') {
        $_SESSION['error'] = 'Unsupported video platform';
        header('Location: edit_bio.php#videos');
        exit;
    }
    
    // Generate embed code
    $embed_code = VideoEmbed::generateEmbed($video_url, $platform, $autoplay);
    
    if (empty($embed_code)) {
        $_SESSION['error'] = 'Failed to generate embed code. Check video URL.';
        header('Location: edit_bio.php#videos');
        exit;
    }
    
    // Get thumbnail
    $thumbnail_url = VideoEmbed::getThumbnail($video_url, $platform);
    
    // Get next display order
    $stmt = $db->prepare("SELECT MAX(display_order) as max_order FROM bio_social_videos WHERE bio_profile_id = ?");
    $stmt->execute([$profile_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $display_order = ($result['max_order'] ?? 0) + 1;
    
    // Insert video
    $stmt = $db->prepare("
        INSERT INTO bio_social_videos 
        (bio_profile_id, platform, video_url, embed_code, title, description, thumbnail_url, display_order, autoplay, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $profile_id,
        $platform,
        $video_url,
        $embed_code,
        $title,
        $description,
        $thumbnail_url,
        $display_order,
        $autoplay
    ]);
    
    $_SESSION['success'] = 'Video added successfully!';
    header('Location: edit_bio.php#videos');
    exit;
}

/**
 * Delete video
 */
function deleteVideo($db, $profile_id) {
    $video_id = (int)($_GET['id'] ?? 0);
    
    if ($video_id <= 0) {
        $_SESSION['error'] = 'Invalid video ID';
        header('Location: edit_bio.php#videos');
        exit;
    }
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM bio_social_videos WHERE id = ? AND bio_profile_id = ?");
    $stmt->execute([$video_id, $profile_id]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Video not found';
        header('Location: edit_bio.php#videos');
        exit;
    }
    
    // Delete video
    $stmt = $db->prepare("DELETE FROM bio_social_videos WHERE id = ?");
    $stmt->execute([$video_id]);
    
    $_SESSION['success'] = 'Video deleted successfully';
    header('Location: edit_bio.php#videos');
    exit;
}

/**
 * Update display order
 */
function updateOrder($db, $profile_id) {
    $order = $_POST['order'] ?? [];
    
    if (!is_array($order)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid order data']);
        exit;
    }
    
    $db->pdo->beginTransaction();
    
    try {
        $stmt = $db->prepare("UPDATE bio_social_videos SET display_order = ? WHERE id = ? AND bio_profile_id = ?");
        
        foreach ($order as $index => $video_id) {
            $stmt->execute([$index + 1, $video_id, $profile_id]);
        }
        
        $db->pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/**
 * Toggle autoplay
 */
function toggleAutoplay($db, $profile_id) {
    $video_id = (int)($_POST['id'] ?? 0);
    $autoplay = (int)($_POST['autoplay'] ?? 0);
    
    if ($video_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid video ID']);
        exit;
    }
    
    // Update autoplay
    $stmt = $db->prepare("UPDATE bio_social_videos SET autoplay = ? WHERE id = ? AND bio_profile_id = ?");
    $stmt->execute([$autoplay, $video_id, $profile_id]);
    
    // Regenerate embed code with new autoplay setting
    $stmt = $db->prepare("SELECT video_url, platform FROM bio_social_videos WHERE id = ?");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($video) {
        $new_embed = VideoEmbed::generateEmbed($video['video_url'], $video['platform'], $autoplay);
        $stmt = $db->prepare("UPDATE bio_social_videos SET embed_code = ? WHERE id = ?");
        $stmt->execute([$new_embed, $video_id]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
?>