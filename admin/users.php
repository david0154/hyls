<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();

// Handle user actions
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $action = $_GET['action'];
    
    try {
        switch ($action) {
            case 'ban':
                $stmt = $db->prepare("UPDATE users SET is_banned = 1, banned_at = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'User banned successfully!';
                break;
                
            case 'unban':
                $stmt = $db->prepare("UPDATE users SET is_banned = 0, banned_at = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'User unbanned successfully!';
                break;
                
            case 'make_admin':
                $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'User promoted to admin!';
                break;
                
            case 'remove_admin':
                $stmt = $db->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'Admin privileges removed!';
                break;
                
            case 'delete':
                if (confirm('Are you sure you want to delete this user? This action cannot be undone!')) {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $_SESSION['success'] = 'User deleted permanently!';
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: users.php');
    exit;
}

// Get all users
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'banned') {
    $where[] = "is_banned = 1";
} elseif ($filter === 'admin') {
    $where[] = "is_admin = 1";
} elseif ($filter === 'active') {
    $where[] = "is_banned = 0";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT COUNT(*) as total FROM users $where_sql");
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $per_page);

$stmt = $db->prepare("
    SELECT id, username, email, is_admin, is_banned, email_verified, created_at, last_login, banned_at
    FROM users 
    $where_sql
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/mobile.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h1 { color: #22c55e; margin-bottom: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; background: #1e293b; border: 2px solid #334155; border-radius: 6px; color: #e2e8f0; cursor: pointer; text-decoration: none; }
        .filter-btn.active { background: #3b82f6; border-color: #3b82f6; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 10px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; width: 250px; }
        .search-box button { padding: 10px 20px; background: #3b82f6; border: none; border-radius: 6px; color: white; cursor: pointer; }
        .table-container { background: #1e293b; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #334155; padding: 15px; text-align: left; font-weight: 600; }
        td { padding: 15px; border-top: 1px solid #334155; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-admin { background: #fbbf24; color: #000; }
        .badge-banned { background: #ef4444; color: white; }
        .badge-active { background: #22c55e; color: white; }
        .badge-unverified { background: #64748b; color: white; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-ban { background: #ef4444; color: white; }
        .btn-unban { background: #22c55e; color: white; }
        .btn-admin { background: #fbbf24; color: #000; }
        .btn-delete { background: #dc2626; color: white; }
        .btn-view { background: #3b82f6; color: white; }
        .success { background: #22c55e; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #ef4444; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .pagination { display: flex; gap: 5px; margin-top: 20px; justify-content: center; }
        .pagination a { padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 4px; color: #e2e8f0; text-decoration: none; }
        .pagination a.active { background: #3b82f6; border-color: #3b82f6; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #1e293b; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; }
        .stat-card h3 { font-size: 14px; color: #94a3b8; margin-bottom: 10px; }
        .stat-card .value { font-size: 28px; font-weight: bold; color: #22c55e; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #3b82f6; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
        
        <h1><i class="fas fa-users"></i> User Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats">
            <?php
            $stmt = $db->query("SELECT COUNT(*) as total FROM users");
            $total_users = $stmt->fetch()['total'];
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_banned = 1");
            $banned_users = $stmt->fetch()['total'];
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 1");
            $admin_users = $stmt->fetch()['total'];
            
            $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $active_users = $stmt->fetch()['total'];
            ?>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?= $total_users ?></div>
            </div>
            <div class="stat-card">
                <h3>Active (7 days)</h3>
                <div class="value"><?= $active_users ?></div>
            </div>
            <div class="stat-card">
                <h3>Banned Users</h3>
                <div class="value" style="color: #ef4444;"><?= $banned_users ?></div>
            </div>
            <div class="stat-card">
                <h3>Administrators</h3>
                <div class="value" style="color: #fbbf24;"><?= $admin_users ?></div>
            </div>
        </div>
        
        <!-- Header -->
        <div class="header">
            <div class="filters">
                <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All Users</a>
                <a href="?filter=active" class="filter-btn <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
                <a href="?filter=banned" class="filter-btn <?= $filter === 'banned' ? 'active' : '' ?>">Banned</a>
                <a href="?filter=admin" class="filter-btn <?= $filter === 'admin' ? 'active' : '' ?>">Admins</a>
            </div>
            
            <form class="search-box" method="GET">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge badge-admin">ADMIN</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($user['email']) ?>
                                <?php if (!$user['email_verified']): ?>
                                    <span class="badge badge-unverified">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_banned']): ?>
                                    <span class="badge badge-banned">BANNED</span>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                        <?= date('M j, Y', strtotime($user['banned_at'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-active">ACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?= date('M j, Y H:i', strtotime($user['last_login'])) ?>
                                <?php else: ?>
                                    <span style="color: #64748b;">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): // Can't ban yourself ?>
                                    <?php if ($user['is_banned']): ?>
                                        <a href="?action=unban&user_id=<?= $user['id'] ?>" class="btn btn-unban" onclick="return confirm('Unban this user?')">
                                            <i class="fas fa-user-check"></i> Unban
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=ban&user_id=<?= $user['id'] ?>" class="btn btn-ban" onclick="return confirm('Ban this user?')">
                                            <i class="fas fa-ban"></i> Ban
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!$user['is_admin']): ?>
                                        <a href="?action=make_admin&user_id=<?= $user['id'] ?>" class="btn btn-admin" onclick="return confirm('Make this user an admin?')">
                                            <i class="fas fa-user-shield"></i> Make Admin
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=remove_admin&user_id=<?= $user['id'] ?>" class="btn btn-admin" onclick="return confirm('Remove admin privileges?')">
                                            <i class="fas fa-user-minus"></i> Remove Admin
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=delete&user_id=<?= $user['id'] ?>" class="btn btn-delete" onclick="return confirm('DELETE this user permanently? This CANNOT be undone!')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php else: ?>
                                    <span style="color: #64748b; font-size: 12px;">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                       class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>