<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

try {
    $db = new Database();
    $user_id = $_SESSION['user_id'];

    // Get user data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: index.php');
        exit;
    }

    // Get user's short links
    $stmt = $db->prepare("SELECT * FROM short_links WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $short_links = $stmt->fetchAll();

    // Get user's bio link
    $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $bio_link = $stmt->fetch();

    // Get settings
    $settings = getSettings($db);
    
    // Calculate total clicks
    $total_clicks = 0;
    foreach ($short_links as $link) {
        $total_clicks += $link['clicks'];
    }
    
    // Get messages
    $success_message = $_SESSION['success'] ?? '';
    $error_message = $_SESSION['error'] ?? '';
    unset($_SESSION['success']);
    unset($_SESSION['error']);
} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HYLS</title>
    <?php if (!empty($settings['ga_tracking_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $settings['ga_tracking_id'] ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= $settings['ga_tracking_id'] ?>');
    </script>
    <?php endif; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .navbar {
            background: white;
            padding: 16px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #6366f1;
            text-decoration: none;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #6366f1;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .user-name {
            font-weight: 600;
            color: #334155;
        }
        .btn-logout {
            padding: 8px 16px;
            background: #ef4444;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background: #dc2626;
        }
        .btn-admin {
            padding: 8px 16px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-admin:hover {
            background: #4f46e5;
        }
        .main-content {
            padding: 40px 20px;
        }
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 600;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border: 2px solid #ef4444;
        }
        .alert-close {
            float: right;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
            line-height: 1;
        }
        .alert-close:hover {
            opacity: 0.7;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 40px;
        }
        .welcome-section h1 {
            font-size: 36px;
            margin-bottom: 12px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #64748b;
            font-size: 14px;
        }
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .btn-secondary {
            background: white;
            color: #6366f1;
            border: 2px solid #6366f1;
        }
        .btn-secondary:hover {
            background: #6366f1;
            color: white;
        }
        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1e293b;
        }
        .links-grid {
            display: grid;
            gap: 16px;
        }
        .link-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .link-info {
            flex: 1;
            min-width: 200px;
        }
        .link-short {
            font-weight: 700;
            color: #6366f1;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .link-original {
            color: #64748b;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .link-stats {
            display: flex;
            gap: 24px;
            align-items: center;
        }
        .link-stat-item {
            text-align: center;
        }
        .link-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #6366f1;
        }
        .link-stat-label {
            font-size: 12px;
            color: #64748b;
        }
        .link-actions {
            display: flex;
            gap: 8px;
        }
        .btn-small {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn-copy {
            background: #10b981;
            color: white;
        }
        .btn-copy:hover {
            background: #059669;
        }
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        .btn-delete:hover {
            background: #dc2626;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
            background: white;
            border-radius: 12px;
        }
        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
        }
        .modal-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #334155;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        @media (max-width: 768px) {
            .welcome-section h1 {
                font-size: 24px;
            }
            .link-card {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="dashboard.php" class="logo">🔗 HYLS</a>
                <div class="nav-user">
                    <div class="user-info">
                        <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                        <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
                    </div>
                    <?php if ($user['is_admin']): ?>
                    <a href="admin/" class="btn-admin">👑 Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <span class="alert-close" onclick="this.parentElement.style.display='none';">×</span>
            ✅ <?= $success_message ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <span class="alert-close" onclick="this.parentElement.style.display='none';">×</span>
            ❌ <?= $error_message ?>
        </div>
        <?php endif; ?>

        <div class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($user['first_name'] ?? $user['username']) ?>! 👋</h1>
            <p>Manage your links and bio page from your dashboard</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🔗</div>
                <div class="stat-value"><?= count($short_links) ?></div>
                <div class="stat-label">Total Links</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?= number_format($total_clicks) ?></div>
                <div class="stat-label">Total Clicks</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-value"><?= $bio_link ? 'Active' : 'Not Set' ?></div>
                <div class="stat-label">Bio Link Status</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value">$<?= number_format($user['earnings'] ?? 0, 2) ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="openModal('createLinkModal')" class="btn btn-primary">➕ Create Short Link</button>
            <a href="biolink.php" class="btn btn-secondary">✏️ Edit Bio Link</a>
            <?php if ($bio_link): ?>
            <a href="<?= SITE_URL ?>/bio/<?= htmlspecialchars($user['username']) ?>" target="_blank" class="btn btn-secondary">👁️ View Bio Page</a>
            <?php endif; ?>
        </div>

        <h2 class="section-title">Your Links</h2>
        
        <?php if (empty($short_links)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔗</div>
            <h3>No links yet</h3>
            <p>Create your first short link to get started!</p>
        </div>
        <?php else: ?>
        <div class="links-grid">
            <?php foreach ($short_links as $link): ?>
            <div class="link-card">
                <div class="link-info">
                    <div class="link-short"><?= SITE_URL ?>/<?= htmlspecialchars($link['short_code']) ?></div>
                    <div class="link-original" title="<?= htmlspecialchars($link['original_url']) ?>">
                        <?= htmlspecialchars($link['original_url']) ?>
                    </div>
                </div>
                <div class="link-stats">
                    <div class="link-stat-item">
                        <div class="link-stat-value"><?= number_format($link['clicks']) ?></div>
                        <div class="link-stat-label">Clicks</div>
                    </div>
                    <div class="link-stat-item">
                        <div class="link-stat-value">$<?= number_format($link['earnings'] ?? 0, 3) ?></div>
                        <div class="link-stat-label">Earned</div>
                    </div>
                </div>
                <div class="link-actions">
                    <button onclick="copyLink('<?= SITE_URL ?>/<?= htmlspecialchars($link['short_code']) ?>')" class="btn-small btn-copy">Copy</button>
                    <a href="delete_link.php?id=<?= $link['id'] ?>" onclick="return confirm('Delete this link?')" class="btn-small btn-delete">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div id="createLinkModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Create Short Link</h2>
            <form action="shorten.php" method="POST">
                <div class="form-group">
                    <label>Original URL <span style="color: #ef4444;">*</span></label>
                    <input type="url" name="url" placeholder="https://example.com/your-long-url" required>
                </div>
                <div class="form-group">
                    <label>Custom Code (Optional)</label>
                    <input type="text" name="custom_code" placeholder="my-link" pattern="[a-zA-Z0-9\-_]{2,20}" title="2-20 characters: letters, numbers, dash, underscore">
                </div>
                <div class="form-group">
                    <label>Title (Optional)</label>
                    <input type="text" name="title" placeholder="Link title">
                </div>
                <div class="modal-buttons">
                    <button type="button" onclick="closeModal('createLinkModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Link</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        function copyLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('✅ Link copied to clipboard!');
            }).catch(() => {
                prompt('Copy this link:', url);
            });
            return false;
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
        
        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>