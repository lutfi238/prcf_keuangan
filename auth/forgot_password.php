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

// Initialize step: Always start at step 1 by default
$step = 1;

// Handle session step based on request type
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // On GET requests (page load), check if we should preserve session
    if (!empty($_SESSION['reset_email']) && 
        isset($_SESSION['reset_otp_time']) && 
        (time() - $_SESSION['reset_otp_time']) < 300) {
        // Valid ongoing session - preserve it (user refreshing during valid flow)
        $step = $_SESSION['reset_step'] ?? 1;
    } else {
        // No valid session - clear everything and start fresh at step 1
        unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_time'], $_SESSION['reset_attempts']);
        $step = 1;
        $_SESSION['reset_step'] = 1;
    }
} else {
    // For POST requests, use session step (will be updated by form processing)
    $step = $_SESSION['reset_step'] ?? 1;
}

// Security: Ensure step 2 and 3 can only be accessed after email is verified
if (($step === 2 || $step === 3) && empty($_SESSION['reset_email'])) {
    $step = 1;
    $_SESSION['reset_step'] = 1;
    $error = 'Silakan masukkan email Anda terlebih dahulu.';
    unset($_SESSION['reset_otp'], $_SESSION['reset_otp_time'], $_SESSION['reset_email'], $_SESSION['reset_attempts']);
}

