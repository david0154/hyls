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
$current_tab = $_GET['tab'] ?? 'general';
$message = '';
$error = '';

// Helper function to save settings (INSERT or UPDATE)
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'save_general':
                $site_name = sanitize($_POST['site_name']);
                $site_keywords = sanitize($_POST['site_keywords']);
                $theme_color = sanitize($_POST['theme_color']);
                
                save_setting($db, 'site_name', $site_name);
                save_setting($db, 'site_keywords', $site_keywords);
                save_setting($db, 'theme_color', $theme_color);
                
                $message = '✅ General settings updated!';
                break;
                
            case 'save_google_analytics':
                $ga_enabled = isset($_POST['ga_enabled']) ? 1 : 0;
                $ga_tracking_id = sanitize($_POST['ga_tracking_id']);
                $ga_api_key = sanitize($_POST['ga_api_key']);
                $ga_view_id = sanitize($_POST['ga_view_id']);
                
                save_setting($db, 'ga_enabled', $ga_enabled);
                save_setting($db, 'ga_tracking_id', $ga_tracking_id);
                save_setting($db, 'ga_api_key', $ga_api_key);
                save_setting($db, 'ga_view_id', $ga_view_id);
                
                $message = '✅ Google Analytics configured!';
                break;
                
            case 'save_google_oauth':
                $google_oauth_enabled = isset($_POST['google_oauth_enabled']) ? 1 : 0;
                $google_client_id = sanitize($_POST['google_client_id']);
                $google_client_secret = sanitize($_POST['google_client_secret']);
                
                save_setting($db, 'google_oauth_enabled', $google_oauth_enabled);
                save_setting($db, 'google_client_id', $google_client_id);
                save_setting($db, 'google_client_secret', $google_client_secret);
                
                $message = '✅ Google OAuth configured!';
                break;
                
            case 'save_ads':
                $ads_enabled = isset($_POST['ads_enabled']) ? 1 : 0;
                $ads_duration = (int)$_POST['ads_duration'];
                $google_ads_enabled = isset($_POST['google_ads_enabled']) ? 1 : 0;
                $google_ads_code = $_POST['google_ads_code'] ?? '';
                $juicy_ads_enabled = isset($_POST['juicy_ads_enabled']) ? 1 : 0;
                $juicy_ads_code = $_POST['juicy_ads_code'] ?? '';
                $custom_ads_enabled = isset($_POST['custom_ads_enabled']) ? 1 : 0;
                $custom_ads_code = $_POST['custom_ads_code'] ?? '';
                
                save_setting($db, 'ads_enabled', $ads_enabled);
                save_setting($db, 'ads_duration', $ads_duration);
                save_setting($db, 'google_ads_enabled', $google_ads_enabled);
                save_setting($db, 'google_ads_code', $google_ads_code);
                save_setting($db, 'juicy_ads_enabled', $juicy_ads_enabled);
                save_setting($db, 'juicy_ads_code', $juicy_ads_code);
                save_setting($db, 'custom_ads_enabled', $custom_ads_enabled);
                save_setting($db, 'custom_ads_code', $custom_ads_code);
                
                $message = '✅ Advertisement settings updated!';
                break;
                
            case 'save_link_scanning':
                $link_scanning_enabled = isset($_POST['link_scanning_enabled']) ? 1 : 0;
                $virustotal_api_key = sanitize($_POST['virustotal_api_key']);
                $block_malicious = isset($_POST['block_malicious']) ? 1 : 0;
                
                save_setting($db, 'link_scanning_enabled', $link_scanning_enabled);
                save_setting($db, 'virustotal_api_key', $virustotal_api_key);
                save_setting($db, 'block_malicious', $block_malicious);
                
                $message = '✅ Link scanning configured!';
                break;
                
            case 'save_announcement':
                $announcement_enabled = isset($_POST['announcement_enabled']) ? 1 : 0;
                $announcement_text = $_POST['announcement_text'] ?? '';
                $announcement_type = sanitize($_POST['announcement_type']);
                
                save_setting($db, 'announcement_enabled', $announcement_enabled);
                save_setting($db, 'announcement_text', $announcement_text);
                save_setting($db, 'announcement_type', $announcement_type);
                
                $message = '✅ Announcement updated!';
                break;
        }
    } catch (Exception $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
        error_log($error);
    }
}

