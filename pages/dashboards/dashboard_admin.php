<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';
require_once '../../includes/session_sync.php';

// Check maintenance mode
check_maintenance();

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Admin') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

// Sync session with database to ensure name is always up-to-date
sync_session_with_database($conn);

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Get system statistics with error handling
$total_users_result = $conn->query("SELECT COUNT(*) as count FROM user");
$total_users = $total_users_result ? $total_users_result->fetch_assoc()['count'] : 0;

$total_projects_result = $conn->query("SELECT COUNT(*) as count FROM proyek");
$total_projects = $total_projects_result ? $total_projects_result->fetch_assoc()['count'] : 0;

$total_proposals_result = $conn->query("SELECT COUNT(*) as count FROM proposal");
$total_proposals = $total_proposals_result ? $total_proposals_result->fetch_assoc()['count'] : 0;

$total_reports_result = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header");
$total_reports = $total_reports_result ? $total_reports_result->fetch_assoc()['count'] : 0;

// Get user breakdown by role
$users_by_role = [];
$role_query = $conn->query("SELECT role, COUNT(*) as count FROM user GROUP BY role");
if ($role_query) {
    while ($row = $role_query->fetch_assoc()) {
        $users_by_role[$row['role']] = $row['count'];
    }
}

// Get recent users
$recent_users = $conn->query("SELECT id_user, nama, email, role, no_HP as phone, created_at 
    FROM user ORDER BY created_at DESC LIMIT 10");

// If query failed, log error
if (!$recent_users) {
    error_log("Admin dashboard: Recent users query failed - " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PRCF Keuangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">PRCF INDONESIA Financial</h1>
                    <span class="ml-3 px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Admin</span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($user_name); ?></span>
                    
                    <!-- Profile -->
                    <div class="relative" id="profileDropdown">
                        <button onclick="toggleProfile()" class="flex items-center space-x-2">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=DC2626&color=fff" 
                                class="w-10 h-10 rounded-full border-2 border-red-500">
                        </button>
                        
                        <div id="profilePanel" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <a href="../profile/profile.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user mr-2"></i> Edit Profil
                            </a>
                            <hr class="border-gray-200">
                            <a href="../../auth/logout.php" class="block px-4 py-3 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Selamat Datang, <?php echo htmlspecialchars($user_name); ?></h2>
            <p class="text-gray-600">System Administrator Panel</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_users; ?></p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Projects</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_projects; ?></p>
                    </div>
                    <div class="bg-purple-500 p-3 rounded-full">
                        <i class="fas fa-project-diagram text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Proposals</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_proposals; ?></p>
                    </div>
                    <div class="bg-green-500 p-3 rounded-full">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg border border-orange-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Reports</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_reports; ?></p>
                    </div>
                    <div class="bg-orange-500 p-3 rounded-full">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Breakdown by Role -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Users by Role</h3>
            <?php if (!empty($users_by_role)): ?>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <?php foreach ($users_by_role as $role => $count): ?>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-800"><?php echo $count; ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($role); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-info-circle mr-2"></i>No user role data available.
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Users -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Recent Users</h3>
                <a href="../admin/manage_users.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-users-cog mr-2"></i>Manage All Users
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($user['nama']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($user['role']) {
                                            case 'Admin': echo 'bg-red-100 text-red-800'; break;
                                            case 'Direktur': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'Finance Manager': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'Staff Accountant': echo 'bg-green-100 text-green-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo $user['created_at'] ? date('d/m/Y', strtotime($user['created_at'])) : '-'; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-users text-gray-400 text-5xl mb-4"></i>
                                        <p class="text-gray-500 text-lg font-medium mb-2">Belum ada pengguna baru</p>
                                        <p class="text-gray-400 text-sm">Tidak ada pengguna baru yang terdaftar saat ini.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-bolt text-yellow-500 mr-2"></i> Quick Actions
            </h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- System Control Panel -->
            <a href="../admin/system_control.php" class="block p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-purple-300 transition">
                <div class="flex items-center space-x-4">
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-sliders-h text-purple-600 text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">System Control</h4>
                        <p class="text-sm text-gray-600">Toggle Maintenance & Under Construction mode</p>
                    </div>
                </div>
            </a>

            <!-- Manage Villages (Nested under Project Management context) -->
            <a href="../admin/manage_villages.php" class="block p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-orange-300 transition">
                <div class="flex items-center space-x-4">
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-map-marker-alt text-orange-600 text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">Kelola Desa</h4>
                        <p class="text-sm text-gray-600">Manajemen data desa dan deskripsi Expense Code</p>
                    </div>
                </div>
            </a>

            <!-- System Health -->
            <a href="../admin/system_health.php" class="block p-6 bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-green-300 transition">
                <div class="flex items-center space-x-4">
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-heartbeat text-green-600 text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">System Health</h4>
                        <p class="text-sm text-gray-600">View database, server, and PHP info</p>
                    </div>
                </div>
            </a>
        </div>
    </main>

    <script>
        // Toggle Profile Dropdown
        function toggleProfile() {
            const panel = document.getElementById('profilePanel');
            panel.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const profileDropdown = document.getElementById('profileDropdown');
            const profilePanel = document.getElementById('profilePanel');
            
            if (profileDropdown && !profileDropdown.contains(event.target)) {
                profilePanel.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
