<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Helper function to save settings
function save_setting($db, $key, $value) {
    try {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Settings save error: " . $e->getMessage());
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'save_smtp') {
            $smtp_enabled = isset($_POST['smtp_enabled']) ? 1 : 0;
            $smtp_host = sanitize($_POST['smtp_host']);
            $smtp_port = (int)$_POST['smtp_port'];
            $smtp_encryption = sanitize($_POST['smtp_encryption']);
            $smtp_username = sanitize($_POST['smtp_username']);
            $smtp_password = $_POST['smtp_password'] ?? ''; // Don't sanitize password
            $smtp_from_email = sanitize($_POST['smtp_from_email']);
            $smtp_from_name = sanitize($_POST['smtp_from_name']);
            
            save_setting($db, 'smtp_enabled', $smtp_enabled);
            save_setting($db, 'smtp_host', $smtp_host);
            save_setting($db, 'smtp_port', $smtp_port);
            save_setting($db, 'smtp_encryption', $smtp_encryption);
            save_setting($db, 'smtp_username', $smtp_username);
            if (!empty($smtp_password)) {
                save_setting($db, 'smtp_password', base64_encode($smtp_password));
            }
            save_setting($db, 'smtp_from_email', $smtp_from_email);
            save_setting($db, 'smtp_from_name', $smtp_from_name);
            
            $message = '✅ SMTP settings saved successfully!';
        } elseif ($_POST['action'] == 'test_smtp') {
            $test_email = sanitize($_POST['test_email']);
            
            // Get SMTP settings
            $settings_result = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
            $smtp_settings = [];
            while ($row = $settings_result->fetch(PDO::FETCH_ASSOC)) {
                $smtp_settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // Test email sending
            require_once '../includes/mailer.php';
            $mailer = new Mailer($smtp_settings);
            
            if ($mailer->send($test_email, 'HYLS Test Email', 'This is a test email from your HYLS URL shortener. If you received this, your SMTP configuration is working correctly!')) {
                $message = '✅ Test email sent successfully to ' . htmlspecialchars($test_email) . '!';
            } else {
                $error = '❌ Failed to send test email. Please check your SMTP settings.';
            }
        }
    } catch (Exception $e) {
        $error = '❌ Error: ' . $e->getMessage();
        error_log($error);
    }
}

// Get settings
$settings_result = $db->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

function get_setting($key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Settings - HYLS Admin</title>
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
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
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
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(32, 128, 145, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 400;
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
        .hint-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #0c4a6e;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid var(--warning);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #92400e;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope"></i> SMTP Mail Settings</h1>
            <a href="settings.php"><i class="fas fa-arrow-left"></i> Back to Settings</a>
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
            <h2 class="section-title">SMTP Configuration</h2>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>About SMTP:</strong> Configure your email server settings to send emails for password resets, notifications, and user communications.
            </div>

            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Popular SMTP Providers:</strong><br>
                • Gmail: smtp.gmail.com (Port: 587, TLS)<br>
                • Outlook: smtp-mail.outlook.com (Port: 587, TLS)<br>
                • SendGrid: smtp.sendgrid.net (Port: 587, TLS)<br>
                • Mailgun: smtp.mailgun.org (Port: 587, TLS)<br>
                • Amazon SES: email-smtp.region.amazonaws.com (Port: 587, TLS)
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="save_smtp">
                
                <div class="checkbox-group">
                    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1" <?php echo get_setting('smtp_enabled') ? 'checked' : ''; ?>>
                    <label for="smtp_enabled"><strong>Enable SMTP Email</strong></label>
                </div>

                <div class="form-group">
                    <label>SMTP Host <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars(get_setting('smtp_host')); ?>" placeholder="smtp.gmail.com" required>
                    <div class="hint-text">Your SMTP server address</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP Port <span style="color: var(--danger);">*</span></label>
                        <input type="number" name="smtp_port" value="<?php echo htmlspecialchars(get_setting('smtp_port', '587')); ?>" placeholder="587" required>
                        <div class="hint-text">Common: 587 (TLS), 465 (SSL), 25 (None)</div>
                    </div>
                    <div class="form-group">
                        <label>Encryption <span style="color: var(--danger);">*</span></label>
                        <select name="smtp_encryption" required>
                            <option value="tls" <?php echo get_setting('smtp_encryption', 'tls') == 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                            <option value="ssl" <?php echo get_setting('smtp_encryption') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo get_setting('smtp_encryption') == 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                        <div class="hint-text">Security protocol</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP Username <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="smtp_username" value="<?php echo htmlspecialchars(get_setting('smtp_username')); ?>" placeholder="your-email@gmail.com" required>
                        <div class="hint-text">Usually your email address</div>
                    </div>
                    <div class="form-group">
                        <label>SMTP Password <span style="color: var(--danger);">*</span></label>
                        <input type="password" name="smtp_password" placeholder="••••••••" <?php echo empty(get_setting('smtp_password')) ? 'required' : ''; ?>>
                        <div class="hint-text">Leave blank to keep existing password</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>From Email <span style="color: var(--danger);">*</span></label>
                        <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars(get_setting('smtp_from_email')); ?>" placeholder="noreply@yourdomain.com" required>
                        <div class="hint-text">Email address that appears as sender</div>
                    </div>
                    <div class="form-group">
                        <label>From Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars(get_setting('smtp_from_name', 'HYLS')); ?>" placeholder="HYLS" required>
                        <div class="hint-text">Name that appears as sender</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save SMTP Settings
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="section-title">Test SMTP Connection</h2>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Test Email:</strong> Send a test email to verify your SMTP configuration is working correctly.
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="test_smtp">
                
                <div class="form-group">
                    <label>Test Email Address</label>
                    <input type="email" name="test_email" placeholder="test@example.com" required>
                    <div class="hint-text">Enter an email address to receive the test email</div>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Send Test Email
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="section-title">Gmail Setup Instructions</h2>
            
            <ol style="line-height: 2; color: var(--text);">
                <li><strong>Enable 2-Factor Authentication</strong> on your Gmail account</li>
                <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: var(--primary);">Google App Passwords</a></li>
                <li>Select "Mail" and "Other (Custom name)"</li>
                <li>Generate an App Password</li>
                <li>Use the generated password in the SMTP Password field above</li>
            </ol>

            <div class="info-box" style="margin-top: 20px;">
                <strong>Gmail Settings:</strong><br>
                • Host: smtp.gmail.com<br>
                • Port: 587<br>
                • Encryption: TLS<br>
                • Username: your-email@gmail.com<br>
                • Password: Your App Password (not your Gmail password)
            </div>
        </div>
    </div>
</body>
</html>