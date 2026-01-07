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

if ($_SESSION['user_role'] !== 'Finance Manager') {
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
        case 'proposal_approved_stage1':
            $success_message = 'Proposal successfully approved! Waiting for Director approval.';
            break;
    }
}

// Get pending proposals for review - store in array to avoid result set consumption
$proposals_result = $conn->query("SELECT DISTINCT p.*, u.nama as creator_name 
    FROM proposal p 
    LEFT JOIN user u ON p.pemohon = u.nama 
    WHERE p.status IN ('submitted', 'approved') 
    ORDER BY p.created_at DESC");
$proposals_array = [];
if ($proposals_result) {
    // Use id_proposal as key to prevent duplicates
    $seen_ids = [];
    while ($row = $proposals_result->fetch_assoc()) {
        $proposal_id = $row['id_proposal'];
        // Only add if we haven't seen this proposal ID before
        if (!isset($seen_ids[$proposal_id])) {
            $proposals_array[] = $row;
            $seen_ids[$proposal_id] = true;
        }
    }
}

// Get validated financial reports for approval
// Debug: Show all reports to check what's in database
$reports = $conn->query("SELECT lh.*, u.nama as creator_name 
    FROM laporan_keuangan_header lh 
    LEFT JOIN user u ON lh.created_by = u.id_user 
    ORDER BY lh.created_at DESC");

// Store reports in array to avoid result set consumption issues
$reports_array = [];
if ($reports) {
    while ($row = $reports->fetch_assoc()) {
        $reports_array[] = $row;
    }
}

// Get notifications (pending proposals + pending reports)
$notif_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'submitted'")->fetch_assoc()['count'];
$notif_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'verified'")->fetch_assoc()['count'];
$total_notifications = $notif_proposals + $notif_reports;

// Get initial notifications (first 5) - will be loaded via AJAX for better performance
$notifications = [];
$current_page = 1;
$per_page = 5;

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
    <title>Dashboard Finance Manager - PRCFI</title>
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
                            <div class="max-h-96 overflow-y-auto" id="notificationsContainer">
                                <div id="notificationsLoading" class="p-4 text-center text-gray-500 text-sm hidden">
                                    <i class="fas fa-spinner fa-spin text-xl mb-2"></i>
                                    <p>Memuat notifikasi...</p>
                                    </div>
                                <div id="notificationsList">
                                    <!-- Notifications will be loaded here via AJAX -->
                                                </div>
                                                </div>
                            <div id="showMoreContainer" class="border-t border-gray-200 p-3 hidden">
                                <button id="showMoreBtn" onclick="loadMoreNotifications()"
                                        class="w-full text-center text-blue-600 hover:text-blue-800 text-sm font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition duration-200">
                                    <i class="fas fa-chevron-down mr-2"></i>Tampilkan Lebih Banyak
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile -->
                    <div class="relative" id="profileDropdown">
                        <button onclick="toggleProfile()" class="flex items-center space-x-2">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=F59E0B&color=fff" 
                                class="w-10 h-10 rounded-full border-2 border-yellow-400">
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
            <p class="text-gray-600">Dashboard Finance Manager</p>
        </div>

        <!-- Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200 hover:shadow-lg transition duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Buku Bank</h3>
                        <p class="text-sm text-gray-600 mt-1">Settlement & Management</p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full">
                        <i class="fas fa-university text-white text-2xl"></i>
                    </div>
                </div>
                <a href="../books/buku_bank.php" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                    <i class="fas fa-arrow-right mr-2"></i>Kelola
                </a>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200 hover:shadow-lg transition duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Buku Piutang</h3>
                        <p class="text-sm text-gray-600 mt-1">Settlement & Tracking</p>
                    </div>
                    <div class="bg-green-500 p-3 rounded-full">
                        <i class="fas fa-file-invoice-dollar text-white text-2xl"></i>
                    </div>
                </div>
                <a href="../books/buku_piutang.php" class="inline-block bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition duration-200 font-medium">
                    <i class="fas fa-arrow-right mr-2"></i>Kelola
                </a>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg border border-orange-200 hover:shadow-lg transition duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Kelola Proyek</h3>
                        <p class="text-sm text-gray-600 mt-1">Kode & Data Proyek</p>
                    </div>
                    <div class="bg-orange-500 p-3 rounded-full">
                        <i class="fas fa-project-diagram text-white text-2xl"></i>
                    </div>
                </div>
                <a href="../projects/manage_projects.php" class="inline-block bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition duration-200 font-medium">
                    Kelola
                </a>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200 hover:shadow-lg transition duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Laporan Donor</h3>
                        <p class="text-sm text-gray-600 mt-1">Review & Manage</p>
                    </div>
                    <div class="bg-purple-500 p-3 rounded-full">
                        <i class="fas fa-handshake text-white text-2xl"></i>
                    </div>
                </div>
                <a href="../../public/under_construction.php?feature=Laporan Donor" class="inline-block bg-purple-500 text-white px-6 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-medium">
                    Kelola
                </a>
            </div>

            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 rounded-lg border border-emerald-200 hover:shadow-lg transition duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Kelola Budget</h3>
                        <p class="text-sm text-gray-600 mt-1">Planning & Control</p>
                    </div>
                    <div class="bg-emerald-500 p-3 rounded-full">
                        <i class="fas fa-calculator text-white text-2xl"></i>
                    </div>
                </div>
                <a href="../finance/manage_budgets.php" class="inline-block bg-emerald-500 text-white px-6 py-2 rounded-lg hover:bg-emerald-600 transition duration-200 font-medium">
                    <i class="fas fa-arrow-right mr-2"></i>Kelola
                </a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="showTab('proposals')" id="tabProposals" 
                        class="tab-button border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                        Proposal Masuk
                    </button>
                    <button onclick="showTab('reports')" id="tabReports"
                        class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Laporan Keuangan
                    </button>
                </nav>
            </div>
        </div>

        <!-- Proposals Tab -->
        <div id="proposalsContent" class="tab-content">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">Proposal yang Perlu Direview</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Judul</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemohon</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proyek</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($proposals_array)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                                            <p class="text-gray-500 text-lg font-medium mb-2">Belum ada proposal masuk</p>
                                            <p class="text-gray-400 text-sm">Tidak ada proposal yang perlu direview saat ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                foreach ($proposals_array as $proposal): 
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $no++; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $proposal['judul_proposal']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $proposal['pemohon']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $proposal['kode_proyek']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($proposal['date'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($proposal['status'] === 'submitted'): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pending Review
                                            </span>
                                        <?php elseif ($proposal['status'] === 'approved'): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Approved
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="../proposals/review_proposal_fm.php?id=<?php echo $proposal['id_proposal']; ?>&return_tab=proposals" 
                                            class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye mr-1"></i> Review
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reportsContent" class="tab-content hidden">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-800">Laporan Keuangan yang Perlu Diapprove</h3>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($reports_array)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-file-invoice text-gray-400 text-5xl mb-4"></i>
                                            <p class="text-gray-500 text-lg font-medium mb-2">Belum ada laporan keuangan</p>
                                            <p class="text-gray-400 text-sm">Tidak ada laporan keuangan yang perlu diapprove saat ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                foreach ($reports_array as $report): 
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $no++; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['nama_projek']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['kode_projek']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['creator_name']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($report['tanggal_laporan'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $status = trim($report['status_lap']);
                                        // Debug: Show actual status
                                        switch ($status) {
                                            case 'draft':
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>';
                                                break;
                                            case 'submitted':
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending SA</span>';
                                                break;
                                            case 'verified':
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Tervalidasi SA</span>';
                                                break;
                                            case 'approved_fm':
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Approved FM</span>';
                                                break;
                                            case 'approved':
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Approved Final</span>';
                                                break;
                                            default:
                                                echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Unknown: ' . htmlspecialchars($status) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if ($status === 'verified' || $status === 'submitted'): ?>
                                            <a href="../reports/approve-report-fm.php?id=<?php echo $report['id_laporan_keu']; ?>&return_tab=reports" 
                                                class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-check-circle mr-1"></i> Approve
                                            </a>
                                        <?php else: ?>
                                            <a href="../reports/view_report_fm.php?id=<?php echo $report['id_laporan_keu']; ?>&return_tab=reports" 
                                                class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        let currentNotificationPage = 1;
        let isLoadingNotifications = false;
        let hasMoreNotifications = true;

        function showTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active state from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected content
            document.getElementById(tabName + 'Content').classList.remove('hidden');
            
            // Set active state on selected button
            const activeButton = document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
            activeButton.classList.remove('border-transparent', 'text-gray-500');
            activeButton.classList.add('border-blue-500', 'text-blue-600');
        }

        function toggleNotifications() {
            const panel = document.getElementById('notificationPanel');
            const profilePanel = document.getElementById('profilePanel');
            if (profilePanel) profilePanel.classList.add('hidden');
            if (panel) {
                const isHidden = panel.classList.contains('hidden');
                panel.classList.toggle('hidden');

                // Load notifications when panel is opened
                if (isHidden) {
                    loadNotifications(true);
                }
            }
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

        function loadNotifications(reset = false) {
            if (isLoadingNotifications) return;

            if (reset) {
                currentNotificationPage = 1;
                hasMoreNotifications = true;
                document.getElementById('notificationsList').innerHTML = '';
                document.getElementById('showMoreContainer').classList.add('hidden');
            }

            if (!hasMoreNotifications && !reset) return;

            isLoadingNotifications = true;
            const loadingEl = document.getElementById('notificationsLoading');
            loadingEl.classList.remove('hidden');

            const perPage = currentNotificationPage === 1 ? 10 : 5;
            fetch(`../api/get_notifications.php?page=${currentNotificationPage}&per_page=${perPage}`)
                .then(response => response.json())
                .then(data => {
                    loadingEl.classList.add('hidden');
                    isLoadingNotifications = false;

                    if (data.success) {
                        renderNotifications(data.notifications);

                        hasMoreNotifications = data.has_more;

                        const showMoreContainer = document.getElementById('showMoreContainer');
                        if (hasMoreNotifications) {
                            showMoreContainer.classList.remove('hidden');
                        } else {
                            showMoreContainer.classList.add('hidden');
                        }

                        if (!reset) {
                            currentNotificationPage++;
                        }
                    } else {
                        console.error('API returned error:', data.error);
                        showNotificationError();
                    }
                })
                .catch(error => {
                    loadingEl.classList.add('hidden');
                    isLoadingNotifications = false;
                    console.error('Error:', error);
                    showNotificationError();
                });
        }

        function renderNotifications(notifications) {
            const container = document.getElementById('notificationsList');

            if (!container) {
                console.error('notificationsList container not found!');
                return;
            }

            if (notifications.length === 0 && currentNotificationPage === 1) {
                container.innerHTML = `
                    <div class="p-4 text-center text-gray-500 text-sm">
                        <i class="fas fa-inbox text-3xl mb-2"></i>
                        <p>Tidak ada notifikasi</p>
                    </div>
                `;
                return;
            }

            // Clear container first if this is the first page
            if (currentNotificationPage === 1) {
                container.innerHTML = '';
            }

            // Use for loop instead of forEach to avoid potential issues
            for (let i = 0; i < notifications.length; i++) {
                const notif = notifications[i];

                try {

                    // Validate notification data
                    if (!notif || typeof notif !== 'object') {
                        console.warn(`Invalid notification at index ${i}:`, notif);
                        continue;
                    }

                    const notificationHtml = `
                        <a href="${notif.link || '#'}"
                           class="block p-4 border-b border-gray-100 transition ${notif.is_unread ? 'bg-blue-50 hover:bg-blue-100' : 'bg-white hover:bg-gray-50'}"
                           onclick="closeNotificationsPanel()">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mr-3">
                                    ${notif.type === 'proposal'
                                        ? '<i class="fas fa-file-alt text-blue-500 text-lg"></i>'
                                        : '<i class="fas fa-chart-line text-green-500 text-lg"></i>'}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm ${notif.is_unread ? 'text-gray-900 font-bold' : 'text-gray-700 font-normal'}">
                                        ${notif.title || 'No title'}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="far fa-clock mr-1"></i>${notif.time || 'Unknown time'}
                                    </p>
                                </div>
                                ${notif.is_unread ? '<div class="flex-shrink-0 ml-2"><span class="w-2 h-2 bg-blue-600 rounded-full inline-block"></span></div>' : ''}
                            </div>
                        </a>
                    `;

                    container.insertAdjacentHTML('beforeend', notificationHtml);

                } catch (error) {
                    console.error(`Error processing notification ${i + 1}:`, error, notif);
                    // Continue processing other notifications even if one fails
                }
            }
        }

        function loadMoreNotifications() {
            if (!isLoadingNotifications && hasMoreNotifications) {
                loadNotifications(false);
            }
        }

        function showNotificationError() {
            const container = document.getElementById('notificationsList');
            if (currentNotificationPage === 1) {
                container.innerHTML = `
                    <div class="p-4 text-center text-red-500 text-sm">
                        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                        <p>Gagal memuat notifikasi</p>
                    </div>
                `;
            }
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

        // Auto-load notifications when page loads (but not when panel is opened)
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial notifications silently in background
            loadNotifications(true);
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
                    refreshNotifications();
                }
            }, 30000); // 30 seconds
        });
    </script>
    
    <!-- Real-time Notifications -->
    <script src="../../assets/js/realtime_notifications.js"></script>
</body>
</html>