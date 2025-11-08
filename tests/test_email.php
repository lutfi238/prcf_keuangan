<?php
require_once 'includes/config.php';

echo "Testing email function...\n";
echo "EMAIL_OTP_ENABLED: " . (defined('EMAIL_OTP_ENABLED') ? EMAIL_OTP_ENABLED : 'NOT_DEFINED') . "\n";
echo "SMTP_HOST: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT_DEFINED') . "\n";
echo "SMTP_PORT: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT_DEFINED') . "\n";
echo "SMTP_USER: " . (defined('SMTP_USER') ? 'SET' : 'NOT_SET') . "\n";
echo "cURL enabled: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";

$result = send_otp_email('test@example.com', '123456');
echo "Email send result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
?>
