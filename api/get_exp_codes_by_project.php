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
    // Get distinct exp codes that have budget allocated for this project
    $stmt = $conn->prepare("
        SELECT DISTINCT exp_code, place_code
        FROM project_code_budgets 
        WHERE kode_proyek = ?
        ORDER BY exp_code ASC
    ");
    $stmt->bind_param("s", $kode_proyek);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exp_codes = [];
    while ($row = $result->fetch_assoc()) {
        // Only add unique exp codes (not duplicates)
        if (!in_array($row['exp_code'], array_column($exp_codes, 'exp_code'))) {
            $exp_codes[] = [
                'exp_code' => $row['exp_code'],
                'example_place_code' => $row['place_code']
            ];
        }
    }
    
    echo json_encode($exp_codes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
