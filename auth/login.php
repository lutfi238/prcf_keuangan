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
      justify-content: center;
      align-items: center;
      background:
        radial-gradient(circle at top, #050818 0%, #020612 40%, #000000 100%);
    }

    /* Starry sky */
    .stars {
      position: absolute;
      top: 0;
      left: 0;
      width: 230%;
      height: 75%;
      background:
        radial-gradient(2px 2px at 6% 12%, #ffffffb3, transparent),
        radial-gradient(2px 2px at 18% 30%, #ffffff90, transparent),
        radial-gradient(2px 2px at 32% 18%, #ffffff80, transparent),
        radial-gradient(2px 2px at 50% 8%,  #ffffffaa, transparent),
        radial-gradient(2px 2px at 65% 25%, #ffffff88, transparent),
        radial-gradient(2px 2px at 82% 16%, #ffffffaa, transparent),
        radial-gradient(2px 2px at 95% 10%, #ffffffaa, transparent),
        radial-gradient(1.5px 1.5px at 15% 40%, #ffffff80, transparent),
        radial-gradient(1.5px 1.5px at 40% 45%, #ffffff80, transparent),
        radial-gradient(1.5px 1.5px at 70% 35%, #ffffff80, transparent),
        radial-gradient(1.2px 1.2px at 28% 20%, #ffffff80, transparent),
        radial-gradient(1.2px 1.2px at 60% 18%, #ffffff80, transparent),
        radial-gradient(1.2px 1.2px at 90% 30%, #ffffff80, transparent),
        radial-gradient(1px 1px at 22% 55%, #ffffff80, transparent),
        radial-gradient(1px 1px at 48% 60%, #ffffff80, transparent),
        radial-gradient(1px 1px at 75% 52%, #ffffff80, transparent);
      opacity: 0.9;
      animation: panStars 120s linear infinite alternate;
      pointer-events: none;
      z-index: 0;
    }

    @keyframes panStars {
      0%   { transform: translateX(0); }
      100% { transform: translateX(-12%); }
    }

    /* Moon (top-left, soft anime glow) */
    .moon-wrap {
      position: absolute;
      top: 4vh;
      left: 4vw;
      width: 90px;
      height: 90px;
      z-index: 1;
      pointer-events: none;
    }

    .moon-glow {
      position: absolute;
      inset: -30%;
      background:
        radial-gradient(circle, rgba(255,255,255,0.06) 0, transparent 70%);
      filter: blur(4px);
    }

    .moon {
      position: absolute;
      top: 14px;
      left: 14px;
      width: 62px;
      height: 62px;
      border-radius: 50%;
      background:
        radial-gradient(circle at 30% 25%, #ffffff 0, #f8fbff 25%, #cfd9ff 55%, #9ba7e6 80%, #6b76b8 100%);
      box-shadow:
        0 0 16px rgba(255,255,255,0.9),
        0 0 32px rgba(180,200,255,0.45);
      overflow: hidden;
    }

    /* Subtle craters */
    .moon::before,
    .moon::after {
      content: "";
      position: absolute;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(150,160,210,0.5) 0, transparent 90%);
      opacity: 0.5;
    }

    .moon::before {
      width: 14px;
      height: 14px;
      top: 16px;
      left: 12px;
    }

    .moon::after {
      width: 10px;
      height: 10px;
      top: 8px;
      right: 12px;
    }

    /* Aurora (behind trees) */
    .aurora {
      position: absolute;
      top: 4vh;
      left: 0;
      width: 100vw;
      height: 32vh;
      background:
        radial-gradient(circle at 20% 120%, rgba(120,255,200,0.16) 0, transparent 70%),
        radial-gradient(circle at 40% 120%, rgba(120,180,255,0.12) 0, transparent 75%),
        radial-gradient(circle at 65% 120%, rgba(200,160,255,0.14) 0, transparent 70%),
        radial-gradient(circle at 85% 120%, rgba(110,220,255,0.14) 0, transparent 70%);
      mix-blend-mode: screen;
      opacity: 0.23;
      filter: blur(10px);
      z-index: 1;
      pointer-events: none;
      animation: auroraWave 22s ease-in-out infinite alternate;
    }

    @keyframes auroraWave {
      0% {
        transform: translateX(-8px);
        opacity: 0.18;
      }
      50% {
        transform: translateX(6px);
        opacity: 0.28;
      }
      100% {
        transform: translateX(-4px);
        opacity: 0.2;
      }
    }

    /* Distant forest */
    .far-forest {
      position: absolute;
      bottom: 18vh;
      left: -5vw;
      width: 110vw;
      height: 22vh;
      background:
        radial-gradient(ellipse at 10% 100%, #0a1624 0%, transparent 65%),
        radial-gradient(ellipse at 30% 100%, #0a1828 0%, transparent 65%),
        radial-gradient(ellipse at 55% 100%, #091725 0%, transparent 65%),
        radial-gradient(ellipse at 80% 100%, #0a1726 0%, transparent 65%),
        radial-gradient(ellipse at 100% 100%, #07121f 0%, transparent 65%);
      opacity: 0.8;
      filter: blur(1.5px);
      pointer-events: none;
      z-index: 2;
    }

    /* Mid forest */
    .mid-forest {
      position: absolute;
      bottom: 10vh;
      left: -8vw;
      width: 120vw;
      height: 26vh;
      background:
        linear-gradient(to top, transparent 75%, #0b141f 100%),
        repeating-linear-gradient(
          -86deg,
          #050b12,
          #050b12 6px,
          transparent 6px,
          transparent 14px
        ),
        repeating-linear-gradient(
          -94deg,
          #060b13,
          #060b13 7px,
          transparent 7px,
          transparent 16px
        );
      mix-blend-mode: normal;
      opacity: 0.92;
      filter: blur(0.7px);
      pointer-events: none;
      z-index: 3;
      animation: forestDrift 40s ease-in-out infinite alternate;
    }

    @keyframes forestDrift {
      0%   { transform: translateX(0); }
      100% { transform: translateX(2vw); }
    }

    .ground {
      position: absolute;
      bottom: 0;
      left: -5vw;
      width: 110vw;
      height: 18vh;
      background:
        radial-gradient(circle at 50% 0%, rgba(255,160,70,0.06) 0, transparent 80%),
        linear-gradient(to top, #020509 0%, #04070d 40%, transparent 100%);
      z-index: 3;
      pointer-events: none;
    }

    /* Foreground tree + owl */
    .tree-foreground {
      position: absolute;
      right: 5vw;
      bottom: 0;
      width: 85px;
      height: 70vh;
      z-index: 6;
      pointer-events: none;
    }

    .tree-trunk {
      position: absolute;
      bottom: 0;
      right: 20px;
      width: 24px;
      height: 70vh;
      background: linear-gradient(to right, #05070b, #0b0f16);
      border-radius: 16px 12px 0 0;
      box-shadow: -4px 0 10px rgba(0,0,0,0.8);
    }

    .tree-branch {
      position: absolute;
      bottom: 32vh;
      right: 24px;
      width: 90px;
      height: 10px;
      background: linear-gradient(to right, #05070b, #0b1018);
      border-radius: 10px;
      transform-origin: right center;
      transform: rotate(-4deg);
      box-shadow: 0 0 8px rgba(0,0,0,0.9);
    }

    .owl {
      position: absolute;
      bottom: 35vh;
      right: 60px;
      width: 38px;
      height: 46px;
      z-index: 7;
      transform-origin: center bottom;
      animation: owlIdle 4s ease-in-out infinite;
    }

    .owl-body {
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 30px;
      height: 34px;
      background: radial-gradient(circle at 50% 0%, #222838 0, #141824 45%, #080a10 100%);
      border-radius: 18px 18px 10px 10px;
      box-shadow: 0 0 6px rgba(0,0,0,0.9);
    }

    .owl-head {
      position: absolute;
      bottom: 26px;
      left: 50%;
      transform: translateX(-50%);
      width: 34px;
      height: 24px;
      background: radial-gradient(circle at 50% 0%, #2b3044 0, #171b27 60%, #090b12 100%);
      border-radius: 18px 18px 14px 14px;
      transition: transform 0.2s ease-out;
    }

    .owl-ear {
      position: absolute;
      top: -4px;
      width: 8px;
      height: 8px;
      background: #151926;
      clip-path: polygon(0 100%, 50% 0, 100% 100%);
    }

    .owl-ear.left  { left: 5px; }
    .owl-ear.right { right: 5px; }

    .owl-eye {
      position: absolute;
      top: 7px;
      width: 8px;
      height: 8px;
      background: radial-gradient(circle, #ffe8a8 0, #ffc865 55%, #a35a1a 100%);
      border-radius: 50%;
      box-shadow: 0 0 4px rgba(255,220,140,0.7);
      transform-origin: center center;
      transition: transform 0.2s ease-out;
    }

    .owl-eye.left  { left: 7px; }
    .owl-eye.right { right: 7px; }

    .owl-eye::after {
      content: "";
      position: absolute;
      top: 2px;
      left: 4px;
      width: 2px;
      height: 2px;
      background: #fff;
      border-radius: 50%;
      opacity: 0.9;
    }

    .owl-lid {
      position: absolute;
      top: 7px;
      width: 8px;
      height: 8px;
      background: #171b27;
      border-radius: 50%;
      transform-origin: top center;
      transform: scaleY(0);
      opacity: 0.98;
      pointer-events: none;
      animation: owlBlink 6s infinite;
    }

    .owl-lid.left  { left: 7px; }
    .owl-lid.right { right: 7px; }

    .owl-beak {
      position: absolute;
      top: 13px;
      left: 50%;
      transform: translateX(-50%);
      width: 6px;
      height: 6px;
      background: #f7a53a;
      clip-path: polygon(50% 100%, 0 0, 100% 0);
    }

    .owl-glow {
      position: absolute;
      bottom: 26px;
      left: 50%;
      transform: translateX(-50%);
      width: 40px;
      height: 40px;
      background: radial-gradient(circle, rgba(255,210,120,0.04) 0, transparent 75%);
      pointer-events: none;
    }

    @keyframes owlIdle {
      0%   { transform: translateY(0); }
      50%  { transform: translateY(-1px); }
      100% { transform: translateY(0); }
    }

    @keyframes owlBlink {
      0%, 8%   { transform: scaleY(0); }
      9%, 11%  { transform: scaleY(1); }
      13%, 70% { transform: scaleY(0); }
      71%,72%  { transform: scaleY(1); }
      73%,100% { transform: scaleY(0); }
    }

    /* Campfire */
    .campfire {
      position: absolute;
      bottom: 20px;
      left: 30px;
      width: 230px;
      height: 260px;
      display: flex;
      justify-content: center;
      align-items: flex-end;
      z-index: 5;
    }

    .ground-glow {
      position: absolute;
      bottom: 28px;
      left: 50%;
      transform: translateX(-50%);
      width: 280px;
      height: 90px;
      background: radial-gradient(circle, rgba(255,180,90,0.16) 0, transparent 75%);
      filter: blur(6px);
      animation: glowPulse 2.5s ease-in-out infinite alternate;
      pointer-events: none;
    }

    @keyframes glowPulse {
      0%   { opacity: 0.35; transform: translateX(-50%) scale(1); }
      100% { opacity: 0.7;  transform: translateX(-50%) scale(1.08); }
    }

    .logs {
      position: absolute;
      bottom: 42px;
      left: 50%;
      transform: translateX(-50%);
      width: 230px;
      height: 48px;
    }

    .log {
      position: absolute;
      bottom: 0;
      width: 130px;
      height: 22px;
      background: linear-gradient(to right, #3a210f, #5b3315);
      border-radius: 20px;
      box-shadow: 0 0 6px rgba(0,0,0,0.85);
    }

    .log::before {
      content: "";
      position: absolute;
      right: -6px;
      top: 4px;
      width: 8px;
      height: 14px;
      border-radius: 50%;
      background: radial-gradient(circle, #e9c7a0 0, #8b5a32 60%, #4a2b16 100%);
    }

    .log:nth-child(1) {
      left: 34px;
      transform: rotate(20deg);
      background: linear-gradient(to right, #3b2210, #6a3a17);
    }

    .log:nth-child(2) {
      right: 34px;
      transform: rotate(-18deg);
      background: linear-gradient(to right, #341d0f, #5a3014);
    }

    .log:nth-child(3) {
      left: 50%;
      transform: translateX(-50%) rotate(88deg);
      width: 110px;
      opacity: 0.96;
      background: linear-gradient(to right, #2c190c, #4f2811);
    }

    .flame {
      position: absolute;
      bottom: 62px;
      left: 50%;
      transform: translateX(-50%);
      width: 115px;
      height: 155px;
      pointer-events: none;
      filter: blur(0.2px);
    }

    .flame-outer,
    .flame-middle,
    .flame-inner {
      position: absolute;
      bottom: 0;
      left: 50%;
      transform-origin: bottom center;
      border-radius: 50% 50% 40% 40%;
      mix-blend-mode: screen;
    }

    .flame-outer {
      width: 115px;
      height: 155px;
      background: radial-gradient(circle at 50% 0%, rgba(255,220,150,0.18) 0, rgba(255,140,40,0.26) 40%, transparent 85%);
      transform: translateX(-50%);
      animation:
        flickerOuter 0.22s infinite ease-in-out alternate,
        sway 1.7s infinite ease-in-out;
    }

    .flame-middle {
      width: 82px;
      height: 125px;
      background: radial-gradient(circle at 50% 0%, rgba(255,240,200,0.32) 0, rgba(255,170,60,0.52) 40%, transparent 85%);
      transform: translateX(-50%);
      animation:
        flickerMiddle 0.18s infinite ease-in-out alternate,
        sway 1.6s infinite ease-in-out;
    }

    .flame-inner {
      width: 48px;
      height: 92px;
      background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.9) 0, rgba(255,220,140,0.95) 25%, rgba(255,150,60,0.0) 80%);
      transform: translateX(-50%);
      animation:
        flickerInner 0.14s infinite ease-in-out alternate,
        sway 1.4s infinite ease-in-out;
    }

    @keyframes sway {
      0%   { transform: translateX(-50%) rotate(-3deg); }
      50%  { transform: translateX(-50%) rotate(3deg); }
      100% { transform: translateX(-50%) rotate(-3deg); }
    }

    @keyframes flickerOuter {
      0%   { transform: translateX(-50%) scaleY(0.92); opacity: 0.36; }
      100% { transform: translateX(-50%) scaleY(1.08); opacity: 0.55; }
    }

    @keyframes flickerMiddle {
      0%   { transform: translateX(-50%) scaleY(0.9); opacity: 0.62; }
      100% { transform: translateX(-50%) scaleY(1.12); opacity: 0.98; }
    }

    @keyframes flickerInner {
      0%   { transform: translateX(-50%) scaleY(0.86); opacity: 0.86; }
      100% { transform: translateX(-50%) scaleY(1.16); opacity: 1; }
    }

    .ember-glow {
      position: absolute;
      bottom: 56px;
      left: 50%;
      transform: translateX(-50%);
      width: 92px;
      height: 40px;
      background: radial-gradient(circle at 50% 50%, rgba(255,120,40,0.7) 0, transparent 75%);
      filter: blur(4px);
      opacity: 0.85;
      animation: emberPulse 1.4s ease-in-out infinite alternate;
      pointer-events: none;
    }

    @keyframes emberPulse {
      0%   { opacity: 0.45; transform: translateX(-50%) scale(0.9); }
      100% { opacity: 0.95; transform: translateX(-50%) scale(1.1); }
    }

    .sparks {
      position: absolute;
      bottom: 104px;
      left: 50%;
      width: 0;
      height: 0;
      overflow: visible;
      pointer-events: none;
    }

    .spark {
      position: absolute;
      width: 3px;
      height: 6px;
      background: radial-gradient(circle, #ffd8a0 0, #ff9f40 50%, transparent 100%);
      border-radius: 50%;
      opacity: 0;
      box-shadow: 0 0 6px rgba(255,180,90,0.9);
    }

    /* Shooting stars */
    .shooting-stars {
      position: absolute;
      top: 0;
      left: 0;
      width: 100vw;
      height: 55vh;
      pointer-events: none;
      overflow: hidden;
      z-index: 2;
    }

    .shooting-star {
      position: absolute;
      width: 2px;
      height: 2px;
      background: #ffffff;
      border-radius: 50%;
      box-shadow: 0 0 6px rgba(255,255,255,0.9);
      opacity: 0;
      transform-origin: left center;
    }

    .shooting-star::after {
      content: "";
      position: absolute;
      left: -80px;
      top: 0;
      width: 80px;
      height: 2px;
      background: linear-gradient(to right, rgba(255,255,255,0), rgba(255,255,255,0.9));
      border-radius: 999px;
      opacity: 0.9;
    }

    /* Screen glow */
    .screen-glow {
      position: fixed;
      inset: 0;
      pointer-events: none;
      background:
        radial-gradient(circle at 50% 85%, rgba(255,160,70,0.08) 0, transparent 65%);
      mix-blend-mode: screen;
      animation: screenFlicker 2s ease-in-out infinite alternate;
      z-index: 1;
    }

    @keyframes screenFlicker {
      0%   { opacity: 0.05; }
      100% { opacity: 0.14; }
    }

    /* Login form overlay styling - Liquid Glass Effect */
    .login-overlay {
      position: relative;
      z-index: 10;
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(20px) saturate(180%);
      border-radius: 20px;
      box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.18);
      position: relative;
      overflow: hidden;
    }

    .login-overlay::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.1) 0%,
        rgba(255, 255, 255, 0.05) 25%,
        rgba(255, 255, 255, 0.02) 50%,
        rgba(255, 255, 255, 0.05) 75%,
        rgba(255, 255, 255, 0.1) 100%
      );
      pointer-events: none;
      border-radius: 20px;
    }

    .login-overlay::after {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(
        circle at 30% 20%,
        rgba(255, 255, 255, 0.15) 0%,
        transparent 50%
      );
      animation: liquidShimmer 8s ease-in-out infinite;
      pointer-events: none;
    }

    @keyframes liquidShimmer {
      0%, 100% {
        transform: translate(-50%, -50%) rotate(0deg) scale(1);
        opacity: 0.3;
      }
      50% {
        transform: translate(-50%, -50%) rotate(180deg) scale(1.1);
        opacity: 0.6;
      }
    }

    .login-title {
      color: rgba(255, 255, 255, 0.95) !important;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    .login-text {
      color: rgba(255, 255, 255, 0.8) !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    .login-input {
      background: rgba(255, 255, 255, 0.15) !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      color: rgba(255, 255, 255, 0.9) !important;
      backdrop-filter: blur(5px);
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }

    .login-input::placeholder {
      color: rgba(255, 255, 255, 0.6) !important;
    }

    .login-input:focus {
      border-color: rgba(59, 130, 246, 0.6) !important;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
      background: rgba(255, 255, 255, 0.2) !important;
    }

    .login-button {
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(29, 78, 216, 0.8)) !important;
      box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.4) !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
      backdrop-filter: blur(5px);
    }

    .login-button:hover {
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(30, 64, 175, 0.9)) !important;
      box-shadow: 0 6px 20px 0 rgba(59, 130, 246, 0.6) !important;
      transform: translateY(-1px);
    }
    </style>
</head>
<body>
    <div class="scene">
        <div class="stars"></div>
        <div class="moon-wrap">
            <div class="moon-glow"></div>
            <div class="moon"></div>
        </div>
        <div class="aurora"></div>
        <div class="far-forest"></div>
        <div class="mid-forest"></div>
        <div class="ground"></div>

        <!-- Shooting stars container -->
        <div class="shooting-stars" id="shooting-stars">
            <div class="shooting-star"></div>
            <div class="shooting-star"></div>
            <div class="shooting-star"></div>
            <div class="shooting-star"></div>
            <div class="shooting-star"></div>
        </div>

        <!-- Foreground tree & owl -->
        <div class="tree-foreground">
            <div class="tree-trunk"></div>
            <div class="tree-branch"></div>

            <div class="owl">
                <div class="owl-body"></div>
                <div class="owl-glow"></div>
                <div class="owl-head">
                    <div class="owl-ear left"></div>
                    <div class="owl-ear right"></div>

                    <div class="owl-eye left"></div>
                    <div class="owl-eye right"></div>

                    <div class="owl-lid left"></div>
                    <div class="owl-lid right"></div>

                    <div class="owl-beak"></div>
                </div>
            </div>
        </div>

        <!-- Campfire -->
        <div class="campfire">
            <div class="ground-glow"></div>

            <div class="logs">
                <div class="log"></div>
                <div class="log"></div>
                <div class="log"></div>
            </div>

            <div class="ember-glow"></div>

            <div class="flame">
                <div class="flame-outer"></div>
                <div class="flame-middle"></div>
                <div class="flame-inner"></div>
            </div>

            <div class="sparks" id="sparks">
                <div class="spark"></div>
                <div class="spark"></div>
                <div class="spark"></div>
                <div class="spark"></div>
                <div class="spark"></div>
                <div class="spark"></div>
                <div class="spark"></div>
                <div class="spark"></div>
            </div>
        </div>

        <div class="screen-glow"></div>

        <div class="login-overlay p-8 rounded-lg shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold login-title mb-2">PRCF INDONESIA Financial</h1>
            <p class="login-text">Sistem Manajemen Keuangan</p>
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
                    <label class="block login-text text-sm font-medium mb-2">Email atau Nomor HP</label>
                    <input type="text" name="identifier" required
                        class="login-input w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Masukkan email atau nomor HP">
                </div>

                <div>
                    <label class="block login-text text-sm font-medium mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="login-input w-full px-4 py-2 pr-12 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Masukkan password">
                        <button type="button" id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-black hover:text-gray-700 transition-colors duration-200">
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
                    class="login-button w-full text-white py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
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
    </div>

    <script>
    // Utility
    function randomRange(min, max) {
      return Math.random() * (max - min) + min;
    }

    // Sparks rising from fire
    const sparks = document.querySelectorAll('.spark');

    sparks.forEach((spark, index) => {
      const duration = randomRange(1.8, 2.8);
      const delay = index * randomRange(0.1, 0.35);
      const horizontalDrift = (Math.random() < 0.5 ? -1 : 1) * randomRange(10, 35);
      const verticalRise = randomRange(90, 160);

      const keyframesName = `sparkRise_${index}`;
      const style = document.createElement('style');

      style.textContent = `
        @keyframes ${keyframesName} {
          0% {
            transform: translate(0, 0) scale(0.25);
            opacity: 0;
          }
          8% {
            opacity: 1;
          }
          40% {
            opacity: 1;
          }
          100% {
            transform: translate(${horizontalDrift}px, -${verticalRise}px) scale(0.65);
            opacity: 0;
          }
        }
      `;
      document.head.appendChild(style);

      spark.style.left = `${randomRange(-14, 14)}px`;
      spark.style.animation = `${keyframesName} ${duration}s linear infinite`;
      spark.style.animationDelay = `${delay}s`;
    });

    // Shooting stars: multiple trails across the sky
    const shootingStars = document.querySelectorAll('.shooting-star');

    shootingStars.forEach((star, index) => {
      const createAnimation = () => {
        const startX = randomRange(5, 85);      // viewport width %
        const startY = randomRange(3, 25);      // viewport height %
        const distance = randomRange(12, 26);   // trail length as vw
        const duration = randomRange(1.2, 2.4);
        const delay = randomRange(2, 10);       // time between passes

        const angle = randomRange(-18, -28);    // diagonal downward-left
        const id = `shoot_${index}_${Math.floor(Math.random() * 99999)}`;

        const style = document.createElement('style');
        style.textContent = `
          @keyframes ${id} {
            0% {
              opacity: 0;
              transform: translate(0,0) rotate(${angle}deg);
            }
            10% {
              opacity: 1;
            }
            70% {
              opacity: 1;
            }
            100% {
              opacity: 0;
              transform: translate(-${distance}vw, ${distance * 0.18}vh) rotate(${angle}deg);
            }
          }
        `;
        document.head.appendChild(style);

        star.style.top = `${startY}vh`;
        star.style.left = `${startX}vw`;
        star.style.animation = `${id} ${duration}s ease-in-out ${delay}s forwards`;

        // After animation ends, schedule a new one for continuous random shooting stars
        setTimeout(() => {
          // Clear old animation so we can assign a new one
          star.style.animation = 'none';
          // Short timeout to force reflow
          requestAnimationFrame(createAnimation);
        }, (duration + delay) * 1000 + 200);
      };

      createAnimation();
    });

    // Owl subtle head turning following mouse X
    const owlHead = document.querySelector('.owl-head');
    const owlEyes = document.querySelectorAll('.owl-eye');

    if (owlHead) {
      window.addEventListener('mousemove', (e) => {
        const x = e.clientX / window.innerWidth;
        const offset = (x - 0.5) * 6; // tilt
        owlHead.style.transform = `translateX(-50%) rotate(${offset}deg)`;

        owlEyes.forEach(eye => {
          eye.style.transform = `translateX(${offset * 0.5}px)`;
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
