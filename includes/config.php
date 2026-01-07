
<?php
// Database configuration
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'prcf_keuangan');

// OTP Configuration (Email only)

// Developer Mode Configuration
define('DEVELOPER_MODE', true); // Set to true for debugging - Shows OTP on screen
$DEVELOPER_EMAILS = [
    '',
];
define('SKIP_OTP_FOR_ALL', false); // true for skip OTP email

// Email SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_PORT', 587);
define('SMTP_USER', 'prcfpbl@gmail.com');
define('SMTP_PASS', 'ykyc bsdb vxlp xdcv');
define('FROM_EMAIL', 'prcfpbl@gmail.com');
define('FROM_NAME', 'PRCF INDONESIA Financial');

// Toggle channels
if (!defined('EMAIL_OTP_ENABLED')) define('EMAIL_OTP_ENABLED', true);
if (!defined('PHONE_LOGIN_ENABLED')) define('PHONE_LOGIN_ENABLED', true);

// Phone number helper functions
if (!function_exists('format_phone_number')) {
    function format_phone_number($phone) {
        if (!$phone) return '';
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        if (substr($phone, 0, 1) === '+') {
            $phone = substr($phone, 1);
        }
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}

if (!function_exists('normalize_phone_number')) {
    function normalize_phone_number($phone) {
        return format_phone_number($phone);
    }
}

if (!function_exists('validate_phone_number_format')) {
    function validate_phone_number_format($phone) {
        if (!$phone) {
            return ['valid' => true, 'error' => ''];
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) < 10) {
            return ['valid' => false, 'error' => 'Nomor telepon minimal 10 digit'];
        }
        if (strlen($clean) > 13) {
            return ['valid' => false, 'error' => 'Nomor telepon maksimal 13 digit'];
        }
        if (!preg_match('/^(08|628)/', $clean)) {
            return ['valid' => false, 'error' => 'Nomor harus diawali 08 atau 628'];
        }
        return ['valid' => true, 'error' => ''];
    }
}

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Jakarta');
@$conn->query("SET time_zone = '+07:00'");

// Email OTP Function
function send_otp_email($email, $otp) {
    // If developer mode is enabled, skip actual email sending but log it
    if (defined('DEVELOPER_MODE') && DEVELOPER_MODE) {
        error_log("🔧 DEVELOPER MODE: OTP email would be sent to $email with OTP: $otp");
        error_log("🔧 DEVELOPER MODE: Email sending skipped - OTP visible on screen");
        return true; // Return true so it doesn't show error to user
    }

    if (!defined('EMAIL_OTP_ENABLED') || EMAIL_OTP_ENABLED !== true) {
        error_log("ℹ️ Email OTP disabled - skipping send to $email");
        return true;
    }

    if (!defined('SMTP_HOST') || !defined('SMTP_PORT') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
        error_log("⚠️ SMTP configuration missing - Email OTP skipped for $email");
        return true;
    }

    try {
        $subject = "Kode OTP Login - PRCF INDONESIA Financial";

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
                    <p style="margin: 10px 0 0 0;">PRCF INDONESIA Financial Management System</p>
                </div>
                <div class="content">
                    <h2 style="color: #1F2937;">Halo!</h2>
                    <p>Anda menerima email ini karena ada permintaan login ke sistem PRCF INDONESIA Financial.</p>
                    
                    <div class="otp-box">
                        <p style="margin: 0 0 10px 0; font-size: 14px; color: #6B7280;">Kode OTP Anda:</p>
                        <div class="otp-code">' . $otp . '</div>
                    </div>
                    
                    <div class="warning">
                        <strong>⏱️ Penting:</strong> Kode ini hanya berlaku selama <strong>1 menit (60 detik)</strong>.<br>
                        🔒 Jangan bagikan kode ini kepada siapapun!
                    </div>
                    
                    <p style="color: #6B7280; font-size: 14px;">Jika Anda tidak melakukan permintaan ini, abaikan email ini atau hubungi administrator.</p>
                </div>
                <div class="footer">
                    <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
                    <p>&copy; ' . date('Y') . ' PRCF INDONESIA Financial. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
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
        
        if ($sent) {
            error_log("✅ OTP email sent successfully to: $email - OTP: $otp");
            return true;
        } else {
            error_log("❌ Failed to send OTP email to: $email");
            
            // Try fallback: PHP mail() function
            error_log("🔄 Attempting fallback: PHP mail() function");
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
            $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
            
            $fallback_sent = @mail($email, $subject, $message, $headers);
            
            if ($fallback_sent) {
                error_log("✅ Fallback email sent via PHP mail() to: $email");
                return true;
            } else {
                error_log("❌ Fallback email also failed for: $email");
                // If developer mode is enabled, return true anyway
                if (defined('DEVELOPER_MODE') && DEVELOPER_MODE) {
                    error_log("🔧 Developer mode: Returning true despite email failure");
                    return true;
                }
                return false;
            }
        }
        
    } catch (Exception $e) {
        error_log("❌ OTP Email error: " . $e->getMessage());
        // If developer mode is enabled, return true anyway
        if (defined('DEVELOPER_MODE') && DEVELOPER_MODE) {
            error_log("🔧 Developer mode: Returning true despite exception");
            return true;
        }
        return false;
    }
}

