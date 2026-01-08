<?php
/**
 * Real-Time Updates API - Server-Sent Events (SSE)
 * Streams real-time notifications and dashboard updates to clients
 */

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

require_once '../includes/config.php';

// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Get user ID from session (if available)
session_start();
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? '';

// CRITICAL: Close session writing immediately to prevent locking!
// If this is kept open, the while(true) loop will block all other pages.
session_write_close();

if (!$user_id) {
    echo "event: error\n";
    echo "data: {\"message\": \"Not authenticated\"}\n\n";
    flush();
    exit();
}

// Function to send SSE message
function sendSSE($event, $data) {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Function to calculate time elapsed (same as dashboard)
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

// Keep connection alive and send updates every 5 seconds
$last_check = time();

while (true) {
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }
    
    // Get current time
    $current_time = time();
    
    // Send updates every 5 seconds
    if ($current_time - $last_check >= 5) {
        $updates = [];
        
        // Get notification counts based on role
        switch ($user_role) {
            case 'Finance Manager':
                // Count pending proposals
                $pending_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'submitted'")->fetch_assoc()['count'];
                
                // Count pending reports (verified by SA)
                $pending_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'verified'")->fetch_assoc()['count'];
                
                $updates['pending_proposals'] = $pending_proposals;
                $updates['pending_reports'] = $pending_reports;
                $updates['total_notifications'] = $pending_proposals + $pending_reports;
                break;
                
            case 'Direktur':
                // Count approved proposals (by FM - DIR only views, FM approval is final)
                $pending_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved_fm'")->fetch_assoc()['count'];
                
                // Count approved reports (by FM - DIR only views, FM approval is final)
                $pending_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'approved'")->fetch_assoc()['count'];
                
                $updates['pending_proposals'] = $pending_proposals;
                $updates['pending_reports'] = $pending_reports;
                $updates['total_notifications'] = $pending_proposals + $pending_reports;
                break;
                
            case 'Staff Accountant':
                // Count submitted reports (waiting for SA validation)
                $pending_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'submitted'")->fetch_assoc()['count'];
                
                $updates['pending_reports'] = $pending_reports;
                $updates['total_notifications'] = $pending_reports;
                break;
                
            case 'Project Manager':
                // Count revision requests - FIXED: Use prepared statements
                $stmt_proposals = $conn->prepare("SELECT COUNT(*) as count FROM proposal WHERE status = 'revision_requested' AND created_by = ?");
                $stmt_proposals->bind_param("i", $user_id);
                $stmt_proposals->execute();
                $revision_proposals = $stmt_proposals->get_result()->fetch_assoc()['count'];
                
                $stmt_reports = $conn->prepare("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'revision_requested' AND created_by = ?");
                $stmt_reports->bind_param("i", $user_id);
                $stmt_reports->execute();
                $revision_reports = $stmt_reports->get_result()->fetch_assoc()['count'];
                
                $updates['revision_proposals'] = $revision_proposals;
                $updates['revision_reports'] = $revision_reports;
                $updates['total_notifications'] = $revision_proposals + $revision_reports;
                break;
        }
        
        // Get latest notifications
        $notifications = [];
        
        // Get recent notifications based on role
        if ($user_role === 'Finance Manager') {
            // Pending proposals
            $proposal_notifs = $conn->query("SELECT p.id_proposal, p.judul_proposal, p.created_at, u.nama as creator
                FROM proposal p
                LEFT JOIN user u ON p.pemohon = u.nama
                WHERE p.status = 'submitted'
                ORDER BY p.created_at DESC LIMIT 5");
            
            while ($row = $proposal_notifs->fetch_assoc()) {
                $notifications[] = [
                    'type' => 'proposal',
                    'id' => $row['id_proposal'],
                    'title' => 'New Proposal: ' . $row['judul_proposal'],
                    'link' => '../proposals/review_proposal_fm.php?id=' . $row['id_proposal'],
                    'time' => time_elapsed_string($row['created_at']),
                    'sort_time' => strtotime($row['created_at']),
                    'is_unread' => true
                ];
            }
            
            // Pending reports
            $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_projek, lh.created_at, u.nama as creator
                FROM laporan_keuangan_header lh
                LEFT JOIN user u ON lh.created_by = u.id_user
                WHERE lh.status_lap = 'verified'
                ORDER BY lh.created_at DESC LIMIT 5");

            while ($row = $report_notifs->fetch_assoc()) {
                $notifications[] = [
                    'type' => 'report',
                    'id' => $row['id_laporan_keu'],
                    'title' => 'Validated Report: ' . $row['nama_projek'],
                    'link' => '../reports/approve-report-fm.php?id=' . $row['id_laporan_keu'],
                    'time' => time_elapsed_string($row['created_at']),
                    'sort_time' => strtotime($row['created_at']),
                    'is_unread' => true
                ];
            }
        } elseif ($user_role === 'Direktur') {
            // Approved proposals waiting for Director
            $proposal_notifs = $conn->query("SELECT p.id_proposal, p.judul_proposal, p.updated_at, u.nama as creator 
                FROM proposal p 
                LEFT JOIN user u ON p.pemohon = u.nama 
                WHERE p.status = 'approved_fm' 
                ORDER BY p.updated_at DESC LIMIT 3");
            
            while ($row = $proposal_notifs->fetch_assoc()) {
                $notifications[] = [
                    'type' => 'proposal',
                    'id' => $row['id_proposal'],
                    'title' => 'Proposal (FM Approved): ' . $row['judul_proposal'],
                    'link' => '../proposals/view_proposal.php?id=' . $row['id_proposal'],
                    'time' => time_elapsed_string($row['updated_at']),
                    'sort_time' => strtotime($row['updated_at']),
                    'is_unread' => true
                ];
            }
            
            // Approved reports by FM (DIR only views, FM approval is final)
            $report_notifs = $conn->query("SELECT lh.id_laporan_keu, lh.nama_projek, lh.updated_at, u.nama as creator
                FROM laporan_keuangan_header lh
                LEFT JOIN user u ON lh.created_by = u.id_user
                WHERE lh.status_lap = 'approved'
                ORDER BY lh.updated_at DESC LIMIT 3");

            while ($row = $report_notifs->fetch_assoc()) {
                $notifications[] = [
                    'type' => 'report',
                    'id' => $row['id_laporan_keu'],
                    'title' => 'Report (FM Approved): ' . $row['nama_projek'],
                    'link' => '../reports/view_report_dir.php?id=' . $row['id_laporan_keu'] . '&return_tab=reports',
                    'time' => time_elapsed_string($row['updated_at']),
                    'sort_time' => strtotime($row['updated_at']),
                    'is_unread' => true
                ];
            }
        }
        
        $updates['notifications'] = $notifications;
        $updates['timestamp'] = date('Y-m-d H:i:s');
        
        // Send update event
        sendSSE('update', $updates);
        
        $last_check = $current_time;
    }
    
    // Send heartbeat to keep connection alive
    if ($current_time % 30 == 0) {
        sendSSE('heartbeat', ['timestamp' => date('Y-m-d H:i:s')]);
    }
    
    // Sleep for 1 second before next iteration
    sleep(1);
}

