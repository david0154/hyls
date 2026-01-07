<?php
// admin/index.php - Admin dashboard
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

try {
    $db = new Database();
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_admin']) {
        header('Location: ../dashboard.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    die("Database connection error. Please check your configuration.");
}

$success = '';
$error = '';
$page = $_GET['page'] ?? 'overview';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        try {
            $settings_to_update = [
                'site_name', 'site_description', 'site_keywords', 'theme_color',
                'ads_enabled', 'ads_duration', 'earning_per_click', 'min_payout',
                'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from'
            ];
            
            foreach ($settings_to_update as $key) {
                $value = $_POST[$key] ?? '';
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            }
            
            $success = 'Settings updated successfully!';
        } catch (Exception $e) {
            $error = 'Failed to update settings: ' . $e->getMessage();
            error_log("Settings Update Error: " . $e->getMessage());
        }
    }
    
    elseif ($action === 'add_ad') {
        try {
            $title = $_POST['ad_title'] ?? '';
            $description = $_POST['ad_description'] ?? '';
            $url = $_POST['ad_url'] ?? '';
            $cta_text = $_POST['ad_cta'] ?? 'Visit Now';
            $is_active = isset($_POST['ad_active']) ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO advertisements (title, description, url, cta_text, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $url, $cta_text, $is_active]);
            
            $success = 'Advertisement added successfully!';
        } catch (Exception $e) {
            $error = 'Failed to add advertisement: ' . $e->getMessage();
            error_log("Add Advertisement Error: " . $e->getMessage());
        }
    }
    
    elseif ($action === 'delete_ad') {
        try {
            $ad_id = $_POST['ad_id'] ?? 0;
            $stmt = $db->prepare("DELETE FROM advertisements WHERE id = ?");
            $stmt->execute([$ad_id]);
            $success = 'Advertisement deleted successfully!';
        } catch (Exception $e) {
            $error = 'Failed to delete advertisement: ' . $e->getMessage();
            error_log("Delete Advertisement Error: " . $e->getMessage());
        }
    }
    
    elseif ($action === 'toggle_ad') {
        try {
            $ad_id = $_POST['ad_id'] ?? 0;
            $stmt = $db->prepare("UPDATE advertisements SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$ad_id]);
            $success = 'Advertisement status updated!';
        } catch (Exception $e) {
            $error = 'Failed to update advertisement: ' . $e->getMessage();
            error_log("Toggle Advertisement Error: " . $e->getMessage());
        }
    }
}

// Get statistics with error handling
try {
    $stats = [
        'total_users' => 0,
        'total_links' => 0,
        'total_clicks' => 0,
        'total_bio_links' => 0,
        'total_earnings' => 0
    ];
    
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    if ($result) $stats['total_users'] = $result->fetch()['count'];
    
    $result = $db->query("SELECT COUNT(*) as count FROM short_links");
    if ($result) $stats['total_links'] = $result->fetch()['count'];
    
    $result = $db->query("SELECT SUM(clicks) as count FROM short_links");
    if ($result) $stats['total_clicks'] = $result->fetch()['count'] ?? 0;
    
    $result = $db->query("SELECT COUNT(*) as count FROM bio_links");
    if ($result) $stats['total_bio_links'] = $result->fetch()['count'];
    
    $result = $db->query("SELECT SUM(earnings) as total FROM users");
    if ($result) $stats['total_earnings'] = $result->fetch()['total'] ?? 0;
} catch (Exception $e) {
    error_log("Statistics Error: " . $e->getMessage());
    $stats = [
        'total_users' => 0,
        'total_links' => 0,
        'total_clicks' => 0,
        'total_bio_links' => 0,
        'total_earnings' => 0
    ];
}

