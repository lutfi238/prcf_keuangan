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
    // Mark notifications as read - using prepared statement
    $stmt = $conn->prepare("UPDATE user SET last_notification_check = NOW() WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit();
}

// Get notifications based on role
$notifications = [];
$total_count = 0;

// Get user's last notification check for filtering new notifications
$stmt = $conn->prepare("SELECT last_notification_check FROM user WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$last_check_query = $stmt->get_result();
$last_check_data = $last_check_query ? $last_check_query->fetch_assoc() : null;
$last_notification_check = $last_check_data['last_notification_check'] ?? '1970-01-01 00:00:00';
$stmt->close();

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
                'link' => '../proposals/review_proposal_fm.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }

        // Get reports with unified timestamp field
        $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_projek, lh.updated_at as notification_time
            FROM laporan_keuangan_header lh
            WHERE lh.status_lap = 'verified' AND (lh.approved_by IS NULL OR lh.approved_by = 0)
            ORDER BY lh.updated_at DESC");

        while ($row = $report_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'report',
                'title' => 'Laporan Terverifikasi',
                'message' => $row['nama_projek'],
                'link' => '../reports/approve-report-fm.php?id=' . $row['id_laporan_keu'],
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
                'link' => '../proposals/review_proposal_dir.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }

        // Get reports approved by FM (recent 30 days)
        $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_projek, lh.updated_at as notification_time
            FROM laporan_keuangan_header lh
            WHERE lh.approved_by IS NOT NULL AND lh.approved_by > 0 AND lh.updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY lh.updated_at DESC");

        while ($row = $report_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'report',
                'title' => 'Laporan Disetujui FM',
                'message' => $row['nama_projek'],
                'link' => '../reports/approve-report-dir.php?id=' . $row['id_laporan_keu'],
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
        
        $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_projek, lh.created_at as notification_time
            FROM laporan_keuangan_header lh
            WHERE lh.status_lap = 'submitted'
            ORDER BY lh.created_at DESC");

        while ($row = $report_notifs->fetch_assoc()) {
            $notifications[] = [
                'type' => 'report',
                'title' => 'Laporan Baru',
                'message' => $row['nama_projek'],
                'link' => '../reports/approve-report-sa.php?id=' . $row['id_laporan_keu'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }
        
        // Already sorted from query, just limit
        $notifications = array_slice($notifications, 0, 10);
        break;
        
    case 'Project Manager':
        // Approved, rejected proposals and verified/approved reports (within 7 days)
        // Using prepared statements for security
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposal WHERE pemohon = ? AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $notif_approved = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM proposal WHERE pemohon = ? AND status = 'rejected' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $notif_rejected = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE created_by = ? AND status_lap IN ('verified', 'approved') AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $notif_reports = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE created_by = ? AND status_lap = 'revision_requested' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $notif_revision = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        $total_count = $notif_approved + $notif_rejected + $notif_reports + $notif_revision;

        // Approved proposals
        $stmt = $conn->prepare("SELECT id_proposal, judul_proposal, updated_at as notification_time
            FROM proposal
            WHERE pemohon = ? AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY updated_at DESC");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $approved_proposals = $stmt->get_result();
        
        while ($row = $approved_proposals->fetch_assoc()) {
            $notifications[] = [
                'type' => 'success',
                'title' => 'Proposal Disetujui',
                'message' => $row['judul_proposal'],
                'link' => '../proposals/view_proposal.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }

        // Rejected proposals
        $stmt = $conn->prepare("SELECT id_proposal, judul_proposal, updated_at as notification_time
            FROM proposal
            WHERE pemohon = ? AND status = 'rejected' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY updated_at DESC");
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $rejected_proposals = $stmt->get_result();

        while ($row = $rejected_proposals->fetch_assoc()) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Proposal Perlu Revisi',
                'message' => $row['judul_proposal'],
                'link' => '../proposals/view_proposal.php?id=' . $row['id_proposal'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }

        // Reports needing revision
        $stmt = $conn->prepare("SELECT id_laporan_keu, nama_projek, updated_at as notification_time
            FROM laporan_keuangan_header
            WHERE created_by = ? AND status_lap = 'revision_requested' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY updated_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $revision_reports = $stmt->get_result();

        while ($row = $revision_reports->fetch_assoc()) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Laporan Perlu Revisi',
                'message' => $row['nama_projek'],
                'link' => '../reports/edit_financial_report.php?id=' . $row['id_laporan_keu'],
                'time' => $row['notification_time'],
                'sort_time' => strtotime($row['notification_time'])
            ];
        }

        // Verified or approved reports
        $stmt = $conn->prepare("SELECT id_laporan_keu, nama_projek, status_lap, updated_at as notification_time
            FROM laporan_keuangan_header
            WHERE created_by = ? AND status_lap IN ('verified', 'approved') AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY updated_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $report_notifs = $stmt->get_result();

        while ($row = $report_notifs->fetch_assoc()) {
            $title = ($row['status_lap'] === 'approved') ? 'Laporan Disetujui' : 'Laporan Diverifikasi';
            $notifications[] = [
                'type' => 'success',
                'title' => $title,
                'message' => $row['nama_projek'],
                'link' => '../reports/approve-report-fm.php?id=' . $row['id_laporan_keu'],
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

