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
