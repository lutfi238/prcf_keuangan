<?php
/**
 * InfinityFree Specific Configuration
 * Copy this to config.php if using InfinityFree hosting
 */

// Enable error reporting (only for debugging, disable in production!)
// COMMENT OUT these lines after fixing errors:
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// InfinityFree Database Configuration
// ⚠️ IMPORTANT: Use exact credentials from InfinityFree cPanel
// Format: Database name MUST include your username prefix
// Example: if username is "if0_12345678", database is "if0_12345678_prcf_keuangan"

define('DB_HOST', 'sql200.infinityfree.com');  // Change to your actual SQL server
define('DB_USER', 'if0_XXXXXXXX');              // Replace with your database username
define('DB_PASS', 'your-database-password');    // Replace with your database password
define('DB_NAME', 'if0_XXXXXXXX_prcf_keuangan'); // Replace with full database name

// OTP Configuration (Email only)
// ⚠️ Gmail SMTP might be blocked by InfinityFree
// Consider using SendGrid or SMTP2GO instead

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'prcfpbl@gmail.com');
define('SMTP_PASS', 'ykyc bsdb vxlp xdcv');
define('FROM_EMAIL', 'prcfpbl@gmail.com');
define('FROM_NAME', 'PRCF INDONESIA Financial');

// Developer Mode Configuration
define('DEVELOPER_MODE', false);
$DEVELOPER_EMAILS = [
    '',
];

// Toggle channels
if (!defined('EMAIL_OTP_ENABLED')) define('EMAIL_OTP_ENABLED', true);
if (!defined('PHONE_LOGIN_ENABLED')) define('PHONE_LOGIN_ENABLED', true);

// Phone number helper functions
if (!function_exists('format_phone_number')) {
    function format_phone_number($phone) {
        if (!$phone) return '';
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (substr($clean, 0, 1) === '0') {
            $clean = '62' . substr($clean, 1);
        }
        if (substr($clean, 0, 2) !== '62') {
            $clean = '62' . $clean;
        }
        return $clean;
    }
}

if (!function_exists('mask_phone_number')) {
    function mask_phone_number($phone) {
        if (!$phone || strlen($phone) < 4) return '****';
        return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 4);
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

// Create database connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        // Log error instead of dying (more graceful)
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please check error.log");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    die("Database connection error. Please check error.log");
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');
@$conn->query("SET time_zone = '+07:00'");

// Email OTP Function - PHPMailer alternative for InfinityFree
function send_otp_email($email, $otp) {
    if (!defined('EMAIL_OTP_ENABLED') || EMAIL_OTP_ENABLED !== true) {
        error_log("ℹ️ Email OTP disabled - skipping send to $email");
        return true;
    }

    // InfinityFree blocks mail() function
    // You MUST use SMTP with PHPMailer or similar
    // For now, return true to bypass (use DEVELOPER_MODE for testing)
    
    if (defined('DEVELOPER_MODE') && DEVELOPER_MODE) {
        error_log("🔧 DEVELOPER MODE: OTP for $email is: $otp");
        return true;
    }

    // TODO: Implement PHPMailer SMTP here
    // InfinityFree blocks standard mail() function
    error_log("⚠️ Email OTP not configured for InfinityFree hosting");
    return false;
}

// Notification email function (simplified for InfinityFree)
function send_notification_email($email, $subject, $message, $type = 'info') {
    // Disabled on InfinityFree - use alternative notification method
    return true;
}

function is_valid_phone_number($phone) {
    $result = validate_phone_number_format($phone);
    return $result['valid'];
}
?>

