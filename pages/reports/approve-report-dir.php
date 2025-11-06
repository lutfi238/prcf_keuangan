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

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Direktur') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$report_id = $_GET['id'] ?? 0;
$return_tab = $_GET['return_tab'] ?? 'reports'; // Default to reports if not specified

// DIR only views - no approval functionality
// Redirect any POST requests to view page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: view_report_dir.php?id=' . $report_id . '&return_tab=' . urlencode($return_tab));
    exit();
}

// Get report data
$stmt = $conn->prepare("SELECT lh.*, u.nama as creator_name, 
    u2.nama as verifier_name, u3.nama as approver_name 
    FROM laporan_keuangan_header lh 
    LEFT JOIN user u ON lh.created_by = u.id_user 
    LEFT JOIN user u2 ON lh.verified_by = u2.id_user
    LEFT JOIN user u3 ON lh.approved_by = u3.id_user
    WHERE lh.id_laporan_keu = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header('Location: ../dashboards/dashboard_dir.php?tab=' . urlencode($return_tab));
    exit();
}

// Get report details
$details = $conn->prepare("SELECT * FROM laporan_keuangan_detail WHERE id_laporan_keu = ?");
$details->bind_param("i", $report_id);
$details->execute();
$items = $details->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Laporan - Direktur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_dir.php?tab=<?php echo urlencode($return_tab); ?>" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">View Laporan Keuangan</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="../dashboards/dashboard_dir.php?tab=<?php echo urlencode($return_tab); ?>" class="block mt-2 text-green-800 underline">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg border border-gray-200">
            <!-- Report Header -->
            <div class="p-8 border-b border-gray-200">
                <div class="text-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">LAPORAN KEUANGAN KEGIATAN</h1>
                    <p class="text-gray-600">PRCFI - Pusat Riset dan Pengembangan</p>
                </div>

                <!-- Approval Status Bar -->
                <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <p class="text-sm font-medium text-gray-700 mb-3">Status Approval:</p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <!-- SA Approval -->
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Staff Accounting</p>
                                    <p class="text-xs text-gray-600"><?php echo $report['verifier_name']; ?></p>
                                </div>
                            </div>
                            <div class="w-16 h-1 bg-green-400"></div>
                            
                            <!-- FM Approval -->
                            <div class="flex items-center">
                                <?php if ($report['approved_by']): ?>
                                    <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white">
                                        <i class="fas fa-check"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Finance Manager</p>
                                    <p class="text-xs text-gray-600">
                                        <?php echo $report['approver_name'] ?? 'Pending'; ?>
                                    </p>
                                </div>
                            </div>
                            <!-- FM Approval is Final - No DIR step needed -->
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Kode Proyek:</p>
                        <p class="font-medium text-gray-800"><?php echo $report['kode_projek']; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Nama Proyek:</p>
                        <p class="font-medium text-gray-800"><?php echo $report['nama_projek']; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Nama Kegiatan:</p>
                        <p class="font-medium text-gray-800"><?php echo $report['nama_projek']; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Pelaksana:</p>
                        <p class="font-medium text-gray-800"><?php echo $report['pelaksana']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Report Details -->
            <div class="p-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Pengeluaran</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">No</th>
                                <th class="px-4 py-2 text-left">Deskripsi</th>
                                <th class="px-4 py-2 text-left">Penerima</th>
                                <th class="px-4 py-2 text-right">Unit Total</th>
                                <th class="px-4 py-2 text-right">Unit Cost</th>
                                <th class="px-4 py-2 text-right">Budget</th>
                                <th class="px-4 py-2 text-right">Actual Cost</th>
                                <th class="px-4 py-2 text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php 
                            $no = 1;
                            $total_budget = 0;
                            $total_actual = 0;
                            while ($item = $items->fetch_assoc()): 
                                // Calculate actual cost as Unit Total × Unit Cost
                                $calculated_actual = ($item['unit_total'] ?? 0) * ($item['unit_cost'] ?? 0);
                                $total_budget += $item['requested'];
                                $total_actual += $calculated_actual;
                            ?>
                            <tr>
                                <td class="px-4 py-2"><?php echo $no++; ?></td>
                                <td class="px-4 py-2"><?php echo $item['item_desc']; ?></td>
                                <td class="px-4 py-2"><?php echo $item['recipient']; ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['unit_total'] ?? 0, 0); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['unit_cost'] ?? 0, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['requested'], 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($calculated_actual, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['requested'] - $calculated_actual, 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <tr class="bg-gray-50 font-bold">
                                <td colspan="5" class="px-4 py-2 text-right">TOTAL:</td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($total_budget, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($total_actual, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($total_budget - $total_actual, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DIR View Only - FM approval is final -->
            <?php if ($report['status_lap'] === 'approved'): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-circle text-3xl mr-3"></i>
                    <div>
                        <p class="font-bold text-lg">Laporan Disetujui oleh Finance Manager</p>
                        <p class="text-sm mt-1">
                            Laporan ini telah disetujui oleh Finance Manager 
                            <?php if (!empty($report['approver_name'])): ?>
                                (<strong><?php echo $report['approver_name']; ?></strong>)
                            <?php endif; ?>.
                            <br>Sebagai Direktur, Anda dapat melihat detail laporan ini untuk review. Approval dari Finance Manager adalah final.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>