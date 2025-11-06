<?php
/**
 * API Endpoint: Get Place Codes for Autocomplete
 * Description: Returns project-specific place codes matching search term
 * Parameters:
 *   - kode_proyek (required): Project code
 *   - search_term (optional): Search term to filter codes
 */

header('Content-Type: application/json');
require_once '../includes/config.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get and validate parameters
$kode_proyek = $_GET['kode_proyek'] ?? '';
$search_term = $_GET['search_term'] ?? '';

if (empty($kode_proyek)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter kode_proyek is required']);
    exit();
}

// Sanitize inputs
$kode_proyek = $conn->real_escape_string($kode_proyek);
$search_term = $conn->real_escape_string($search_term);

// Build query
$query = "SELECT 
    pc.id,
    pc.place_code,
    pc.exp_code,
    pc.activity_code,
    pc.description,
    pcs.subcategory_name,
    pcc.category_name
FROM project_codes pc
LEFT JOIN project_code_subcategories pcs ON pc.subcategory_id = pcs.id
LEFT JOIN project_code_categories pcc ON pcs.category_id = pcc.id
WHERE pc.kode_proyek = '$kode_proyek'";

// Add search filter if provided
if (!empty($search_term)) {
    $query .= " AND (
        pc.place_code LIKE '$search_term%' OR 
        pc.exp_code LIKE '$search_term%' OR 
        pc.activity_code LIKE '%$search_term%' OR
        pc.description LIKE '%$search_term%'
    )";
}

$query .= " ORDER BY pc.place_code ASC LIMIT 50";

// Execute query
$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    exit();
}

// Format results
$codes = [];
while ($row = $result->fetch_assoc()) {
    $codes[] = [
        'id' => $row['id'],
        'place_code' => $row['place_code'],
        'exp_code' => $row['exp_code'],
        'activity_code' => $row['activity_code'],
        'description' => $row['description'],
        'subcategory' => $row['subcategory_name'],
        'category' => $row['category_name'],
        'label' => $row['place_code'] . ' - ' . $row['description']
    ];
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => count($codes),
    'codes' => $codes
]);
