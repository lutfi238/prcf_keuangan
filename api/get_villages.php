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
    // Filter out deleted villages and include audit trail
    $query = "SELECT v.id_village, v.village_code, v.village_name, v.village_abbr,
              uc.nama as created_by_name, 
              uu.nama as updated_by_name,
              v.created_at, v.updated_at
              FROM villages v
              LEFT JOIN user uc ON v.created_by = uc.id_user
              LEFT JOIN user uu ON v.updated_by = uu.id_user
              WHERE v.is_deleted = 0
              ORDER BY v.village_name ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception($conn->error);
    }
    
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
