<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $rate = 15500.00; // Default fallback
    
    $check = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($check && $check->num_rows > 0) {
        $res = $conn->query("SELECT value FROM settings WHERE `key` = 'usd_idr_rate'");
        if ($res && $res->num_rows > 0) {
            $rate = floatval($res->fetch_assoc()['value']);
        }
    }
    
    echo json_encode(['rate' => $rate]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
