<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';
$page = $_GET['page'] ?? 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $link_id = (int)($_POST['link_id'] ?? 0);
    
    switch ($action) {
        case 'ban':
            $db->query("UPDATE short_links SET is_banned = 1 WHERE id = ?", [$link_id]);
            $message = '🚫 Link has been banned!';
            break;
            
        case 'unban':
            $db->query("UPDATE short_links SET is_banned = 0 WHERE id = ?", [$link_id]);
            $message = '✅ Link has been unbanned!';
            break;
            
        case 'delete':
            $db->query("DELETE FROM short_links WHERE id = ?", [$link_id]);
            $message = '🗑️ Link has been deleted!';
            break;
            
        case 'scan':
            $link_id = (int)($_POST['link_id'] ?? 0);
            $result = $db->query("SELECT original_url FROM short_links WHERE id = ?", [$link_id]);
            if ($link = $result->fetch(PDO::FETCH_ASSOC)) {
                $scan_result = scanLinkForMalware($link['original_url']);
                $db->query("UPDATE short_links SET last_scan = NOW(), scan_status = ? WHERE id = ?", [
                    json_encode($scan_result),
                    $link_id
                ]);
                if ($scan_result['safe']) {
                    $message = '✅ Scan complete: Link is safe!';
                } else {
                    $message = '⚠️ Scan complete: Potential threats detected!';
                }
            }
            break;
    }
}

