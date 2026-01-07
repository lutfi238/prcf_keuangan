<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $identifier = trim($_POST['identifier']);
        $password = $_POST['password'];

        $email_identifier = strtolower($identifier);
        $phone_identifier = null;
        if (PHONE_LOGIN_ENABLED && preg_match('/^[0-9+\s-]+$/', $identifier)) {
            $phone_identifier = normalize_phone_number($identifier);
        }

        if ($phone_identifier) {
            $stmt = $conn->prepare("SELECT * FROM user WHERE no_HP = ?");
            $stmt->bind_param("s", $phone_identifier);
        } else {
            $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
            $stmt->bind_param("s", $email_identifier);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check account status
            $user_status = $user['status'] ?? 'active';
            
            if ($user_status === 'inactive') {
                $error = 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.';
            } elseif ($user_status === 'pending') {
                // Set session for pending user and redirect to pending page
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_name'] = $user['nama'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_status'] = 'pending';
                $_SESSION['logged_in'] = true;
                header('Location: account_pending.php');
                exit();
            } elseif (password_verify($password, $user['password_hash'])) {
                
                // 🔧 DEVELOPER MODE: Check if OTP should be bypassed
                $is_developer = (defined('DEVELOPER_MODE') && DEVELOPER_MODE && 
                                isset($DEVELOPER_EMAILS) && in_array($user['email'], $DEVELOPER_EMAILS));
                $skip_all_otp = (defined('SKIP_OTP_FOR_ALL') && SKIP_OTP_FOR_ALL);
                
                if ($skip_all_otp) {
                    // 🚀 BYPASS OTP - Direct login when skip is explicitly enabled
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_name'] = $user['nama'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_status'] = $user_status;
                    $_SESSION['logged_in'] = true;
                    
                    error_log("🔧 Developer Mode: OTP bypassed for {$user['email']}");
                    
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'Admin':
                            header('Location: ../pages/dashboards/dashboard_admin.php');
                            break;
                        case 'Project Manager':
                            header('Location: ../pages/dashboards/dashboard_pm.php');
                            break;
                        case 'Staff Accountant':
                            header('Location: ../pages/dashboards/dashboard_sa.php');
                            break;
                        case 'Finance Manager':
                            header('Location: ../pages/dashboards/dashboard_fm.php');
                            break;
                        case 'Direktur':
                            header('Location: ../pages/dashboards/dashboard_dir.php');
                            break;
                        default:
                            header('Location: ../pages/dashboards/dashboard_pm.php');
                    }
                    exit();
                }
                
                // Normal OTP flow for non-developers
                $_SESSION['pending_login'] = true;
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_name'] = $user['nama'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_status'] = $user_status;
                $_SESSION['logged_in'] = false;
                
                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_time'] = time();
                $_SESSION['otp_attempts'] = 0;
                $_SESSION['otp_phone_masked'] = '';
                
                // Send OTP via email
                if (defined('EMAIL_OTP_ENABLED') && EMAIL_OTP_ENABLED === true) {
                    $email_sent = send_otp_email($user['email'], $otp);
                    if ($email_sent) {
                        $success = 'Kode OTP telah dikirim ke email Anda: ' . htmlspecialchars($user['email']);
                    } else {
                        $error = 'Gagal mengirim OTP email. Silakan coba lagi.';
                        unset($_SESSION['otp']);
                        unset($_SESSION['pending_login']);
                    }
                } else {
                    $error = 'OTP email saat ini tidak tersedia. Hubungi administrator.';
                    unset($_SESSION['otp']);
                    unset($_SESSION['pending_login']);
                }

                if (empty($error)) {
                    header('Location: verify_otp.php');
                    exit();
                }
            } else {
                $error = PHONE_LOGIN_ENABLED ? 'Email/nomor atau password salah.' : 'Email atau password salah.';
            }
        } else {
            $error = 'Email/nomor atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">PRCF INDONESIA Financial</h1>
            <p class="text-gray-600">Sistem Manajemen Keuangan</p>
        </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['reset_success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $_SESSION['reset_success']; unset($_SESSION['reset_success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email atau Nomor HP</label>
                    <input type="text" name="identifier" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Masukkan email atau nomor HP">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                    <!-- <input type="password" name="password" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Masukkan password"> -->
                        <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="login-input w-full px-4 py-2 pr-12 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Masukkan password">
                        <button type="button" id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70 hover:text-white/90 transition-colors duration-200">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-between items-center text-sm">
                    <a href="forgot_password.php" class="text-blue-600 hover:text-blue-700">Lupa password?</a>
                </div>
                <button type="submit" name="login" 
                    class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                    Login
                </button>
            </form>

            <?php if (defined('REGISTRATION_ENABLED') && REGISTRATION_ENABLED === true): ?>
            <div class="mt-6 text-center">
                <a href="register.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                    Buat Akun Baru
                </a>
            </div>
            <?php endif; ?>
</body>
</html>
