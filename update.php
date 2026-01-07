<?php
/**
 * Simple Update Script
 * One-click update from GitHub using git pull
 */

// Security: Set your secret key here
define('UPDATE_SECRET', 'your_secret_key_here_change_this');

// Check if secret key is provided
if (!isset($_GET['key']) || $_GET['key'] !== UPDATE_SECRET) {
    http_response_code(403);
    die('❌ Access Denied. Invalid or missing secret key.');
}

// Set timeout
set_time_limit(300);
ini_set('max_execution_time', 300);

// HTML Header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HYLS Update</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #1e293b;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        h1 {
            color: #22c55e;
            font-size: 32px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status {
            background: #0f172a;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .log-container {
            background: #0f172a;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #334155;
        }
        .log-line {
            padding: 5px 0;
            border-bottom: 1px solid #1e293b;
        }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        .info { color: #3b82f6; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover { background: #2563eb; }
        .spinner {
            border: 3px solid #334155;
            border-top: 3px solid #22c55e;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .step {
            padding: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .step-icon.pending { background: #334155; }
        .step-icon.success { background: #22c55e; }
        .step-icon.error { background: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span style="font-size: 40px;">🔄</span>
            HYLS Auto-Update
        </h1>
        
        <?php
        $update_success = false;
        $update_log = [];
        
        function addLog($message, $type = 'info') {
            global $update_log;
            $icons = [
                'success' => '✅',
                'error' => '❌',
                'warning' => '⚠️',
                'info' => 'ℹ️'
            ];
            $icon = $icons[$type] ?? 'ℹ️';
            $update_log[] = "<span class='$type'>$icon $message</span>";
        }
        
        try {
            addLog('Starting update process...', 'info');
            addLog('Current time: ' . date('Y-m-d H:i:s'), 'info');
            
            // Check if git is available
            $git_check = shell_exec('which git 2>&1');
            if (empty($git_check)) {
                throw new Exception('Git is not installed or not in PATH');
            }
            addLog('Git found: ' . trim($git_check), 'success');
            
            // Get current directory
            $repo_dir = dirname(__FILE__);
            addLog('Repository directory: ' . $repo_dir, 'info');
            
            // Check if it's a git repository
            if (!file_exists($repo_dir . '/.git')) {
                throw new Exception('Not a git repository. Please clone from GitHub first.');
            }
            addLog('Git repository detected', 'success');
            
            // Get current branch
            $current_branch = trim(shell_exec('cd ' . escapeshellarg($repo_dir) . ' && git rev-parse --abbrev-ref HEAD 2>&1'));
            addLog('Current branch: ' . $current_branch, 'info');
            
            // Get current commit
            $current_commit = trim(shell_exec('cd ' . escapeshellarg($repo_dir) . ' && git rev-parse --short HEAD 2>&1'));
            addLog('Current commit: ' . $current_commit, 'info');
            
            // Fetch latest changes
            addLog('Fetching latest changes from GitHub...', 'info');
            exec('cd ' . escapeshellarg($repo_dir) . ' && git fetch origin 2>&1', $fetch_output, $fetch_return);
            
            foreach ($fetch_output as $line) {
                addLog($line, 'info');
            }
            
            if ($fetch_return !== 0) {
                throw new Exception('Failed to fetch from GitHub');
            }
            addLog('Fetch completed successfully', 'success');
            
            // Check if updates are available
            $updates_available = trim(shell_exec('cd ' . escapeshellarg($repo_dir) . ' && git rev-list HEAD..origin/' . escapeshellarg($current_branch) . ' --count 2>&1'));
            
            if ($updates_available == '0') {
                addLog('No updates available. Already up to date!', 'warning');
                $update_success = true;
            } else {
                addLog($updates_available . ' commit(s) available for update', 'info');
                
                // Stash any local changes
                addLog('Stashing local changes (if any)...', 'info');
                exec('cd ' . escapeshellarg($repo_dir) . ' && git stash 2>&1', $stash_output, $stash_return);
                foreach ($stash_output as $line) {
                    addLog($line, 'info');
                }
                
                // Pull latest changes
                addLog('Pulling latest changes...', 'info');
                exec('cd ' . escapeshellarg($repo_dir) . ' && git pull origin ' . escapeshellarg($current_branch) . ' 2>&1', $pull_output, $pull_return);
                
                foreach ($pull_output as $line) {
                    addLog($line, $pull_return === 0 ? 'success' : 'error');
                }
                
                if ($pull_return === 0) {
                    addLog('✅ Update completed successfully!', 'success');
                    $update_success = true;
                    
                    // Get new commit
                    $new_commit = trim(shell_exec('cd ' . escapeshellarg($repo_dir) . ' && git rev-parse --short HEAD 2>&1'));
                    addLog('New commit: ' . $new_commit, 'success');
                    
                    // Show what changed
                    addLog('Changes applied:', 'success');
                    $changes = shell_exec('cd ' . escapeshellarg($repo_dir) . ' && git log --oneline ' . $current_commit . '..' . $new_commit . ' 2>&1');
                    foreach (explode("\n", trim($changes)) as $change) {
                        if (!empty($change)) {
                            addLog('  • ' . $change, 'success');
                        }
                    }
                } else {
                    throw new Exception('Git pull failed');
                }
            }
            
        } catch (Exception $e) {
            addLog('❌ Update failed: ' . $e->getMessage(), 'error');
            $update_success = false;
        }
        
        // Display status
        if ($update_success) {
            echo '<div class="status" style="border-left-color: #22c55e;">';
            echo '<h2 class="success">✅ Update Successful!</h2>';
            echo '<p>Your HYLS installation has been updated to the latest version.</p>';
            echo '</div>';
        } else {
            echo '<div class="status" style="border-left-color: #ef4444;">';
            echo '<h2 class="error">❌ Update Failed</h2>';
            echo '<p>There was an error updating your installation. Check the log below.</p>';
            echo '</div>';
        }
        
        // Display log
        echo '<div class="log-container">';
        foreach ($update_log as $log_line) {
            echo '<div class="log-line">' . $log_line . '</div>';
        }
        echo '</div>';
        
        // Action buttons
        echo '<div style="margin-top: 30px;">';
        if ($update_success) {
            echo '<a href="/" class="btn" style="background: #22c55e;">🏠 Go to Homepage</a>';
            echo '<a href="/admin/" class="btn" style="background: #3b82f6; margin-left: 10px;">⚙️ Admin Panel</a>';
        } else {
            echo '<a href="?key=' . UPDATE_SECRET . '" class="btn" style="background: #f59e0b;">🔄 Try Again</a>';
        }
        echo '</div>';
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #0f172a; border-radius: 8px; font-size: 13px; color: #94a3b8;">
            <strong>💡 How to Use:</strong><br>
            1. Bookmark this URL: <code style="background: #1e293b; padding: 2px 6px; border-radius: 4px; color: #22c55e;"><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/update.php?key=' . UPDATE_SECRET; ?></code><br>
            2. Visit this URL whenever you want to update<br>
            3. Keep your secret key safe!<br>
            <br>
            <strong>⚠️ Security:</strong> Change the UPDATE_SECRET in update.php to your own secret key!
        </div>
    </div>
</body>
</html>