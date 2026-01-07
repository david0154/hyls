<?php
// includes/functions.php - Helper functions

/**
 * Get all settings from database
 */
function getSettings($db) {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
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
 * Check if short code is unique
 */
function isShortCodeUnique($db, $code) {
    $stmt = $db->prepare("SELECT id FROM short_links WHERE short_code = ?");
    $stmt->execute([$code]);
    return !$stmt->fetch();
}

/**
 * Create unique short code
 */
function createUniqueShortCode($db, $length = 6) {
    do {
        $code = generateShortCode($length);
    } while (!isShortCodeUnique($db, $code));
    return $code;
}

/**
 * Validate URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Send email using SMTP
 */
function sendEmail($to, $subject, $message, $settings = []) {
    if (empty($settings['smtp_host']) || empty($settings['smtp_user'])) {
        return false;
    }
    
    require_once 'phpmailer/PHPMailer.php';
    require_once 'phpmailer/SMTP.php';
    require_once 'phpmailer/Exception.php';
    
    $mail = new PHPMailerPHPMailerPHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_user'];
        $mail->Password = $settings['smtp_pass'];
        $mail->SMTPSecure = PHPMailerPHPMailerPHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $settings['smtp_port'] ?? 587;
        
        // Recipients
        $mail->setFrom($settings['smtp_from'], $settings['site_name'] ?? 'HYLS');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Format number with K, M, B suffixes
 */
function formatNumber($num) {
    if ($num >= 1000000000) {
        return round($num / 1000000000, 1) . 'B';
    } elseif ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}

/**
 * Sanitize username
 */
function sanitizeUsername($username) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
}

/**
 * Check if user is admin
 */
function isAdmin($db, $user_id) {
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user && $user['is_admin'];
}

/**
 * Get user earnings
 */
function getUserEarnings($db, $user_id) {
    $stmt = $db->prepare("SELECT earnings FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user ? floatval($user['earnings']) : 0;
}

/**
 * Log activity
 */
function logActivity($db, $user_id, $action, $details = '') {
    try {
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details]);
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
    }
}

/**
 * Get country from IP
 */
function getCountryFromIP($ip) {
    try {
        $response = @file_get_contents("http://ip-api.com/json/{$ip}");
        if ($response) {
            $data = json_decode($response, true);
            return $data['country'] ?? 'Unknown';
        }
    } catch (Exception $e) {
        error_log("IP Location Error: " . $e->getMessage());
    }
    return 'Unknown';
}

/**
 * Truncate text
 */
function truncate($text, $length = 50) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Generate OTP
 */
function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

/**
 * Clean old analytics data
 */
function cleanOldAnalytics($db, $days = 90) {
    try {
        $stmt = $db->prepare("DELETE FROM analytics WHERE clicked_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return true;
    } catch (Exception $e) {
        error_log("Clean Analytics Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get top links
 */
function getTopLinks($db, $user_id = null, $limit = 10) {
    if ($user_id) {
        $stmt = $db->prepare("SELECT * FROM short_links WHERE user_id = ? ORDER BY clicks DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
    } else {
        $stmt = $db->prepare("SELECT * FROM short_links ORDER BY clicks DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

/**
 * Export links to CSV
 */
function exportLinksToCSV($db, $user_id) {
    $stmt = $db->prepare("SELECT short_code, original_url, title, clicks, earnings, created_at FROM short_links WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="my-links-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Short Code', 'Original URL', 'Title', 'Clicks', 'Earnings', 'Created At']);
    
    foreach ($links as $link) {
        fputcsv($output, $link);
    }
    
    fclose($output);
    exit;
}

/**
 * Check rate limit
 */
function checkRateLimit($db, $user_id, $action, $max_requests = 10, $time_window = 60) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE user_id = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$user_id, $action, $time_window]);
    $result = $stmt->fetch();
    
    if ($result['count'] >= $max_requests) {
        return false;
    }
    
    $stmt = $db->prepare("INSERT INTO rate_limits (user_id, action, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $action]);
    
    return true;
}
?>
