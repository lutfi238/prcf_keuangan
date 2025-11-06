<?php
require_once '../includes/config.php';

// Test dengan email yang SAMA (self-send)
$test_email = 'lutfifirdaus238@gmail.com'; // Email yang verified di Brevo
$test_otp = rand(100000, 999999);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Simple Email Test</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Simple Email Test (Brevo)</h1>
        <hr>";

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_email = $_POST['email'] ?? $test_email;
    $otp = rand(100000, 999999);
    
    echo "<div class='info'>
        📧 <strong>Mengirim email ke:</strong> $target_email<br>
        🔢 <strong>OTP:</strong> $otp<br>
        ⏱️ <strong>Waktu:</strong> " . date('Y-m-d H:i:s') . "
    </div>";
    
    // Send email
    $result = send_otp_email($target_email, $otp);
    
    if ($result) {
        echo "<div class='success'>
            ✅ <strong>EMAIL BERHASIL DIKIRIM!</strong><br><br>
            <strong>CEK 3 TEMPAT INI:</strong><br>
            1. 📨 Inbox: <a href='https://mail.google.com/mail/u/0/#inbox' target='_blank'>Gmail Inbox</a><br>
            2. 📮 SPAM: <a href='https://mail.google.com/mail/u/0/#spam' target='_blank'>Gmail SPAM</a> ← <strong>CEK INI DULU!</strong><br>
            3. 📊 Brevo Logs: <a href='https://app.brevo.com/email-campaigns/logs' target='_blank'>Brevo Dashboard</a><br><br>
            <strong>OTP yang dikirim:</strong> <span style='font-size: 24px; font-weight: bold; color: #007bff;'>$otp</span>
        </div>";
    } else {
        echo "<div class='error'>
            ❌ <strong>GAGAL MENGIRIM EMAIL</strong><br><br>
            Cek error log di: <code>C:\\xampp\\apache\\logs\\error.log</code>
        </div>";
    }
    
    // Show last 20 lines of error log
    $log_file = 'C:/xampp/apache/logs/error.log';
    if (file_exists($log_file)) {
        $log_lines = file($log_file);
        $last_lines = array_slice($log_lines, -20);
        
        echo "<h3>📋 Recent PHP Errors (last 20 lines):</h3>";
        echo "<pre>" . htmlspecialchars(implode('', $last_lines)) . "</pre>";
    }
}

// Show config info
echo "
<hr>
<h3>📋 Current Brevo Config:</h3>
<div class='info'>
    <strong>SMTP Host:</strong> " . SMTP_HOST . "<br>
    <strong>SMTP Port:</strong> " . SMTP_PORT . "<br>
    <strong>SMTP User:</strong> " . SMTP_USER . "<br>
    <strong>FROM Email:</strong> " . FROM_EMAIL . "<br>
    <strong>FROM Name:</strong> " . FROM_NAME . "
</div>

<hr>
<h3>🚀 Test Email Sekarang:</h3>
<form method='POST'>
    <label>Email Tujuan:</label><br>
    <input type='email' name='email' value='$test_email' style='width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px;' required>
    <br><br>
    <button type='submit' style='background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>
        📧 Kirim Test Email
    </button>
</form>

<hr>
<h3>💡 Tips:</h3>
<ul>
    <li><strong>Self-Send:</strong> Kirim ke email yang sama dengan FROM_EMAIL (<code>" . FROM_EMAIL . "</code>)</li>
    <li><strong>Gmail SPAM:</strong> 90% email pertama masuk SPAM folder</li>
    <li><strong>Brevo Logs:</strong> Cek status detail di dashboard Brevo</li>
    <li><strong>Wait 1-2 minutes:</strong> Kadang ada delay pengiriman</li>
</ul>

<p><a href='test_email.php'>← Kembali ke Test Email Lengkap</a></p>
</div>
</body>
</html>";
?>

