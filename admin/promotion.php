<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Helper function to save settings
function save_setting($db, $key, $value) {
    try {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Settings save error: " . $e->getMessage());
        return false;
    }
}

// Handle logo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['promo_logo_file'])) {
    $upload_dir = '../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['promo_logo_file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($file['type'], $allowed_types) && $file['error'] == 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'promo_logo_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $logo_url = SITE_URL . '/uploads/' . $filename;
            save_setting($db, 'promo_logo', $logo_url);
            $message = '✅ Logo uploaded successfully!';
        } else {
            $error = '❌ Failed to upload logo';
        }
    } else {
        $error = '❌ Invalid file type. Only JPG, PNG, GIF, WEBP allowed.';
    }
}

// Handle promotion settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_promotion') {
    try {
        $promo_enabled = isset($_POST['promo_enabled']) ? 1 : 0;
        $promo_url = sanitize($_POST['promo_url']);
        $promo_title = sanitize($_POST['promo_title']);
        $promo_description = sanitize($_POST['promo_description']);
        $promo_logo_url = sanitize($_POST['promo_logo_url']);
        
        save_setting($db, 'promo_enabled', $promo_enabled);
        save_setting($db, 'promo_url', $promo_url);
        save_setting($db, 'promo_title', $promo_title);
        save_setting($db, 'promo_description', $promo_description);
        
        if (!empty($promo_logo_url)) {
            save_setting($db, 'promo_logo', $promo_logo_url);
        }
        
        $message = '✅ Promotion settings updated!';
    } catch (Exception $e) {
        $error = '❌ Error saving settings: ' . $e->getMessage();
    }
}

// Get settings
$settings_result = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

function get_setting($key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotion Settings - HYLS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #208091;
            --primary-dark: #1a6a78;
            --success: #22c55e;
            --danger: #ef4444;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .header {
            background: var(--card);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 28px; color: var(--text); }
        .header a {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .header a:hover { background: var(--primary-dark); }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }
        .card {
            background: var(--card);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            color: var(--text);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 128, 145, 0.1);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 400;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--border);
            color: var(--text);
        }
        .hint-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #0c4a6e;
        }
        .logo-preview {
            max-width: 300px;
            height: auto;
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .upload-area:hover { border-color: var(--primary); background: #f9fafb; }
        .upload-icon { font-size: 48px; color: var(--primary); margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bullhorn"></i> Promotion Settings</h1>
            <a href="settings.php"><i class="fas fa-arrow-left"></i> Back to Settings</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">Promotion Configuration</h2>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>About:</strong> This promotion will appear on the 5-second ad page before users are redirected to their shortened links or bio pages.
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_promotion">
                
                <div class="checkbox-group">
                    <input type="checkbox" id="promo_enabled" name="promo_enabled" value="1" <?php echo get_setting('promo_enabled', '1') ? 'checked' : ''; ?>>
                    <label for="promo_enabled"><strong>Enable Promotion</strong></label>
                </div>

                <div class="form-group">
                    <label>Promotion URL</label>
                    <input type="url" name="promo_url" value="<?php echo htmlspecialchars(get_setting('promo_url', 'https://hypechats.com')); ?>" placeholder="https://hypechats.com" required>
                    <div class="hint-text">The link users will visit when clicking the promotion button</div>
                </div>

                <div class="form-group">
                    <label>Promotion Title</label>
                    <input type="text" name="promo_title" value="<?php echo htmlspecialchars(get_setting('promo_title', 'HypeChats - Connect & Chat')); ?>" placeholder="HypeChats - Connect & Chat" required>
                    <div class="hint-text">Catchy title for your promotion</div>
                </div>

                <div class="form-group">
                    <label>Promotion Description</label>
                    <textarea name="promo_description" placeholder="Join the fastest growing chat platform!" required><?php echo htmlspecialchars(get_setting('promo_description', 'Join the fastest growing chat platform!')); ?></textarea>
                    <div class="hint-text">Short description of your promotion</div>
                </div>

                <div class="form-group">
                    <label>Logo URL (or upload below)</label>
                    <input type="url" name="promo_logo_url" value="<?php echo htmlspecialchars(get_setting('promo_logo')); ?>" placeholder="https://example.com/logo.png">
                    <div class="hint-text">Direct URL to your logo image</div>
                </div>

                <?php if (!empty(get_setting('promo_logo'))): ?>
                    <div class="form-group">
                        <label>Current Logo:</label>
                        <img src="<?php echo htmlspecialchars(get_setting('promo_logo')); ?>" alt="Promo Logo" class="logo-preview">
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Promotion Settings
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="section-title">Upload Logo</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <h3 style="margin-bottom: 10px;">Upload Your Logo</h3>
                    <p style="color: var(--text-light); margin-bottom: 20px;">JPG, PNG, GIF, or WEBP format</p>
                    <input type="file" name="promo_logo_file" accept="image/*" required style="margin: 0 auto;">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Logo
                </button>
            </form>
        </div>
    </div>
</body>
</html>