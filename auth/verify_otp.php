<?php
// Start output buffering to prevent any output before headers
ob_start();

session_start();
require_once '../includes/config.php';
require_once '../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

// Debug: Log session state
error_log("🔍 verify_otp.php - Session state: " . json_encode([
    'pending_login' => isset($_SESSION['pending_login']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'logged_in' => $_SESSION['logged_in'] ?? false,
    'session_id' => session_id()
]));

if (!isset($_SESSION['pending_login'])) {
    error_log("⚠️ verify_otp.php - No pending_login, redirecting to login.php");
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("🔍 verify_otp.php - POST received: " . json_encode([
        'verify_otp_isset' => isset($_POST['verify_otp']),
        'otp_isset' => isset($_POST['otp']),
        'otp_value' => $_POST['otp'] ?? 'NOT SET',
        'resend_otp_isset' => isset($_POST['resend_otp']),
        'session_otp' => $_SESSION['otp'] ?? 'NOT SET',
        'session_otp_time' => $_SESSION['otp_time'] ?? 'NOT SET'
    ]));
    
    if (!empty($_POST['verify_otp'])) {
        $entered_otp = preg_replace('/\D/', '', $_POST['otp'] ?? '');
        $current_time = time();
        
        // Debug: Log OTP comparison
        error_log("🔍 OTP Comparison - Entered: '$entered_otp' vs Session: '" . ($_SESSION['otp'] ?? 'NOT SET') . "'");
        error_log("🔍 Time check - Current: $current_time, OTP Time: " . ($_SESSION['otp_time'] ?? 0) . ", Diff: " . ($current_time - ($_SESSION['otp_time'] ?? 0)) . " seconds");
        
        // Check if OTP expired (300 seconds = 5 minutes)
        if ($current_time - ($_SESSION['otp_time'] ?? 0) > 300) {
            $error = 'Kode OTP telah kadaluarsa';
            error_log("❌ OTP Expired");
        } elseif ($entered_otp === (string)($_SESSION['otp'] ?? '')) {
            // OTP correct
            $user_id = $_SESSION['user_id'];
            
            // Get user data
            $stmt = $conn->prepare("SELECT * FROM user WHERE id_user = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            // Clear OTP data
            unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['pending_login'], $_SESSION['otp_attempts']);
            
            // Set session with proper data
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_name'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            // Log untuk debugging
            error_log("✅ OTP Verified - User {$user['nama']} ({$user['role']}) logging in");
            
            // Determine redirect URL based on role
            $redirect = '../pages/dashboards/';
            switch ($user['role']) {
                case 'Project Manager':  $redirect .= 'dashboard_pm.php'; break;
                case 'Staff Accountant': $redirect .= 'dashboard_sa.php'; break;
                case 'Finance Manager':  $redirect .= 'dashboard_fm.php'; break;
                case 'Direktur':         $redirect .= 'dashboard_dir.php'; break;
                default:                 $redirect .= 'dashboard_pm.php'; break;
            }
            
            error_log("✅ Redirecting to: $redirect");
            
            // Force session to be written BEFORE any output
            session_write_close();
            
            // Use JavaScript redirect for better compatibility
            // This works even if headers are already sent
            ob_end_clean(); // Clear output buffer
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($redirect); ?>">
                <script>
                    // JavaScript redirect as fallback
                    window.location.href = "<?php echo htmlspecialchars($redirect); ?>";
                </script>
            </head>
            <body>
                <p>Redirecting to dashboard...</p>
                <p>If not redirected, <a href="<?php echo htmlspecialchars($redirect); ?>">click here</a>.</p>
            </body>
            </html>
            <?php
            exit();
        } else {
            $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
            $error = 'Kode OTP salah, ulangi lagi';
            error_log("❌ OTP Wrong - Entered: '$entered_otp' vs Expected: '" . ($_SESSION['otp'] ?? 'NOT SET') . "' (Attempt: " . $_SESSION['otp_attempts'] . ")");
        }
    } elseif (!empty($_POST['resend_otp'])) {
        $current_time = time();
        if ($current_time - ($_SESSION['otp_time'] ?? 0) < 15) {
            $error = 'Tunggu sebentar sebelum meminta OTP baru';
        } else {
            $_SESSION['otp'] = rand(100000, 999999);
            $_SESSION['otp_time'] = $current_time;
            $_SESSION['otp_attempts'] = 0;

            if (!empty($_SESSION['user_email']) && EMAIL_OTP_ENABLED) {
                if (send_otp_email($_SESSION['user_email'], $_SESSION['otp'])) {
                    $success = 'Kode OTP baru telah dikirim ke email Anda.';
                } else {
                    $error = 'Gagal mengirim OTP email. Silakan coba lagi.';
                }
            } else {
                $error = 'OTP email saat ini tidak tersedia. Hubungi administrator.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="px-8 py-10">
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-key text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1">Verifikasi Email OTP</h1>
                <p class="text-gray-600 text-sm">Kode verifikasi telah dikirim ke email Anda.</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm" role="alert">
                <i class="fas fa-times-circle mr-2"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <!-- DEBUG MODE: Show OTP (REMOVE IN PRODUCTION!) -->
            <?php if (defined('DEVELOPER_MODE') && DEVELOPER_MODE): ?>
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg text-sm" role="alert">
                <strong>🔧 DEBUG MODE:</strong><br>
                OTP: <strong class="text-2xl font-mono"><?php echo $_SESSION['otp'] ?? 'NOT SET'; ?></strong><br>
                <small>Time remaining: <?php echo max(0, 300 - (time() - ($_SESSION['otp_time'] ?? time()))); ?> seconds</small>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" id="otpForm">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kode OTP</label>
                    <input type="text" name="otp" maxlength="6" required autocomplete="one-time-code"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-2xl tracking-widest font-mono"
                        placeholder="0 0 0 0 0 0">
                    <p class="mt-2 text-sm text-gray-500 text-center">
                        Masukkan kode 6 digit yang dikirim ke email: <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong>
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <button type="submit" name="resend_otp" value="1" formnovalidate class="text-blue-600 hover:text-blue-700 font-medium">
                        Kirim ulang kode
                    </button>
                    <span class="text-gray-400">Kode berlaku selama 5 menit</span>
                </div>

                <button type="submit" name="verify_otp" value="1"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition duration-200 shadow-lg">
                    <i class="fas fa-check mr-2"></i> Verifikasi & Masuk
                </button>
            </form>
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-8 py-6 text-sm text-gray-500 text-center">
            <p>Belum menerima email?</p>
            <p class="mt-1">Periksa folder spam atau klik "Kirim ulang kode" untuk mendapatkan OTP baru.</p>
        </div>
    </div>

    <!-- DEBUG: Form submission logger -->
    <script>
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            console.log('🔍 Form submitting...');
            console.log('OTP value:', document.querySelector('input[name="otp"]').value);
            console.log('verify_otp button:', document.querySelector('button[name="verify_otp"]'));
            console.log('Form action:', this.action || 'same page');
            console.log('Form method:', this.method);
        });

        // Auto-focus OTP input
        document.querySelector('input[name="otp"]').focus();

        // Debug: Log when page loads
        console.log('✅ verify_otp.php loaded');
        console.log('Session ID visible in cookies:', document.cookie.includes('PHPSESSID'));
    </script>
</body>
</html>