// Get settings with error handling
try {
    $settings = getSettings($db);
} catch (Exception $e) {
    error_log("getSettings Error: " . $e->getMessage());
    $settings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>/assets/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 {
            color: #6366f1;
            font-size: 24px;
        }
        .navbar nav a {
            color: #64748b;
            text-decoration: none;
            margin-left: 24px;
            font-weight: 600;
        }
        .navbar nav a:hover {
            color: #6366f1;
        }
        .admin-container {
            display: flex;
            min-height: calc(100vh - 64px);
        }
        .sidebar {
            width: 250px;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 24px;
        }
        .sidebar a {
            display: block;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background: #f1f5f9;
            color: #6366f1;
        }
        .sidebar .section-title {
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            margin-top: 20px;
            margin-bottom: 8px;
            padding: 0 16px;
        }
        .sidebar .section-title:first-child {
            margin-top: 0;
        }
        .main-content {
            flex: 1;
            padding: 40px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .stat-card .value {
            color: #1e293b;
            font-size: 32px;
            font-weight: 700;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .card h2 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 24px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-danger {
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-secondary {
            padding: 8px 16px;
            background: #64748b;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }
        .ad-item {
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ad-info h4 {
            color: #1e293b;
            margin-bottom: 8px;
        }
        .ad-info p {
            color: #64748b;
            font-size: 14px;
        }
        .ad-actions {
            display: flex;
            gap: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            border-bottom: 2px solid #e2e8f0;
        }
        tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            font-weight: 600;
            color: #334155;
        }
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👑 Admin Dashboard</h1>
        <nav>
            <a href="../dashboard.php">User View</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>

    <div class="admin-container">
        <div class="sidebar">
            <div class="section-title">Dashboard</div>
            <a href="?page=overview" class="<?= $page === 'overview' ? 'active' : '' ?>">📊 Overview</a>
            <a href="updates.php" class="<?= $page === 'updates' ? 'active' : '' ?>">🔄 Updates</a>
            
            <div class="section-title">Management</div>
            <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>">⚙️ Settings</a>
            <a href="?page=ads" class="<?= $page === 'ads' ? 'active' : '' ?>">📢 Advertisements</a>
            
            <div class="section-title">Data</div>
            <a href="?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">👥 Users</a>
            <a href="?page=links" class="<?= $page === 'links' ? 'active' : '' ?>">🔗 Links</a>
        </div>

        <div class="main-content">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($page === 'overview'): ?>
                <h1 style="margin-bottom: 32px;">📊 Overview</h1>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <div class="value"><?= number_format($stats['total_users']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Links</h3>
                        <div class="value"><?= number_format($stats['total_links']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Clicks</h3>
                        <div class="value"><?= number_format($stats['total_clicks']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Bio Links</h3>
                        <div class="value"><?= number_format($stats['total_bio_links']) ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Earnings</h3>
                        <div class="value">$<?= number_format($stats['total_earnings'], 2) ?></div>
                    </div>
                </div>

            <?php elseif ($page === 'settings'): ?>
                <div class="card">
                    <h2>⚙️ System Settings</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <h3 style="margin-bottom: 16px; color: #6366f1;">Site Information</h3>
                        
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Site Description</label>
                            <textarea name="site_description"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Site Keywords</label>
                            <input type="text" name="site_keywords" value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>">
                        </div>
                        
                        <h3 style="margin: 32px 0 16px; color: #6366f1;">Ads & Earnings</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ads Enabled</label>
                                <select name="ads_enabled">
                                    <option value="1" <?= ($settings['ads_enabled'] ?? 1) == 1 ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= ($settings['ads_enabled'] ?? 1) == 0 ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Ads Duration (seconds)</label>
                                <input type="number" name="ads_duration" value="<?= htmlspecialchars($settings['ads_duration'] ?? 5) ?>" min="3" max="30">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Earning Per Click ($)</label>
                                <input type="number" name="earning_per_click" value="<?= htmlspecialchars($settings['earning_per_click'] ?? 0.001) ?>" step="0.001" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Minimum Payout ($)</label>
                                <input type="number" name="min_payout" value="<?= htmlspecialchars($settings['min_payout'] ?? 10) ?>" step="0.01" min="1">
                            </div>
                        </div>
                        
                        <h3 style="margin: 32px 0 16px; color: #6366f1;">SMTP Configuration</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>SMTP Host</label>
                                <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>SMTP Port</label>
                                <input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? 587) ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="smtp_pass" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>From Email</label>
                            <input type="email" name="smtp_from" value="<?= htmlspecialchars($settings['smtp_from'] ?? '') ?>">
                        </div>
                        
                        <button type="submit" class="btn-primary">💾 Save Settings</button>
                    </form>
                </div>

            <?php elseif ($page === 'ads'): ?>
                <div class="card">
                    <h2>📢 Add New Advertisement</h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_ad">
                        
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="ad_title" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="ad_description" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>URL</label>
                                <input type="url" name="ad_url" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Call-to-Action Text</label>
                                <input type="text" name="ad_cta" value="Visit Now">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="ad_active" checked>
                                Active
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-primary">➕ Add Advertisement</button>
                    </form>
                </div>
                
                <div class="card">
                    <h2>📋 Existing Advertisements</h2>
                    
                    <?php
                    try {
                        $stmt = $db->query("SELECT * FROM advertisements ORDER BY position ASC, created_at DESC");
                        $ads = $stmt->fetchAll();
                        
                        if (empty($ads)):
                        ?>
                            <p style="color: #64748b;">No advertisements yet.</p>
                        <?php else: ?>
                            <?php foreach ($ads as $ad): ?>
                            <div class="ad-item">
                                <div class="ad-info">
                                    <h4><?= htmlspecialchars($ad['title']) ?></h4>
                                    <p><?= htmlspecialchars($ad['url']) ?></p>
                                    <p style="margin-top: 4px;">
                                        <strong>Status:</strong> 
                                        <?= $ad['is_active'] ? '<span style="color: #16a34a;">Active</span>' : '<span style="color: #dc2626;">Inactive</span>' ?>
                                    </p>
                                </div>
                                <div class="ad-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_ad">
                                        <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                        <button type="submit" class="btn-secondary">
                                            <?= $ad['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this ad?');">
                                        <input type="hidden" name="action" value="delete_ad">
                                        <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                        <button type="submit" class="btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php } catch (Exception $e) {
                        error_log("Advertisements Query Error: " . $e->getMessage());
                        echo '<p style="color: #dc2626;">Error loading advertisements.</p>';
                    } ?>
                </div>

            <?php elseif ($page === 'users'): ?>
                <div class="card">
                    <h2>👥 All Users</h2>
                    
                    <?php
                    try {
                        $stmt = $db->query("SELECT u.*, COUNT(DISTINCT sl.id) as link_count, SUM(sl.clicks) as total_clicks 
                                            FROM users u 
                                            LEFT JOIN short_links sl ON u.id = sl.user_id 
                                            GROUP BY u.id 
                                            ORDER BY u.created_at DESC");
                        $users = $stmt->fetchAll();
                    ?>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th style="text-align: right;">Links</th>
                                <th style="text-align: right;">Clicks</th>
                                <th style="text-align: right;">Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td style="text-align: right;"><?= number_format($u['link_count']) ?></td>
                                <td style="text-align: right;"><?= number_format($u['total_clicks'] ?? 0) ?></td>
                                <td style="text-align: right;">$<?= number_format($u['earnings'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php } catch (Exception $e) {
                        error_log("Users Query Error: " . $e->getMessage());
                        echo '<p style="color: #dc2626;">Error loading users data.</p>';
                    } ?>
                </div>

            <?php elseif ($page === 'links'): ?>
                <div class="card">
                    <h2>🔗 Recent Links</h2>
                    
                    <?php
                    try {
                        $stmt = $db->query("SELECT sl.*, u.username 
                                            FROM short_links sl 
                                            JOIN users u ON sl.user_id = u.id 
                                            ORDER BY sl.created_at DESC 
                                            LIMIT 50");
                        $links = $stmt->fetchAll();
                    ?>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Short Code</th>
                                <th>User</th>
                                <th>Original URL</th>
                                <th style="text-align: right;">Clicks</th>
                                <th style="text-align: right;">Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($links as $link): ?>
                            <tr>
                                <td>
                                    <a href="<?= SITE_URL ?>/<?= htmlspecialchars($link['short_code']) ?>" target="_blank" style="color: #6366f1;">
                                        <?= htmlspecialchars($link['short_code']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($link['username']) ?></td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($link['original_url']) ?>
                                </td>
                                <td style="text-align: right;"><?= number_format($link['clicks']) ?></td>
                                <td style="text-align: right;">$<?= number_format($link['earnings'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php } catch (Exception $e) {
                        error_log("Links Query Error: " . $e->getMessage());
                        echo '<p style="color: #dc2626;">Error loading links data.</p>';
                    } ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>