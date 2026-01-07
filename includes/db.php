<?php
// ===========================
// FILE: includes/db.php
// ===========================

class Database {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
}

// ===========================
// FILE: includes/functions.php
// ===========================

function getUserById($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserLinks($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM short_links WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserBioLink($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getSettings($db) {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function generateShortCode($length = 6) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function isCodeUnique($db, $code) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM short_links WHERE short_code = ?");
    $stmt->execute([$code]);
    return $stmt->fetchColumn() == 0;
}

function createShortLink($db, $user_id, $url, $custom_code = null, $title = null) {
    if ($custom_code) {
        $code = $custom_code;
        if (!isCodeUnique($db, $code)) {
            return ['success' => false, 'message' => 'Custom code already in use'];
        }
    } else {
        do {
            $code = generateShortCode();
        } while (!isCodeUnique($db, $code));
    }
    
    $stmt = $db->prepare("INSERT INTO short_links (user_id, short_code, original_url, title) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $code, $url, $title]);
    
    return [
        'success' => true,
        'code' => $code,
        'url' => SITE_URL . '/' . $code
    ];
}

function getTotalLinks($db) {
    $stmt = $db->query("SELECT COUNT(*) FROM short_links");
    return $stmt->fetchColumn();
}

function getTotalClicks($db) {
    $stmt = $db->query("SELECT SUM(clicks) FROM short_links");
    return $stmt->fetchColumn() ?? 0;
}

function getTotalUsers($db) {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    return $stmt->fetchColumn();
}

function isAdmin($db, $user_id) {
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result && $result['is_admin'] == 1;
}

function sanitizeUrl($url) {
    $url = trim($url);
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
}

function getClientIp() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// ===========================
// FILE: shorten.php
// ===========================

<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];

$url = $_POST['url'] ?? '';
$custom_code = $_POST['custom_code'] ?? null;
$title = $_POST['title'] ?? null;

if (empty($url)) {
    $_SESSION['error'] = 'URL is required';
    header('Location: dashboard.php');
    exit;
}

$url = sanitizeUrl($url);
if (!$url) {
    $_SESSION['error'] = 'Invalid URL';
    header('Location: dashboard.php');
    exit;
}

if ($custom_code && !preg_match('/^[a-zA-Z0-9-_]+$/', $custom_code)) {
    $_SESSION['error'] = 'Custom code can only contain letters, numbers, hyphens and underscores';
    header('Location: dashboard.php');
    exit;
}

$result = createShortLink($db, $user_id, $url, $custom_code, $title);

if ($result['success']) {
    $_SESSION['success'] = 'Short link created: ' . $result['url'];
} else {
    $_SESSION['error'] = $result['message'];
}

header('Location: dashboard.php');
exit;
?>

// ===========================
// FILE: delete_link.php
// ===========================

<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$link_id = $_GET['id'];

$stmt = $db->prepare("DELETE FROM short_links WHERE id = ? AND user_id = ?");
$stmt->execute([$link_id, $user_id]);

$_SESSION['success'] = 'Link deleted successfully';
header('Location: dashboard.php');
exit;
?>

// ===========================
// FILE: logout.php
// ===========================

<?php
session_start();
session_destroy();
header('Location: index.php');
exit;
?>

// ===========================
// FILE: login.php
// ===========================

<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $db = new Database();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_picture'] = $user['profile_picture'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HYLS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #6366f1;
            font-size: 32px;
            margin-bottom: 8px;
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
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 16px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        .oauth-btn {
            width: 100%;
            padding: 14px;
            background: white;
            color: #6366f1;
            border: 2px solid #6366f1;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <h1>🔗 HYLS</h1>
            <p>Sign in to your account</p>
        </div>

        <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="divider">OR</div>

        <a href="https://hypechats.com/oauth?app_id=<?= APP_ID ?>" class="oauth-btn">
            💬 Sign in with HypeChats
        </a>
    </div>
</body>
</html>

// ===========================
// FILE: .htaccess
// ===========================

RewriteEngine On
RewriteBase /

# Redirect to install if config doesn't exist
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/install\.php
RewriteRule ^(.*)$ install.php [L]

# Bio link routing
RewriteRule ^bio/([a-zA-Z0-9_-]+)$ bio.php?u=$1 [L,QSA]

# Short link routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9_-]+)$ r.php?c=$1 [L,QSA]
