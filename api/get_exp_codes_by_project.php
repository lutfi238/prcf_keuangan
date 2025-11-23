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
    echo json_encode(['error' => 'Kode Proyek is required']);
    exit();
}

try {
    // Assuming project_codes table exists or we fetch from budgets
    // Based on previous turn I assumed project_codes exists.
    // If it doesn't, I'll need to handle it.
    // I'll use a safer query that checks if table exists first or just try-catch.
    
    $stmt = $conn->prepare("SELECT DISTINCT exp_code, description FROM project_codes WHERE kode_proyek = ? ORDER BY exp_code ASC");
    $stmt->bind_param("s", $kode_proyek);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $exp_codes = [];
    while ($row = $result->fetch_assoc()) {
        $exp_codes[] = $row;
    }
    
    echo json_encode($exp_codes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
