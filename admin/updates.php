<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

try {
    $db = new Database();
    $user_id = $_SESSION['user_id'];

    // Check if user is admin
    if (!isAdmin($db, $user_id)) {
        die('Access denied. Admin only.');
    }

    // Get settings
    $settings = getSettings($db);
    
    // Current version from config
    $current_version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
    
    $action = $_GET['action'] ?? '';
    $message = '';
    $error = '';
    $update_available = false;
    $latest_version = $current_version;
    $update_log = [];
    
    // GitHub API endpoints
    $github_owner = 'david0154';
    $github_repo = 'hyls';
    $github_branch = 'main';
    
    // Function to get latest commit info from GitHub
    function getLatestGitHubCommit($owner, $repo, $branch) {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/commits/{$branch}";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: HYLS-Update-Checker\r\n",
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if (!$response) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    // Function to get all files from GitHub
    function getGitHubFiles($owner, $repo, $branch, $path = '') {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}?ref={$branch}";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: HYLS-Update-Checker\r\n",
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if (!$response) {
            return [];
        }
        
        return json_decode($response, true);
    }
    
    // Function to download file from GitHub
    function downloadFromGitHub($owner, $repo, $branch, $file_path) {
        $url = "https://raw.githubusercontent.com/{$owner}/{$repo}/{$branch}/{$file_path}";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10
            ]
        ]);
        
        return @file_get_contents($url, false, $context);
    }
    
    if ($action === 'check') {
        // Check for updates
        $commit = getLatestGitHubCommit($github_owner, $github_repo, $github_branch);
        
        if ($commit) {
            $latest_sha = substr($commit['sha'], 0, 7);
            $current_sha = file_get_contents('../.git-sha') ?? 'unknown';
            
            if ($latest_sha !== trim($current_sha)) {
                $update_available = true;
                $latest_version = date('Y-m-d H:i', strtotime($commit['commit']['committer']['date']));
                $message = "Update available! Latest commit: {$latest_sha}";
            } else {
                $message = "Your system is up to date!";
            }
        } else {
            $error = "Could not connect to GitHub. Please check your internet connection.";
        }
    }
    
    if ($action === 'install') {
        // Install updates
        $files_updated = 0;
        $files_failed = 0;
        $errors_log = [];
        
        // List of files to update (exclude sensitive files)
        $exclude_files = ['.gitignore', 'config.php', '.git', '.git-sha'];
        $exclude_dirs = ['.git', '.github', 'uploads', 'node_modules'];
        
        try {
            // Get repository tree
            $tree_url = "https://api.github.com/repos/{$github_owner}/{$github_repo}/git/trees/{$github_branch}?recursive=1";
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: HYLS-Update-Checker\r\n",
                    'timeout' => 15
                ]
            ]);
            
            $tree_response = @file_get_contents($tree_url, false, $context);
            if (!$tree_response) {
                throw new Exception('Failed to fetch repository tree from GitHub');
            }
            
            $tree_data = json_decode($tree_response, true);
            
            if (!isset($tree_data['tree']) || !is_array($tree_data['tree'])) {
                throw new Exception('Invalid GitHub response');
            }
            
            // Process each file
            foreach ($tree_data['tree'] as $item) {
                if ($item['type'] !== 'blob') continue;
                
                $file_path = $item['path'];
                
                // Skip excluded files
                $should_skip = false;
                foreach ($exclude_files as $exclude) {
                    if ($file_path === $exclude) {
                        $should_skip = true;
                        break;
                    }
                }
                
                // Skip excluded directories
                foreach ($exclude_dirs as $exclude) {
                    if (strpos($file_path, $exclude . '/') === 0) {
                        $should_skip = true;
                        break;
                    }
                }
                
                if ($should_skip) continue;
                
                // Download file
                $content = downloadFromGitHub($github_owner, $github_repo, $github_branch, $file_path);
                if ($content === false) {
                    $files_failed++;
                    $errors_log[] = "Failed to download: {$file_path}";
                    continue;
                }
                
                // Create directory if needed
                $local_path = '../' . $file_path;
                $dir = dirname($local_path);
                if (!is_dir($dir)) {
                    if (!@mkdir($dir, 0755, true)) {
                        $files_failed++;
                        $errors_log[] = "Failed to create directory: {$dir}";
                        continue;
                    }
                }
                
                // Write file
                if (@file_put_contents($local_path, $content) === false) {
                    $files_failed++;
                    $errors_log[] = "Failed to write: {$file_path}";
                    continue;
                }
                
                $files_updated++;
                $update_log[] = "✓ Updated: {$file_path}";
            }
            
            // Save current commit SHA
            $commit = getLatestGitHubCommit($github_owner, $github_repo, $github_branch);
            if ($commit) {
                @file_put_contents('../.git-sha', substr($commit['sha'], 0, 7));
            }
            
            if ($files_updated > 0) {
                $message = "✅ Update successful! {$files_updated} files updated.";
                
                // Log update
                logActivity($db, $user_id, 'system_update', "Updated {$files_updated} files, {$files_failed} failed");
            }
            
            if ($files_failed > 0) {
                $error = "⚠️ {$files_failed} files failed to update. Check logs.";
            }
            
        } catch (Exception $e) {
            $error = "Update failed: " . $e->getMessage();
            error_log("Update Error: " . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    error_log("Updates Page Error: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Updates - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .navbar {
            background: white;
            padding: 16px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #6366f1;
        }
        .nav-links {
            display: flex;
            gap: 24px;
        }
        .nav-links a {
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .nav-links a:hover,
        .nav-links a.active {
            color: #6366f1;
        }
        .main {
            padding: 40px 20px;
        }
        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .page-subtitle {
            color: #64748b;
            margin-bottom: 40px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-color: #ef4444;
        }
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-color: #3b82f6;
        }
        .version-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .version-box {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
        }
        .version-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .version-value {
            font-size: 24px;
            font-weight: 700;
            color: #6366f1;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-up-to-date {
            background: #d1fae5;
            color: #065f46;
        }
        .status-update-available {
            background: #fef3c7;
            color: #92400e;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
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
            background: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        .update-log {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .log-entry {
            padding: 4px 0;
        }
        .log-success {
            color: #10b981;
        }
        .log-error {
            color: #ef4444;
        }
        .button-group {
            display: flex;
            gap: 12px;
        }
        .warning {
            background: #fff7ed;
            border: 2px solid #fb923c;
            color: #92400e;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        @media (max-width: 768px) {
            .page-title {
                font-size: 24px;
            }
            .button-group {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="logo">🔗 HYLS Admin</div>
                <div class="nav-links">
                    <a href="index.php">Dashboard</a>
                    <a href="updates.php" class="active">Updates</a>
                    <a href="settings.php">Settings</a>
                    <a href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container main">
        <h1 class="page-title">🔄 System Updates</h1>
        <p class="page-subtitle">Check and install updates from GitHub</p>

        <?php if ($message): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <div>
                    <h2 style="font-size: 20px; margin-bottom: 8px;">Update Status</h2>
                    <div class="status-badge <?= $update_available ? 'status-update-available' : 'status-up-to-date' ?>">
                        <?= $update_available ? '⚠️ Update Available' : '✅ Up to Date' ?>
                    </div>
                </div>
            </div>

            <div class="version-info">
                <div class="version-box">
                    <div class="version-label">Current Version</div>
                    <div class="version-value"><?= htmlspecialchars($current_version) ?></div>
                </div>
                <div class="version-box">
                    <div class="version-label">Latest Version</div>
                    <div class="version-value"><?= htmlspecialchars($latest_version) ?></div>
                </div>
            </div>

            <div class="warning">
                <strong>⚠️ Important:</strong> 
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Backup your database before updating</li>
                    <li>The update will NOT overwrite config.php</li>
                    <li>The update will NOT remove uploaded files</li>
                    <li>The process may take a few minutes</li>
                </ul>
            </div>

            <div class="button-group" style="margin-top: 32px;">
                <a href="updates.php?action=check" class="btn btn-primary">🔍 Check for Updates</a>
                <?php if ($update_available): ?>
                <a href="updates.php?action=install" class="btn btn-primary" onclick="return confirm('Are you sure you want to install the update? This may take a few minutes.');">
                    📥 Install Update
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($update_log)): ?>
        <div class="card">
            <h2 style="font-size: 20px; margin-bottom: 16px;">📋 Update Log</h2>
            <div class="update-log">
                <?php foreach ($update_log as $log): ?>
                <div class="log-entry log-success"><?= htmlspecialchars($log) ?></div>
                <?php endforeach; ?>
                <?php foreach ($errors_log as $log): ?>
                <div class="log-entry log-error">❌ <?= htmlspecialchars($log) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 32px;">
            <h3 style="margin-bottom: 16px;">📚 Information</h3>
            <ul style="line-height: 1.8; color: #64748b;">
                <li><strong>Repository:</strong> <code>https://github.com/<?= htmlspecialchars($github_owner) ?>/<?= htmlspecialchars($github_repo) ?></code></li>
                <li><strong>Branch:</strong> <code><?= htmlspecialchars($github_branch) ?></code></li>
                <li><strong>Protected Files:</strong> config.php, uploads/</li>
                <li><strong>Last Check:</strong> Just now</li>
            </ul>
        </div>
    </div>
</body>
</html>