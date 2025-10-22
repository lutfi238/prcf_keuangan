<?php
session_start();

// Prevent browser caching to fix back button session issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode (admin with whitelisted IP can bypass)
check_maintenance();

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Staff Accountant') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Get user's last notification check time (with error handling if column doesn't exist yet)
// Check if last_notification_check column exists first to avoid error
$check_notif_column = $conn->query("SHOW COLUMNS FROM user LIKE 'last_notification_check'");
if ($check_notif_column && $check_notif_column->num_rows > 0) {
    // Column exists, get the value
    $last_check_query = $conn->query("SELECT last_notification_check FROM user WHERE id_user = {$user_id}");
    $last_check_data = $last_check_query->fetch_assoc();
    $last_notification_check = $last_check_data['last_notification_check'] ?? '1970-01-01 00:00:00';
    
    // DISABLED auto-update to prevent session issues with back button
    // User can manually import SQL to enable read/unread feature
    // @$conn->query("UPDATE user SET last_notification_check = NOW() WHERE id_user = {$user_id}");
} else {
    // Column doesn't exist yet - treat all as unread, skip update
    $last_notification_check = '1970-01-01 00:00:00';
}

// Handle success messages from redirects
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'password_changed':
            $success_message = 'Password berhasil diubah!';
            break;
    }
}

