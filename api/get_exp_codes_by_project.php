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

$kode_proyek = $_GET['kode_proyek'] ?? '';

if (empty($kode_proyek)) {
    http_response_code(400);
    echo json_encode(['error' => 'Project code required']);
    exit();
}

try {
    $id_village = $_GET['id_village'] ?? '';

    // Build query to fetch valid exp codes from assignment table
    $sql = "SELECT DISTINCT exp_code, place_code, description 
            FROM project_village_expcodes 
            WHERE kode_proyek = ?";
    
    $params = ["s", $kode_proyek];
    
    if (!empty($id_village)) {
        $sql .= " AND id_village = ?";
        $params[0] .= "i";
        $params[] = $id_village;
    }
    
    $sql .= " ORDER BY exp_code ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exp_codes = [];
    while ($row = $result->fetch_assoc()) {
        $exp_codes[] = [
            'exp_code' => $row['exp_code'],
            'place_code' => $row['place_code'],
            'description' => $row['description']
        ];
    }
    
    echo json_encode($exp_codes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
