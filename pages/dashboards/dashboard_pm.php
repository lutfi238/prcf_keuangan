<?php
session_start();

// Prevent browser caching to fix back button session issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';
require_once '../../includes/session_sync.php';

// Check maintenance mode (admin with whitelisted IP can bypass)
check_maintenance();

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Project Manager') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

// Sync session with database to ensure name is always up-to-date
sync_session_with_database($conn);

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Get user's last notification check time (with error handling if column doesn't exist yet)
// Check if last_notification_check column exists first to avoid error
$check_notif_column = $conn->query("SHOW COLUMNS FROM user LIKE 'last_notification_check'");
if ($check_notif_column && $check_notif_column->num_rows > 0) {
    // Column exists, get the value using prepared statement
    $stmt = $conn->prepare("SELECT last_notification_check FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $last_check_query = $stmt->get_result();
    $last_check_data = $last_check_query->fetch_assoc();
    $last_notification_check = $last_check_data['last_notification_check'] ?? '1970-01-01 00:00:00';
    $stmt->close();
    
    // DISABLED auto-update to prevent session issues with back button
    // User can manually import SQL to enable read/unread feature
    // @$conn->query("UPDATE user SET last_notification_check = NOW() WHERE id_user = {$user_id}");
} else {
    // Column doesn't exist yet - treat all as unread, skip update
    $last_notification_check = '1970-01-01 00:00:00';
}

// Get proposals created by this PM - using prepared statement
$stmt = $conn->prepare("SELECT p.*, pr.nama_proyek
    FROM proposal p
    LEFT JOIN proyek pr ON p.kode_proyek = pr.kode_proyek
    WHERE p.pemohon = ?
    ORDER BY p.created_at DESC");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$proposals = $stmt->get_result();

// Get notifications for PM - using prepared statements
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposal WHERE pemohon = ? AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$notif_approved_proposals = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposal WHERE pemohon = ? AND status = 'rejected' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$notif_rejected_proposals = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE created_by = ? AND status_lap = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notif_approved_reports = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE created_by = ? AND status_lap = 'revision_requested' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notif_revision_reports = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE created_by = ? AND status_lap IN ('verified', 'approved', 'revision_requested') AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notif_reports = $stmt->get_result()->fetch_assoc()['count'];

$total_notifications = $notif_approved_proposals + $notif_rejected_proposals + $notif_approved_reports + $notif_revision_reports;

// Get recent notifications with details
$notifications = [];

// Add approved proposal notifications
$stmt = $conn->prepare("SELECT id_proposal, judul_proposal, updated_at
    FROM proposal
    WHERE pemohon = ? AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY updated_at DESC LIMIT 5");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$approved_proposals = $stmt->get_result();
while ($row = $approved_proposals->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'success',
        'id' => $row['id_proposal'],
        'title' => 'Proposal disetujui: ' . $row['judul_proposal'],
        'link' => '../proposals/view_proposal.php?id=' . $row['id_proposal'],
        'time' => time_elapsed_string($row['updated_at']),
        'is_unread' => $is_unread
    ];
}

// Add rejected proposal notifications
$stmt = $conn->prepare("SELECT id_proposal, judul_proposal, updated_at
    FROM proposal
    WHERE pemohon = ? AND status = 'rejected' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY updated_at DESC LIMIT 5");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$rejected_proposals = $stmt->get_result();
while ($row = $rejected_proposals->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'rejected',
        'id' => $row['id_proposal'],
        'title' => 'Proposal ditolak: ' . $row['judul_proposal'],
        'link' => '../proposals/view_proposal.php?id=' . $row['id_proposal'],
        'time' => time_elapsed_string($row['updated_at']),
        'is_unread' => $is_unread
    ];
}

// Add revision report notifications
$stmt = $conn->prepare("SELECT id_laporan_keu, nama_projek, updated_at
    FROM laporan_keuangan_header
    WHERE created_by = ? AND status_lap = 'revision_requested' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY updated_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$revision_reports = $stmt->get_result();
while ($row = $revision_reports->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'rejected',
        'id' => $row['id_laporan_keu'],
        'title' => 'Laporan perlu revisi: ' . $row['nama_projek'],
        'link' => '../reports/edit_financial_report.php?id=' . $row['id_laporan_keu'],
        'time' => time_elapsed_string($row['updated_at']),
        'is_unread' => $is_unread
    ];
}

// Add approved report notifications
$stmt = $conn->prepare("SELECT id_laporan_keu, nama_projek, updated_at
    FROM laporan_keuangan_header
    WHERE created_by = ? AND status_lap = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY updated_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$approved_reports = $stmt->get_result();
while ($row = $approved_reports->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'success',
        'id' => $row['id_laporan_keu'],
        'title' => 'Laporan disetujui: ' . $row['nama_projek'],
        'link' => '../reports/approve_report.php?id=' . $row['id_laporan_keu'],
        'time' => time_elapsed_string($row['updated_at']),
        'is_unread' => $is_unread
    ];
}

// Function to calculate time elapsed
function time_elapsed_string($datetime) {
    if (!$datetime) return 'Baru saja';
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) return $diff->d . ' hari yang lalu';
    if ($diff->h > 0) return $diff->h . ' jam yang lalu';
    if ($diff->i > 0) return $diff->i . ' menit yang lalu';
    return 'Baru saja';
}

// Handle AJAX Panel Requests
if (isset($_GET['ajax_panel']) && $_GET['ajax_panel'] === 'recent_activities') {
    // Get recent activities logic (Copied from below)
    $recent_activities = [];

    // Get recent proposals - using prepared statement
    $stmt = $conn->prepare("SELECT 'proposal' as type, id_proposal as id, judul_proposal as title, status, created_at as activity_date
        FROM proposal
        WHERE pemohon = ?
        ORDER BY created_at DESC
        LIMIT 3");
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $recent_proposals_query = $stmt->get_result();
    if ($recent_proposals_query) {
        while ($row = $recent_proposals_query->fetch_assoc()) $recent_activities[] = $row;
    }
    $stmt->close();

    // Get recent reports - using prepared statement
    $stmt = $conn->prepare("SELECT 'report' as type, id_laporan_keu as id, nama_projek as title, status_lap as status, created_at as activity_date
        FROM laporan_keuangan_header
        WHERE created_by = ?
        ORDER BY created_at DESC
        LIMIT 3");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_reports_query = $stmt->get_result();
    if ($recent_reports_query) {
        while ($row = $recent_reports_query->fetch_assoc()) $recent_activities[] = $row;
    }
    $stmt->close();

    // Sort by date descending
    usort($recent_activities, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });

    // Limit to 5 most recent
    $recent_activities = array_slice($recent_activities, 0, 5);
    
    include 'components/list_recent_activities_pm.php';
    exit();
}

// Handle success messages from redirects
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'report_created':
            $success_message = 'Laporan keuangan berhasil dikirimkan ke Staff Accounting!';
            break;
        case 'proposal_created':
            $success_message = 'Proposal berhasil dikirimkan ke Finance Manager!';
            break;
        case 'password_changed':
            $success_message = 'Password berhasil diubah!';
            break;
    }
}

