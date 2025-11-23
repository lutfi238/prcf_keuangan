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
    $query = "SELECT id_village, village_code, village_name, village_abbr FROM villages ORDER BY village_name ASC";
    $result = $conn->query($query);
    
    $villages = [];
    while ($row = $result->fetch_assoc()) {
        $villages[] = $row;
    }
    
    echo json_encode($villages);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
