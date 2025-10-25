
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'prcf_keuangan');

// OTP Configuration
define('FONNTE_API_URL', 'https://api.fonnte.com/send');
define('FONNTE_TOKEN', 'JtuW4APts6pCciuizucu');
define('WA_OTP_ENABLED', false);

// Developer Mode Configuration
define('DEVELOPER_MODE', false);
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
if (!defined('WA_OTP_ENABLED')) define('WA_OTP_ENABLED', false);
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

if (!function_exists('validate_whatsapp_number')) {
    function validate_whatsapp_number($phone) {
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
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ OTP Email error: " . $e->getMessage());
        return false;
    }
}

function smtp_send_email($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name, $to_email, $subject, $html_message) {
    try {
        $email_content = "From: " . $from_name . " <" . $from_email . ">\r\n";
        $email_content .= "To: <" . $to_email . ">\r\n";
        $email_content .= "Subject: " . $subject . "\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_content .= "\r\n";
        $email_content .= $html_message;
        
        $temp_file = tmpfile();
        fwrite($temp_file, $email_content);
        rewind($temp_file);
        $file_stat = fstat($temp_file);
        $email_size = $file_stat['size'] ?? 0;
        
        $ch = curl_init();
        $verbose_log = fopen('php://temp', 'rw+');
        
        curl_setopt($ch, CURLOPT_URL, "smtp://" . $smtp_host . ":" . $smtp_port);
        curl_setopt($ch, CURLOPT_USE_SSL, CURLUSESSL_TRY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERNAME, $smtp_user);
        curl_setopt($ch, CURLOPT_PASSWORD, $smtp_pass);
        curl_setopt($ch, CURLOPT_MAIL_FROM, $from_email);
        curl_setopt($ch, CURLOPT_MAIL_RCPT, array($to_email));
        curl_setopt($ch, CURLOPT_READDATA, $temp_file);
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        if ($email_size > 0) {
            curl_setopt($ch, CURLOPT_INFILESIZE, $email_size);
        }
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $verbose_log);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $curl_info = curl_getinfo($ch);
        
        rewind($verbose_log);
        $verbose_output = stream_get_contents($verbose_log);
        
        curl_close($ch);
        fclose($temp_file);
        fclose($verbose_log);
        
        error_log("📧 SMTP Debug Info:");
        error_log("  To: $to_email");
        error_log("  SMTP: $smtp_host:$smtp_port");
        error_log("  Response Code: " . $curl_info['http_code']);
        error_log("  Total Time: " . round($curl_info['total_time'], 2) . "s");
        
        if ($error) {
            error_log("❌ SMTP cURL Error: " . $error);
            error_log("Verbose Output: " . substr($verbose_output, 0, 500));
            return false;
        }
        
        if (strpos($verbose_output, '250') !== false) {
            error_log("✅ Email ACCEPTED by SMTP server: $to_email");
            return true;
        } else {
            error_log("⚠️ Email sent but unclear status: $to_email");
            error_log("Verbose Output: " . substr($verbose_output, 0, 500));
            return true;
        }
        
    } catch (Exception $e) {
        error_log("❌ SMTP Error: " . $e->getMessage());
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

function send_otp_whatsapp($phone, $otp) {
    try {
        if (!WA_OTP_ENABLED) {
            error_log("⚠️ WhatsApp OTP is disabled");
            return false;
        }
        
        if (FONNTE_TOKEN === 'YOUR_FONNTE_TOKEN_HERE' || empty(FONNTE_TOKEN)) {
            error_log("⚠️ Fonnte token not configured. Please update FONNTE_TOKEN in config.php");
            return false;
        }
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        $message = "🔐 *Kode OTP Login - PRCF INDONESIA Financial*\n\n";
        $message .= "Kode OTP Anda: *{$otp}*\n\n";
        $message .= "⏱️ Berlaku selama 60 detik.\n";
        $message .= "🔒 Jangan bagikan kode ini kepada siapapun!\n\n";
        $message .= "PRCF INDONESIA Financial Management System";
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => FONNTE_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62'
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . FONNTE_TOKEN
            ],
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            error_log("❌ WhatsApp OTP cURL Error: " . $error);
            return false;
        }
        
        $result = json_decode($response, true);
        
        error_log("📱 WhatsApp OTP Debug:");
        error_log("  Phone: " . $phone);
        error_log("  HTTP Code: " . $http_code);
        error_log("  Response: " . $response);
        
        if ($http_code === 200 && isset($result['status']) && $result['status'] === true) {
            error_log("✅ WhatsApp OTP sent successfully to: " . $phone . " - OTP: " . $otp);
            return true;
        } else {
            $error_msg = isset($result['reason']) ? $result['reason'] : 'Unknown error';
            error_log("❌ Failed to send WhatsApp OTP to: " . $phone . " - Error: " . $error_msg);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ WhatsApp OTP Exception: " . $e->getMessage());
        return false;
    }
}

function is_valid_phone_number($phone) {
    $result = validate_whatsapp_number($phone);
    return $result['valid'];
}
?>
