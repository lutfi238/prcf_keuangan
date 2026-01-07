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

if ($_SESSION['user_role'] !== 'Direktur') {
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

// Handle AJAX Table Requests
if (isset($_GET['ajax_table'])) {
    if ($_GET['ajax_table'] === 'proposals') {
        // Get proposals logic (Copied from below)
        $check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");
        if ($check_column && $check_column->num_rows > 0) {
            $proposals_result = $conn->query("SELECT p.*, u.nama as creator_name, u2.nama as fm_name
                FROM proposal p 
                LEFT JOIN user u ON p.pemohon = u.nama 
                LEFT JOIN user u2 ON p.approved_by_fm = u2.id_user
                WHERE p.status IN ('approved_fm', 'approved') 
                ORDER BY p.created_at DESC");
        } else {
            $proposals_result = $conn->query("SELECT p.*, u.nama as creator_name
                FROM proposal p 
                LEFT JOIN user u ON p.pemohon = u.nama 
                WHERE p.status = 'approved' 
                ORDER BY p.created_at DESC");
        }
        $proposals_array = [];
        if ($proposals_result) {
            while ($row = $proposals_result->fetch_assoc()) $proposals_array[] = $row;
        }
        include 'components/table_proposals_dir.php';
        exit();
    }
    
    if ($_GET['ajax_table'] === 'reports') {
        $reports_result = $conn->query("SELECT lh.*, u.nama as creator_name, u2.nama as fm_name
            FROM laporan_keuangan_header lh
            LEFT JOIN user u ON lh.created_by = u.id_user
            LEFT JOIN user u2 ON lh.approved_by = u2.id_user
            WHERE lh.status_lap = 'approved'
            ORDER BY lh.created_at DESC");
        $reports_array = [];
        if ($reports_result) {
            while ($row = $reports_result->fetch_assoc()) $reports_array[] = $row;
        }
        include 'components/table_reports_dir.php';
        exit();
    }
}

// Normal Page Load - Fetch All Data logic starts here (keeping existing flow)
$reports_array = []; // Initialize to avoid warnings
// Get proposals approved by FM (DIR only views, no approval needed)
// Check if approved_by_fm column exists (2-stage approval feature)
$check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");

if ($check_column && $check_column->num_rows > 0) {
    // Show only FM-approved proposals (DIR just views, FM approval is final)
    $proposals_result = $conn->query("SELECT p.*, u.nama as creator_name,
        u2.nama as fm_name
        FROM proposal p 
        LEFT JOIN user u ON p.pemohon = u.nama 
        LEFT JOIN user u2 ON p.approved_by_fm = u2.id_user
        WHERE p.status IN ('approved_fm', 'approved') 
        ORDER BY p.created_at DESC");
    
    if (!$proposals_result) {
        $proposals_array = [];
    } else {
        // Store results in array to avoid result set issues
        $proposals_array = [];
        while ($row = $proposals_result->fetch_assoc()) {
            $proposals_array[] = $row;
        }
    }
} else {
    // Fallback: Show approved proposals (if 2-stage not enabled, FM approval = final)
    $proposals_result = $conn->query("SELECT p.*, u.nama as creator_name
        FROM proposal p 
        LEFT JOIN user u ON p.pemohon = u.nama 
        WHERE p.status = 'approved' 
        ORDER BY p.created_at DESC");
    
    if (!$proposals_result) {
        $proposals_array = [];
    } else {
        // Store results in array to avoid result set issues
        $proposals_array = [];
        while ($row = $proposals_result->fetch_assoc()) {
            $proposals_array[] = $row;
        }
    }
}

// Get financial reports approved by FM (DIR only views, no approval needed)
$reports_result = $conn->query("SELECT lh.*, u.nama as creator_name,
    u2.nama as fm_name
    FROM laporan_keuangan_header lh
    LEFT JOIN user u ON lh.created_by = u.id_user
    LEFT JOIN user u2 ON lh.approved_by = u2.id_user
    WHERE lh.status_lap = 'approved'
    ORDER BY lh.created_at DESC");

if (!$reports_result) {
    $reports_array = [];
} else {
    // Store results in array to avoid result set issues
    $reports_array = [];

    while ($row = $reports_result->fetch_assoc()) {
        $reports_array[] = $row;
    }
}

// Get notifications for Direktur (newly approved by FM - for viewing only)
$notif_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved_fm' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
$notif_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
$total_notifications = $notif_proposals + $notif_reports;

// Get recent notifications with details
$notifications = [];

// Add proposal notifications (approved by FM - for DIR viewing)
// Get all notifications, then limit display to 5 initially
$approved_proposals = $conn->query("SELECT p.id_proposal, p.judul_proposal, p.updated_at, u.nama as creator, u2.nama as fm_name
    FROM proposal p 
    LEFT JOIN user u ON p.pemohon = u.nama 
    LEFT JOIN user u2 ON p.approved_by_fm = u2.id_user
    WHERE p.status = 'approved_fm' 
    ORDER BY p.updated_at DESC");
while ($row = $approved_proposals->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'proposal',
        'id' => $row['id_proposal'],
        'title' => 'Proposal disetujui FM: ' . $row['judul_proposal'],
        'link' => '../proposals/approve_proposal.php?id=' . $row['id_proposal'] . '&return_tab=proposals',
        'time' => time_elapsed_string($row['updated_at']),
        'sort_time' => strtotime($row['updated_at']),
        'is_unread' => $is_unread
    ];
}

// Add report notifications (approved by FM - for DIR viewing)
$report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_projek, lh.updated_at, lh.status_lap, u2.nama as fm_name
    FROM laporan_keuangan_header lh
    LEFT JOIN user u2 ON lh.approved_by = u2.id_user
    WHERE lh.status_lap = 'approved' AND lh.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY lh.updated_at DESC");
while ($row = $report_notifs->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'report',
        'id' => $row['id_laporan_keu'],
        'title' => 'Laporan disetujui FM: ' . $row['nama_projek'],
        'link' => '../reports/view_report_dir.php?id=' . $row['id_laporan_keu'] . '&return_tab=reports',
        'time' => time_elapsed_string($row['updated_at']),
        'sort_time' => strtotime($row['updated_at']),
        'is_unread' => $is_unread
    ];
}

// Sort notifications by time (newest first)
usort($notifications, function($a, $b) {
    $timeA = isset($a['sort_time']) ? $a['sort_time'] : 0;
    $timeB = isset($b['sort_time']) ? $b['sort_time'] : 0;
    return $timeB - $timeA;
});
$total_notifications_count = count($notifications);
$notifications_display = array_slice($notifications, 0, 5); // Show first 5
$has_more_notifications = $total_notifications_count > 5;

// Function to calculate time elapsed
function time_elapsed_string($datetime) {
    if (empty($datetime)) {
        return 'Tidak diketahui';
    }
    
    try {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->d > 0) return $diff->d . ' hari yang lalu';
        if ($diff->h > 0) return $diff->h . ' jam yang lalu';
        if ($diff->i > 0) return $diff->i . ' menit yang lalu';
        return 'Baru saja';
    } catch (Exception $e) {
        return 'Tidak diketahui';
    }
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

// Get statistics for dashboard cards (store results properly to avoid result set consumption)
$total_proyek_result = $conn->query("SELECT COUNT(*) as count FROM proyek WHERE status_proyek != 'cancelled'");
$total_proyek = $total_proyek_result ? $total_proyek_result->fetch_assoc()['count'] : 0;

$proposal_masuk_result = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved_fm'");
$proposal_masuk = $proposal_masuk_result ? $proposal_masuk_result->fetch_assoc()['count'] : 0;

$laporan_approved_result = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'approved'");
$laporan_approved = $laporan_approved_result ? $laporan_approved_result->fetch_assoc()['count'] : 0;

// Close session writing to ensure session is fully saved before HTML output
// This prevents session conflicts when user clicks notification links
session_write_close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Direktur - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Define functions in global scope immediately - must be in head to be available when onclick handlers execute
        window.showTab = function(tabName, updateUrl = true) {
            console.log('showTab called with:', tabName);
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-purple-500', 'text-purple-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });

            const contentEl = document.getElementById(tabName + 'Content');
            if (contentEl) {
                contentEl.classList.remove('hidden');
                console.log('Tab content shown:', tabName);
            } else {
                console.error('Tab content not found:', tabName + 'Content');
            }

            const activeButton = document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
            if (activeButton) {
                activeButton.classList.remove('border-transparent', 'text-gray-500');
                activeButton.classList.add('border-purple-500', 'text-purple-600');
            } else {
                console.error('Tab button not found:', 'tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
            }

            // Update URL without reloading page
            if (updateUrl) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
            }
        };

        window.toggleNotifications = function() {
            console.log('toggleNotifications called');
            const panel = document.getElementById('notificationPanel');
            const profilePanel = document.getElementById('profilePanel');
            if (profilePanel) profilePanel.classList.add('hidden');
            if (panel) {
                panel.classList.toggle('hidden');
                console.log('Notification panel toggled, hidden:', panel.classList.contains('hidden'));
            } else {
                console.error('Notification panel not found!');
            }
        };

        window.closeNotificationsPanel = function() {
            const panel = document.getElementById('notificationPanel');
            if (panel) panel.classList.add('hidden');
        };

        window.toggleProfile = function() {
            console.log('toggleProfile called');
            const panel = document.getElementById('profilePanel');
            const notifPanel = document.getElementById('notificationPanel');
            if (notifPanel) notifPanel.classList.add('hidden');
            if (panel) {
                panel.classList.toggle('hidden');
                console.log('Profile panel toggled, hidden:', panel.classList.contains('hidden'));
            } else {
                console.error('Profile panel not found!');
            }
        };

        window.filterProposals = function() {
            const searchTerm = document.getElementById('searchProposals');
            const filterProject = document.getElementById('filterProposalProject');
            if (!searchTerm || !filterProject) return;
            
            const searchValue = (searchTerm.value || '').toLowerCase().trim();
            const projectValue = (filterProject.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.proposal-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const title = (row.dataset.title || '').toLowerCase();
                const pj = (row.dataset.pj || '').toLowerCase();
                const project = (row.dataset.project || '').toLowerCase().trim();
                
                const matchesSearch = !searchValue || title.includes(searchValue) || pj.includes(searchValue);
                const matchesProject = !projectValue || project === projectValue;
                
                if (matchesSearch && matchesProject) {
                    row.style.display = '';
                    visibleCount++;
                    const firstCell = row.querySelector('td:first-child');
                    if (firstCell) firstCell.textContent = visibleCount;
                } else {
                    row.style.display = 'none';
                }
            });
        };
        
        window.resetProposalFilters = function() {
            const searchProposals = document.getElementById('searchProposals');
            const filterProposalProject = document.getElementById('filterProposalProject');
            if (searchProposals) searchProposals.value = '';
            if (filterProposalProject) filterProposalProject.value = '';
            filterProposals();
        };
    </script>
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
                    <div class="relative" id="notificationDropdown" style="z-index: 1000;">
                        <button type="button" onclick="toggleNotifications(); return false;" class="notification-bell-button relative p-2 text-gray-600 hover:text-gray-800" aria-label="Buka Notifikasi" style="cursor: pointer; z-index: 100;">
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
                            <div class="max-h-96 overflow-y-auto" id="notificationList">
                                <?php if (empty($notifications_display)): ?>
                                    <div class="p-4 text-center text-gray-500 text-sm">
                                        <i class="fas fa-inbox text-3xl mb-2"></i>
                                        <p>Tidak ada notifikasi</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications_display as $notif): ?>
                                        <!-- Fix: add onclick handler to notification. It closes dropdown before navigation. -->
                                        <a href="<?php echo $notif['link']; ?>" 
                                           class="notification-item block p-4 border-b border-gray-100 transition <?php echo $notif['is_unread'] ? 'bg-blue-50 hover:bg-blue-100' : 'bg-white hover:bg-gray-50'; ?>"
                                           onclick="closeNotificationsPanel()">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 mr-3">
                                                    <?php if ($notif['type'] == 'proposal'): ?>
                                                        <i class="fas fa-file-alt text-blue-500 text-lg"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-chart-line text-green-500 text-lg"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm <?php echo $notif['is_unread'] ? 'text-gray-900 font-bold' : 'text-gray-700 font-normal'; ?>">
                                                        <?php echo htmlspecialchars($notif['title']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <i class="far fa-clock mr-1"></i><?php echo htmlspecialchars($notif['time'] ?? 'Tidak diketahui'); ?>
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
                                    <?php if ($has_more_notifications): ?>
                                        <div class="p-3 border-t border-gray-200 bg-gray-50">
                                            <button type="button" 
                                                onclick="loadMoreNotifications(<?php echo count($notifications_display); ?>, <?php echo $total_notifications_count; ?>)" 
                                                class="w-full text-center text-sm text-purple-600 hover:text-purple-800 font-medium py-2">
                                                <i class="fas fa-chevron-down mr-1"></i> Tampilkan Lebih Banyak (<?php echo $total_notifications_count - count($notifications_display); ?> lagi)
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <script>
                                // Store all notifications for "Show More" functionality
                                window.allNotifications = <?php echo json_encode($notifications); ?>;
                                
                                function loadMoreNotifications(currentCount, totalCount) {
                                    const container = document.getElementById('notificationList');
                                    const currentItems = container.querySelectorAll('.notification-item').length;
                                    const nextBatch = Math.min(currentItems + 10, totalCount);
                                    
                                    // Remove "Show More" button
                                    const showMoreBtn = container.querySelector('button');
                                    if (showMoreBtn) {
                                        showMoreBtn.parentElement.remove();
                                    }
                                    
                                    // Add next batch of notifications
                                    const notificationsToAdd = window.allNotifications.slice(currentItems, nextBatch);
                                    notificationsToAdd.forEach(notif => {
                                        const iconClass = notif.type === 'proposal' ? 'fa-file-alt text-blue-500' : 'fa-chart-line text-green-500';
                                        const bgClass = notif.is_unread ? 'bg-blue-50 hover:bg-blue-100' : 'bg-white hover:bg-gray-50';
                                        const textClass = notif.is_unread ? 'text-gray-900 font-bold' : 'text-gray-700 font-normal';
                                        
                                        const notifElement = document.createElement('a');
                                        notifElement.href = notif.link;
                                        notifElement.className = `notification-item block p-4 border-b border-gray-100 transition ${bgClass}`;
                                        notifElement.onclick = () => closeNotificationsPanel();
                                        notifElement.innerHTML = `
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 mr-3">
                                                    <i class="fas ${iconClass} text-lg"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-sm ${textClass}">${escapeHtml(notif.title || 'Tidak ada judul')}</p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <i class="far fa-clock mr-1"></i>${escapeHtml(notif.time || 'Tidak diketahui')}
                                                    </p>
                                                </div>
                                                ${notif.is_unread ? '<div class="flex-shrink-0 ml-2"><span class="w-2 h-2 bg-blue-600 rounded-full inline-block"></span></div>' : ''}
                                            </div>
                                        `;
                                        container.appendChild(notifElement);
                                    });
                                    
                                    // Add "Show More" button again if there are more notifications
                                    if (nextBatch < totalCount) {
                                        const showMoreDiv = document.createElement('div');
                                        showMoreDiv.className = 'p-3 border-t border-gray-200 bg-gray-50';
                                        showMoreDiv.innerHTML = `
                                            <button type="button" 
                                                onclick="loadMoreNotifications(${nextBatch}, ${totalCount})" 
                                                class="w-full text-center text-sm text-purple-600 hover:text-purple-800 font-medium py-2">
                                                <i class="fas fa-chevron-down mr-1"></i> Tampilkan Lebih Banyak (${totalCount - nextBatch} lagi)
                                            </button>
                                        `;
                                        container.appendChild(showMoreDiv);
                                    }
                                }
                                
                                function escapeHtml(text) {
                                    const div = document.createElement('div');
                                    div.textContent = text;
                                    return div.innerHTML;
                                }
                            </script>
                        </div>
                    </div>
                    
                    <!-- Profile -->
                    <div class="relative" id="profileDropdown" style="z-index: 1000;">
                        <button type="button" onclick="toggleProfile(); return false;" class="flex items-center space-x-2" style="cursor: pointer; z-index: 100;">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=8B5CF6&color=fff" 
                                class="w-10 h-10 rounded-full border-2 border-purple-400">
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
            <p class="text-gray-600">Dashboard Direktur</p>
        </div>

        <!-- Stats Overview -->
        <div class="mb-8">
            <!-- Proposals Section -->
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                    Proposals Overview
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Approved by FM</p>
                                <p class="text-3xl font-bold text-gray-800" data-stat="pending_proposals"><?php echo $proposal_masuk; ?></p>
                                <p class="text-xs text-gray-500 mt-1">Ready for Director review</p>
                            </div>
                            <div class="bg-blue-500 p-3 rounded-full">
                                <i class="fas fa-check-circle text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Reports Section -->
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-chart-line text-purple-600 mr-2"></i>
                    Financial Reports Overview
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Approved by FM</p>
                                <p class="text-3xl font-bold text-gray-800" data-stat="pending_reports"><?php echo $laporan_approved; ?></p>
                                <p class="text-xs text-gray-500 mt-1">Ready for Director review</p>
                            </div>
                            <div class="bg-purple-500 p-3 rounded-full">
                                <i class="fas fa-check-circle text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Section -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-project-diagram text-orange-600 mr-2"></i>
                    Projects Overview
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg border border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Total Projects</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $total_proyek; ?></p>
                            </div>
                            <div class="bg-orange-500 p-3 rounded-full">
                                <i class="fas fa-folder text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 p-6 rounded-lg border border-cyan-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Active Projects</p>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo $conn->query("SELECT COUNT(*) as count FROM proyek WHERE status_proyek IN ('planning', 'ongoing')")->fetch_assoc()['count']; ?>
                                </p>
                            </div>
                            <div class="bg-cyan-500 p-3 rounded-full">
                                <i class="fas fa-spinner text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-6 rounded-lg border border-indigo-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Completed Projects</p>
                                <p class="text-3xl font-bold text-gray-800">
                                    <?php echo $conn->query("SELECT COUNT(*) as count FROM proyek WHERE status_proyek = 'completed'")->fetch_assoc()['count']; ?>
                                </p>
                            </div>
                            <div class="bg-indigo-500 p-3 rounded-full">
                                <i class="fas fa-flag-checkered text-white text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button type="button" onclick="showTab('proposals')" id="tabProposals" 
                        class="tab-button border-b-2 border-purple-500 py-4 px-1 text-sm font-medium text-purple-600" style="cursor: pointer;">
                        Proposal Masuk
                    </button>
                    <button type="button" onclick="showTab('reports')" id="tabReports"
                        class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300" style="cursor: pointer;">
                        Laporan Keuangan
                    </button>
                </nav>
            </div>
        </div>

        <!-- Proposals Tab -->
        <div id="proposalsContent" class="tab-content">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Proposal yang Disetujui FM</h3>
                        <div class="relative">
                            <input type="text" id="searchProposals" placeholder="Cari judul atau PJ..." 
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400 w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <select id="filterProposalProject" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option value="">Semua Proyek</option>
                            <?php 
                            $projects = $conn->query("SELECT DISTINCT kode_proyek FROM proyek ORDER BY kode_proyek");
                            if ($projects && $projects->num_rows > 0):
                            while ($proj = $projects->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($proj['kode_proyek']); ?>">
                                    <?php echo htmlspecialchars($proj['kode_proyek']); ?>
                                </option>
                                <?php endwhile;
                            endif; ?>
                        </select>
                        <button onclick="resetProposalFilters()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Judul</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PJ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proyek</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="proposals-table-body" class="bg-white divide-y divide-gray-200">
                            <?php include 'components/table_proposals_dir.php'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reportsContent" class="tab-content hidden">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">Laporan Keuangan yang Disetujui FM</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kegiatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proyek</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dibuat Oleh</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Approval</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="reports-table-body" class="bg-white divide-y divide-gray-200">
                            <?php include 'components/table_reports_dir.php'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Functions are now defined in <head> section above

        // On page load, check for tab parameter and restore it
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            
            if (activeTab && (activeTab === 'proposals' || activeTab === 'reports')) {
                showTab(activeTab, false);
            }
        });

        // Setup dropdown event listeners after DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
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

            // Prevent dropdown from closing if click is inside notification panel or bell
            const notifPanelEl = document.getElementById('notificationPanel');
            if (notifPanelEl) {
                notifPanelEl.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            // Note: Button clicks are handled by onclick handlers, no need for stopPropagation here
            const profilePanelEl = document.getElementById('profilePanel');
            if (profilePanelEl) {
                profilePanelEl.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
        
        // Filter functions are now defined in <head> section above
        
        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const searchProposals = document.getElementById('searchProposals');
            const filterProposalProject = document.getElementById('filterProposalProject');
            
            if (searchProposals) {
                searchProposals.addEventListener('input', filterProposals);
            }
            if (filterProposalProject) {
                filterProposalProject.addEventListener('change', filterProposals);
            }
            
            // Initial filter on page load to ensure consistency
            filterProposals();
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
    <script>
        // Real-time Table Updates for Director
        window.addEventListener('dashboard-data-refresh', function(e) {
            console.log('Refreshing Director dashboard tables...');
            
            // Reload Proposals Table
            fetch('dashboard_dir.php?ajax_table=proposals')
                .then(response => response.text())
                .then(html => {
                    const tbody = document.getElementById('proposals-table-body');
                    if (tbody) {
                        tbody.innerHTML = html;
                        // Add subtle flash effect
                        tbody.parentElement.classList.add('ring-2', 'ring-blue-200');
                        setTimeout(() => tbody.parentElement.classList.remove('ring-2', 'ring-blue-200'), 1000);
                    }
                })
                .catch(err => console.error('Error reloading proposals:', err));

            // Reload Reports Table
            fetch('dashboard_dir.php?ajax_table=reports')
                .then(response => response.text())
                .then(html => {
                    const tbody = document.getElementById('reports-table-body');
                    if (tbody) {
                        tbody.innerHTML = html;
                    }
                })
                .catch(err => console.error('Error reloading reports:', err));
        });
    </script>
</body>
</html>