<?php
/**
 * Mailer class for sending emails via SMTP
 * Uses PHPMailer library
 */

class Mailer {
    private $settings;
    private $mail;
    
    public function __construct($settings = []) {
        $this->settings = $settings;
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $message, $altBody = '') {
        // Check if PHPMailer is installed
        if (!file_exists(__DIR__ . '/../vendor/autoload.php') && !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Try to use built-in PHP mail as fallback
            return $this->sendWithPhpMail($to, $subject, $message);
        }
        
        try {
            // Load PHPMailer
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->settings['smtp_host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings['smtp_username'] ?? '';
            $mail->Password = $this->getPassword();
            
            // Encryption
            $encryption = strtolower($this->settings['smtp_encryption'] ?? 'tls');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = (int)($this->settings['smtp_port'] ?? 587);
            
            // Disable SSL certificate verification if needed (for testing)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Recipients
            $fromEmail = $this->settings['smtp_from_email'] ?? $this->settings['smtp_username'] ?? 'noreply@hyls.space';
            $fromName = $this->settings['smtp_from_name'] ?? 'HYLS';
            
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = $altBody ?: strip_tags($message);
            
            $mail->send();
            return true;
            
        } catch (\Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            
            // Try fallback to PHP mail
            return $this->sendWithPhpMail($to, $subject, $message);
        }
    }
    
    /**
     * Fallback: Send with PHP's built-in mail function
     */
    private function sendWithPhpMail($to, $subject, $message) {
        try {
            $fromEmail = $this->settings['smtp_from_email'] ?? 'noreply@hyls.space';
            $fromName = $this->settings['smtp_from_name'] ?? 'HYLS';
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "Reply-To: {$fromEmail}\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            return mail($to, $subject, $message, $headers);
            
        } catch (\Exception $e) {
            error_log("PHP Mail Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get decoded password
     */
    private function getPassword() {
        $password = $this->settings['smtp_password'] ?? '';
        
        // Check if password is base64 encoded
        if (!empty($password) && base64_encode(base64_decode($password, true)) === $password) {
            return base64_decode($password);
        }
        
        return $password;
    }
    
    /**
     * Test SMTP connection
     */
    public function testConnection() {
        try {
            if (!file_exists(__DIR__ . '/../vendor/autoload.php') && !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return [
                    'success' => false,
                    'message' => 'PHPMailer not installed. Please install via Composer: composer require phpmailer/phpmailer'
                ];
            }
            
            // Try to connect
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $this->settings['smtp_host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings['smtp_username'] ?? '';
            $mail->Password = $this->getPassword();
            
            $encryption = strtolower($this->settings['smtp_encryption'] ?? 'tls');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = (int)($this->settings['smtp_port'] ?? 587);
            $mail->SMTPDebug = 0;
            
            // Disable SSL verification for testing
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Try to connect
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                return [
                    'success' => true,
                    'message' => 'SMTP connection successful!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to SMTP server'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
}
?>