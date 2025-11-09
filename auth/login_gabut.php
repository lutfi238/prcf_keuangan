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
            if ($user['status'] === 'inactive') {
                $error = 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.';
            } elseif ($user['status'] === 'pending') {
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
                
                if ($is_developer || $skip_all_otp) {
                    // 🚀 BYPASS OTP - Direct login for developers
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_name'] = $user['nama'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_status'] = $user['status'];
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
                $_SESSION['user_status'] = $user['status'];
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
    <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden;
      background: #000;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: #eee;
    }

    .scene {
      position: relative;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
      display: flex;
    }

    /* Video Background */
    .video-background {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 0;
    }

    /* Overlay for better text readability */
    .video-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        to right,
        rgba(0, 0, 0, 0.4) 0%,
        rgba(0, 0, 0, 0.2) 50%,
        rgba(0, 0, 0, 0.1) 100%
      );
      z-index: 1;
      pointer-events: none;
    }

    /* Split Layout Container */
    .split-container {
      position: relative;
      z-index: 2;
      width: 100%;
      height: 100%;
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    /* Left Welcome Section */
    .welcome-section {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      padding: 80px 60px;
      color: white;
      position: relative;
    }

    .welcome-title {
      font-size: 5rem;
      font-weight: 700;
      line-height: 1.1;
      margin-bottom: 32px;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      letter-spacing: -0.02em;
      color: #ffffff;
    }

    .welcome-title br {
      display: block;
      content: "";
      margin-top: 0.2em;
    }

    .welcome-description {
      font-size: 1rem;
      line-height: 1.8;
      color: rgba(255, 255, 255, 0.95);
      max-width: 480px;
      margin-bottom: 60px;
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
      opacity: 0.95;
    }

    .social-icons {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .social-icon {
      width: 50px;
      height: 50px;
      border: 2px solid rgba(255, 255, 255, 1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      transition: all 0.3s ease;
      cursor: pointer;
      background: transparent;
      backdrop-filter: none;
      flex-shrink: 0;
    }

    .social-icon svg {
      width: 22px;
      height: 22px;
      fill: currentColor;
    }

    .social-icon:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-2px);
      border-color: rgba(255, 255, 255, 1);
    }

    /* Right Login Section */
    .login-section {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 60px;
      background: transparent;
    }

    /* Login Card - Transparent Design */
    .login-overlay {
      position: relative;
      z-index: 10;
      background: transparent;
      border-radius: 0;
      box-shadow: none;
      width: 100%;
      max-width: 450px;
      padding: 48px;
    }

    .login-title {
      color: #ffffff !important;
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 32px;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
    }

    .login-text {
      color: rgba(255, 255, 255, 0.9) !important;
      font-size: 0.875rem;
    }

    .login-label {
      color: rgba(255, 255, 255, 0.95) !important;
      font-weight: 500;
      font-size: 0.875rem;
      margin-bottom: 8px;
      display: block;
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
    }

    .login-input {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.3) !important;
      color: #ffffff !important;
      transition: all 0.2s ease;
      width: 100%;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 0.875rem;
      backdrop-filter: none;
    }

    .login-input::placeholder {
      color: rgba(255, 255, 255, 0.6) !important;
    }

    .login-input:focus {
      border-color: rgba(255, 255, 255, 0.6) !important;
      box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1) !important;
      outline: none;
      background: rgba(255, 255, 255, 0.2) !important;
    }

    /* ---- BEGIN: GLOWY PURPLE BLUE LOGIN BUTTON ---- */
    .login-button {
      background: linear-gradient(90deg, #7c3aed, #3b82f6, #06b6d4) !important;
      color: #fff !important;
      border: none !important;
      transition: all 0.3s ease;
      font-weight: 600;
      font-size: 1rem;
      padding: 14px;
      border-radius: 8px;
      width: 100%;
      cursor: pointer;
      box-shadow:
        0 0 12px 2px #7c3aed88,
        0 0 40px 8px #06b6d455;
      position: relative;
      z-index: 1;
      outline: none;
    }

    .login-button:hover,
    .login-button:focus {
      background: linear-gradient(90deg, #a21caf, #6366f1, #0ea5e9) !important;
      box-shadow:
        0 0 30px 4px #7c3aedcc,
        0 0 64px 14px #0ea5e988;
      transform: translateY(-1px) scale(1.03);
    }

    .login-button:active {
      background: linear-gradient(90deg, #7c3aed, #3b82f6, #06b6d4) !important;
      box-shadow:
        0 0 16px 4px #3b82f666,
        0 0 30px 6px #06b6d4cc;
      transform: translateY(0) scale(0.98);
    }
    /* ---- END: GLOWY PURPLE BLUE ---- */

    /* Alert messages styling */
    .alert-error {
      background: rgba(239, 68, 68, 0.2) !important;
      border: 1px solid rgba(239, 68, 68, 0.5) !important;
      color: #ffffff !important;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 0.875rem;
      backdrop-filter: none;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    .alert-success {
      background: rgba(34, 197, 94, 0.2) !important;
      border: 1px solid rgba(34, 197, 94, 0.5) !important;
      color: #ffffff !important;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 0.875rem;
      backdrop-filter: none;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    /* Link styling */
    .login-link {
      color: rgba(255, 255, 255, 0.9) !important;
      text-decoration: none;
      transition: color 0.2s ease;
      font-size: 0.875rem;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    .login-link:hover {
      color: #ffffff !important;
      text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
    }

    /* Remember Me Checkbox */
    .remember-me {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 24px;
    }

    .remember-me input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: #f97316;
      cursor: pointer;
    }

    .remember-me label {
      color: rgba(255, 255, 255, 0.95);
      font-size: 0.875rem;
      cursor: pointer;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .split-container {
        grid-template-columns: 1fr;
      }
      
      .welcome-section {
        display: none;
      }
      
      .login-section {
        background: transparent;
      }
    }
    </style>
</head>
<body>
    <div class="scene">
        <!-- Video Background -->
        <video class="video-background" autoplay muted loop playsinline>
            <source src="../assets/video/the-celestial-cat-and-the-starry-grove-moewalls-com.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        
        <!-- Dark overlay for better text readability -->
        <div class="video-overlay"></div>

        <!-- Split Layout Container -->
        <div class="split-container">
            <!-- Left Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">Welcome<br>Back</h1>
                <p class="welcome-description">
                    Selamat datang kembali di PRCF INDONESIA Financial. Sistem manajemen keuangan yang membantu Anda mengelola keuangan dengan lebih efisien dan terorganisir.
                </p>
                <div class="social-icons">
                    <a href="#" class="social-icon" title="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-icon" title="Twitter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-icon" title="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-icon" title="YouTube">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Right Login Section -->
            <div class="login-section">
                <div class="login-overlay">
                    <h1 class="login-title">Sign in</h1>

                    <?php if ($error): ?>
                        <div class="alert-error mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-success mb-4">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['reset_success'])): ?>
                        <div class="alert-success mb-4">
                            <?php echo $_SESSION['reset_success']; unset($_SESSION['reset_success']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="login-label">Email Address</label>
                            <input type="text" name="identifier" required
                                class="login-input"
                                placeholder="Masukkan email atau nomor HP">
                        </div>

                        <div>
                            <label class="login-label">Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required
                                    class="login-input pr-12"
                                    placeholder="Masukkan password">
                                <button type="button" id="togglePassword"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-200 transition-colors duration-200">
                                    <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember Me</label>
                        </div>

                        <button type="submit" name="login" class="login-button">
                            Sign in now
                        </button>

                        <div class="mt-4">
                            <a href="forgot_password.php" class="login-link">Lost your password?</a>
                        </div>
                    </form>

                    <?php if (defined('REGISTRATION_ENABLED') && REGISTRATION_ENABLED === true): ?>
                    <div class="mt-6 text-center text-sm" style="color: rgba(255, 255, 255, 0.8); text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);">
                        By clicking on 'Sign in now' you agree to <a href="#" class="login-link underline">Terms of Service</a> | <a href="#" class="login-link underline">Privacy Policy</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Ensure video loops properly
    const video = document.querySelector('.video-background');
    if (video) {
      video.addEventListener('ended', function() {
        this.currentTime = 0;
        this.play();
      });
      
      // Ensure video plays even if autoplay fails
      video.addEventListener('loadeddata', function() {
        this.play().catch(function(error) {
          console.log('Video autoplay prevented:', error);
        });
      });
    }
    
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (togglePassword && passwordInput && eyeIcon) {
      togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

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
    </script>
</body>
</html>
