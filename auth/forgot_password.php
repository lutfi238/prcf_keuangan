<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/maintenance_config.php';

check_maintenance();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: ../pages/dashboards/dashboard_pm.php');
    exit();
}

$error = '';
$success = '';
$step = $_SESSION['reset_step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1 && isset($_POST['email'])) {
        $email = strtolower(trim($_POST['email']));
        $stmt = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_time'] = time();
            $_SESSION['reset_attempts'] = 0;
            $_SESSION['reset_step'] = 2;
            if (EMAIL_OTP_ENABLED && send_otp_email($email, $otp)) {
                $success = 'Kode OTP telah dikirim ke email Anda.';
                $step = 2;
            } else {
                $error = 'Gagal mengirim OTP email. Hubungi administrator.';
            }
        } else {
            $error = 'Email tidak ditemukan di sistem.';
        }
    } elseif ($step === 2 && isset($_POST['otp'])) {
        $entered = preg_replace('/\D/', '', $_POST['otp']);
        if (time() - ($_SESSION['reset_otp_time'] ?? 0) > 300) {
            $error = 'Kode OTP telah kadaluarsa. Silakan minta ulang.';
        } elseif ($entered === (string)($_SESSION['reset_otp'] ?? '')) {
            $_SESSION['reset_step'] = 3;
            $step = 3;
            $success = 'OTP valid. Silakan buat password baru.';
        } else {
            $_SESSION['reset_attempts'] = ($_SESSION['reset_attempts'] ?? 0) + 1;
            $error = 'Kode OTP salah. Periksa email Anda.';
        }
    } elseif ($step === 3 && isset($_POST['password'], $_POST['confirm_password'])) {
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        if (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter';
        } elseif ($password !== $confirm) {
            $error = 'Konfirmasi password tidak cocok';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE email = ?");
            $stmt->bind_param("ss", $hash, $_SESSION['reset_email']);
            if ($stmt->execute()) {
                unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_time'], $_SESSION['reset_step']);
                $_SESSION['reset_success'] = 'Password berhasil direset. Silakan login dengan password baru.';
                header('Location: login.php');
                exit();
            } else {
                $error = 'Gagal mengubah password. Coba lagi.';
            }
        }
    } elseif (isset($_POST['resend_otp'])) {
        if (!empty($_SESSION['reset_email']) && EMAIL_OTP_ENABLED) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_time'] = time();
            $_SESSION['reset_attempts'] = 0;
            if (send_otp_email($_SESSION['reset_email'], $otp)) {
                $success = 'Kode OTP baru telah dikirim ke email Anda.';
                $step = 2;
                $_SESSION['reset_step'] = 2;
            } else {
                $error = 'Gagal mengirim OTP email. Silakan coba lagi.';
            }
        } else {
            $error = 'Silakan masukkan email Anda terlebih dahulu.';
            $step = 1;
            $_SESSION['reset_step'] = 1;
        }
    }
}

function reset_form_action($step) {
    return htmlspecialchars($_SERVER['PHP_SELF']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full bg-white shadow-xl border border-gray-200 rounded-xl">
        <div class="px-8 py-10">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Reset Password</h1>
            <p class="text-sm text-gray-600 mb-6">Masukkan email Anda untuk menerima kode OTP reset password.</p>

            <?php if ($error): ?>
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg" role="alert">
                <i class="fas fa-times-circle mr-2"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email terdaftar</label>
                    <input type="email" name="email" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="nama@domain.com">
                </div>
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                    Kirim Kode OTP
                </button>
            </form>

            <?php elseif ($step === 2): ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kode OTP</label>
                    <input type="text" name="otp" maxlength="6" autocomplete="one-time-code"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center text-2xl tracking-widest font-mono"
                        placeholder="0 0 0 0 0 0" required>
                    <p class="mt-2 text-sm text-gray-500">Masukkan kode 6 digit yang dikirim ke email Anda.</p>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <button type="submit" name="resend_otp" value="1" formnovalidate class="text-blue-600 hover:text-blue-700">
                        Kirim ulang kode
                    </button>
                    <span class="text-gray-400">Berlaku 5 menit</span>
                </div>
                <button type="submit" name="verify_otp" value="1"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                    Verifikasi OTP
                </button>
            </form>

            <?php elseif ($step === 3): ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password baru</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Minimal 8 karakter">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi password</label>
                    <input type="password" name="confirm_password" required minlength="8"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Masukkan ulang password">
                </div>
                <button type="submit"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                    Simpan Password Baru
                </button>
            </form>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Kembali ke halaman login</a>
            </div>
        </div>
    </div>
</body>
</html>
