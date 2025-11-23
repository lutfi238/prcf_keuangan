<?php
session_start();
header('Content-Type: application/json');

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

// Check if user is logged in
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SESSION['user_role'] !== 'Finance Manager') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 5;
$offset = ($page - 1) * $per_page;

$user_id = $_SESSION['user_id'];

// Get user's last notification check time
$check_notif_column = $conn->query("SHOW COLUMNS FROM user LIKE 'last_notification_check'");
if ($check_notif_column && $check_notif_column->num_rows > 0) {
    $last_check_query = $conn->query("SELECT last_notification_check FROM user WHERE id_user = {$user_id}");
    $last_check_data = $last_check_query->fetch_assoc();
    $last_notification_check = $last_check_data['last_notification_check'] ?? '1970-01-01 00:00:00';
} else {
    $last_notification_check = '1970-01-01 00:00:00';
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

try {
    $notifications = [];

    // Get proposal notifications with pagination
    $proposal_query = "SELECT p.id_proposal, p.judul_proposal, p.created_at, u.nama as creator
        FROM proposal p
        LEFT JOIN user u ON p.pemohon = u.nama
        WHERE p.status = 'submitted'
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($proposal_query);
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $proposal_result = $stmt->get_result();

    while ($row = $proposal_result->fetch_assoc()) {
        $is_unread = (strtotime($row['created_at']) > strtotime($last_notification_check));
        $notifications[] = [
            'type' => 'proposal',
            'id' => $row['id_proposal'],
            'title' => 'Proposal baru: ' . $row['judul_proposal'],
            'link' => '../proposals/review_proposal_fm.php?id=' . $row['id_proposal'],
            'time' => time_elapsed_string($row['created_at']),
            'is_unread' => $is_unread,
            'created_at' => $row['created_at']
        ];
    }

    // If we have space for more notifications, get report notifications
    $remaining_slots = $per_page - count($notifications);
    if ($remaining_slots > 0) {
        $report_offset = max(0, $offset - $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'submitted'")->fetch_assoc()['count']);
        $report_query = "SELECT lh.id_laporan_keu, lh.nama_projek, lh.created_at, u.nama as creator
            FROM laporan_keuangan_header lh
            LEFT JOIN user u ON lh.created_by = u.id_user
            WHERE lh.status_lap = 'verified'
            ORDER BY lh.created_at DESC
            LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($report_query);
        $stmt->bind_param("ii", $remaining_slots, $report_offset);
        $stmt->execute();
        $report_result = $stmt->get_result();

        while ($row = $report_result->fetch_assoc()) {
            $is_unread = (strtotime($row['created_at']) > strtotime($last_notification_check));
            $notifications[] = [
                'type' => 'report',
                'id' => $row['id_laporan_keu'],
                'title' => 'Laporan sudah diverifikasi: ' . $row['nama_projek'],
                'link' => '../reports/approve-report-fm.php?id=' . $row['id_laporan_keu'] . '&return_tab=reports',
                'time' => time_elapsed_string($row['created_at']),
                'is_unread' => $is_unread,
                'created_at' => $row['created_at']
            ];
        }
    }

    // Sort notifications by created_at DESC to maintain chronological order
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Get total count for pagination info
    $total_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'submitted'")->fetch_assoc()['count'];
    $total_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'verified'")->fetch_assoc()['count'];
    $total_notifications = $total_proposals + $total_reports;

    // Calculate if there are more notifications
    $has_more = (count($notifications) === $per_page) &&
                (($total_proposals > ($offset + $per_page)) ||
                 ($total_reports > ($report_offset + $remaining_slots)));

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'has_more' => $has_more,
        'total_count' => $total_notifications,
        'page' => $page
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