// Get all settings
$settings_result = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Helper function to get setting value
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
    <title>Admin Settings - HYLS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #208091;
            --primary-dark: #1a6a78;
            --danger: #c01537;
            --success: #22c55e;
            --warning: #f59e0b;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

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

        .header h1 {
            font-size: 28px;
            color: var(--text);
        }

        .header a {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .header a:hover {
            background: var(--primary-dark);
        }

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

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: var(--card);
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
            color: var(--text-light);
            white-space: nowrap;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        .tab-btn:hover {
            background: #e5e7eb;
        }

        .tab-btn.active:hover {
            background: var(--primary);
        }

        .tab-content {
            display: none;
            background: var(--card);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 128, 145, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(32, 128, 145, 0.3);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            color: var(--text);
        }

        .section {
            margin-bottom: 30px;
        }

        .code-input {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            background: #f9fafb;
        }

        .hint-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .tab-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Admin Settings</h1>
            <a href="index.php">← Back to Dashboard</a>
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

        <div class="tabs">
            <button class="tab-btn <?php echo $current_tab == 'general' ? 'active' : ''; ?>" onclick="switchTab(event, 'general')"><i class="fas fa-sliders-h"></i> General</button>
            <button class="tab-btn <?php echo $current_tab == 'analytics' ? 'active' : ''; ?>" onclick="switchTab(event, 'analytics')"><i class="fas fa-chart-line"></i> Analytics</button>
            <button class="tab-btn <?php echo $current_tab == 'google_oauth' ? 'active' : ''; ?>" onclick="switchTab(event, 'google_oauth')"><i class="fab fa-google"></i> Google OAuth</button>
            <button class="tab-btn <?php echo $current_tab == 'ads' ? 'active' : ''; ?>" onclick="switchTab(event, 'ads')"><i class="fas fa-ad"></i> Ads Networks</button>
            <button class="tab-btn <?php echo $current_tab == 'scanning' ? 'active' : ''; ?>" onclick="switchTab(event, 'scanning')"><i class="fas fa-shield-alt"></i> Link Scanning</button>
            <button class="tab-btn <?php echo $current_tab == 'announce' ? 'active' : ''; ?>" onclick="switchTab(event, 'announce')"><i class="fas fa-bullhorn"></i> Announcements</button>
        </div>

        <!-- General Settings -->
        <div id="general" class="tab-content <?php echo $current_tab == 'general' ? 'active' : ''; ?>">
            <h2 class="section-title">General Settings</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_general">
                
                <div class="form-group">
                    <label>Site Name</label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars(get_setting('site_name', 'HYLS')); ?>" required>
                </div>

                <div class="form-group">
                    <label>Site Keywords</label>
                    <input type="text" name="site_keywords" value="<?php echo htmlspecialchars(get_setting('site_keywords')); ?>" placeholder="link shortener, bio links, url">
                </div>

                <div class="form-group">
                    <label>Theme Color (Hex Code)</label>
                    <input type="text" name="theme_color" value="<?php echo htmlspecialchars(get_setting('theme_color', '#208091')); ?>" placeholder="#208091">
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>

        <!-- Google Analytics -->
        <div id="analytics" class="tab-content <?php echo $current_tab == 'analytics' ? 'active' : ''; ?>">
            <h2 class="section-title">Google Analytics Configuration</h2>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Setup Instructions:</strong> Get your Tracking ID from Google Analytics. For API access, create a Service Account in Google Cloud Console and download the JSON key.
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="save_google_analytics">
                
                <div class="checkbox-group">
                    <input type="checkbox" id="ga_enabled" name="ga_enabled" value="1" <?php echo get_setting('ga_enabled') ? 'checked' : ''; ?>>
                    <label for="ga_enabled"><strong>Enable Google Analytics</strong></label>
                </div>

                <div class="form-group">
                    <label>Google Analytics Tracking ID</label>
                    <input type="text" name="ga_tracking_id" value="<?php echo htmlspecialchars(get_setting('ga_tracking_id')); ?>" placeholder="G-XXXXXXXXXX">
                    <div class="hint-text">Example: G-1A2B3C4D5E</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Google Analytics View ID (optional)</label>
                        <input type="text" name="ga_view_id" value="<?php echo htmlspecialchars(get_setting('ga_view_id')); ?>" placeholder="123456789">
                        <div class="hint-text">For advanced analytics reporting</div>
                    </div>
                    <div class="form-group">
                        <label>API Key (optional)</label>
                        <input type="password" name="ga_api_key" value="<?php echo htmlspecialchars(get_setting('ga_api_key')); ?>" placeholder="Your API key">
                        <div class="hint-text">For server-side analytics requests</div>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Analytics</button>
            </form>
        </div>

        <!-- Google OAuth -->
        <div id="google_oauth" class="tab-content <?php echo $current_tab == 'google_oauth' ? 'active' : ''; ?>">
            <h2 class="section-title">Google OAuth - Login & Signup</h2>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Setup Instructions:</strong> Go to Google Cloud Console → Create Project → OAuth 2.0 Client ID (Web Application) → Set Authorized redirect URIs to your domain/google-auth.php
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="save_google_oauth">
                
                <div class="checkbox-group">
                    <input type="checkbox" id="google_oauth_enabled" name="google_oauth_enabled" value="1" <?php echo get_setting('google_oauth_enabled') ? 'checked' : ''; ?>>
                    <label for="google_oauth_enabled"><strong>Enable Google Login & Signup</strong></label>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Google Client ID</label>
                        <input type="text" name="google_client_id" value="<?php echo htmlspecialchars(get_setting('google_client_id')); ?>" placeholder="Your Client ID">
                        <div class="hint-text">From Google Cloud Console</div>
                    </div>
                    <div class="form-group">
                        <label>Google Client Secret</label>
                        <input type="password" name="google_client_secret" value="<?php echo htmlspecialchars(get_setting('google_client_secret')); ?>" placeholder="Your Client Secret">
                        <div class="hint-text">From Google Cloud Console</div>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Google OAuth</button>
            </form>
        </div>

        <!-- Ads Networks -->
        <div id="ads" class="tab-content <?php echo $current_tab == 'ads' ? 'active' : ''; ?>">
            <h2 class="section-title">Advertisement Networks</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_ads">
                
                <div class="section">
                    <h3 style="font-size: 16px; margin-bottom: 15px; color: var(--text);"><i class="fas fa-cog"></i> Main Settings</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="ads_enabled" name="ads_enabled" value="1" <?php echo get_setting('ads_enabled', '1') ? 'checked' : ''; ?>>
                        <label for="ads_enabled"><strong>Enable All Ads</strong></label>
                    </div>

                    <div class="form-group">
                        <label>Ad Display Duration (seconds)</label>
                        <input type="number" name="ads_duration" value="<?php echo htmlspecialchars(get_setting('ads_duration', '5')); ?>" min="1" max="60">
                        <div class="hint-text">Time to show ads before redirect</div>
                    </div>
                </div>

                <!-- Google AdSense -->
                <div class="section">
                    <h3 style="font-size: 16px; margin-bottom: 15px; color: var(--text);"><i class="fab fa-google"></i> Google AdSense</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="google_ads_enabled" name="google_ads_enabled" value="1" <?php echo get_setting('google_ads_enabled') ? 'checked' : ''; ?>>
                        <label for="google_ads_enabled"><strong>Enable Google AdSense</strong></label>
                    </div>

                    <div class="form-group">
                        <label>Google AdSense Code</label>
                        <textarea name="google_ads_code" class="code-input" placeholder="Paste your AdSense script here..."><?php echo htmlspecialchars(get_setting('google_ads_code')); ?></textarea>
                        <div class="hint-text">Paste the entire AdSense script tag</div>
                    </div>
                </div>

                <!-- Juicy Ads -->
                <div class="section">
                    <h3 style="font-size: 16px; margin-bottom: 15px; color: var(--text);"><i class="fas fa-ad"></i> Juicy Ads</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="juicy_ads_enabled" name="juicy_ads_enabled" value="1" <?php echo get_setting('juicy_ads_enabled') ? 'checked' : ''; ?>>
                        <label for="juicy_ads_enabled"><strong>Enable Juicy Ads</strong></label>
                    </div>

                    <div class="form-group">
                        <label>Juicy Ads Code</label>
                        <textarea name="juicy_ads_code" class="code-input" placeholder="Paste your Juicy Ads script here..."><?php echo htmlspecialchars(get_setting('juicy_ads_code')); ?></textarea>
                        <div class="hint-text">Paste the entire Juicy Ads script tag</div>
                    </div>
                </div>

                <!-- Custom Ads -->
                <div class="section">
                    <h3 style="font-size: 16px; margin-bottom: 15px; color: var(--text);"><i class="fas fa-code"></i> Custom Ad Networks</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="custom_ads_enabled" name="custom_ads_enabled" value="1" <?php echo get_setting('custom_ads_enabled') ? 'checked' : ''; ?>>
                        <label for="custom_ads_enabled"><strong>Enable Custom Ads</strong></label>
                    </div>

                    <div class="form-group">
                        <label>Custom Ads Code</label>
                        <textarea name="custom_ads_code" class="code-input" placeholder="Paste your custom ads script here..."><?php echo htmlspecialchars(get_setting('custom_ads_code')); ?></textarea>
                        <div class="hint-text">For any other ad networks (PropellerAds, ExoClick, etc.)</div>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Ad Networks</button>
            </form>
        </div>

        <!-- Link Scanning -->
        <div id="scanning" class="tab-content <?php echo $current_tab == 'scanning' ? 'active' : ''; ?>">
            <h2 class="section-title">Link Security Scanning</h2>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Setup Instructions:</strong> Get your VirusTotal API key from <a href="https://www.virustotal.com/gui/home/upload" style="color: #0c4a6e; text-decoration: underline;" target="_blank">VirusTotal.com</a>. This will scan all short links for malware before allowing creation.
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="save_link_scanning">
                
                <div class="checkbox-group">
                    <input type="checkbox" id="link_scanning_enabled" name="link_scanning_enabled" value="1" <?php echo get_setting('link_scanning_enabled') ? 'checked' : ''; ?>>
                    <label for="link_scanning_enabled"><strong>Enable Link Scanning</strong></label>
                </div>

                <div class="form-group">
                    <label>VirusTotal API Key</label>
                    <input type="password" name="virustotal_api_key" value="<?php echo htmlspecialchars(get_setting('virustotal_api_key')); ?>" placeholder="Your VirusTotal API key">
                    <div class="hint-text">Get your free API key from virustotal.com</div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="block_malicious" name="block_malicious" value="1" <?php echo get_setting('block_malicious') ? 'checked' : ''; ?>>
                    <label for="block_malicious"><strong>Block Malicious Links</strong> - Prevent users from creating links to malicious URLs</label>
                </div>

                <div class="info-box" style="background: #fef3c7; border-left-color: var(--warning); color: #92400e;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Note:</strong> Free VirusTotal API has rate limits (4 requests/minute). Consider upgrading for higher limits.
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Link Scanning</button>
            </form>
        </div>

        <!-- Announcements -->
        <div id="announce" class="tab-content <?php echo $current_tab == 'announce' ? 'active' : ''; ?>">
            <h2 class="section-title">Site Announcements</h2>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Announcements</strong> will be displayed to all users on the homepage and dashboard
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="save_announcement">
                
                <div class="checkbox-group">
                    <input type="checkbox" id="announcement_enabled" name="announcement_enabled" value="1" <?php echo get_setting('announcement_enabled') ? 'checked' : ''; ?>>
                    <label for="announcement_enabled"><strong>Enable Announcement</strong></label>
                </div>

                <div class="form-group">
                    <label>Announcement Type</label>
                    <select name="announcement_type">
                        <option value="info" <?php echo get_setting('announcement_type') == 'info' ? 'selected' : ''; ?>>ℹ️ Info (Blue)</option>
                        <option value="success" <?php echo get_setting('announcement_type') == 'success' ? 'selected' : ''; ?>>✅ Success (Green)</option>
                        <option value="warning" <?php echo get_setting('announcement_type') == 'warning' ? 'selected' : ''; ?>>⚠️ Warning (Yellow)</option>
                        <option value="danger" <?php echo get_setting('announcement_type') == 'danger' ? 'selected' : ''; ?>>❌ Danger (Red)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Announcement Message</label>
                    <textarea name="announcement_text" placeholder="Enter your announcement message..."><?php echo htmlspecialchars(get_setting('announcement_text')); ?></textarea>
                    <div class="hint-text">This message will be displayed to all users</div>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Announcement</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(e, tabName) {
            e.preventDefault();
            
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Deactivate all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
            
            // Update URL
            window.history.pushState({}, '', '?tab=' + tabName);
        }
    </script>
</body>
</html>
