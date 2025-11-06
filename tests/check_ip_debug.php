<?php
/**
 * CHECK IP & MAINTENANCE DEBUG
 * =============================
 * 
 * Buka file ini via NGROK untuk lihat IP dan status maintenance
 */

require_once '../includes/maintenance_config.php';

// Get all possible IP addresses
$ip_sources = [
    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'not set',
    'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? 'not set',
    'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set',
    'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? 'not set',
    'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'not set',
];

$is_maintenance = MAINTENANCE_MODE;
$is_whitelisted = is_ip_whitelisted();
$will_redirect = is_maintenance_active();
$current_file = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP & Maintenance Debug</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-center">🔍 IP & Maintenance Debug</h1>

        <!-- Critical Status -->
        <div class="grid md:grid-cols-3 gap-4 mb-6">
            <div class="bg-<?php echo $is_maintenance ? 'yellow' : 'green'; ?>-500 p-4 rounded-lg text-center">
                <div class="text-4xl mb-2"><?php echo $is_maintenance ? '⚠️' : '✅'; ?></div>
                <div class="font-bold">Maintenance Mode</div>
                <code class="text-sm"><?php echo $is_maintenance ? 'TRUE' : 'FALSE'; ?></code>
            </div>
            <div class="bg-<?php echo $is_whitelisted ? 'blue' : 'red'; ?>-500 p-4 rounded-lg text-center">
                <div class="text-4xl mb-2"><?php echo $is_whitelisted ? '👨‍💼' : '🚫'; ?></div>
                <div class="font-bold">IP Whitelisted</div>
                <code class="text-sm"><?php echo $is_whitelisted ? 'YES' : 'NO'; ?></code>
            </div>
            <div class="bg-<?php echo $will_redirect ? 'red' : 'green'; ?>-500 p-4 rounded-lg text-center">
                <div class="text-4xl mb-2"><?php echo $will_redirect ? '🚫' : '✅'; ?></div>
                <div class="font-bold">Will Redirect?</div>
                <code class="text-sm"><?php echo $will_redirect ? 'YES' : 'NO'; ?></code>
            </div>
        </div>

        <!-- IP Detection -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4 text-yellow-400">🌐 Your IP Addresses</h2>
            <div class="space-y-3">
                <?php foreach ($ip_sources as $source => $ip): ?>
                    <div class="bg-gray-700 p-4 rounded flex justify-between items-center">
                        <span class="font-mono text-sm text-gray-400"><?php echo $source; ?></span>
                        <code class="font-bold text-lg <?php echo $ip === 'not set' ? 'text-gray-500' : 'text-cyan-400'; ?>">
                            <?php echo $ip; ?>
                        </code>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Whitelist IPs -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4 text-green-400">✅ Whitelisted IPs</h2>
            <div class="space-y-2">
                <?php 
                global $MAINTENANCE_WHITELIST_IPS;
                foreach ($MAINTENANCE_WHITELIST_IPS as $ip): 
                ?>
                    <div class="bg-gray-700 p-3 rounded font-mono text-green-300">
                        → <?php echo $ip; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Session Check -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4 text-purple-400">🔐 Session Status</h2>
            <div class="space-y-2">
                <?php
                session_start();
                $session_keys = ['logged_in', 'user_id', 'user_role', 'user_name'];
                foreach ($session_keys as $key):
                    $value = isset($_SESSION[$key]) ? $_SESSION[$key] : 'NOT SET';
                    $is_set = isset($_SESSION[$key]);
                ?>
                    <div class="bg-gray-700 p-3 rounded flex justify-between">
                        <span class="text-gray-400"><?php echo $key; ?></span>
                        <code class="<?php echo $is_set ? 'text-yellow-300' : 'text-gray-500'; ?>">
                            <?php echo is_bool($value) ? ($value ? 'true' : 'false') : $value; ?>
                        </code>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Logic Explanation -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4 text-red-400">🔍 Why Not Redirecting?</h2>
            <div class="space-y-3">
                <?php if (!$is_maintenance): ?>
                    <div class="bg-yellow-900 border-l-4 border-yellow-400 p-4">
                        <strong class="text-yellow-400">❌ MAINTENANCE_MODE = FALSE</strong>
                        <p class="text-sm mt-2">Maintenance mode tidak aktif! Set ke TRUE di maintenance_config.php</p>
                    </div>
                <?php else: ?>
                    <div class="bg-green-900 border-l-4 border-green-400 p-4">
                        <strong class="text-green-400">✅ MAINTENANCE_MODE = TRUE</strong>
                        <p class="text-sm mt-2">Maintenance mode sudah aktif</p>
                    </div>
                <?php endif; ?>

                <?php if ($is_whitelisted): ?>
                    <div class="bg-blue-900 border-l-4 border-blue-400 p-4">
                        <strong class="text-blue-400">👨‍💼 IP WHITELISTED</strong>
                        <p class="text-sm mt-2">IP kamu ada di whitelist, jadi bypass maintenance mode!</p>
                        <p class="text-xs text-gray-400 mt-2">
                            Current IP: <code><?php echo $_SERVER['REMOTE_ADDR']; ?></code> matches whitelist
                        </p>
                    </div>
                <?php else: ?>
                    <div class="bg-red-900 border-l-4 border-red-400 p-4">
                        <strong class="text-red-400">🚫 IP NOT WHITELISTED</strong>
                        <p class="text-sm mt-2">IP tidak di whitelist, SHOULD redirect ke maintenance!</p>
                    </div>
                <?php endif; ?>

                <?php if ($current_file === 'check_ip_debug.php'): ?>
                    <div class="bg-purple-900 border-l-4 border-purple-400 p-4">
                        <strong class="text-purple-400">ℹ️ DEBUG FILE</strong>
                        <p class="text-sm mt-2">File ini tidak ada check_maintenance(), jadi tidak redirect.</p>
                        <p class="text-sm mt-1">Test redirect di: <a href="login.php" class="text-cyan-400 underline">login.php</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Test Links -->
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4 text-cyan-400">🧪 Test Links</h2>
            <div class="space-y-2">
                <a href="login.php" class="block bg-blue-600 hover:bg-blue-700 p-4 rounded text-center font-bold transition">
                    → Test Login Page (should redirect if maintenance ON & not whitelisted)
                </a>
                <a href="register.php" class="block bg-green-600 hover:bg-green-700 p-4 rounded text-center font-bold transition">
                    → Test Register Page (should redirect if maintenance ON & not whitelisted)
                </a>
                <a href="maintenance.php" class="block bg-yellow-600 hover:bg-yellow-700 p-4 rounded text-center font-bold transition">
                    → View Maintenance Page (always accessible)
                </a>
            </div>
        </div>

        <!-- Solution -->
        <div class="mt-6 bg-red-900 border-2 border-red-500 rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4 text-red-300">💡 SOLUTION</h2>
            <div class="space-y-3 text-sm">
                <div class="bg-gray-800 p-4 rounded">
                    <strong class="text-yellow-400">Problem: Ngrok tidak redirect ke maintenance</strong>
                    <p class="mt-2">Possible causes:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1 text-gray-300">
                        <li>Apache belum di-restart setelah update files</li>
                        <li>Browser cache (ngrok session cache)</li>
                        <li>Already logged in (session masih aktif)</li>
                        <li>IP somehow masuk whitelist</li>
                    </ul>
                </div>
                <div class="bg-gray-800 p-4 rounded">
                    <strong class="text-green-400">Fix:</strong>
                    <ol class="list-decimal list-inside mt-2 space-y-1 text-gray-300">
                        <li>RESTART APACHE (stop → start di XAMPP)</li>
                        <li>Logout dulu (clear session): <a href="logout.php" class="text-cyan-400 underline">logout.php</a></li>
                        <li>Clear browser cache (Ctrl + Shift + R)</li>
                        <li>Test lagi via ngrok URL</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm mt-8 pb-4">
            File: <?php echo __FILE__; ?><br>
            Time: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>