// Final safety check: If no email in session, ALWAYS force step 1
if (empty($_SESSION['reset_email'])) {
    $step = 1;
    $_SESSION['reset_step'] = 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1 && isset($_POST['email'])) {
        $email = strtolower(trim($_POST['email']));

        // Debug: Log the email being processed
        error_log("Forgot Password: Processing email: $email");

        $stmt = $conn->prepare("SELECT id_user FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        error_log("Forgot Password: User found in database: " . ($result->num_rows === 1 ? 'YES' : 'NO'));

        if ($result->num_rows === 1) {
            $otp = rand(100000, 999999);

            error_log("Forgot Password: Attempting to send OTP to: $email, OTP: $otp");
            error_log("Forgot Password: EMAIL_OTP_ENABLED: " . (defined('EMAIL_OTP_ENABLED') ? EMAIL_OTP_ENABLED : 'NOT_DEFINED'));

            // Only proceed to step 2 if email is sent successfully
            if (EMAIL_OTP_ENABLED && send_otp_email($email, $otp)) {
                // Store session data only after successful email send
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_otp_time'] = time();
                $_SESSION['reset_attempts'] = 0;
                $_SESSION['reset_step'] = 2;
                $step = 2;
                $success = 'Kode OTP telah dikirim ke email Anda: ' . htmlspecialchars($email);
                error_log("Forgot Password: OTP email sent successfully - Moving to step 2");
            } else {
                // Don't store session data if email failed
                $error = 'Gagal mengirim OTP email. Silakan coba lagi atau hubungi administrator.';
                error_log("Forgot Password: Failed to send OTP email - Staying on step 1");
                // Clear any existing session data
                unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_time'], $_SESSION['reset_step']);
            }
        } else {
            $error = 'Email tidak ditemukan di sistem.';
            error_log("Forgot Password: Email not found in database: $email");
            // Clear any existing session data
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_otp_time'], $_SESSION['reset_step']);
        }
    } elseif ($step === 2 && isset($_POST['otp'])) {
        // Ensure email exists in session
        if (empty($_SESSION['reset_email'])) {
            $step = 1;
            $_SESSION['reset_step'] = 1;
            $error = 'Silakan masukkan email Anda terlebih dahulu.';
            unset($_SESSION['reset_otp'], $_SESSION['reset_otp_time']);
        } else {
            $entered = preg_replace('/\D/', '', $_POST['otp']);
            if (time() - ($_SESSION['reset_otp_time'] ?? 0) > 300) {
                $error = 'Kode OTP telah kadaluarsa. Silakan minta ulang.';
                // Optionally reset to step 1 if OTP expired
                // $_SESSION['reset_step'] = 1;
                // $step = 1;
            } elseif ($entered === (string)($_SESSION['reset_otp'] ?? '')) {
                $_SESSION['reset_step'] = 3;
                $step = 3;
                $success = 'OTP valid. Silakan buat password baru.';
            } else {
                $_SESSION['reset_attempts'] = ($_SESSION['reset_attempts'] ?? 0) + 1;
                $error = 'Kode OTP salah. Periksa email Anda.';
            }
        }
    } elseif ($step === 3 && isset($_POST['password'], $_POST['confirm_password'])) {
        // Ensure email exists in session
        if (empty($_SESSION['reset_email'])) {
            $step = 1;
            $_SESSION['reset_step'] = 1;
            $error = 'Silakan masukkan email Anda terlebih dahulu.';
            unset($_SESSION['reset_otp'], $_SESSION['reset_otp_time']);
        } else {
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
        }
    } elseif (isset($_POST['resend_otp'])) {
        error_log("Forgot Password: Resend OTP requested");
        if (!empty($_SESSION['reset_email']) && EMAIL_OTP_ENABLED) {
            $new_otp = rand(100000, 999999);
            
            error_log("Forgot Password: Generating new OTP: $new_otp");
            error_log("Forgot Password: Resending OTP to: " . $_SESSION['reset_email']);

            // Try to send email BEFORE updating session
            $email_sent = send_otp_email($_SESSION['reset_email'], $new_otp);
            
            error_log("Forgot Password: Email send result: " . ($email_sent ? 'SUCCESS' : 'FAILED'));

            if ($email_sent) {
                // Only update session if email was sent successfully
                $_SESSION['reset_otp'] = $new_otp;
                $_SESSION['reset_otp_time'] = time();
                $_SESSION['reset_attempts'] = 0;
                $success = 'Kode OTP baru telah dikirim ke email Anda: ' . htmlspecialchars($_SESSION['reset_email']);
                $step = 2;
                $_SESSION['reset_step'] = 2;
                error_log("Forgot Password: OTP resend successful - New OTP stored in session");
            } else {
                // Keep old OTP if email failed
                $error = 'Gagal mengirim OTP email. Silakan coba lagi atau hubungi administrator.';
                error_log("Forgot Password: OTP resend failed - Keeping old OTP in session");
            }
        } else {
            $error = 'Silakan masukkan email Anda terlebih dahulu.';
            $step = 1;
            $_SESSION['reset_step'] = 1;
            error_log("Forgot Password: Resend failed - no email in session or EMAIL_OTP_ENABLED is false");
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
    <title>Reset Password - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Password input with eye icon */
    .password-container {
      position: relative;
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(0, 0, 0, 0.7);
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .password-toggle:hover {
      color: rgba(0, 0, 0, 0.9);
    }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full bg-white shadow-xl border border-gray-200 rounded-xl">
        <div class="px-8 py-10">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Reset Password</h1>
            <?php if ($step === 1): ?>
                <p class="text-sm text-gray-600 mb-6">Masukkan email Anda untuk menerima kode OTP reset password.</p>
            <?php elseif ($step === 2): ?>
                <p class="text-sm text-gray-600 mb-6">Masukkan kode OTP yang telah dikirim ke email Anda.</p>
            <?php elseif ($step === 3): ?>
                <p class="text-sm text-gray-600 mb-6">Buat password baru untuk akun Anda.</p>
            <?php endif; ?>

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
            <!-- DEBUG MODE: Show OTP (TEMPORARY FOR DEBUGGING) -->
            <?php if ((defined('DEVELOPER_MODE') && DEVELOPER_MODE) || (defined('SKIP_OTP_FOR_ALL') && SKIP_OTP_FOR_ALL)): ?>
            <div class="mb-6 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg">
                <strong>🔧 DEBUG MODE:</strong><br>
                Current OTP: <strong class="text-2xl font-mono"><?php echo $_SESSION['reset_otp'] ?? 'NOT SET'; ?></strong><br>
                <small>Time remaining: <?php echo max(0, 300 - (time() - ($_SESSION['reset_otp_time'] ?? time()))); ?> seconds</small><br>
                <small>Email: <?php echo htmlspecialchars($_SESSION['reset_email'] ?? 'NOT SET'); ?></small><br>
                <small class="text-red-600 font-semibold">⚠️ Email sending is disabled in Developer Mode - Use OTP above</small>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kode OTP</label>
                    <input type="text" name="otp" maxlength="6" autocomplete="one-time-code"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center text-2xl tracking-widest font-mono"
                        placeholder="0 0 0 0 0 0" required>
                    <p class="mt-2 text-sm text-gray-500">Masukkan kode 6 digit yang dikirim ke email Anda.</p>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <button type="button" id="resendOtpBtn" class="text-blue-600 hover:text-blue-700 font-medium cursor-pointer underline hover:no-underline">
                        🔄 Kirim ulang kode
                    </button>
                    <span class="text-gray-400">Berlaku 5 menit</span>
                </div>
                <button type="submit" name="verify_otp" value="1"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                    Verifikasi OTP
                </button>
            </form>
            
            <!-- Hidden form for resend OTP -->
            <form method="POST" id="resendOtpForm" style="display: none;">
                <input type="hidden" name="resend_otp" value="1">
            </form>

            <?php elseif ($step === 3): ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password baru</label>
                    <div class="password-container">
                        <input type="password" id="new-password" name="password" required minlength="8"
                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Minimal 8 karakter">
                        <button type="button" id="toggle-new-password" class="password-toggle">
                            <svg id="new-password-eye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi password</label>
                    <div class="password-container">
                        <input type="password" id="confirm-password" name="confirm_password" required minlength="8"
                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Masukkan ulang password">
                        <button type="button" id="toggle-confirm-password" class="password-toggle">
                            <svg id="confirm-password-eye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
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

    <script>
    // Password toggle functionality for new password fields
    function setupPasswordToggle(toggleId, inputId, eyeId) {
      const toggleBtn = document.getElementById(toggleId);
      const input = document.getElementById(inputId);
      const eyeIcon = document.getElementById(eyeId);

      if (toggleBtn && input && eyeIcon) {
        toggleBtn.addEventListener('click', function() {
          const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
          input.setAttribute('type', type);

          // Toggle eye icon
          if (type === 'password') {
            // Show password (open eye)
            eyeIcon.innerHTML = `
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            `;
          } else {
            // Hide password (closed eye with line)
            eyeIcon.innerHTML = `
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
            `;
          }
        });
      }
    }

    // Setup password toggles for the new password fields
    setupPasswordToggle('toggle-new-password', 'new-password', 'new-password-eye');
    setupPasswordToggle('toggle-confirm-password', 'confirm-password', 'confirm-password-eye');

    // Handle resend OTP button click (if exists)
    const resendOtpBtn = document.getElementById('resendOtpBtn');
    const resendOtpForm = document.getElementById('resendOtpForm');
    
    if (resendOtpBtn && resendOtpForm) {
      resendOtpBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('🔄 Resend OTP button clicked (Forgot Password)');
        
        // Show loading state
        const originalText = this.textContent;
        this.disabled = true;
        this.textContent = '⏳ Mengirim...';
        this.style.opacity = '0.6';
        this.style.cursor = 'not-allowed';
        this.style.pointerEvents = 'none';
        
        // Submit the hidden resend form
        console.log('🔄 Submitting resend form...');
        resendOtpForm.submit();
      });
      
      console.log('✅ Resend OTP button handler attached (Forgot Password)');
    } else {
      console.warn('⚠️ Resend OTP button or form not found (Forgot Password)', {
        button: resendOtpBtn,
        form: resendOtpForm
      });
    }
    </script>
</body>
</html>
