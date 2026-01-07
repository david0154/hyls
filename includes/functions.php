<?php
// ===========================
// FILE: includes/functions.php
// ===========================

/**
 * Get user by ID
 */
function getUserById($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all short links of a user
 */
function getUserLinks($db, $user_id) {
    $stmt = $db->prepare(
        "SELECT * FROM short_links 
         WHERE user_id = ? 
         ORDER BY created_at DESC"
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get bio link profile of a user
 */
function getUserBioLink($db, $user_id) {
    $stmt = $db->prepare("SELECT * FROM bio_links WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get site settings
 */
function getSettings($db) {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

/**
 * Generate random short code
 */
function generateShortCode($length = 6) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
}

/**
 * Check short code uniqueness
 */
function isCodeUnique($db, $code) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM short_links WHERE short_code = ?");
    $stmt->execute([$code]);
    return $stmt->fetchColumn() == 0;
}

/**
 * Create short link
 */
function createShortLink($db, $user_id, $url, $custom_code = null, $title = null) {

    if ($custom_code) {
        if (!isCodeUnique($db, $custom_code)) {
            return [
                'success' => false,
                'message' => 'Custom short code already exists'
            ];
        }
        $code = $custom_code;
    } else {
        do {
            $code = generateShortCode();
        } while (!isCodeUnique($db, $code));
    }

    $stmt = $db->prepare(
        "INSERT INTO short_links 
        (user_id, short_code, original_url, title) 
        VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$user_id, $code, $url, $title]);

    return [
        'success' => true,
        'code'    => $code,
        'url'     => SITE_URL . '/' . $code
    ];
}

/**
 * Total short links
 */
function getTotalLinks($db) {
    return (int)$db->query("SELECT COUNT(*) FROM short_links")->fetchColumn();
}

/**
 * Total clicks
 */
function getTotalClicks($db) {
    return (int)$db->query(
        "SELECT IFNULL(SUM(clicks),0) FROM short_links"
    )->fetchColumn();
}

/**
 * Total users
 */
function getTotalUsers($db) {
    return (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

/**
 * Check admin
 */
function isAdmin($db, $user_id) {
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Sanitize URL
 */
function sanitizeUrl($url) {
    $url = trim($url);

    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }

    return filter_var($url, FILTER_VALIDATE_URL) ?: false;
}

/**
 * Get client IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
