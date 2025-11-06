<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$kode_proyek = $_GET['kode_proyek'] ?? '';
$include_used = isset($_GET['include_used']) && $_GET['include_used'] === '1';

if (empty($kode_proyek)) {
    echo json_encode(['success' => false, 'message' => 'Kode proyek required']);
    exit();
}

// Fetch approved proposals for the selected project
if ($include_used) {
    // Include all approved proposals (used for checking if any exist)
    $stmt = $conn->prepare("SELECT id_proposal, judul_proposal, pj, date
        FROM proposal
        WHERE kode_proyek = ? AND status = 'approved'
        ORDER BY created_at DESC");
} else {
    // Exclude those already used in financial reports
    $stmt = $conn->prepare("SELECT id_proposal, judul_proposal, pj, date
        FROM proposal p
        WHERE kode_proyek = ? AND status = 'approved'
        AND NOT EXISTS (
            SELECT 1 FROM laporan_keuangan_header lh
            WHERE lh.nama_projek = p.judul_proposal
        )
        ORDER BY created_at DESC");
}
$stmt->bind_param("s", $kode_proyek);
$stmt->execute();
$result = $stmt->get_result();

$proposals = [];
while ($row = $result->fetch_assoc()) {
    $proposals[] = $row;
}

echo json_encode([
    'success' => true,
    'proposals' => $proposals
]);
?>

