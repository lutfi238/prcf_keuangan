<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prcf_keuangan');

// Email configuration - GANTI DENGAN EMAIL ANDA
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'pblprcf@gmail.com'); // Email untuk mengirim OTP
define('SMTP_PASS', 'vwkx trnf ordu sfuh'); // App Password Gmail
define('FROM_EMAIL', 'pblprcf@gmail.com');
define('FROM_NAME', 'PRCFI Financial');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Helper function to send OTP email - GMAIL SMTP VERSION
function send_otp_email($email, $otp) {
    try {
        $subject = "Kode OTP Login - PRCFI Financial";
        
        // HTML Email
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f3f4f6; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-box { background: #EFF6FF; border: 2px solid #3B82F6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-code { font-size: 36px; font-weight: bold; color: #3B82F6; letter-spacing: 10px; font-family: monospace; }
                .warning { background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 15px; margin: 15px 0; border-radius: 4px; }
                .footer { text-align: center; color: #6B7280; font-size: 12px; padding: 20px; background: #F9FAFB; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin: 0;">🔐 Kode OTP Anda</h1>
                    <p style="margin: 10px 0 0 0;">PRCFI Financial Management System</p>
                </div>
                <div class="content">
                    <h2 style="color: #1F2937;">Halo!</h2>
                    <p>Anda menerima email ini karena ada permintaan login ke sistem PRCFI Financial.</p>
                    
                    <div class="otp-box">
                        <p style="margin: 0 0 10px 0; font-size: 14px; color: #6B7280;">Kode OTP Anda:</p>
                        <div class="otp-code">' . $otp . '</div>
                    </div>
                    
                    <div class="warning">
                        <strong>⏱️ Penting:</strong> Kode ini hanya berlaku selama <strong>5 menit</strong>.<br>
                        🔒 Jangan bagikan kode ini kepada siapapun!
                    </div>
                    
                    <p style="color: #6B7280; font-size: 14px;">Jika Anda tidak melakukan permintaan ini, abaikan email ini atau hubungi administrator.</p>
                </div>
                <div class="footer">
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
                    <p>&copy; ' . date('Y') . ' PRCFI. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Send via Gmail SMTP
        $sent = smtp_send_email(
            SMTP_HOST,
            SMTP_PORT,
            SMTP_USER,
            SMTP_PASS,
            FROM_EMAIL,
            FROM_NAME,
            $email,
            $subject,
            $message
        );
        
        // Log untuk debugging
        if ($sent) {
            error_log("✅ OTP email sent successfully to: $email - OTP: $otp");
        } else {
            error_log("❌ Failed to send OTP email to: $email");
        }
        
        return $sent;
        
    } catch (Exception $e) {
        error_log("❌ Email error: " . $e->getMessage());
        return false;
    }
}

// SMTP Send Function - Native PHP (no library needed)
function smtp_send_email($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name, $to_email, $subject, $html_message) {
    try {
        // Connect to SMTP server
        $smtp = fsockopen('tls://' . $smtp_host, $smtp_port, $errno, $errstr, 30);
        
        if (!$smtp) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        // Read server response
        $response = fgets($smtp, 515);
        
        // Send EHLO
        fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $response = fgets($smtp, 515);
        
        // Read all EHLO responses
        while (substr($response, 3, 1) == "-") {
            $response = fgets($smtp, 515);
        }
        
        // AUTH LOGIN
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        
        // Send username
        fputs($smtp, base64_encode($smtp_user) . "\r\n");
        $response = fgets($smtp, 515);
        
        // Send password
        fputs($smtp, base64_encode($smtp_pass) . "\r\n");
        $response = fgets($smtp, 515);
        
        // Check if authentication succeeded
        if (substr($response, 0, 3) != "235") {
            fclose($smtp);
            error_log("SMTP Authentication failed: " . $response);
            return false;
        }
        
        // MAIL FROM
        fputs($smtp, "MAIL FROM: <" . $from_email . ">\r\n");
        $response = fgets($smtp, 515);
        
        // RCPT TO
        fputs($smtp, "RCPT TO: <" . $to_email . ">\r\n");
        $response = fgets($smtp, 515);
        
        // DATA
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        
        // Email headers and body
        $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
        $headers .= "To: <" . $to_email . ">\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "\r\n";
        
        fputs($smtp, $headers . $html_message . "\r\n.\r\n");
        $response = fgets($smtp, 515);
        
        // QUIT
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        return true;
        
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

// Helper function to send notification email
function send_notification_email($email, $subject, $message) {
    try {
        $html_message = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f3f4f6; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .footer { text-align: center; color: #6B7280; font-size: 12px; padding: 20px; background: #F9FAFB; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2 style="margin: 0;">📧 Notifikasi PRCFI</h2>
                </div>
                <div class="content">
                    ' . nl2br(htmlspecialchars($message)) . '
                </div>
                <div class="footer">
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
                    <p>&copy; ' . date('Y') . ' PRCFI. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Send via Gmail SMTP
        $sent = smtp_send_email(
            SMTP_HOST,
            SMTP_PORT,
            SMTP_USER,
            SMTP_PASS,
            FROM_EMAIL,
            FROM_NAME,
            $email,
            $subject,
            $html_message
        );
        
        if ($sent) {
            error_log("✅ Notification email sent to: $email - Subject: $subject");
        } else {
            error_log("❌ Failed to send notification email to: $email");
        }
        
        return $sent;
        
    } catch (Exception $e) {
        error_log("❌ Notification email error: " . $e->getMessage());
        return false;
    }
}
?>