// Get recent activities (proposals and reports combined)
$recent_activities = [];

// Get recent proposals - using prepared statement
$stmt = $conn->prepare("SELECT 'proposal' as type, id_proposal as id, judul_proposal as title, status, created_at as activity_date
    FROM proposal
    WHERE pemohon = ?
    ORDER BY created_at DESC
    LIMIT 3");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$recent_proposals_query = $stmt->get_result();
if ($recent_proposals_query) {
    while ($row = $recent_proposals_query->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}
$stmt->close();

// Get recent reports - using prepared statement
$stmt = $conn->prepare("SELECT 'report' as type, id_laporan_keu as id, nama_projek as title, status_lap as status, created_at as activity_date
    FROM laporan_keuangan_header
    WHERE created_by = ?
    ORDER BY created_at DESC
    LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_reports_query = $stmt->get_result();
if ($recent_reports_query) {
    while ($row = $recent_reports_query->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}
$stmt->close();

// Sort by date descending
usort($recent_activities, function($a, $b) {
    return strtotime($b['activity_date']) - strtotime($a['activity_date']);
});

// Limit to 5 most recent
$recent_activities = array_slice($recent_activities, 0, 5);

// Close session writing to ensure session is fully saved before HTML output
// This prevents session conflicts when user clicks notification links
session_write_close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Project Manager - PRCFI</title>
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
                                                    <?php if ($notif['type'] == 'success'): ?>
                                                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                                                    <?php elseif ($notif['type'] == 'rejected'): ?>
                                                        <i class="fas fa-times-circle text-red-500 text-lg"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                                                    <?php endif; ?>
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
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=3B82F6&color=fff" 
                                class="w-10 h-10 rounded-full border-2 border-blue-400">
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
            <p class="text-gray-600">Dashboard Project Manager</p>
        </div>

        <!-- Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200 hover:shadow-lg transition duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Buat Proposal</h3>
                        <p class="text-sm text-gray-600 mt-1">Ajukan proposal baru ke Finance Manager</p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                </div>
                <a href="../proposals/create_proposal.php" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                    Buat Proposal
                </a>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200 hover:shadow-lg transition duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Buat Laporan Keuangan</h3>
                        <p class="text-sm text-gray-600 mt-1">Kirim laporan ke Staff Accounting</p>
                    </div>
                    <div class="bg-green-500 p-3 rounded-full">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                </div>
                <a href="../reports/create_financial_report.php" class="inline-block bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition duration-200 font-medium">
                    Buat Laporan
                </a>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-history mr-2 text-blue-600"></i>Aktivitas Terbaru
            </h3>
            <div id="recent-activities-list" class="space-y-4">
                <?php include 'components/list_recent_activities_pm.php'; ?>
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

        // Close dropdowns when clicking outside
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

    <script>
        // Real-time Table Updates for PM
        window.addEventListener('dashboard-data-refresh', function(e) {
            console.log('Refreshing PM dashboard activities...');
            
            // Reload Recent Activities
            fetch('dashboard_pm.php?ajax_panel=recent_activities')
                .then(response => response.text())
                .then(html => {
                    const container = document.getElementById('recent-activities-list');
                    if (container) {
                        container.innerHTML = html;
                        // Add subtle flash effect
                        container.parentElement.classList.add('ring-2', 'ring-blue-200');
                        setTimeout(() => container.parentElement.classList.remove('ring-2', 'ring-blue-200'), 1000);
                    }
                })
                .catch(err => console.error('Error reloading activities:', err));
        });
    </script>

    <script>
        // Enhanced real-time updates for dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh notifications when returning from action (success message)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success') || urlParams.has('error')) {
                // Immediately refresh notifications when returning from action
                setTimeout(function() {
                    if (typeof refreshNotifications === 'function') {
                        console.log('Refreshing notifications after action...');
                        refreshNotifications();
                    }
                }, 500); // Small delay to ensure SSE is initialized

                // Clean URL after showing message
                setTimeout(function() {
                    const cleanUrl = window.location.protocol + "//" +
                                    window.location.host +
                                    window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                }, 2000); // Wait 2 seconds for user to see message
            }

            // Auto-refresh dashboard data every 30 seconds as fallback
            // This ensures data stays fresh even if SSE connection drops
            setInterval(function() {
                if (typeof refreshNotifications === 'function') {
                    console.log('Auto-refreshing notifications (30s interval)...');
                    refreshNotifications();
                }
            }, 30000); // 30 seconds
        });
    </script>

    <!-- Real-time Notifications -->
    <script src="../../assets/js/realtime_notifications.js"></script>
</body>
</html>