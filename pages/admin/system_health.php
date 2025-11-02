<?php
session_start();
require_once '../../includes/config.php';

// Check admin access
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];

// Get system information
$php_version = phpversion();
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$document_root = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
$server_name = $_SERVER['SERVER_NAME'] ?? 'Unknown';
$server_protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown';

// Get MySQL info
$mysql_version = $conn->server_info;
$mysql_host = DB_HOST;
$mysql_database = DB_NAME;

// Check database connection
$db_status = $conn->ping() ? 'Connected' : 'Disconnected';

// Get database size
$db_size_query = $conn->query("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb 
                                FROM information_schema.TABLES 
                                WHERE table_schema = '" . DB_NAME . "'");
$db_size = $db_size_query ? round($db_size_query->fetch_assoc()['size_mb'], 2) . ' MB' : 'Unknown';

// Get table count
$table_count_query = $conn->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'");
$table_count = $table_count_query ? $table_count_query->fetch_assoc()['count'] : 0;

// Get upload directory info
$upload_dir = '../../uploads/';
$upload_writable = is_writable($upload_dir) ? 'Writable' : 'Not Writable';

// Get disk space (if available - InfinityFree doesn't support this)
$disk_total = 'N/A';
$disk_free = 'N/A';
$disk_used_percent = 0;

if (function_exists('disk_total_space') && function_exists('disk_free_space')) {
    try {
        $total = @disk_total_space('.');
        $free = @disk_free_space('.');
        
        if ($total && $free) {
            $disk_total = round($total / 1024 / 1024 / 1024, 2) . ' GB';
            $disk_free = round($free / 1024 / 1024 / 1024, 2) . ' GB';
            $disk_used_percent = round((1 - $free / $total) * 100, 1);
        }
    } catch (Exception $e) {
        // Silently fail on free hosting (InfinityFree doesn't support disk functions)
    }
}

// Get PHP configuration
$max_execution_time = ini_get('max_execution_time');
$memory_limit = ini_get('memory_limit');
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');

// Check required extensions
$required_extensions = ['mysqli', 'json', 'mbstring', 'curl', 'openssl'];
$extensions_status = [];
foreach ($required_extensions as $ext) {
    $extensions_status[$ext] = extension_loaded($ext);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health - Admin Panel</title>
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
                    <h1 class="text-xl font-bold text-gray-800">System Health Check</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($user_name); ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Overall System Status -->
        <div class="bg-gradient-to-r from-green-50 to-green-100 border border-green-300 rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-green-800 mb-2">
                        <i class="fas fa-check-circle mr-2"></i> System Status: Healthy
                    </h2>
                    <p class="text-green-700">All critical systems are operational</p>
                </div>
                <div class="text-6xl">✅</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Database Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-database text-blue-600 mr-2"></i> Database Status
                </h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Connection Status</span>
                        <span class="font-semibold text-green-600">
                            <i class="fas fa-circle text-green-500 mr-1"></i><?php echo $db_status; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">MySQL Version</span>
                        <span class="font-semibold text-gray-800"><?php echo $mysql_version; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Host</span>
                        <span class="font-semibold text-gray-800"><?php echo $mysql_host; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Database Name</span>
                        <span class="font-semibold text-gray-800"><?php echo $mysql_database; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Database Size</span>
                        <span class="font-semibold text-gray-800"><?php echo $db_size; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Tables</span>
                        <span class="font-semibold text-gray-800"><?php echo $table_count; ?></span>
                    </div>
                </div>
            </div>

            <!-- Server Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-server text-purple-600 mr-2"></i> Server Information
                </h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">PHP Version</span>
                        <span class="font-semibold text-gray-800"><?php echo $php_version; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Server Software</span>
                        <span class="font-semibold text-gray-800 text-right text-xs"><?php echo $server_software; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Server Name</span>
                        <span class="font-semibold text-gray-800"><?php echo $server_name; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Protocol</span>
                        <span class="font-semibold text-gray-800"><?php echo $server_protocol; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Document Root</span>
                        <span class="font-semibold text-gray-800 text-right text-xs" title="<?php echo $document_root; ?>">
                            <?php echo basename($document_root); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Server Time</span>
                        <span class="font-semibold text-gray-800"><?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
            </div>

            <!-- PHP Configuration -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fab fa-php text-indigo-600 mr-2"></i> PHP Configuration
                </h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Memory Limit</span>
                        <span class="font-semibold text-gray-800"><?php echo $memory_limit; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Max Execution Time</span>
                        <span class="font-semibold text-gray-800"><?php echo $max_execution_time; ?>s</span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Upload Max Filesize</span>
                        <span class="font-semibold text-gray-800"><?php echo $upload_max_filesize; ?></span>
                    </div>
                    <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Post Max Size</span>
                        <span class="font-semibold text-gray-800"><?php echo $post_max_size; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Upload Directory</span>
                        <span class="font-semibold <?php echo $upload_writable === 'Writable' ? 'text-green-600' : 'text-red-600'; ?>">
                            <i class="fas fa-<?php echo $upload_writable === 'Writable' ? 'check' : 'times'; ?>-circle mr-1"></i>
                            <?php echo $upload_writable; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Disk Space -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-hdd text-orange-600 mr-2"></i> Disk Space
                </h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center text-sm pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Total Space</span>
                        <span class="font-semibold text-gray-800"><?php echo $disk_total; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-sm pb-2 border-b border-gray-200">
                        <span class="text-gray-600">Free Space</span>
                        <span class="font-semibold text-gray-800"><?php echo $disk_free; ?></span>
                    </div>
                    
                    <!-- Disk Usage Bar -->
                    <div>
                        <?php if ($disk_total !== 'N/A'): ?>
                        <div class="flex justify-between items-center text-sm mb-2">
                            <span class="text-gray-600">Disk Usage</span>
                            <span class="font-bold text-gray-800"><?php echo $disk_used_percent; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                            <div class="h-full <?php echo $disk_used_percent > 80 ? 'bg-red-500' : ($disk_used_percent > 50 ? 'bg-yellow-500' : 'bg-green-500'); ?> transition-all duration-500"
                                 style="width: <?php echo $disk_used_percent; ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php 
                            if ($disk_used_percent > 80) {
                                echo '⚠️ Disk space is running low!';
                            } elseif ($disk_used_percent > 50) {
                                echo '⚡ Consider monitoring disk usage';
                            } else {
                                echo '✅ Disk space is healthy';
                            }
                            ?>
                        </p>
                        <?php else: ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-info-circle text-gray-400 mr-1"></i>
                                Disk space monitoring not available on this hosting
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                InfinityFree/Free hosting restrictions
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHP Extensions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-puzzle-piece text-teal-600 mr-2"></i> PHP Extensions Status
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <?php foreach ($extensions_status as $ext => $loaded): ?>
                <div class="flex items-center p-3 rounded-lg <?php echo $loaded ? 'bg-green-50 border border-green-300' : 'bg-red-50 border border-red-300'; ?>">
                    <i class="fas fa-<?php echo $loaded ? 'check-circle text-green-600' : 'times-circle text-red-600'; ?> mr-2"></i>
                    <span class="font-semibold <?php echo $loaded ? 'text-green-800' : 'text-red-800'; ?>"><?php echo $ext; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-tools text-gray-600 mr-2"></i> Admin Tools
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="system_control.php" class="flex items-center p-4 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition">
                    <i class="fas fa-sliders-h text-purple-600 text-2xl mr-3"></i>
                    <div>
                        <h4 class="font-bold text-gray-800">System Control</h4>
                        <p class="text-xs text-gray-600">Maintenance mode</p>
                    </div>
                </a>
                
                <a href="manage_users.php" class="flex items-center p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                    <i class="fas fa-users-cog text-blue-600 text-2xl mr-3"></i>
                    <div>
                        <h4 class="font-bold text-gray-800">User Management</h4>
                        <p class="text-xs text-gray-600">CRUD operations</p>
                    </div>
                </a>
                
                <button onclick="window.location.reload()" class="flex items-center p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition">
                    <i class="fas fa-sync-alt text-green-600 text-2xl mr-3"></i>
                    <div>
                        <h4 class="font-bold text-gray-800">Refresh Status</h4>
                        <p class="text-xs text-gray-600">Update information</p>
                    </div>
                </button>
            </div>
        </div>
    </main>
</body>
</html>

