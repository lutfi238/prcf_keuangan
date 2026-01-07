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

$place_code = $_GET['place_code'] ?? '';
$kode_proyek = $_GET['kode_proyek'] ?? '';

if (empty($place_code) || empty($kode_proyek)) {
    http_response_code(400);
    echo json_encode(['error' => 'Place Code and Kode Proyek are required']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT remaining_usd, remaining_idr, budget_usd, budget_idr, used_usd, used_idr 
                           FROM project_code_budgets 
                           WHERE place_code = ? AND kode_proyek = ?");
    $stmt->bind_param("ss", $place_code, $kode_proyek);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode([
            'remaining_usd' => 0,
            'remaining_idr' => 0,
            'budget_usd' => 0,
            'budget_idr' => 0,
            'used_usd' => 0,
            'used_idr' => 0,
            'not_found' => true
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
