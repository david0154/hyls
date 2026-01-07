<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';
$update_log = [];

// Get current Git info
function getCurrentGitInfo() {
    $output = [];
    $branch = trim(shell_exec('cd .. && git rev-parse --abbrev-ref HEAD 2>&1'));
    $commit = trim(shell_exec('cd .. && git rev-parse --short HEAD 2>&1'));
    $commit_date = trim(shell_exec('cd .. && git log -1 --format=%cd --date=relative 2>&1'));
    $commit_message = trim(shell_exec('cd .. && git log -1 --format=%s 2>&1'));
    
    return [
        'branch' => $branch ?: 'unknown',
        'commit' => $commit ?: 'unknown',
        'date' => $commit_date ?: 'unknown',
        'message' => $commit_message ?: 'unknown'
    ];
}

// Check for updates
function checkForUpdates() {
    exec('cd .. && git fetch origin 2>&1', $output, $return_var);
    $behind = trim(shell_exec('cd .. && git rev-list HEAD..origin/main --count 2>&1'));
    return (int)$behind;
}

// Perform update
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    try {
        // Create backup directory
        $backup_dir = '../backups/backup_' . date('Y-m-d_His');
        if (!file_exists('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        $update_log[] = '📦 Creating backup...';
        exec('cp -r ../ ' . $backup_dir . ' 2>&1', $backup_output, $backup_return);
        
        if ($backup_return === 0) {
            $update_log[] = '✅ Backup created: ' . basename($backup_dir);
        } else {
            throw new Exception('Failed to create backup');
        }
        
        // Pull latest changes
        $update_log[] = '🔄 Pulling latest changes from GitHub...';
        exec('cd .. && git pull origin main 2>&1', $pull_output, $pull_return);
        
        foreach ($pull_output as $line) {
            $update_log[] = $line;
        }
        
        if ($pull_return === 0) {
            $update_log[] = '✅ Update completed successfully!';
            $message = '✅ Website updated successfully from GitHub!';
        } else {
            throw new Exception('Git pull failed');
        }
        
    } catch (Exception $e) {
        $error = '❌ Update failed: ' . $e->getMessage();
        $update_log[] = '❌ Error: ' . $e->getMessage();
    }
}

// Restore from backup
if (isset($_POST['action']) && $_POST['action'] == 'restore' && !empty($_POST['backup'])) {
    try {
        $backup = basename($_POST['backup']);
        $backup_path = '../backups/' . $backup;
        
        if (!file_exists($backup_path)) {
            throw new Exception('Backup not found');
        }
        
        $update_log[] = '🔙 Restoring from backup: ' . $backup;
        exec('cp -r ' . $backup_path . '/* ../ 2>&1', $restore_output, $restore_return);
        
        if ($restore_return === 0) {
            $update_log[] = '✅ Restore completed!';
            $message = '✅ Website restored from backup!';
        } else {
            throw new Exception('Restore failed');
        }
        
    } catch (Exception $e) {
        $error = '❌ Restore failed: ' . $e->getMessage();
    }
}

$git_info = getCurrentGitInfo();
$updates_available = checkForUpdates();

// Get available backups
$backups = [];
if (file_exists('../backups')) {
    $backups = array_diff(scandir('../backups'), ['.', '..']);
    rsort($backups);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Auto-Update - HYLS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #208091;
            --primary-dark: #1a6a78;
            --success: #22c55e;
            --danger: #ef4444;
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
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header {
            background: var(--card);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 28px; color: var(--text); }
        .header a {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
        .alert-warning {
            background: #fef3c7;
            border-left: 4px solid var(--warning);
            color: #92400e;
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
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }
        .info-label {
            font-size: 12px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            word-break: break-word;
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
        .btn-success {
            background: var(--success);
            color: white;
        }
        .btn-success:hover { background: #16a34a; }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover { background: #dc2626; }
        .btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
        }
        .log-container {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .log-line {
            padding: 5px 0;
            border-bottom: 1px solid #334155;
        }
        .update-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .badge-uptodate {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-updates {
            background: #fef3c7;
            color: #92400e;
        }
        .backup-list {
            list-style: none;
            padding: 0;
        }
        .backup-item {
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fab fa-github"></i> GitHub Auto-Update</h1>
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">Current Version</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Branch</div>
                    <div class="info-value"><i class="fas fa-code-branch"></i> <?php echo htmlspecialchars($git_info['branch']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Commit</div>
                    <div class="info-value"><i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($git_info['commit']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Update</div>
                    <div class="info-value"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($git_info['date']); ?></div>
                </div>
            </div>

            <div style="padding: 15px; background: #f9fafb; border-radius: 6px; margin-bottom: 20px;">
                <div class="info-label">Last Commit Message</div>
                <div class="info-value"><?php echo htmlspecialchars($git_info['message']); ?></div>
            </div>

            <div style="margin-bottom: 20px;">
                <?php if ($updates_available > 0): ?>
                    <span class="update-badge badge-updates">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $updates_available; ?> update(s) available
                    </span>
                <?php else: ?>
                    <span class="update-badge badge-uptodate">
                        <i class="fas fa-check-circle"></i> Up to date
                    </span>
                <?php endif; ?>
            </div>

            <div class="alert-warning" style="margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> Always backup before updating. Updates will overwrite your files.
            </div>

            <form method="POST" onsubmit="return confirm('Are you sure you want to update? This will pull the latest code from GitHub.')">
                <input type="hidden" name="action" value="update">
                <button type="submit" class="btn btn-success" <?php echo $updates_available === 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-download"></i> Update from GitHub
                </button>
            </form>
        </div>

        <?php if (!empty($update_log)): ?>
        <div class="card">
            <h2 class="section-title">Update Log</h2>
            <div class="log-container">
                <?php foreach ($update_log as $line): ?>
                    <div class="log-line"><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">Backups</h2>
            <?php if (empty($backups)): ?>
                <p style="color: var(--text-light); text-align: center; padding: 20px;">No backups available</p>
            <?php else: ?>
                <ul class="backup-list">
                    <?php foreach ($backups as $backup): ?>
                        <li class="backup-item">
                            <span>
                                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($backup); ?>
                            </span>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to restore from this backup?')">
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="backup" value="<?php echo htmlspecialchars($backup); ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 13px;">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>