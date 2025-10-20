<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Get action
$action = $_GET['action'] ?? 'get';

if ($action === 'mark_read') {
    // Mark notifications as read
    $conn->query("UPDATE user SET last_notification_check = NOW() WHERE id_user = {$user_id}");
    echo json_encode(['success' => true]);
    exit();
}

// Get notifications based on role
$notifications = [];
$total_count = 0;

// Get user's last notification check for filtering new notifications
$last_check_query = $conn->query("SELECT last_notification_check FROM user WHERE id_user = {$user_id}");
$last_check_data = $last_check_query ? $last_check_query->fetch_assoc() : null;
$last_notification_check = $last_check_data['last_notification_check'] ?? '1970-01-01 00:00:00';

switch ($user_role) {
    case 'Finance Manager':
        // Pending proposals
        $notif_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'submitted'")->fetch_assoc()['count'];
        // Verified reports (not approved yet)
        $notif_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'verified' AND (approved_by IS NULL OR approved_by = 0)")->fetch_assoc()['count'];
        $total_count = $notif_proposals + $notif_reports;
        
        // Get proposals with unified timestamp field
        $proposal_notifs = $conn->query("SELECT p.id_proposal, p.judul_proposal, p.created_at as notification_time, 'proposal' as notif_type, u.nama as creator 
            FROM proposal p 
            LEFT JOIN user u ON p.pemohon = u.nama 
            WHERE p.status = 'submitted' 
            ORDER BY p.created_at DESC");
        
        while ($row = $proposal_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'proposal',
                'title' => 'Proposal Baru',
                'message' => $row['judul_proposal'],
                'link' => 'review_proposal_fm.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Get reports with unified timestamp field
        $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_kegiatan, lh.updated_at as notification_time
            FROM laporan_keuangan_header lh 
            WHERE lh.status_lap = 'verified' AND (lh.approved_by IS NULL OR lh.approved_by = 0)
            ORDER BY lh.updated_at DESC");
        
        while ($row = $report_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'report',
                'title' => 'Laporan Terverifikasi',
                'message' => $row['nama_kegiatan'],
                'link' => 'approve_report.php?id=' . $row['id_laporan_keu'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Sort all notifications by time DESC (newest first)
        usort($notifications, function($a, $b) {
            return $b['sort_time'] - $a['sort_time'];
        });
        
        // Limit to 10 most recent
        $notifications = array_slice($notifications, 0, 10);
        break;
        
    case 'Direktur':
        // Count recent approved proposals (within 30 days)
        $notif_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
        // Count reports approved by FM (within 30 days)
        $notif_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE approved_by IS NOT NULL AND approved_by > 0 AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
        $total_count = $notif_proposals + $notif_reports;
        
        // Get recent approved proposals
        $proposal_notifs = $conn->query("SELECT p.id_proposal, p.judul_proposal, p.updated_at as notification_time
            FROM proposal p 
            WHERE p.status = 'approved' AND p.updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY p.updated_at DESC");
        
        while ($row = $proposal_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'proposal',
                'title' => 'Proposal Disetujui FM',
                'message' => $row['judul_proposal'],
                'link' => 'review_proposal_dir.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Get reports approved by FM (recent 30 days)
        $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_kegiatan, lh.updated_at as notification_time
            FROM laporan_keuangan_header lh 
            WHERE lh.approved_by IS NOT NULL AND lh.approved_by > 0 AND lh.updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY lh.updated_at DESC");
        
        while ($row = $report_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'report',
                'title' => 'Laporan Disetujui FM',
                'message' => $row['nama_kegiatan'],
                'link' => 'approve_report_dir.php?id=' . $row['id_laporan_keu'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Sort all notifications by time DESC (newest first)
        usort($notifications, function($a, $b) {
            return $b['sort_time'] - $a['sort_time'];
        });
        
        // Limit to 10 most recent
        $notifications = array_slice($notifications, 0, 10);
        break;
        
    case 'Staff Accountant':
        // Submitted reports (not verified yet)
        $notif_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'submitted'")->fetch_assoc()['count'];
        $total_count = $notif_reports;
        
        $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_kegiatan, lh.created_at as notification_time
            FROM laporan_keuangan_header lh 
            WHERE lh.status_lap = 'submitted' 
            ORDER BY lh.created_at DESC");
        
        while ($row = $report_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'report',
                'title' => 'Laporan Baru',
                'message' => $row['nama_kegiatan'],
                'link' => 'validate_report.php?id=' . $row['id_laporan_keu'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Already sorted from query, just limit
        $notifications = array_slice($notifications, 0, 10);
        break;
        
    case 'Project Manager':
        // Approved, rejected proposals and verified/approved reports (within 7 days)
        $notif_approved = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE pemohon = '{$user_name}' AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
        $notif_rejected = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE pemohon = '{$user_name}' AND status = 'rejected' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
        $notif_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE created_by = {$user_id} AND status_lap IN ('verified', 'approved') AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
        $total_count = $notif_approved + $notif_rejected + $notif_reports;
        
        // Approved proposals
        $approved_proposals = $conn->query("SELECT id_proposal, judul_proposal, updated_at as notification_time
            FROM proposal 
            WHERE pemohon = '{$user_name}' AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY updated_at DESC");
        
        while ($row = $approved_proposals->fetch_assoc()) {
            $notifications[] = [
                'type' => 'success',
                'title' => 'Proposal Disetujui',
                'message' => $row['judul_proposal'],
                'link' => 'view_proposal.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Rejected proposals
        $rejected_proposals = $conn->query("SELECT id_proposal, judul_proposal, updated_at as notification_time
            FROM proposal 
            WHERE pemohon = '{$user_name}' AND status = 'rejected' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY updated_at DESC");
        
        while ($row = $rejected_proposals->fetch_assoc()) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Proposal Perlu Revisi',
                'message' => $row['judul_proposal'],
                'link' => 'view_proposal.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Verified or approved reports
        $report_notifs = $conn->query("SELECT id_laporan_keu, nama_kegiatan, status_lap, updated_at as notification_time
            FROM laporan_keuangan_header 
            WHERE created_by = {$user_id} AND status_lap IN ('verified', 'approved') AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY updated_at DESC");
        
        while ($row = $report_notifs->fetch_assoc()) {
            $title = ($row['status_lap'] === 'approved') ? 'Laporan Disetujui' : 'Laporan Diverifikasi';
            $notifications[] = [
                'type' => 'success',
                'title' => $title,
                'message' => $row['nama_kegiatan'],
                'link' => 'approve_report.php?id=' . $row['id_laporan_keu'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Sort all notifications by time DESC (newest first)
        usort($notifications, function($a, $b) {
            return $b['sort_time'] - $a['sort_time'];
        });
        
        // Limit to 10 most recent
        $notifications = array_slice($notifications, 0, 10);
        break;
}

echo json_encode([
    'success' => true,
    'total_count' => $total_count,
    'notifications' => $notifications
]);