// Get pending financial reports
$reports = $conn->query("SELECT lh.*, u.nama as creator_name 
    FROM laporan_keuangan_header lh 
    LEFT JOIN user u ON lh.created_by = u.id_user 
    WHERE lh.status_lap IN ('submitted', 'verified') 
    ORDER BY lh.created_at DESC");

// Get notifications for SA
$notif_pending_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'submitted'")->fetch_assoc()['count'];
$total_notifications = $notif_pending_reports;

// Get recent notifications with details
$notifications = [];

// Add pending report notifications
$pending_reports = $conn->query("SELECT id_laporan_keu, nama_kegiatan, created_at, created_by 
    FROM laporan_keuangan_header 
    WHERE status_lap = 'submitted' 
    ORDER BY created_at DESC LIMIT 10");
while ($row = $pending_reports->fetch_assoc()) {
    $is_unread = (strtotime($row['created_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'report',
        'id' => $row['id_laporan_keu'],
        'title' => 'Laporan baru perlu validasi: ' . $row['nama_kegiatan'],
        'link' => '../reports/validate_report.php?id=' . $row['id_laporan_keu'],
        'time' => time_elapsed_string($row['created_at']),
        'is_unread' => $is_unread
    ];
}

// Function to calculate time elapsed
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}

// Close session writing to ensure session is fully saved before HTML output
// This prevents session conflicts when user clicks notification links
session_write_close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Staff Accounting - PRCFI</title>
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
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
                    
                    <!-- Notifications -->
                    <div class="relative" id="notificationDropdown">
                        <button type="button" onclick="toggleNotifications()" class="notification-bell-button relative p-2 text-gray-600 hover:text-gray-800" aria-label="Buka Notifikasi">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if ($total_notifications > 0): ?>
                                <span class="notification-badge absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                    <?php echo $total_notifications > 9 ? '9+' : $total_notifications; ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        
                        <div id="notificationPanel" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="font-bold text-gray-800">Notifikasi</h3>
                                <?php if ($total_notifications > 0): ?>
                                    <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full"><span class="notification-count-text"><?php echo $total_notifications; ?></span> baru</span>
                                <?php endif; ?>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <?php if (empty($notifications)): ?>
                                    <div class="p-4 text-center text-gray-500 text-sm">
                                        <i class="fas fa-inbox text-3xl mb-2"></i>
                                        <p>Tidak ada notifikasi</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <a href="<?php echo $notif['link']; ?>" 
                                           class="block p-4 border-b border-gray-100 transition <?php echo $notif['is_unread'] ? 'bg-blue-50 hover:bg-blue-100' : 'bg-white hover:bg-gray-50'; ?>"
                                           onclick="closeNotificationsPanel()">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 mr-3">
                                                    <i class="fas fa-chart-line text-blue-500 text-lg"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm <?php echo $notif['is_unread'] ? 'text-gray-900 font-bold' : 'text-gray-700 font-normal'; ?>">
                                                        <?php echo $notif['title']; ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <i class="far fa-clock mr-1"></i><?php echo $notif['time']; ?>
                                                    </p>
                                                </div>
                                                <?php if ($notif['is_unread']): ?>
                                                <div class="flex-shrink-0 ml-2">
                                                    <span class="w-2 h-2 bg-blue-600 rounded-full inline-block"></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile -->
                    <div class="relative" id="profileDropdown">
                        <button onclick="toggleProfile()" class="flex items-center space-x-2">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=10B981&color=fff" 
                                class="w-10 h-10 rounded-full border-2 border-green-400">
                        </button>
                        
                        <div id="profilePanel" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <a href="../profile/profile.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user mr-2"></i> Edit Profil
                            </a>
                            <a href="../../auth/logout.php" class="block px-4 py-3 text-red-600 hover:bg-gray-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($success_message): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?></span>
        </div>
        <?php endif; ?>
        
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Selamat Datang, <?php echo $user_name; ?></h2>
            <p class="text-gray-600">Dashboard Staff Accounting</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Menunggu Validasi</p>
                        <p class="text-3xl font-bold text-gray-800">5</p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Telah Divalidasi</p>
                        <p class="text-3xl font-bold text-gray-800">12</p>
                    </div>
                    <div class="bg-green-500 p-3 rounded-full">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-lg border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Perlu Revisi</p>
                        <p class="text-3xl font-bold text-gray-800">3</p>
                    </div>
                    <div class="bg-yellow-500 p-3 rounded-full">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Reports Table -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Laporan Keuangan Masuk</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kegiatan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proyek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat Oleh</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $no = 1;
                        while ($report = $reports->fetch_assoc()): 
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $report['nama_kegiatan']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $report['kode_projek']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $report['creator_name']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('d/m/Y', strtotime($report['tanggal_laporan'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($report['status_lap'] === 'submitted'): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Menunggu Validasi
                                    </span>
                                <?php elseif ($report['status_lap'] === 'verified'): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Tervalidasi
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="../reports/validate_report.php?id=<?php echo $report['id_laporan_keu']; ?>" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye mr-1"></i> Review
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleNotifications() {
            const panel = document.getElementById('notificationPanel');
            const profilePanel = document.getElementById('profilePanel');
            if (profilePanel) profilePanel.classList.add('hidden');
            if (panel) panel.classList.toggle('hidden');
        }

        function closeNotificationsPanel() {
            const panel = document.getElementById('notificationPanel');
            if (panel) panel.classList.add('hidden');
        }

        function toggleProfile() {
            const panel = document.getElementById('profilePanel');
            const notifPanel = document.getElementById('notificationPanel');
            if (notifPanel) notifPanel.classList.add('hidden');
            if (panel) panel.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const notifDropdown = document.getElementById('notificationDropdown');
            const profileDropdown = document.getElementById('profileDropdown');
            
            if (notifDropdown && !notifDropdown.contains(event.target)) {
                const notifPanel = document.getElementById('notificationPanel');
                if (notifPanel) notifPanel.classList.add('hidden');
            }
            if (profileDropdown && !profileDropdown.contains(event.target)) {
                const profilePanel = document.getElementById('profilePanel');
                if (profilePanel) profilePanel.classList.add('hidden');
            }
        });

        const notifPanelEl = document.getElementById('notificationPanel');
        if (notifPanelEl) {
            notifPanelEl.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        const notifButtonEl = document.querySelector('.notification-bell-button');
        if (notifButtonEl) {
            notifButtonEl.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    </script>
    
    <!-- Real-time Notifications -->
    <script src="../../assets/js/realtime_notifications.js"></script>
</body>
</html>