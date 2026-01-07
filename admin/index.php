<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];

if (!isAdmin($db, $user_id)) {
    header('Location: ../dashboard.php');
    exit;
}

$total_users = getTotalUsers($db);
$total_links = getTotalLinks($db);
$total_clicks = getTotalClicks($db);

$stmt = $db->query("SELECT COUNT(*) FROM bio_links");
$total_bios = $stmt->fetchColumn();

$recent_users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$recent_links = $db->query("SELECT sl.*, u.username FROM short_links sl JOIN users u ON sl.user_id = u.id ORDER BY sl.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HYLS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .navbar {
            background: #1e293b;
            padding: 16px 0;
            color: white;
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
            color: white;
        }
        .nav-links {
            display: flex;
            gap: 24px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
        }
        .main-content {
            padding: 40px 20px;
        }
        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 40px;
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
            font-size: 36px;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #64748b;
            font-size: 14px;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-admin {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-user {
            background: #dbeafe;
            color: #1e40af;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <div class="logo">🔗 HYLS Admin</div>
                <div class="nav-links">
                    <a href="index.php">Dashboard</a>
                    <a href="users.php">Users</a>
                    <a href="links.php">Links</a>
                    <a href="settings.php">Settings</a>
                    <a href="../dashboard.php">← Back to Site</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <h1 class="page-title">Admin Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= number_format($total_users) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔗</div>
                <div class="stat-value"><?= number_format($total_links) ?></div>
                <div class="stat-label">Total Links</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?= number_format($total_clicks) ?></div>
                <div class="stat-label">Total Clicks</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-value"><?= number_format($total_bios) ?></div>
                <div class="stat-label">Bio Links</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Recent Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge <?= $user['is_admin'] ? 'badge-admin' : 'badge-user' ?>">
                                <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <a href="user_details.php?id=<?= $user['id'] ?>" class="btn btn-primary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Recent Links</h2>
            <table>
                <thead>
                    <tr>
                        <th>Short Code</th>
                        <th>Original URL</th>
                        <th>User</th>
                        <th>Clicks</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_links as $link): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($link['short_code']) ?></strong></td>
                        <td><?= substr(htmlspecialchars($link['original_url']), 0, 50) ?>...</td>
                        <td><?= htmlspecialchars($link['username']) ?></td>
                        <td><?= number_format($link['clicks']) ?></td>
                        <td><?= date('M d, Y', strtotime($link['created_at'])) ?></td>
                        <td>
                            <a href="../<?= htmlspecialchars($link['short_code']) ?>" target="_blank" class="btn btn-primary">Visit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
