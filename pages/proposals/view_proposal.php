<?php
session_start();

// Prevent browser caching to fix back button session issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

// All roles can view (read-only)
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$proposal_id = $_GET['id'] ?? 0;

// Determine return dashboard based on role
$return_dashboard = '../dashboards/';
switch ($user_role) {
    case 'Project Manager':
        $return_dashboard .= 'dashboard_pm.php';
        break;
    case 'Staff Accountant':
        $return_dashboard .= 'dashboard_sa.php';
        break;
    case 'Finance Manager':
        $return_dashboard .= 'dashboard_fm.php';
        break;
    case 'Direktur':
        $return_dashboard .= 'dashboard_dir.php';
        break;
    default:
        $return_dashboard .= 'dashboard_pm.php';
}

// Get proposal data
$stmt = $conn->prepare("SELECT p.*, u.nama as creator_name, u.email as creator_email
    FROM proposal p 
    LEFT JOIN user u ON p.pemohon = u.nama 
    WHERE p.id_proposal = ?");
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    error_log("⚠️ view_proposal.php - Proposal not found: ID = $proposal_id");
    header("Location: $return_dashboard");
    exit();
}

// Get budget details
$stmt_budget = $conn->prepare("
    SELECT pbd.*, v.village_name 
    FROM proposal_budget_details pbd
    LEFT JOIN villages v ON pbd.id_village = v.id_village
    WHERE pbd.id_proposal = ?
    ORDER BY pbd.id_detail ASC
");
$stmt_budget->bind_param("i", $proposal_id);
$stmt_budget->execute();
$budget_details = $stmt_budget->get_result();
$budget_items = [];
while ($item = $budget_details->fetch_assoc()) {
    $budget_items[] = $item;
}

// Log access for debugging
error_log("✅ view_proposal.php - User ($user_role) viewing proposal: ID = $proposal_id, Status = " . $proposal['status']);

// Check if user can take action (redirect to appropriate review page)
// Only FM can approve - Direktur only views
$can_review = false;
$review_link = '';

if ($user_role === 'Finance Manager' && $proposal['status'] === 'submitted') {
    $can_review = true;
    $review_link = 'review_proposal_fm.php?id=' . $proposal_id;
}

// Close session writing
session_write_close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Proposal - PRCF INDONESIA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo $return_dashboard; ?>" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">View Proposal</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600"><?php echo $user_role; ?></span>
                    <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($can_review): ?>
            <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-800 px-4 py-3 rounded-lg flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Anda dapat melakukan review pada proposal ini</p>
                        <p class="text-sm">Klik tombol "Review & Approve" untuk melakukan approval.</p>
                    </div>
                </div>
                <a href="<?php echo $review_link; ?>" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    <i class="fas fa-clipboard-check mr-2"></i> Review & Approve
                </a>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg border border-gray-200">
            <!-- Proposal Header -->
            <div class="p-8 border-b border-gray-200">
                <div class="text-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">PROPOSAL KEGIATAN</h1>
                    <p class="text-gray-600">PRCF INDONESIA - Pusat Riset dan Pengembangan</p>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600">Status Proposal</p>
                            <p class="text-lg font-bold">
                                <?php 
                                $status_text = [
                                    'draft' => 'Draft',
                                    'submitted' => 'Menunggu Review FM',
                                    'approved_fm' => 'Disetujui FM (Final)',
                                    'approved' => 'Disetujui FM (Final)',
                                    'rejected' => 'Ditolak'
                                ];
                                $status_class = [
                                    'draft' => 'text-gray-800',
                                    'submitted' => 'text-yellow-800',
                                    'approved_fm' => 'text-blue-800',
                                    'approved' => 'text-green-800',
                                    'rejected' => 'text-red-800'
                                ];
                                $status_icon = [
                                    'draft' => 'fa-file',
                                    'submitted' => 'fa-clock',
                                    'approved_fm' => 'fa-check',
                                    'approved' => 'fa-check-double',
                                    'rejected' => 'fa-times'
                                ];
                                ?>
                                <i class="fas <?php echo $status_icon[$proposal['status']] ?? 'fa-question'; ?> mr-2"></i>
                                <span class="<?php echo $status_class[$proposal['status']] ?? 'text-gray-800'; ?>">
                                    <?php echo $status_text[$proposal['status']] ?? $proposal['status']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Tanggal Pengajuan</p>
                            <p class="text-lg font-bold text-gray-800">
                                <?php echo date('d/m/Y', strtotime($proposal['date'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Proposal Content -->
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Judul Proposal</label>
                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($proposal['judul_proposal']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Kode Proyek</label>
                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($proposal['kode_proyek']); ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Penanggung Jawab</label>
                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($proposal['pj']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Pemohon</label>
                        <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($proposal['pemohon']); ?></p>
                    </div>
                </div>

                <!-- Budget Details Table -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Detail Anggaran yang Diajukan</h3>
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left">Desa</th>
                                    <th class="px-4 py-3 text-left">Place Code</th>
                                    <th class="px-4 py-3 text-right">Requested (USD)</th>
                                    <th class="px-4 py-3 text-right">Requested (IDR)</th>
                                    <th class="px-4 py-3 text-left">Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                <?php if (empty($budget_items)): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-4 text-center text-gray-500 italic">Tidak ada detail anggaran.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $total_usd = 0;
                                    $total_idr = 0;
                                    foreach ($budget_items as $item): 
                                        $total_usd += $item['requested_usd'];
                                        $total_idr += $item['requested_idr'];
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-900 font-medium"><?php echo htmlspecialchars($item['village_name']); ?></td>
                                        <td class="px-4 py-3 font-mono text-blue-600"><?php echo htmlspecialchars($item['place_code']); ?></td>
                                        <td class="px-4 py-3 text-right font-mono">$<?php echo number_format($item['requested_usd'], 2); ?></td>
                                        <td class="px-4 py-3 text-right font-mono">Rp <?php echo number_format($item['requested_idr'], 0, ',', '.'); ?></td>
                                        <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($item['description']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-gray-50 font-bold border-t-2 border-gray-200">
                                        <td colspan="2" class="px-4 py-3 text-right text-gray-700">TOTAL</td>
                                        <td class="px-4 py-3 text-right text-blue-800 font-mono">$<?php echo number_format($total_usd, 2); ?></td>
                                        <td class="px-4 py-3 text-right text-blue-800 font-mono">Rp <?php echo number_format($total_idr, 0, ',', '.'); ?></td>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2 italic">* Exchange Rate Submission: 1 USD = Rp <?php echo number_format($proposal['exrate_at_submission'], 0, ',', '.'); ?></p>
                </div>

                <?php if (!empty($proposal['tor']) && file_exists($proposal['tor'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">Terms of Reference (TOR)</label>
                    <div class="flex items-center space-x-4 p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="bg-green-500 p-3 rounded flex-shrink-0">
                            <i class="fas fa-file-pdf text-white text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800">File TOR</p>
                            <p class="text-sm text-gray-600 truncate"><?php echo basename($proposal['tor']); ?></p>
                        </div>
                        <a href="<?php echo $proposal['tor']; ?>" target="_blank" download
                            class="flex-shrink-0 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200">
                            <i class="fas fa-download mr-2"></i> Download
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($proposal['file_budget']) && file_exists($proposal['file_budget'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">Lampiran Budget/RAB</label>
                    <div class="flex items-center space-x-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="bg-blue-500 p-3 rounded flex-shrink-0">
                            <i class="fas fa-file-excel text-white text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800">File Budget</p>
                            <p class="text-sm text-gray-600 truncate"><?php echo basename($proposal['file_budget']); ?></p>
                        </div>
                        <a href="<?php echo $proposal['file_budget']; ?>" target="_blank" download
                            class="flex-shrink-0 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200">
                            <i class="fas fa-download mr-2"></i> Download
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($proposal['status'] === 'rejected' && !empty($proposal['catatan_fm'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">
                        <i class="fas fa-comment-alt mr-2 text-red-500"></i>Catatan Revisi dari Finance Manager
                    </label>
                    <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3 mt-1"></i>
                            <div class="flex-1">
                                <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($proposal['catatan_fm']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Footer Info -->
            <div class="p-8 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center text-gray-600">
                    <i class="fas fa-info-circle text-xl mr-3"></i>
                    <div class="text-sm">
                        <p class="font-medium">Halaman View Only (Read-Only)</p>
                        <p>Halaman ini hanya untuk melihat detail proposal. Approval dilakukan oleh Finance Manager.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript to handle back button and prevent cache issues -->
    <script>
        // Detect browser back button navigation
        window.addEventListener('pageshow', function(event) {
            // If page is loaded from browser cache (back button)
            if (event.persisted) {
                console.log('Page loaded from cache (back button) - reloading...');
                window.location.reload();
            }
        });

        // Log page access for debugging
        console.log('✅ view_proposal.php loaded - Proposal ID: <?php echo $proposal_id; ?>, Status: <?php echo $proposal['status']; ?>, Role: <?php echo $user_role; ?>');
    </script>
</body>
</html>