function smtp_send_email($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name, $to_email, $subject, $html_message) {
    // Try PHPMailer first if available
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_user;
            $mail->Password = $smtp_pass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtp_port;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to_email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_message;
            
            $mail->send();
            error_log("✅ Email sent successfully via PHPMailer to: $to_email");
            return true;
        } catch (Exception $e) {
            error_log("❌ PHPMailer Error: " . $e->getMessage());
            // Fall through to socket method
        }
    }
    
    // Use socket-based SMTP (more reliable than cURL)
    try {
        // Use stream_context for SSL/TLS support
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        // Connect to SMTP server
        $smtp = stream_socket_client(
            $smtp_host . ':' . $smtp_port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$smtp) {
            error_log("❌ SMTP Connection Failed: $errstr ($errno)");
            return false;
        }
        
        // Read server greeting
        $response = fgets($smtp, 515);
        error_log("📧 SMTP Greeting: " . trim($response));
        
        // Send EHLO
        fputs($smtp, "EHLO " . $smtp_host . "\r\n");
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        error_log("📧 SMTP EHLO Response: " . trim($response));
        
        // Start TLS if port is 587
        if ($smtp_port == 587) {
            // Check if OpenSSL is available
            if (!extension_loaded('openssl')) {
                error_log("❌ OpenSSL extension not loaded - Cannot use TLS encryption");
                error_log("💡 Enable OpenSSL in php.ini: extension=openssl");
                fclose($smtp);
                return false;
            }
            
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            error_log("📧 SMTP STARTTLS: " . trim($response));
            
            if (substr($response, 0, 3) == '220') {
                $crypto_enabled = @stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$crypto_enabled) {
                    error_log("❌ Failed to enable TLS encryption - Check OpenSSL configuration");
                    fclose($smtp);
                    return false;
                }
                // Send EHLO again after TLS
                fputs($smtp, "EHLO " . $smtp_host . "\r\n");
                $response = '';
                while ($line = fgets($smtp, 515)) {
                    $response .= $line;
                    if (substr($line, 3, 1) == ' ') break;
                }
                error_log("📧 SMTP EHLO After TLS: " . trim($response));
            }
        }
        
        // Authenticate
        fputs($smtp, "AUTH LOGIN\r\n");
        $response = fgets($smtp, 515);
        error_log("📧 SMTP AUTH LOGIN: " . trim($response));
        
        fputs($smtp, base64_encode($smtp_user) . "\r\n");
        $response = fgets($smtp, 515);
        error_log("📧 SMTP Username: " . trim($response));
        
        fputs($smtp, base64_encode($smtp_pass) . "\r\n");
        $response = fgets($smtp, 515);
        error_log("📧 SMTP Password: " . trim($response));
        
        if (substr($response, 0, 3) != '235') {
            error_log("❌ SMTP Authentication Failed: " . trim($response));
            fclose($smtp);
            return false;
        }
        
        // Send email
        fputs($smtp, "MAIL FROM: <" . $from_email . ">\r\n");
        $response = fgets($smtp, 515);
        error_log("📧 SMTP MAIL FROM: " . trim($response));
        
        fputs($smtp, "RCPT TO: <" . $to_email . ">\r\n");
        $response = fgets($smtp, 515);
        error_log("📧 SMTP RCPT TO: " . trim($response));
        
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        error_log("📧 SMTP DATA: " . trim($response));
        
        // Email headers and body
        $email_content = "From: " . $from_name . " <" . $from_email . ">\r\n";
        $email_content .= "To: <" . $to_email . ">\r\n";
        $email_content .= "Subject: " . $subject . "\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_content .= "\r\n";
        $email_content .= $html_message . "\r\n";
        $email_content .= ".\r\n";
        
        fputs($smtp, $email_content);
        $response = fgets($smtp, 515);
        error_log("📧 SMTP Email Sent: " . trim($response));
        
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        if (substr($response, 0, 3) == '250') {
            error_log("✅ Email sent successfully via Socket SMTP to: $to_email");
            return true;
        } else {
            error_log("⚠️ Email send response unclear: " . trim($response));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ Socket SMTP Error: " . $e->getMessage());
        
        // Final fallback: PHP mail() function
        error_log("🔄 Attempting final fallback: PHP mail() function");
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . $from_name . " <" . $from_email . ">\r\n";
        $headers .= "Reply-To: " . $from_email . "\r\n";
        
        $fallback_sent = @mail($to_email, $subject, $html_message, $headers);
        if ($fallback_sent) {
            error_log("✅ Fallback email sent via PHP mail() to: $to_email");
            return true;
        }
        
        return false;
    }
}

function send_notification_email($email, $subject, $message) {
    try {
        if (!defined('SMTP_HOST') || !defined('SMTP_PORT') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
            error_log("⚠️ SMTP not configured - Email notification skipped");
            error_log("   To: $email");
            error_log("   Subject: $subject");
            error_log("   Message: " . substr($message, 0, 100) . "...");
            return true;
        }
        
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
                <h2 style="margin: 0;">📧 Notifikasi PRCF INDONESIA Financial</h2>
            </div>
            <div class="content">
                ' . nl2br(htmlspecialchars($message)) . '
            </div>
            <div class="footer">
                <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
                <p>&copy; ' . date('Y') . ' PRCF INDONESIA Financial. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
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

function is_valid_phone_number($phone) {
    $result = validate_phone_number_format($phone);
    return $result['valid'];
}
?>