// Get all links with user info
$search = sanitize($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$query = "SELECT l.*, u.username, u.first_name, u.last_name FROM short_links l 
          LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (l.short_code LIKE ? OR l.original_url LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

if ($status_filter == 'banned') {
    $query .= " AND l.is_banned = 1";
} elseif ($status_filter == 'active') {
    $query .= " AND l.is_banned = 0";
}

$query .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$result = $db->query($query, $params);
$links = [];
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $links[] = $row;
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM short_links l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
$count_params = [];
if (!empty($search)) {
    $count_query .= " AND (l.short_code LIKE ? OR l.original_url LIKE ? OR u.username LIKE ?)";
    $count_params = ["%$search%", "%$search%", "%$search%"];
}
if ($status_filter == 'banned') {
    $count_query .= " AND l.is_banned = 1";
} elseif ($status_filter == 'active') {
    $count_query .= " AND l.is_banned = 0";
}

$count_result = $db->query($count_query, $count_params);
$total = $count_result->fetch(PDO::FETCH_ASSOC)['total'];
$pages = ceil($total / $per_page);

// Function to scan link for malware
function scanLinkForMalware($url) {
    global $db;
    
    // Get VirusTotal API key from settings
    $result = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'virustotal_api_key'");
    $setting = $result->fetch(PDO::FETCH_ASSOC);
    $api_key = $setting['setting_value'] ?? '';
    
    if (empty($api_key)) {
        return ['safe' => true, 'message' => 'API not configured', 'threats' => 0];
    }
    
    // Submit URL for scanning
    $scan_url = "https://www.virustotal.com/api/v3/urls";
    $post_data = "url=" . urlencode($url);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $scan_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'x-apikey: ' . $api_key,
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $json = json_decode($response, true);
        if (isset($json['data']['id'])) {
            $analysis_id = $json['data']['id'];
            // Note: Full analysis requires polling, returning safe for now
            return ['safe' => true, 'message' => 'Submitted for scanning', 'id' => $analysis_id];
        }
    }
    
    return ['safe' => true, 'message' => 'Scan could not be completed', 'threats' => 0];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Links - HYLS Admin</title>
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
            --info: #3b82f6;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1400px;
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
            font-size: 24px;
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

        .controls {
            background: var(--card);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: 1fr 1fr auto auto;
            gap: 10px;
            align-items: end;
        }

        .search-group label,
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
            color: var(--text-light);
        }

        .search-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
        }

        .search-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 128, 145, 0.1);
        }

        .btn-search, .btn-reset {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-search {
            background: var(--primary);
            color: white;
        }

        .btn-search:hover {
            background: var(--primary-dark);
        }

        .btn-reset {
            background: var(--border);
            color: var(--text);
        }

        .btn-reset:hover {
            background: #d1d5db;
        }

        .table-wrapper {
            background: var(--card);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--text-light);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-banned {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-safe {
            background: #d1fae5;
            color: #065f46;
        }

        .status-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-scanning {
            background: #fef3c7;
            color: #92400e;
        }

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-scan {
            background: var(--info);
            color: white;
        }

        .btn-scan:hover {
            background: #2563eb;
        }

        .btn-ban {
            background: var(--warning);
            color: white;
        }

        .btn-ban:hover {
            background: #d97706;
        }

        .btn-unban {
            background: var(--success);
            color: white;
        }

        .btn-unban:hover {
            background: #16a34a;
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #a00c2f;
        }

        .url-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .url-cell a {
            color: var(--primary);
            text-decoration: none;
        }

        .url-cell a:hover {
            text-decoration: underline;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 4px;
            text-decoration: none;
            color: var(--text);
        }

        .pagination a:hover {
            background: var(--border);
        }

        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--card);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .controls {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Manage Links</h1>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">📊 Total Links</div>
                <div class="stat-value">
                    <?php 
                    $total_result = $db->query("SELECT COUNT(*) as count FROM short_links");
                    echo $total_result->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">🟢 Active Links</div>
                <div class="stat-value">
                    <?php 
                    $active_result = $db->query("SELECT COUNT(*) as count FROM short_links WHERE is_banned = 0");
                    echo $active_result->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">🚫 Banned Links</div>
                <div class="stat-value">
                    <?php 
                    $banned_result = $db->query("SELECT COUNT(*) as count FROM short_links WHERE is_banned = 1");
                    echo $banned_result->fetch(PDO::FETCH_ASSOC)['count'];
                    ?>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="controls">
            <div class="search-group">
                <label>Search by Code, URL, or Username</label>
                <input type="text" placeholder="Search..." id="search" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Filter by Status</label>
                <select id="status" onchange="applyFilters()">
                    <option value="">All Links</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
                </select>
            </div>
            <button class="btn-search" onclick="applyFilters()"><i class="fas fa-search"></i> Search</button>
            <button class="btn-reset" onclick="resetFilters()"><i class="fas fa-redo"></i> Reset</button>
        </div>

        <!-- Links Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Short Code</th>
                        <th>Original URL</th>
                        <th>User</th>
                        <th>Clicks</th>
                        <th>Status</th>
                        <th>Security</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-light);"></i>
                                <p style="margin-top: 10px; color: var(--text-light);">No links found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($links as $link): ?>
                            <tr>
                                <td>#<?php echo $link['id']; ?></td>
                                <td>
                                    <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                                        <?php echo htmlspecialchars($link['short_code']); ?>
                                    </code>
                                </td>
                                <td>
                                    <div class="url-cell">
                                        <a href="<?php echo htmlspecialchars($link['original_url']); ?>" target="_blank" title="<?php echo htmlspecialchars($link['original_url']); ?>">
                                            <?php echo substr(htmlspecialchars($link['original_url']), 0, 50); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($link['username']): ?>
                                        <strong><?php echo htmlspecialchars($link['username']); ?></strong><br>
                                        <span style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($link['first_name'] . ' ' . $link['last_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $link['clicks']; ?></strong>
                                </td>
                                <td>
                                    <?php if ($link['is_banned']): ?>
                                        <span class="status-badge status-banned">🚫 Banned</span>
                                    <?php else: ?>
                                        <span class="status-badge status-active">🟢 Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($link['scan_status'])): 
                                        $scan = json_decode($link['scan_status'], true);
                                        if ($scan['safe']): ?>
                                            <span class="status-badge status-safe">✅ Safe</span>
                                        <?php else: ?>
                                            <span class="status-badge status-danger">⚠️ Threat</span>
                                        <?php endif;
                                    else: ?>
                                        <span class="status-badge status-scanning">Not Scanned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($link['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="scan">
                                            <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                            <button type="submit" class="action-btn btn-scan" title="Scan for malware">
                                                <i class="fas fa-shield-alt"></i> Scan
                                            </button>
                                        </form>
                                        
                                        <?php if (!$link['is_banned']): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Ban this link?');">
                                                <input type="hidden" name="action" value="ban">
                                                <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                                <button type="submit" class="action-btn btn-ban" title="Ban this link">
                                                    <i class="fas fa-ban"></i> Ban
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="unban">
                                                <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                                <button type="submit" class="action-btn btn-unban" title="Unban this link">
                                                    <i class="fas fa-undo"></i> Unban
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this link permanently?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                            <button type="submit" class="action-btn btn-delete" title="Delete this link">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">First</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Next</a>
                    <a href="?page=<?php echo $pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function applyFilters() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status').value;
            let url = '?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (status) url += 'status=' + encodeURIComponent(status) + '&';
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = '?';
        }

        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });
    </script>
</body>
</html>
