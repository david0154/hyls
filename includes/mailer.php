<?php
/**
 * Simple PHP Mailer Class for SMTP Email Sending
 * Uses PHP's built-in mail() or SMTP with fsockopen
 */

class Mailer {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_encryption;
    private $smtp_from_email;
    private $smtp_from_name;
    private $smtp_enabled;
    
    public function __construct($settings = []) {
        $this->smtp_enabled = $settings['smtp_enabled'] ?? false;
        $this->smtp_host = $settings['smtp_host'] ?? '';
        $this->smtp_port = $settings['smtp_port'] ?? 587;
        $this->smtp_username = $settings['smtp_username'] ?? '';
        $this->smtp_password = isset($settings['smtp_password']) ? base64_decode($settings['smtp_password']) : '';
        $this->smtp_encryption = $settings['smtp_encryption'] ?? 'tls';
        $this->smtp_from_email = $settings['smtp_from_email'] ?? 'noreply@example.com';
        $this->smtp_from_name = $settings['smtp_from_name'] ?? 'HYLS';
    }
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email body (HTML supported)
     * @param array $headers Additional headers (optional)
     * @return bool Success status
     */
    public function send($to, $subject, $message, $headers = []) {
        if (!$this->smtp_enabled) {
            // Use PHP's built-in mail() function
            return $this->sendWithPHPMail($to, $subject, $message, $headers);
        } else {
            // Use SMTP
            return $this->sendWithSMTP($to, $subject, $message, $headers);
        }
    }
    
    /**
     * Send email using PHP's mail() function
     */
    private function sendWithPHPMail($to, $subject, $message, $additional_headers = []) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->smtp_from_name . ' <' . $this->smtp_from_email . '>',
            'Reply-To: ' . $this->smtp_from_email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Merge additional headers
        $headers = array_merge($headers, $additional_headers);
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Send email using SMTP
     */
    private function sendWithSMTP($to, $subject, $message, $additional_headers = []) {
        try {
            // Create socket connection
            $socket = $this->createConnection();
            
            if (!$socket) {
                error_log("SMTP: Failed to create connection");
                return false;
            }
            
            // Send EHLO/HELO
            $this->sendCommand($socket, "EHLO " . gethostname());
            
            // Start TLS if needed
            if ($this->smtp_encryption == 'tls' && $this->smtp_port != 465) {
                $this->sendCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand($socket, "EHLO " . gethostname());
            }
            
            // Authenticate
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->sendCommand($socket, base64_encode($this->smtp_username));
            $this->sendCommand($socket, base64_encode($this->smtp_password));
            
            // Send email
            $this->sendCommand($socket, "MAIL FROM: <" . $this->smtp_from_email . ">");
            $this->sendCommand($socket, "RCPT TO: <" . $to . ">");
            $this->sendCommand($socket, "DATA");
            
            // Prepare email content
            $email_content = $this->prepareEmailContent($to, $subject, $message, $additional_headers);
            fputs($socket, $email_content . "\r\n.\r\n");
            $response = fgets($socket);
            
            // Close connection
            $this->sendCommand($socket, "QUIT");
            fclose($socket);
            
            return strpos($response, '250') !== false;
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create SMTP connection
     */
    private function createConnection() {
        $errno = 0;
        $errstr = '';
        
        if ($this->smtp_encryption == 'ssl' || $this->smtp_port == 465) {
            $host = 'ssl://' . $this->smtp_host;
        } else {
            $host = $this->smtp_host;
        }
        
        $socket = @fsockopen($host, $this->smtp_port, $errno, $errstr, 30);
        
        if (!$socket) {
            error_log("SMTP Connection Error: $errstr ($errno)");
            return false;
        }
        
        // Set timeout
        stream_set_timeout($socket, 30);
        
        // Read welcome message
        $response = fgets($socket);
        
        if (strpos($response, '220') === false) {
            error_log("SMTP: Invalid welcome message: $response");
            fclose($socket);
            return false;
        }
        
        return $socket;
    }
    
    /**
     * Send SMTP command and get response
     */
    private function sendCommand($socket, $command) {
        fputs($socket, $command . "\r\n");
        $response = fgets($socket);
        
        // Log if error
        if (strpos($response, '2') !== 0 && strpos($response, '3') !== 0) {
            error_log("SMTP Command '$command' failed: $response");
        }
        
        return $response;
    }
    
    /**
     * Prepare email content with headers
     */
    private function prepareEmailContent($to, $subject, $message, $additional_headers = []) {
        $headers = [
            'From: ' . $this->smtp_from_name . ' <' . $this->smtp_from_email . '>',
            'To: ' . $to,
            'Subject: ' . $subject,
            'Date: ' . date('r'),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            'X-Mailer: HYLS Mailer'
        ];
        
        // Merge additional headers
        $headers = array_merge($headers, $additional_headers);
        
        $content = implode("\r\n", $headers) . "\r\n\r\n";
        $content .= quoted_printable_encode($message);
        
        return $content;
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($to, $username, $reset_link) {
        $subject = 'Password Reset Request - HYLS';
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #208091; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
                .button { display: inline-block; padding: 12px 30px; background: #208091; color: white !important; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Password Reset</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>' . htmlspecialchars($username) . '</strong>,</p>
                    <p>We received a request to reset your password. Click the button below to reset it:</p>
                    <p style="text-align: center;">
                        <a href="' . htmlspecialchars($reset_link) . '" class="button">Reset Password</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style="word-break: break-all; color: #208091;">' . htmlspecialchars($reset_link) . '</p>
                    <p style="color: #ef4444;"><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' HYLS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcome($to, $username) {
        $subject = 'Welcome to HYLS - Your Account is Ready!';
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .feature { padding: 15px; background: #f9fafb; border-radius: 6px; margin: 10px 0; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Welcome to HYLS!</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>' . htmlspecialchars($username) . '</strong>,</p>
                    <p>Thank you for joining HYLS! Your account has been successfully created.</p>
                    
                    <h3>What you can do with HYLS:</h3>
                    <div class="feature">• Create unlimited short links</div>
                    <div class="feature">• Build your personal bio link page</div>
                    <div class="feature">• Track link analytics and clicks</div>
                    <div class="feature">• Earn money from your links</div>
                    
                    <p style="margin-top: 30px;">Get started by creating your first short link or setting up your bio page!</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' HYLS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $this->send($to, $subject, $message);
    }
}
