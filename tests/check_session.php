<?php
/**
 * SESSION DEBUG CHECKER
 * ====================
 * File ini untuk debug masalah session OTP yang tidak redirect ke dashboard
 * 
 * CARA PAKAI:
 * 1. Login dan verifikasi OTP
 * 2. Jika gagal masuk dashboard, buka file ini di browser
 * 3. Lihat status session yang tersimpan
 */

session_start();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug Checker</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">🔍 Session Debug Checker</h1>
            
            <div class="space-y-4">
                <!-- Session Status -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h2 class="font-semibold text-lg mb-3 text-blue-600">Session Status</h2>
                    <div class="space-y-2 font-mono text-sm">
                        <div class="flex">
                            <span class="w-48 text-gray-600">Session ID:</span>
                            <span class="font-semibold"><?php echo session_id(); ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">Session Name:</span>
                            <span class="font-semibold"><?php echo session_name(); ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">Session Save Path:</span>
                            <span class="font-semibold"><?php echo session_save_path(); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Login Status -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h2 class="font-semibold text-lg mb-3 text-blue-600">Login Status</h2>
                    <div class="space-y-2 font-mono text-sm">
                        <div class="flex">
                            <span class="w-48 text-gray-600">Logged In:</span>
                            <span class="font-semibold <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? '✅ YES' : '❌ NO'; ?>
                            </span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">Pending Login:</span>
                            <span class="font-semibold <?php echo isset($_SESSION['pending_login']) ? 'text-orange-600' : 'text-gray-400'; ?>">
                                <?php echo isset($_SESSION['pending_login']) ? '⏳ YES (Waiting for OTP)' : 'NO'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- User Data -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h2 class="font-semibold text-lg mb-3 text-blue-600">User Data</h2>
                    <div class="space-y-2 font-mono text-sm">
                        <div class="flex">
                            <span class="w-48 text-gray-600">User ID:</span>
                            <span class="font-semibold"><?php echo $_SESSION['user_id'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">User Name:</span>
                            <span class="font-semibold"><?php echo $_SESSION['user_name'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">User Role:</span>
                            <span class="font-semibold"><?php echo $_SESSION['user_role'] ?? 'Not set'; ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">User Email:</span>
                            <span class="font-semibold"><?php echo $_SESSION['user_email'] ?? 'Not set'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- OTP Data -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h2 class="font-semibold text-lg mb-3 text-blue-600">OTP Data</h2>
                    <div class="space-y-2 font-mono text-sm">
                        <div class="flex">
                            <span class="w-48 text-gray-600">OTP:</span>
                            <span class="font-semibold"><?php echo $_SESSION['otp'] ?? 'Not set (cleared after verification)'; ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">OTP Time:</span>
                            <span class="font-semibold"><?php echo isset($_SESSION['otp_time']) ? date('Y-m-d H:i:s', $_SESSION['otp_time']) : 'Not set'; ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-48 text-gray-600">OTP Attempts:</span>
                            <span class="font-semibold"><?php echo $_SESSION['otp_attempts'] ?? '0'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- All Session Data -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h2 class="font-semibold text-lg mb-3 text-blue-600">All Session Data (Raw)</h2>
                    <pre class="bg-gray-50 p-4 rounded text-xs overflow-x-auto"><?php print_r($_SESSION); ?></pre>
                </div>

                <!-- Actions -->
                <div class="flex gap-4 mt-6">
                    <a href="../auth/login.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Go to Login
                    </a>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <?php
                        $redirect = '../pages/dashboards/';
                        switch ($_SESSION['user_role'] ?? '') {
                            case 'Project Manager':  $redirect .= 'dashboard_pm.php'; break;
                            case 'Staff Accountant': $redirect .= 'dashboard_sa.php'; break;
                            case 'Finance Manager':  $redirect .= 'dashboard_fm.php'; break;
                            case 'Direktur':         $redirect .= 'dashboard_dir.php'; break;
                            default:                 $redirect .= 'dashboard_pm.php'; break;
                        }
                        ?>
                        <a href="<?php echo $redirect; ?>" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                            Go to Dashboard
                        </a>
                    <?php endif; ?>
                    <button onclick="location.reload()" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Refresh
                    </button>
                </div>

                <!-- Diagnostic Info -->
                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">🔧 Diagnostic Info</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>✅ <strong>Jika logged_in = YES:</strong> Session berhasil, coba klik "Go to Dashboard"</li>
                        <li>⚠️ <strong>Jika logged_in = NO & pending_login = YES:</strong> Masih menunggu verifikasi OTP</li>
                        <li>❌ <strong>Jika logged_in = NO & pending_login = NO:</strong> Session hilang, harus login ulang</li>
                        <li>💡 <strong>Jika OTP masih ada:</strong> OTP belum diverifikasi atau belum di-clear</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
