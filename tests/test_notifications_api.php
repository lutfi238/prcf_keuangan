<?php
/**
 * Quick Test Script for Notification API
 * 
 * Usage:
 * 1. Open this file in browser: http://localhost/prcf_keuangan/test_notifications_api.php
 * 2. Login to your dashboard in another tab
 * 3. This script will fetch notifications for your session
 */

session_start();
require_once '../includes/config.php';

// Check if logged in
if (!isset($_SESSION['logged_in'])) {
    die('❌ Not logged in. Please login to dashboard first, then open this page.');
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Notification API</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .notification { padding: 10px; margin: 10px 0; background: #f9f9f9; border-left: 4px solid #4CAF50; }
        .proposal { border-left-color: #2196F3; }
        .report { border-left-color: #4CAF50; }
        .warning { border-left-color: #FF9800; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h1 { color: #333; }
        h2 { color: #666; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔔 Notification API Test</h1>
";

echo "<p class='info'><strong>Current User:</strong> {$user_name} ({$user_role})</p>";

// Test API call
echo "<h2>Test Results</h2>";

$response = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api_notifications.php?action=get');
$data = json_decode($response, true);

if ($data && isset($data['success']) && $data['success']) {
    echo "<p class='success'>✅ API Call Successful!</p>";
    
    echo "<p><strong>Total Notifications:</strong> " . $data['total_count'] . "</p>";
    
    if (count($data['notifications']) > 0) {
        echo "<h3>Notifications (Chronological Order - Newest First):</h3>";
        
        foreach ($data['notifications'] as $index => $notif) {
            $typeClass = $notif['type'];
            $time = $notif['time'];
            $sortTime = isset($notif['sort_time']) ? date('Y-m-d H:i:s', $notif['sort_time']) : 'N/A';
            $displayIndex = $index + 1;
            
            echo "<div class='notification {$typeClass}'>";
            echo "<strong>#{$displayIndex}</strong> - ";
            echo "<strong>{$notif['title']}</strong><br>";
            echo "📄 {$notif['message']}<br>";
            echo "🔗 <a href='{$notif['link']}'>{$notif['link']}</a><br>";
            echo "⏰ Time: {$time}<br>";
            echo "<small>Sort timestamp: {$sortTime}</small>";
            echo "</div>";
        }
        
        // Check chronological order
        echo "<h3>✅ Chronological Order Check:</h3>";
        $is_sorted = true;
        $prev_sort_time = PHP_INT_MAX;
        
        foreach ($data['notifications'] as $notif) {
            if (isset($notif['sort_time'])) {
                if ($notif['sort_time'] > $prev_sort_time) {
                    $is_sorted = false;
                    echo "<p class='error'>❌ Order broken at: {$notif['title']}</p>";
                    break;
                }
                $prev_sort_time = $notif['sort_time'];
            }
        }
        
        if ($is_sorted) {
            echo "<p class='success'>✅ All notifications are in correct chronological order (newest first)!</p>";
        }
        
    } else {
        echo "<p class='info'>ℹ️ No notifications found for your role.</p>";
    }
    
    // Show raw JSON
    echo "<h3>Raw API Response:</h3>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    
} else {
    echo "<p class='error'>❌ API Call Failed!</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Database queries debug
echo "<h2>Database Debug Info</h2>";

switch ($user_role) {
    case 'Finance Manager':
        $pending_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'submitted'")->fetch_assoc()['count'];
        $verified_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'verified' AND (approved_by IS NULL OR approved_by = 0)")->fetch_assoc()['count'];
        
        echo "<p>📊 <strong>Pending Proposals:</strong> {$pending_proposals}</p>";
        echo "<p>📊 <strong>Verified Reports (not approved):</strong> {$verified_reports}</p>";
        break;
        
    case 'Direktur':
        $approved_proposals = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
        $approved_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE approved_by IS NOT NULL AND approved_by > 0 AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
        
        echo "<p>📊 <strong>Approved Proposals (30 days):</strong> {$approved_proposals}</p>";
        echo "<p>📊 <strong>Reports Approved by FM (30 days):</strong> {$approved_reports}</p>";
        break;
        
    case 'Staff Accountant':
        $submitted_reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE status_lap = 'submitted'")->fetch_assoc()['count'];
        
        echo "<p>📊 <strong>Submitted Reports:</strong> {$submitted_reports}</p>";
        break;
        
    case 'Project Manager':
        $approved = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE pemohon = '{$user_name}' AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
        $rejected = $conn->query("SELECT COUNT(*) as count FROM proposal WHERE pemohon = '{$user_name}' AND status = 'rejected' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
        $reports = $conn->query("SELECT COUNT(*) as count FROM laporan_keuangan_header WHERE created_by = {$user_id} AND status_lap IN ('verified', 'approved') AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
        
        echo "<p>📊 <strong>Approved Proposals (7 days):</strong> {$approved}</p>";
        echo "<p>📊 <strong>Rejected Proposals (7 days):</strong> {$rejected}</p>";
        echo "<p>📊 <strong>Verified/Approved Reports (7 days):</strong> {$reports}</p>";
        break;
}

// Determine dashboard link
$dashboard_link = 'dashboard_pm.php';
if ($user_role === 'Finance Manager') {
    $dashboard_link = 'dashboard_fm.php';
} elseif ($user_role === 'Direktur') {
    $dashboard_link = 'dashboard_dir.php';
} elseif ($user_role === 'Staff Accountant') {
    $dashboard_link = 'dashboard_sa.php';
}

echo "
<h2>✅ Test Complete</h2>
<p><a href='javascript:location.reload()'>🔄 Refresh Test</a> | <a href='{$dashboard_link}'>🏠 Back to Dashboard</a></p>
</div>
</body>
</html>";
?>

