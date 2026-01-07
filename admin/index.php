<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access - properly check if is_admin exists and is truthy
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();

// Get statistics
$total_users = $db->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC)['count'];
$total_links = $db->query("SELECT COUNT(*) as count FROM short_links")->fetch(PDO::FETCH_ASSOC)['count'];
$total_clicks = $db->query("SELECT SUM(clicks) as total FROM short_links")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$total_earnings = $db->query("SELECT SUM(earnings) as total FROM short_links")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$active_links = $db->query("SELECT COUNT(*) as count FROM short_links WHERE is_banned = 0")->fetch(PDO::FETCH_ASSOC)['count'];
$banned_links = $db->query("SELECT COUNT(*) as count FROM short_links WHERE is_banned = 1")->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent links
$recent_links = $db->query("SELECT l.*, u.username FROM short_links l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get recent users
$recent_users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HYLS</title>
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
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #c01537;
            --info: #3b82f6;
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
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .navbar {
            background: var(--card);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .navbar-brand i {
            font-size: 28px;
        }

        .navbar-menu {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .navbar-menu a {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .navbar-menu a:hover {
            background: var(--bg);
            color: var(--primary);
        }

        .navbar-menu a.user-dashboard {
            color: white;
            background: #10b981;
        }

        .navbar-menu a.user-dashboard:hover {
            background: #059669;
        }

        .navbar-menu a.logout {
            color: white;
            background: var(--danger);
        }

        .navbar-menu a.logout:hover {
            background: #a00c2f;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--text);
        }

        .header p {
            color: var(--text-light);
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 8px;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }

        .stat-card.primary .stat-icon {
            background: var(--primary);
        }

        .stat-card.success .stat-icon {
            background: var(--success);
        }

        .stat-card.warning .stat-icon {
            background: var(--warning);
        }

        .stat-card.danger .stat-icon {
            background: var(--danger);
        }

        .stat-card.info .stat-icon {
            background: var(--info);
        }

        .stat-label {
            display: block;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text);
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
        }

        .section-title i {
            color: var(--primary);
        }

        .grid-2col {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
        }

        .list-card {
            background: var(--card);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .list-card-header {
            background: #f9fafb;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-card-header h3 {
            font-size: 16px;
            margin: 0;
        }

        .list-card-header a {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .list-card-header a:hover {
            color: var(--primary-dark);
        }

        .list-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item-content {
            flex: 1;
            min-width: 0;
        }

        .list-item-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text);
        }

        .list-item-subtitle {
            font-size: 13px;
            color: var(--text-light);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .list-item-meta {
            font-size: 13px;
            color: var(--text-light);
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 15px;
            border-top: 1px solid var(--border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
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

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .grid-2col {
                grid-template-columns: 1fr;
            }

            .navbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .navbar-menu {
                width: 100%;
                justify-content: flex-start;
                flex-direction: column;
            }

            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navbar -->
        <div class="navbar">
            <div class="navbar-brand">
                <i class="fas fa-link"></i>
                <span>HYLS Admin</span>
            </div>
            <div class="navbar-menu">
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="links.php"><i class="fas fa-link"></i> Links</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="../dashboard.php" class="user-dashboard"><i class="fas fa-user"></i> My Dashboard</a>
                <a href="../logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Header -->
        <div class="header">
            <h1>📋 Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Here's your admin dashboard overview</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <span class="stat-label">Total Users</span>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-link"></i></div>
                <span class="stat-label">Total Links</span>
                <div class="stat-value"><?php echo number_format($total_links); ?></div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-mouse-pointer"></i></div>
                <span class="stat-label">Total Clicks</span>
                <div class="stat-value"><?php echo number_format($total_clicks); ?></div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <span class="stat-label">Total Earnings</span>
                <div class="stat-value">$<?php echo number_format($total_earnings, 2); ?></div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <span class="stat-label">Active Links</span>
                <div class="stat-value"><?php echo number_format($active_links); ?></div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon"><i class="fas fa-ban"></i></div>
                <span class="stat-label">Banned Links</span>
                <div class="stat-value"><?php echo number_format($banned_links); ?></div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Recent Activity
            </h2>

            <div class="grid-2col">
                <!-- Recent Links -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3><i class="fas fa-link" style="margin-right: 8px;"></i>Recent Links</h3>
                        <a href="links.php">View All →</a>
                    </div>
                    <?php if (empty($recent_links)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No links yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_links as $link): ?>
                            <div class="list-item">
                                <div class="list-item-content">
                                    <div class="list-item-title">
                                        <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 3px;"><?php echo htmlspecialchars($link['short_code']); ?></code>
                                    </div>
                                    <div class="list-item-subtitle"><?php echo substr(htmlspecialchars($link['original_url']), 0, 40); ?>...</div>
                                </div>
                                <div class="list-item-meta">
                                    <?php echo htmlspecialchars($link['username'] ?? 'Guest'); ?><br>
                                    <small><?php echo timeago($link['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="action-buttons">
                        <a href="links.php" class="btn btn-primary"><i class="fas fa-list"></i> Manage Links</a>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="list-card">
                    <div class="list-card-header">
                        <h3><i class="fas fa-user-plus" style="margin-right: 8px;"></i>Recent Users</h3>
                        <a href="users.php">View All →</a>
                    </div>
                    <?php if (empty($recent_users)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No users yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_users as $user): ?>
                            <div class="list-item">
                                <div class="list-item-content">
                                    <div class="list-item-title"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="list-item-subtitle"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <div class="list-item-meta">
                                    <?php echo $user['is_admin'] ? '<span class="badge badge-danger">Admin</span>' : '<span class="badge badge-success">User</span>'; ?><br>
                                    <small><?php echo timeago($user['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="action-buttons">
                        <a href="users.php" class="btn btn-primary"><i class="fas fa-users"></i> Manage Users</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h2>
            <div class="list-card">
                <div class="action-buttons" style="justify-content: flex-start; flex-wrap: wrap; gap: 15px; padding: 20px;">
                    <a href="settings.php?tab=ads" class="btn btn-primary">
                        <i class="fas fa-ad"></i> Ad Networks (Time-based Ads)
                    </a>
                    <a href="promotion.php" class="btn btn-primary">
                        <i class="fas fa-bullhorn"></i> Promotion Settings
                    </a>
                    <a href="smtp-settings.php" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> SMTP Mail Settings
                    </a>
                    <a href="github-update.php" class="btn btn-primary">
                        <i class="fab fa-github"></i> GitHub Auto-Update
                    </a>
                    <a href="settings.php?tab=analytics" class="btn btn-secondary">
                        <i class="fas fa-chart-line"></i> Google Analytics
                    </a>
                    <a href="settings.php?tab=google_oauth" class="btn btn-secondary">
                        <i class="fab fa-google"></i> Google OAuth
                    </a>
                    <a href="settings.php?tab=scanning" class="btn btn-secondary">
                        <i class="fas fa-shield-alt"></i> Link Scanning
                    </a>
                    <a href="settings.php?tab=announce" class="btn btn-secondary">
                        <i class="fas fa-bullhorn"></i> Announcements
                    </a>
                    <a href="settings.php" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> All Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>