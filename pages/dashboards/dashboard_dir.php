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
$last_check_query = $conn->query("SELECT last_notification_check FROM user WHERE id_user = {$user_id}");
if ($last_check_query) {
    $last_check_data = $last_check_query->fetch_assoc();
    $last_notification_check = $last_check_data['last_notification_check'] ?? '1970-01-01 00:00:00';
    
    // DISABLED auto-update to prevent session issues with back button
    // User can manually import SQL to enable read/unread feature
    // @$conn->query("UPDATE user SET last_notification_check = NOW() WHERE id_user = {$user_id}");
} else {
    // Column doesn't exist yet - treat all as unread, skip update
    $last_notification_check = '1970-01-01 00:00:00';
}

// Get proposals for DIR approval (stage 2: approved by FM, waiting DIR)
// Check if approved_by_fm column exists (2-stage approval feature)
$check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");
if ($check_column && $check_column->num_rows > 0) {
    // 2-stage approval is active
    $proposals = $conn->query("SELECT p.*, u.nama as creator_name,
        u2.nama as fm_name
        FROM proposal p 
        LEFT JOIN user u ON p.pemohon = u.nama 
        LEFT JOIN user u2 ON p.approved_by_fm = u2.id_user
        WHERE p.status IN ('approved_fm', 'approved') 
        ORDER BY p.created_at DESC");
} else {
    // Fallback: 2-stage approval not yet enabled
    $proposals = $conn->query("SELECT p.*, u.nama as creator_name
        FROM proposal p 
        LEFT JOIN user u ON p.pemohon = u.nama 
        WHERE p.status = 'approved' 
        ORDER BY p.created_at DESC");
}

// Get validated financial reports
$reports = $conn->query("SELECT lh.*, u.nama as creator_name,
    u2.nama as fm_name
    FROM laporan_keuangan_header lh 
    LEFT JOIN user u ON lh.created_by = u.id_user
    LEFT JOIN user u2 ON lh.approved_by = u2.id_user
    WHERE lh.status_lap IN ('verified', 'approved') 
    ORDER BY lh.created_at DESC");

// Get all projects for filter
$projects = $conn->query("SELECT kode_proyek, nama_proyek FROM proyek WHERE status_proyek != 'cancelled' ORDER BY nama_proyek ASC");
$project_list = [];
if ($projects) {
    while ($proj = $projects->fetch_assoc()) {
        $project_list[] = $proj;
    }
}

// Get notifications for Direktur (proposals waiting for stage 2 approval)
$notif_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved_fm'")->fetch_assoc()['count'];
$notif_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
$total_notifications = $notif_proposals + $notif_reports;

// Get recent notifications with details
$notifications = [];

// Add proposal notifications (waiting for DIR approval - stage 2)
$approved_proposals = $conn->query("SELECT p.id_proposal, p.judul_proposal, p.updated_at, u.nama as creator 
    FROM proposal p 
    LEFT JOIN user u ON p.pemohon = u.nama 
    WHERE p.status = 'approved_fm' 
    ORDER BY p.updated_at DESC LIMIT 5");
while ($row = $approved_proposals->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'proposal',
        'id' => $row['id_proposal'],
        'title' => 'Proposal disetujui FM (1/2): ' . $row['judul_proposal'],
        'link' => '../proposals/review_proposal_dir.php?id=' . $row['id_proposal'],
        'time' => time_elapsed_string($row['updated_at']),
        'is_unread' => $is_unread
    ];
}

// Add approved report notifications
$approved_reports = $conn->query("SELECT lh.id_laporan_keu, lh.nama_kegiatan, lh.updated_at 
    FROM laporan_keuangan_header lh 
    WHERE lh.status_lap = 'approved' AND lh.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY lh.updated_at DESC LIMIT 5");
while ($row = $approved_reports->fetch_assoc()) {
    $is_unread = (strtotime($row['updated_at']) > strtotime($last_notification_check));
    $notifications[] = [
        'type' => 'report',
        'id' => $row['id_laporan_keu'],
        'title' => 'Laporan disetujui FM: ' . $row['nama_kegiatan'],
        'link' => 'approve_report_dir.php?id=' . $row['id_laporan_keu'],
        'time' => time_elapsed_string($row['updated_at']),
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

// Handle success messages from redirects
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'password_changed':
            $success_message = 'Password berhasil diubah!';
            break;
        case 'proposal_approved_final':
            $success_message = 'Proposal berhasil disetujui secara final!';
            break;
    }
}

// Get statistics for dashboard cards
$total_proyek = $conn->query("SELECT COUNT(*) as count FROM proyek WHERE status_proyek != 'cancelled'")->fetch_assoc()['count'];

// Proposal stats (for Director)
$proposal_total = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status IN ('approved_fm','approved')")->fetch_assoc()['count'];
$proposal_pending_dir = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved_fm'")->fetch_assoc()['count'];
$proposal_approved_final = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved'")->fetch_assoc()['count'];

// Reports stats (for Director)
$reports_verified = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'verified'")->fetch_assoc()['count'];
$reports_approved = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'approved'")->fetch_assoc()['count'];
$reports_pending_dir = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE approved_by IS NOT NULL AND approved_by > 0 AND status_lap != 'approved'")->fetch_assoc()['count'];

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
                        <!-- Fix: Use toggleNotifications() instead of unknown toggleNotificationsRealtime() -->
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
                                        <!-- Fix: add onclick handler to notification. It closes dropdown before navigation. -->
                                        <a href="<?php echo $notif['link']; ?>" 
                                           class="block p-4 border-b border-gray-100 transition <?php echo $notif['is_unread'] ? 'bg-blue-50 hover:bg-blue-100' : 'bg-white hover:bg-gray-50'; ?>"
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" id="statsCards">
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Proyek</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_proyek; ?></p>
                    </div>
                    <div class="bg-purple-500 p-3 rounded-full">
                        <i class="fas fa-project-diagram text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg border border-blue-200" data-prop-label="Proposal Masuk" data-prop-value="<?php echo $proposal_total; ?>" data-rep-label="Laporan Diverifikasi" data-rep-value="<?php echo $reports_verified; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1 js-card-label">Proposal Masuk</p>
                        <p class="text-3xl font-bold text-gray-800 js-card-value"><?php echo $proposal_total; ?></p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg border border-green-200" data-prop-label="Approved Final" data-prop-value="<?php echo $proposal_approved_final; ?>" data-rep-label="Laporan Approved" data-rep-value="<?php echo $reports_approved; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1 js-card-label">Approved Final</p>
                        <p class="text-3xl font-bold text-gray-800 js-card-value"><?php echo $proposal_approved_final; ?></p>
                    </div>
                    <div class="bg-green-500 p-3 rounded-full">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-lg border border-yellow-200" data-prop-label="Pending Review (DIR)" data-prop-value="<?php echo ($proposal_pending_dir); ?>" data-rep-label="Pending Review (DIR)" data-rep-value="<?php echo ($reports_pending_dir); ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1 js-card-label">Pending Review (DIR)</p>
                        <p class="text-3xl font-bold text-gray-800 js-card-value"><?php echo ($proposal_pending_dir); ?></p>
                    </div>
                    <div class="bg-yellow-500 p-3 rounded-full">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="showTab('proposals')" id="tabProposals" 
                        class="tab-button border-b-2 border-purple-500 py-4 px-1 text-sm font-medium text-purple-600">
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
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">Proposal untuk Approval</h3>
                        
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="sortProposalTable('judul')" title="Klik untuk sort">
                                    <div class="flex items-center">
                                        Judul
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-sort sort-icon" id="sort-proposal-judul"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="filterProposalByColumn('pj')" title="Klik untuk filter">
                                    <div class="flex items-center justify-between">
                                        PJ
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-filter filter-icon"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="filterProposalByColumn('project')" title="Klik untuk filter">
                                    <div class="flex items-center justify-between">
                                        Proyek
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-filter filter-icon"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="showDateFilterDIR('proposals')" title="Klik untuk filter tanggal">
                                    <div class="flex items-center">
                                        Tanggal
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-sort sort-icon" id="sort-proposal-date"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="filterProposalByColumn('status')" title="Klik untuk filter">
                                    <div class="flex items-center justify-between">
                                        Status
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-filter filter-icon"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $no = 1;
                            while ($proposal = $proposals->fetch_assoc()): 
                            ?>
                            <tr class="hover:bg-gray-50 proposal-row" 
                                data-project="<?php echo htmlspecialchars($proposal['kode_proyek']); ?>"
                                data-status="<?php echo htmlspecialchars($proposal['status']); ?>"
                                data-pj="<?php echo htmlspecialchars($proposal['pj']); ?>"
                                data-judul="<?php echo strtolower(htmlspecialchars($proposal['judul_proposal'])); ?>"
                                data-date="<?php echo $proposal['date']; ?>">
                                <td class="px-6 py-4 text-sm text-gray-900 row-number"><?php echo $no++; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $proposal['judul_proposal']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $proposal['pj']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $proposal['kode_proyek']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($proposal['date'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($proposal['status'] === 'submitted'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Menunggu Approval FM
                                        </span>
                                    <?php elseif ($proposal['status'] === 'approved_fm'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <i class="fas fa-check mr-1"></i> 1/2 Approved (FM)
                                        </span>
                                    <?php elseif ($proposal['status'] === 'approved'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-double mr-1"></i> 2/2 Approved (Final)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                <?php if ($proposal['status'] === 'approved_fm'): ?>
                                        <a href="../proposals/approve_proposal.php?id=<?php echo $proposal['id_proposal']; ?>" 
                                            class="text-purple-600 hover:text-purple-900 font-medium">
                                            <i class="fas fa-clipboard-check mr-1"></i> Approve Stage 2
                                        </a>
                                <?php else: ?>
                                        <a href="../proposals/review_proposal_dir.php?id=<?php echo $proposal['id_proposal']; ?>" 
                                            class="text-gray-600 hover:text-gray-900">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reportsContent" class="tab-content hidden">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">Laporan Keuangan untuk Approval</h3>
                        
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="sortReportTable('kegiatan')" title="Klik untuk sort">
                                    <div class="flex items-center">
                                        Kegiatan
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-sort sort-icon" id="sort-report-kegiatan"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="filterReportByColumn('project')" title="Klik untuk filter">
                                    <div class="flex items-center justify-between">
                                        Proyek
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-filter filter-icon"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="filterReportByColumn('creator')" title="Klik untuk filter">
                                    <div class="flex items-center justify-between">
                                        Dibuat Oleh
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-filter filter-icon"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none" onclick="showDateFilterDIR('reports')" title="Klik untuk filter tanggal">
                                    <div class="flex items-center">
                                        Tanggal
                                        <span class="ml-2 text-gray-400">
                                            <i class="fas fa-sort sort-icon" id="sort-report-date"></i>
                                        </span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Approval</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $no = 1;
                            while ($report = $reports->fetch_assoc()): 
                            ?>
                            <tr class="hover:bg-gray-50 report-row" 
                                data-project="<?php echo htmlspecialchars($report['kode_projek']); ?>"
                                data-creator="<?php echo htmlspecialchars($report['creator_name']); ?>"
                                data-kegiatan="<?php echo strtolower(htmlspecialchars($report['nama_kegiatan'])); ?>"
                                data-date="<?php echo $report['tanggal_laporan']; ?>">
                                <td class="px-6 py-4 text-sm text-gray-900 row-number"><?php echo $no++; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $report['nama_kegiatan']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $report['kode_projek']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $report['creator_name']; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($report['tanggal_laporan'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($report['approved_by']): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                ✓ FM
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                                ○ FM
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($report['status_lap'] === 'approved'): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                ✓ DIR
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                ○ DIR
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="approve_report_dir.php?id=<?php echo $report['id_laporan_keu']; ?>" 
                                        class="text-purple-600 hover:text-purple-900">
                                        <i class="fas fa-check-circle mr-1"></i> Approve
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Switch stats when tab changes (Proposal <-> Laporan)
        function updateStatsForTab(tab) {
            const cards = document.querySelectorAll('#statsCards > div');
            cards.forEach((card, idx) => {
                if (idx === 0) return; // total project static
                const labelEl = card.querySelector('.js-card-label');
                const valueEl = card.querySelector('.js-card-value');
                if (!labelEl || !valueEl) return;
                if (tab === 'proposals') {
                    labelEl.textContent = card.getAttribute('data-prop-label');
                    valueEl.textContent = card.getAttribute('data-prop-value');
                } else {
                    labelEl.textContent = card.getAttribute('data-rep-label');
                    valueEl.textContent = card.getAttribute('data-rep-value');
                }
            });
        }
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-purple-500', 'text-purple-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            document.getElementById(tabName + 'Content').classList.remove('hidden');
            
            const activeButton = document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
            activeButton.classList.remove('border-transparent', 'text-gray-500');
            activeButton.classList.add('border-purple-500', 'text-purple-600');

            // Update stats numbers/labels to match selected tab
            updateStatsForTab(tabName);
        }

        function toggleNotifications() {
            const panel = document.getElementById('notificationPanel');
            const profilePanel = document.getElementById('profilePanel');
            if (profilePanel) profilePanel.classList.add('hidden');
            if (panel) panel.classList.toggle('hidden');
        }

        // New: Helper to close notifications panel when notification is clicked
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
                document.getElementById('notificationPanel').classList.add('hidden');
            }
            if (profileDropdown && !profileDropdown.contains(event.target)) {
                document.getElementById('profilePanel').classList.add('hidden');
            }
        });

        // Prevent dropdown from closing if click is inside notification panel or bell
        document.getElementById('notificationPanel').addEventListener('click', function(e) {
            e.stopPropagation();
        });
        document.querySelector('.notification-bell-button').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Track active filter state for proposals
        let activeProposalFilters = {
            project: '',
            pj: '',
            status: ''
        };

        // Track active filter state for reports
        let activeReportFilters = {
            project: '',
            creator: ''
        };

        // Project filter functions
        function filterProposals() {
            const selectedProject = document.getElementById('filterProposalProject').value;
            const proposalRows = document.querySelectorAll('.proposal-row');

            proposalRows.forEach(row => {
                if (selectedProject === '' || row.getAttribute('data-project') === selectedProject) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterReports() {
            const selectedProject = document.getElementById('filterReportProject').value;
            const reportRows = document.querySelectorAll('.report-row');
            
            reportRows.forEach(row => {
                if (selectedProject === '' || row.getAttribute('data-project') === selectedProject) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // ====== CLICKABLE COLUMN HEADER FEATURES FOR PROPOSALS ======
        let proposalSortState = { judul: 'none', date: 'none' };

        function sortProposalTable(column) {
            const tbody = document.querySelector('#proposalsContent tbody');
            const rows = Array.from(document.querySelectorAll('.proposal-row'));
            
            let direction = proposalSortState[column] === 'asc' ? 'desc' : 'asc';
            proposalSortState[column] = direction;
            
            rows.sort((a, b) => {
                let aVal, bVal;
                if (column === 'judul') {
                    aVal = a.getAttribute('data-judul');
                    bVal = b.getAttribute('data-judul');
                } else if (column === 'date') {
                    aVal = new Date(a.getAttribute('data-date'));
                    bVal = new Date(b.getAttribute('data-date'));
                }
                
                if (direction === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            rows.forEach((row, index) => {
                tbody.appendChild(row);
                const noCell = row.querySelector('.row-number');
                if (noCell) noCell.textContent = index + 1;
            });
            
            const icon = document.getElementById('sort-proposal-' + column);
            if (icon) {
                icon.className = direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
            }
            
            Object.keys(proposalSortState).forEach(key => {
                if (key !== column) {
                    proposalSortState[key] = 'none';
                    const otherIcon = document.getElementById('sort-proposal-' + key);
                    if (otherIcon) otherIcon.className = 'fas fa-sort';
                }
            });
        }

        function filterProposalByColumn(column) {
            const rows = document.querySelectorAll('.proposal-row');
            const uniqueValues = new Set();
            
            rows.forEach(row => {
                const value = row.getAttribute('data-' + column);
                if (value) uniqueValues.add(value);
            });
            
            const menu = document.createElement('div');
            menu.id = 'columnFilterMenu';
            menu.className = 'absolute bg-white border border-gray-300 rounded-lg shadow-lg z-50 mt-2 max-h-64 overflow-y-auto';
            menu.style.minWidth = '200px';
            
            let menuHTML = `
                <div class="p-2 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Filter ${getColumnNameProposal(column)}</span>
                        <button onclick="closeColumnFilter()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="p-2">
                    <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                        <input type="radio" name="filter_${column}" value="" onchange="applyProposalFilter('${column}', '')" checked class="mr-2">
                        <span class="text-sm">(Semua)</span>
                    </label>
            `;

            Array.from(uniqueValues).sort().forEach(value => {
                const displayValue = column === 'status' ? getProposalStatusText(value) : value;
                menuHTML += `
                    <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                        <input type="radio" name="filter_${column}" value="${value}" onchange="applyProposalFilter('${column}', this.value)" class="mr-2">
                        <span class="text-sm">${displayValue}</span>
                    </label>
                `;
            });
            
            menuHTML += '</div>';
            menu.innerHTML = menuHTML;
            
            const existingMenu = document.getElementById('columnFilterMenu');
            if (existingMenu) existingMenu.remove();
            
            const event = window.event;
            const target = event.target.closest('th');
            const rect = target.getBoundingClientRect();
            
            menu.style.position = 'fixed';
            menu.style.left = rect.left + 'px';
            menu.style.top = (rect.bottom + 5) + 'px';
            
            document.body.appendChild(menu);

            // Ensure radio buttons reflect active proposal filters when menu opens
            syncProposalRadioButtons();
            
            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target) && !e.target.closest('th')) {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }, 100);
        }

        function applyProposalFilter(column, value) {
            // Update active filter state
            activeProposalFilters[column] = value || '';

            // First, show all proposal rows
            document.querySelectorAll('.proposal-row').forEach(row => {
                row.style.display = '';
            });

            // Then apply the current filter if it's not "all"
            if (value && value.trim() !== '') {
                const rows = document.querySelectorAll('.proposal-row');
                const target = value.toString().trim().toLowerCase();
                rows.forEach(row => {
                    const rowValue = (row.getAttribute('data-' + column) || '').toString().trim().toLowerCase();
                    if (rowValue !== target) {
                        row.style.display = 'none';
                    }
                });
            }

            // Update visual indicators
            updateProposalFilterIndicators();

            closeColumnFilter();
        }

        function getColumnNameProposal(column) {
            const names = { 'project': 'Proyek', 'pj': 'PJ', 'status': 'Status' };
            return names[column] || column;
        }

        function getProposalStatusText(status) {
            if (status === 'submitted') return 'Menunggu Approval FM';
            if (status === 'approved_fm') return '1/2 Approved (FM)';
            if (status === 'approved') return '2/2 Approved (Final)';
            return status;
        }

        // ====== CLICKABLE COLUMN HEADER FEATURES FOR REPORTS ======
        let reportSortState = { kegiatan: 'none', date: 'none' };

        function sortReportTable(column) {
            const tbody = document.querySelector('#reportsContent tbody');
            const rows = Array.from(document.querySelectorAll('.report-row'));
            
            let direction = reportSortState[column] === 'asc' ? 'desc' : 'asc';
            reportSortState[column] = direction;
            
            rows.sort((a, b) => {
                let aVal, bVal;
                if (column === 'kegiatan') {
                    aVal = a.getAttribute('data-kegiatan');
                    bVal = b.getAttribute('data-kegiatan');
                } else if (column === 'date') {
                    aVal = new Date(a.getAttribute('data-date'));
                    bVal = new Date(b.getAttribute('data-date'));
                }
                
                if (direction === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            rows.forEach((row, index) => {
                tbody.appendChild(row);
                const noCell = row.querySelector('.row-number');
                if (noCell) noCell.textContent = index + 1;
            });
            
            const icon = document.getElementById('sort-report-' + column);
            if (icon) {
                icon.className = direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
            }
            
            Object.keys(reportSortState).forEach(key => {
                if (key !== column) {
                    reportSortState[key] = 'none';
                    const otherIcon = document.getElementById('sort-report-' + key);
                    if (otherIcon) otherIcon.className = 'fas fa-sort';
                }
            });
        }

        function filterReportByColumn(column) {
            const rows = document.querySelectorAll('.report-row');
            const uniqueValues = new Set();
            
            rows.forEach(row => {
                const value = row.getAttribute('data-' + column);
                if (value) uniqueValues.add(value);
            });
            
            const menu = document.createElement('div');
            menu.id = 'columnFilterMenu';
            menu.className = 'absolute bg-white border border-gray-300 rounded-lg shadow-lg z-50 mt-2 max-h-64 overflow-y-auto';
            menu.style.minWidth = '200px';
            
            let menuHTML = `
                <div class="p-2 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Filter ${getColumnNameReport(column)}</span>
                        <button onclick="closeColumnFilter()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="p-2">
                    <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                        <input type="radio" name="filter_${column}" value="" onchange="applyReportFilter('${column}', '')" checked class="mr-2">
                        <span class="text-sm">(Semua)</span>
                    </label>
            `;

            Array.from(uniqueValues).sort().forEach(value => {
                menuHTML += `
                    <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                        <input type="radio" name="filter_${column}" value="${value}" onchange="applyReportFilter('${column}', this.value)" class="mr-2">
                        <span class="text-sm">${value}</span>
                    </label>
                `;
            });
            
            menuHTML += '</div>';
            menu.innerHTML = menuHTML;
            
            const existingMenu = document.getElementById('columnFilterMenu');
            if (existingMenu) existingMenu.remove();
            
            const event = window.event;
            const target = event.target.closest('th');
            const rect = target.getBoundingClientRect();
            
            menu.style.position = 'fixed';
            menu.style.left = rect.left + 'px';
            menu.style.top = (rect.bottom + 5) + 'px';
            
            document.body.appendChild(menu);

            // Ensure radio buttons reflect active report filters when menu opens
            syncReportRadioButtons();
            
            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target) && !e.target.closest('th')) {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }, 100);
        }

        function applyReportFilter(column, value) {
            // Update active filter state
            activeReportFilters[column] = value || '';

            // First, show all report rows
            document.querySelectorAll('.report-row').forEach(row => {
                row.style.display = '';
            });

            // Then apply the current filter if it's not "all"
            if (value && value.trim() !== '') {
                const rows = document.querySelectorAll('.report-row');
                const target = value.toString().trim().toLowerCase();
                rows.forEach(row => {
                    const rowValue = (row.getAttribute('data-' + column) || '').toString().trim().toLowerCase();
                    if (rowValue !== target) {
                        row.style.display = 'none';
                    }
                });
            }

            // Update visual indicators
            updateReportFilterIndicators();

            closeColumnFilter();
        }

        function getColumnNameReport(column) {
            const names = { 'project': 'Proyek', 'creator': 'Dibuat Oleh' };
            return names[column] || column;
        }

        // Update visual indicators on table headers for proposals
        function updateProposalFilterIndicators() {
            // Remove existing indicators
            document.querySelectorAll('.filter-indicator').forEach(indicator => {
                indicator.remove();
            });
            document.querySelectorAll('#proposalsContent th[onclick*="filterProposalByColumn"]').forEach(header => {
                header.classList.remove('filter-active');
            });

            // Add indicators for active filters in proposals tab
            Object.keys(activeProposalFilters).forEach(column => {
                const value = activeProposalFilters[column];
                if (value && value.trim() !== '') {
                    const header = document.querySelector(`#proposalsContent th[onclick*="filterProposalByColumn('${column}')"]`);
                    if (header) {
                        header.classList.add('filter-active');
                    }
                }
            });

            // Sync radio buttons for proposals
            syncProposalRadioButtons();
        }

        // Update visual indicators on table headers for reports
        function updateReportFilterIndicators() {
            // Remove existing indicators
            document.querySelectorAll('.filter-indicator').forEach(indicator => {
                indicator.remove();
            });
            document.querySelectorAll('#reportsContent th[onclick*="filterReportByColumn"]').forEach(header => {
                header.classList.remove('filter-active');
            });

            // Add indicators for active filters in reports tab
            Object.keys(activeReportFilters).forEach(column => {
                const value = activeReportFilters[column];
                if (value && value.trim() !== '') {
                    const header = document.querySelector(`#reportsContent th[onclick*="filterReportByColumn('${column}')"]`);
                    if (header) {
                        header.classList.add('filter-active');
                    }
                }
            });

            // Sync radio buttons for reports
            syncReportRadioButtons();
        }

        // Sync radio buttons with active filters for proposals
        function syncProposalRadioButtons() {
            Object.keys(activeProposalFilters).forEach(column => {
                const value = activeProposalFilters[column];
                const radioButtons = document.querySelectorAll(`#proposalsContent input[name="filter_${column}"]`);

                radioButtons.forEach(radio => {
                    if (radio.value === value) {
                        radio.checked = true;
                    } else if (value === '' && radio.value === '') {
                        radio.checked = true;
                    } else {
                        radio.checked = false;
                    }
                });
            });
        }

        // Sync radio buttons with active filters for reports
        function syncReportRadioButtons() {
            Object.keys(activeReportFilters).forEach(column => {
                const value = activeReportFilters[column];
                const radioButtons = document.querySelectorAll(`#reportsContent input[name="filter_${column}"]`);

                radioButtons.forEach(radio => {
                    if (radio.value === value) {
                        radio.checked = true;
                    } else if (value === '' && radio.value === '') {
                        radio.checked = true;
                    } else {
                        radio.checked = false;
                    }
                });
            });
        }

        // Initialize filter indicators on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateProposalFilterIndicators();
            updateReportFilterIndicators();
        });

        function closeColumnFilter() {
            const menu = document.getElementById('columnFilterMenu');
            if (menu) menu.remove();
        }

        // Date filter for DIR (proposals/reports)
        function showDateFilterDIR(context) {
            // Remove any existing menu and clean up event listeners
            const existingMenu = document.getElementById('columnFilterMenu');
            if (existingMenu) existingMenu.remove();

            const menu = document.createElement('div');
            menu.id = 'columnFilterMenu';
            menu.className = 'absolute bg-white border border-gray-300 rounded-lg shadow-lg z-50 mt-2 p-3 w-72';
            menu.innerHTML = `
                <div class=\"flex items-center justify-between mb-2\">
                    <span class=\"text-sm font-medium text-gray-700\">Filter Tanggal</span>
                    <button onclick=\"closeColumnFilter()\" class=\"text-gray-400 hover:text-gray-600\"><i class=\"fas fa-times\"></i></button>
                </div>
                <div class=\"space-y-3\">
                    <div>
                        <label class=\"block text-xs text-gray-600 mb-1\">Bulan</label>
                        <input type=\"month\" id=\"dfMonthDIR\" class=\"w-full px-2 py-1 border border-gray-300 rounded\" onchange=\"syncMonthToRangeDIR()\">
                    </div>
                    <div class=\"grid grid-cols-2 gap-2\">
                        <div>
                            <label class=\"block text-xs text-gray-600 mb-1\">Dari</label>
                            <input type=\"date\" id=\"dfFromDIR\" class=\"w-full px-2 py-1 border border-gray-300 rounded\">
                        </div>
                        <div>
                            <label class=\"block text-xs text-gray-600 mb-1\">Sampai</label>
                            <input type=\"date\" id=\"dfToDIR\" class=\"w-full px-2 py-1 border border-gray-300 rounded\">
                        </div>
                    </div>
                    <div class=\"flex justify-end space-x-2 pt-2 border-t border-gray-200\">
                        <button class=\"px-3 py-1 text-sm bg-gray-100 rounded\" onclick=\"resetDateFilterDIR('`+context+`')\">Reset</button>
                        <button class=\"px-3 py-1 text-sm bg-blue-600 text-white rounded\" onclick=\"applyDateRangeFilterDIR('`+context+`')\">Terapkan</button>
                    </div>
                </div>
            `;

            const event = window.event;
            const target = event.target.closest('th');
            const rect = target.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.left = rect.left + 'px';
            menu.style.top = (rect.bottom + 5) + 'px';
            document.body.appendChild(menu);

            setTimeout(() => {
                // Store reference to the close function for cleanup
                const closeMenuHandler = function closeMenu(e) {
                    if (!menu.contains(e.target) && !e.target.closest('th')) {
                        menu.remove();
                        document.removeEventListener('click', closeMenuHandler);
                    }
                };
                document.addEventListener('click', closeMenuHandler);
            }, 50);
        }

        function syncMonthToRangeDIR() {
            const m = document.getElementById('dfMonthDIR').value;
            if (!m) return;
            const [year, month] = m.split('-').map(Number);
            const first = new Date(year, month - 1, 1);
            const last = new Date(year, month, 0);
            document.getElementById('dfFromDIR').value = first.toISOString().slice(0,10);
            document.getElementById('dfToDIR').value = last.toISOString().slice(0,10);
        }

        function applyDateRangeFilterDIR(context) {
            const fromVal = document.getElementById('dfFromDIR').value;
            const toVal = document.getElementById('dfToDIR').value;
            const fromDate = fromVal ? new Date(fromVal) : null;
            const toDate = toVal ? new Date(toVal + 'T23:59:59') : null;
            const selector = context === 'proposals' ? '.proposal-row' : '.report-row';

            document.querySelectorAll(selector).forEach(row => {
                const rowDate = new Date(row.getAttribute('data-date'));
                let show = true;
                if (fromDate && rowDate < fromDate) show = false;
                if (toDate && rowDate > toDate) show = false;
                row.style.display = show ? '' : 'none';
            });
            closeColumnFilter();
        }

        function resetDateFilterDIR(context) {
            document.getElementById('dfMonthDIR').value = '';
            document.getElementById('dfFromDIR').value = '';
            document.getElementById('dfToDIR').value = '';
            const selector = context === 'proposals' ? '.proposal-row' : '.report-row';
            document.querySelectorAll(selector).forEach(row => row.style.display = '');
        }
    </script>
    
    <!-- Real-time Notifications -->
    <script src="assets/js/realtime_notifications.js"></script>
</body>
</html>