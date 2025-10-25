<?php
/**
 * TEST MAINTENANCE STATUS
 * ========================
 * 
 * File ini untuk test apakah maintenance mode aktif atau tidak.
 * Buka di browser: http://localhost/prcf_keuangan/test_maintenance_status.php
 */

require_once '../includes/maintenance_config.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Maintenance Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-2xl w-full">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">
            🔧 Maintenance Status Test
        </h1>

        <?php
        $is_maintenance = MAINTENANCE_MODE;
        $is_whitelisted = is_ip_whitelisted();
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $will_redirect = is_maintenance_active();
        ?>

        <!-- Status Cards -->
        <div class="space-y-4">
            <!-- Maintenance Mode -->
            <div class="p-4 rounded-lg border-2 <?php echo $is_maintenance ? 'bg-yellow-50 border-yellow-500' : 'bg-green-50 border-green-500'; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">Maintenance Mode</h3>
                        <p class="text-gray-600 text-sm">Status global maintenance</p>
                    </div>
                    <div class="text-3xl">
                        <?php echo $is_maintenance ? '⚠️' : '✅'; ?>
                    </div>
                </div>
                <div class="mt-3 p-3 bg-white rounded border">
                    <code class="text-sm font-mono">
                        MAINTENANCE_MODE = <span class="font-bold <?php echo $is_maintenance ? 'text-yellow-600' : 'text-green-600'; ?>">
                            <?php echo $is_maintenance ? 'true' : 'false'; ?>
                        </span>
                    </code>
                </div>
            </div>

            <!-- IP Status -->
            <div class="p-4 rounded-lg border-2 <?php echo $is_whitelisted ? 'bg-blue-50 border-blue-500' : 'bg-gray-50 border-gray-300'; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">Your IP Status</h3>
                        <p class="text-gray-600 text-sm">IP whitelist check</p>
                    </div>
                    <div class="text-3xl">
                        <?php echo $is_whitelisted ? '👨‍💼' : '👤'; ?>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    <div class="p-3 bg-white rounded border">
                        <span class="text-sm text-gray-600">Your IP:</span>
                        <code class="ml-2 font-mono font-bold text-blue-600"><?php echo $user_ip; ?></code>
                    </div>
                    <div class="p-3 bg-white rounded border">
                        <span class="text-sm text-gray-600">Whitelisted:</span>
                        <code class="ml-2 font-mono font-bold <?php echo $is_whitelisted ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $is_whitelisted ? 'YES (Admin)' : 'NO (Regular User)'; ?>
                        </code>
                    </div>
                </div>
            </div>

            <!-- Result -->
            <div class="p-4 rounded-lg border-2 <?php echo $will_redirect ? 'bg-red-50 border-red-500' : 'bg-green-50 border-green-500'; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">Final Result</h3>
                        <p class="text-gray-600 text-sm">Akan redirect ke maintenance?</p>
                    </div>
                    <div class="text-3xl">
                        <?php echo $will_redirect ? '🚫' : '✅'; ?>
                    </div>
                </div>
                <div class="mt-3 p-3 bg-white rounded border">
                    <code class="text-sm font-mono">
                        will_redirect = <span class="font-bold <?php echo $will_redirect ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo $will_redirect ? 'YES' : 'NO'; ?>
                        </span>
                    </code>
                </div>
                <div class="mt-2 p-3 bg-gray-100 rounded">
                    <p class="text-sm">
                        <?php if ($will_redirect): ?>
                            <strong class="text-red-600">⚠️ Pages akan redirect ke maintenance.php</strong><br>
                            <span class="text-gray-600">Login, dashboard, dll akan redirect ke halaman maintenance.</span>
                        <?php else: ?>
                            <strong class="text-green-600">✅ Pages berjalan normal</strong><br>
                            <span class="text-gray-600">
                                <?php if ($is_whitelisted): ?>
                                    Kamu admin (whitelisted), bisa bypass maintenance!
                                <?php else: ?>
                                    Maintenance mode OFF, semua user bisa akses.
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Test Links -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-3">🧪 Test Links:</h3>
            <div class="space-y-2">
                <a href="login.php" class="block p-3 bg-white border border-gray-300 rounded hover:bg-blue-50 hover:border-blue-500 transition">
                    <strong>→ Login Page</strong>
                    <span class="text-sm text-gray-600 ml-2">
                        <?php echo $will_redirect ? '(akan redirect ke maintenance)' : '(berjalan normal)'; ?>
                    </span>
                </a>
                <a href="register.php" class="block p-3 bg-white border border-gray-300 rounded hover:bg-blue-50 hover:border-blue-500 transition">
                    <strong>→ Register Page</strong>
                    <span class="text-sm text-gray-600 ml-2">
                        <?php echo $will_redirect ? '(akan redirect ke maintenance)' : '(berjalan normal)'; ?>
                    </span>
                </a>
                <a href="maintenance.php" class="block p-3 bg-white border border-gray-300 rounded hover:bg-blue-50 hover:border-blue-500 transition">
                    <strong>→ Preview Maintenance Page</strong>
                    <span class="text-sm text-gray-600 ml-2">(always accessible)</span>
                </a>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-300 rounded-lg">
            <h3 class="font-bold text-blue-800 mb-2">📝 Cara Enable/Disable:</h3>
            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700">
                <li>Edit: <code class="bg-white px-2 py-1 rounded">maintenance_config.php</code></li>
                <li>Set: <code class="bg-white px-2 py-1 rounded">MAINTENANCE_MODE = true/false</code></li>
                <li>Restart Apache (XAMPP Control Panel)</li>
                <li>Refresh halaman ini untuk lihat perubahan</li>
            </ol>
        </div>
    </div>
</body>
</html>

