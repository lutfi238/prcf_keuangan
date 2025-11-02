<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check admin access
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_maintenance'])) {
        $new_status = ($_POST['maintenance_status'] === 'true');
        
        // Update maintenance config file
        $config_content = file_get_contents('../../includes/maintenance_config.php');
        $config_content = preg_replace(
            '/define\(\'MAINTENANCE_MODE\', (true|false)\);/',
            "define('MAINTENANCE_MODE', " . ($new_status ? 'true' : 'false') . ");",
            $config_content
        );
        
        if (file_put_contents('../../includes/maintenance_config.php', $config_content)) {
            $success_message = $new_status ? 
                "✅ Maintenance Mode ACTIVATED! Website is now offline for public." : 
                "✅ Maintenance Mode DEACTIVATED! Website is now online.";
            error_log("ADMIN ACTION: User '{$user_name}' " . ($new_status ? 'enabled' : 'disabled') . " maintenance mode");
        } else {
            $error_message = "❌ Failed to update maintenance config file. Check file permissions.";
        }
    }
}

// Get current maintenance status
$maintenance_enabled = defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Control - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_admin.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">System Control Panel</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <span><?php echo $success_message; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <span><?php echo $error_message; ?></span>
        </div>
        <?php endif; ?>

        <!-- Current Status Overview -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i> Current System Status
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Maintenance Mode Status -->
                <div class="p-4 rounded-lg border-2 <?php echo $maintenance_enabled ? 'border-red-300 bg-red-50' : 'border-green-300 bg-green-50'; ?>">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800 mb-1">Maintenance Mode</h3>
                            <p class="text-sm text-gray-600">Website accessibility</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl mb-1">
                                <?php echo $maintenance_enabled ? '🔴' : '🟢'; ?>
                            </div>
                            <span class="text-sm font-semibold <?php echo $maintenance_enabled ? 'text-red-700' : 'text-green-700'; ?>">
                                <?php echo $maintenance_enabled ? 'OFFLINE' : 'ONLINE'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="p-4 rounded-lg border-2 border-blue-300 bg-blue-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800 mb-1">Active Users</h3>
                            <p class="text-sm text-gray-600">Currently logged in</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold text-blue-700 mb-1">
                                <?php 
                                // Count active sessions (simplified - you can improve this)
                                echo "1+";
                                ?>
                            </div>
                            <span class="text-sm font-semibold text-blue-700">Users</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Mode Control -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-tools text-purple-600 mr-2"></i> Maintenance Mode Control
            </h2>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-yellow-800">Important Notice</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p class="mb-2">When Maintenance Mode is enabled:</p>
                            <ul class="list-disc list-inside space-y-1 ml-2">
                                <li>Public users will see maintenance page (animated)</li>
                                <li>All non-admin users will be logged out</li>
                                <li>Only Admin can access the system</li>
                                <li>Use this when updating database or fixing critical bugs</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h3 class="font-bold text-gray-800 mb-1">Current Status:</h3>
                        <p class="text-sm text-gray-600">
                            <?php echo $maintenance_enabled ? 
                                '🔴 Website is currently in maintenance mode' : 
                                '🟢 Website is currently accessible to all users'; 
                            ?>
                        </p>
                    </div>
                    <button type="button" 
                            onclick="toggleMaintenanceConfirm(<?php echo $maintenance_enabled ? 'false' : 'true'; ?>)"
                            class="px-6 py-3 rounded-lg font-bold text-white transition duration-200 <?php echo $maintenance_enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'; ?>">
                        <i class="fas <?php echo $maintenance_enabled ? 'fa-play' : 'fa-pause'; ?> mr-2"></i>
                        <?php echo $maintenance_enabled ? 'Enable Website' : 'Enable Maintenance'; ?>
                    </button>
                </div>

                <!-- Hidden form for submission -->
                <input type="hidden" name="maintenance_status" id="maintenance_status" value="">
                <input type="hidden" name="toggle_maintenance" value="1">
            </form>
        </div>

        <!-- System Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-server text-green-600 mr-2"></i> Quick System Info
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">PHP Version:</span>
                    <span class="font-semibold text-gray-800"><?php echo phpversion(); ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Server Software:</span>
                    <span class="font-semibold text-gray-800"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Database:</span>
                    <span class="font-semibold text-gray-800">MySQL <?php echo $conn->server_info; ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Server Time:</span>
                    <span class="font-semibold text-gray-800"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
            </div>

            <div class="mt-6">
                <a href="system_health.php" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <i class="fas fa-heartbeat mr-2"></i> View Detailed System Health
                </a>
            </div>
        </div>
    </main>

    <script>
        function toggleMaintenanceConfirm(enable) {
            const action = enable ? 'ENABLE' : 'DISABLE';
            const message = enable ? 
                '⚠️ Are you sure you want to ENABLE Maintenance Mode?\n\n' +
                '• All users (except Admin) will be logged out\n' +
                '• Public will see maintenance page\n' +
                '• Website will be offline' :
                '✅ Are you sure you want to DISABLE Maintenance Mode?\n\n' +
                '• Website will be accessible to all users\n' +
                '• Public can access login page';
            
            if (confirm(message)) {
                document.getElementById('maintenance_status').value = enable;
                document.querySelector('form').submit();
            }
        }
    </script>
</body>
</html>

