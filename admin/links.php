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

// Handle link actions
if (isset($_GET['action']) && isset($_GET['link_id'])) {
    $link_id = (int)$_GET['link_id'];
    $action = $_GET['action'];
    
    try {
        switch ($action) {
            case 'ban':
            case 'block':
                $stmt = $db->prepare("UPDATE short_links SET is_banned = 1, banned_at = NOW() WHERE id = ?");
                $stmt->execute([$link_id]);
                $_SESSION['success'] = 'Link blocked successfully!';
                break;
                
            case 'unban':
            case 'unblock':
                $stmt = $db->prepare("UPDATE short_links SET is_banned = 0, ban_reason = NULL, banned_at = NULL WHERE id = ?");
                $stmt->execute([$link_id]);
                $_SESSION['success'] = 'Link unblocked successfully!';
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM short_links WHERE id = ?");
                $stmt->execute([$link_id]);
                $_SESSION['success'] = 'Link deleted permanently!';
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: links.php');
    exit;
}

// Get all links with better error handling
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where = [];
$params = [];

if ($search) {
    $where[] = "(l.short_code LIKE ? OR l.original_url LIKE ? OR l.title LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($filter === 'blocked') {
    $where[] = "l.is_banned = 1";
} elseif ($filter === 'active') {
    $where[] = "l.is_banned = 0";
} elseif ($filter === 'expired') {
    $where[] = "l.expires_at IS NOT NULL AND l.expires_at < NOW()";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Get total count
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM short_links l LEFT JOIN users u ON l.user_id = u.id $where_sql");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $total_pages = $total > 0 ? ceil($total / $per_page) : 1;

    // Get links
    $stmt = $db->prepare("
        SELECT l.*, u.username, u.email, u.id as owner_id
        FROM short_links l 
        LEFT JOIN users u ON l.user_id = u.id
        $where_sql
        ORDER BY l.created_at DESC 
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Get stats
    $stmt = $db->query("SELECT COUNT(*) as total FROM short_links");
    $total_links = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM short_links WHERE is_banned = 1");
    $blocked_links = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $db->query("SELECT SUM(clicks) as total FROM short_links");
    $total_clicks = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM short_links WHERE expires_at IS NOT NULL AND expires_at < NOW()");
    $expired_links = $stmt->fetch()['total'] ?? 0;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    $links = [];
    $total = 0;
    $total_pages = 1;
    $total_links = 0;
    $blocked_links = 0;
    $total_clicks = 0;
    $expired_links = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #0f172a; color: #e2e8f0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h1 { color: #22c55e; margin-bottom: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; background: #1e293b; border: 2px solid #334155; border-radius: 6px; color: #e2e8f0; cursor: pointer; text-decoration: none; transition: all 0.3s; }
        .filter-btn:hover { background: #334155; }
        .filter-btn.active { background: #3b82f6; border-color: #3b82f6; }
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 10px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #e2e8f0; width: 250px; }
        .search-box button { padding: 10px 20px; background: #3b82f6; border: none; border-radius: 6px; color: white; cursor: pointer; transition: all 0.3s; }
        .search-box button:hover { background: #2563eb; }
        .table-container { background: #1e293b; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #334155; padding: 15px; text-align: left; font-weight: 600; white-space: nowrap; }
        td { padding: 15px; border-top: 1px solid #334155; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-blocked { background: #ef4444; color: white; }
        .badge-active { background: #22c55e; color: white; }
        .badge-expired { background: #f97316; color: white; }
        .badge-password { background: #a855f7; color: white; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; margin: 2px; transition: all 0.3s; }
        .btn:hover { transform: translateY(-1px); }
        .btn-block { background: #ef4444; color: white; }
        .btn-block:hover { background: #dc2626; }
        .btn-unblock { background: #22c55e; color: white; }
        .btn-unblock:hover { background: #16a34a; }
        .btn-delete { background: #dc2626; color: white; }
        .btn-delete:hover { background: #b91c1c; }
        .btn-view { background: #3b82f6; color: white; }
        .btn-view:hover { background: #2563eb; }
        .success { background: #22c55e; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #ef4444; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .pagination { display: flex; gap: 5px; margin-top: 20px; justify-content: center; flex-wrap: wrap; }
        .pagination a { padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 4px; color: #e2e8f0; text-decoration: none; }
        .pagination a.active { background: #3b82f6; border-color: #3b82f6; }
        .pagination a:hover { background: #334155; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #1e293b; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; }
        .stat-card h3 { font-size: 14px; color: #94a3b8; margin-bottom: 10px; }
        .stat-card .value { font-size: 28px; font-weight: bold; color: #22c55e; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #3b82f6; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .url-text { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }
        .empty-state h3 { font-size: 20px; margin-bottom: 10px; }
        .empty-state p { font-size: 14px; }
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: stretch; }
            .search-box { flex-direction: column; }
            .search-box input { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
        
        <h1><i class="fas fa-link"></i> Link Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total Links</h3>
                <div class="value"><?= number_format($total_links) ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Clicks</h3>
                <div class="value"><?= number_format($total_clicks) ?></div>
            </div>
            <div class="stat-card">
                <h3>Blocked Links</h3>
                <div class="value" style="color: #ef4444;"><?= number_format($blocked_links) ?></div>
            </div>
            <div class="stat-card">
                <h3>Expired Links</h3>
                <div class="value" style="color: #f97316;"><?= number_format($expired_links) ?></div>
            </div>
        </div>
        
        <!-- Header -->
        <div class="header">
            <div class="filters">
                <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All Links (<?= $total_links ?>)</a>
                <a href="?filter=active" class="filter-btn <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
                <a href="?filter=blocked" class="filter-btn <?= $filter === 'blocked' ? 'active' : '' ?>">Blocked (<?= $blocked_links ?>)</a>
                <a href="?filter=expired" class="filter-btn <?= $filter === 'expired' ? 'active' : '' ?>">Expired (<?= $expired_links ?>)</a>
            </div>
            
            <form class="search-box" method="GET">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <input type="text" name="search" placeholder="Search links, users, URLs..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
        
        <!-- Links Table -->
        <div class="table-container">
            <?php if (empty($links)): ?>
                <div class="empty-state">
                    <i class="fas fa-link"></i>
                    <h3>No Links Found</h3>
                    <?php if ($search): ?>
                        <p>No links match your search "<?= htmlspecialchars($search) ?>". Try different keywords.</p>
                    <?php elseif ($filter !== 'all'): ?>
                        <p>No <?= htmlspecialchars($filter) ?> links at the moment.</p>
                    <?php else: ?>
                        <p>No links have been created yet. Links will appear here once users start creating them.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Short Code</th>
                        <th>Original URL</th>
                        <th>Owner</th>
                        <th>Clicks</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= SITE_URL ?>/<?= htmlspecialchars($link['short_code']) ?>" target="_blank" style="color: #3b82f6;">
                                    <?= htmlspecialchars($link['short_code']) ?>
                                </a></strong>
                                <?php if (!empty($link['password'])): ?>
                                    <br><span class="badge badge-password"><i class="fas fa-lock"></i> Protected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="url-text" title="<?= htmlspecialchars($link['original_url']) ?>">
                                    <?= htmlspecialchars($link['original_url']) ?>
                                </div>
                                <?php if (!empty($link['title'])): ?>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
                                        <?= htmlspecialchars($link['title']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($link['username'])): ?>
                                    <strong><?= htmlspecialchars($link['username']) ?></strong>
                                    <div style="font-size: 11px; color: #64748b;">
                                        <?= htmlspecialchars($link['email']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #64748b;"><i>Anonymous</i></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= number_format($link['clicks']) ?></strong></td>
                            <td>
                                <?php if ($link['is_banned']): ?>
                                    <span class="badge badge-blocked"><i class="fas fa-ban"></i> BLOCKED</span>
                                    <?php if (!empty($link['banned_at'])): ?>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                            <?= date('M j, Y', strtotime($link['banned_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()): ?>
                                    <span class="badge badge-expired"><i class="fas fa-clock"></i> EXPIRED</span>
                                <?php else: ?>
                                    <span class="badge badge-active"><i class="fas fa-check"></i> ACTIVE</span>
                                <?php endif; ?>
                                <?php if (!empty($link['expires_at']) && strtotime($link['expires_at']) >= time()): ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                        Expires: <?= date('M j, Y', strtotime($link['expires_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('M j, Y', strtotime($link['created_at'])) ?>
                                <div style="font-size: 11px; color: #64748b;">
                                    <?= date('g:i A', strtotime($link['created_at'])) ?>
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($link['is_banned']): ?>
                                    <a href="?action=unblock&link_id=<?= $link['id'] ?>" class="btn btn-unblock" onclick="return confirm('Unblock this link?')">
                                        <i class="fas fa-check"></i> Unblock
                                    </a>
                                <?php else: ?>
                                    <a href="?action=block&link_id=<?= $link['id'] ?>" class="btn btn-block" onclick="return confirm('Block this link? It will no longer be accessible.')">
                                        <i class="fas fa-ban"></i> Block
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?= SITE_URL ?>/<?= htmlspecialchars($link['short_code']) ?>" target="_blank" class="btn btn-view">
                                    <i class="fas fa-external-link-alt"></i> Visit
                                </a>
                                
                                <a href="?action=delete&link_id=<?= $link['id'] ?>" class="btn btn-delete" onclick="return confirm('⚠️ DELETE this link permanently?\n\nShort Code: <?= htmlspecialchars($link['short_code']) ?>\nURL: <?= htmlspecialchars(substr($link['original_url'], 0, 50)) ?>...\n\nThis action CANNOT be undone!')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>" 
                       class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search) ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>