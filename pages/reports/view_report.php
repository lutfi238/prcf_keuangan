<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Allow PM, FM, SA, DIR to view
if (!in_array($_SESSION['user_role'], ['Project Manager', 'Finance Manager', 'Staff Accountant', 'Direktur'])) {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$report_id = $_GET['id'] ?? 0;

// Determine return dashboard
$return_dashboard = match($user_role) {
    'Project Manager' => '../dashboards/dashboard_pm.php',
    'Finance Manager' => '../dashboards/dashboard_fm.php',
    'Staff Accountant' => '../dashboards/dashboard_sa.php',
    'Direktur' => '../dashboards/dashboard_dir.php',
    default => '../dashboards/dashboard_pm.php'
};

// Get report header
$stmt = $conn->prepare("SELECT lh.*, u.nama as creator_name 
    FROM laporan_keuangan_header lh
    LEFT JOIN user u ON lh.created_by = u.id_user
    WHERE lh.id_laporan_keu = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header('Location: ' . $return_dashboard);
    exit();
}

// Get report details
$details_stmt = $conn->prepare("SELECT * FROM laporan_keuangan_detail WHERE id_laporan_keu = ? ORDER BY id_detail_keu ASC");
if ($details_stmt) {
    $details_stmt->bind_param("i", $report_id);
    $details_stmt->execute();
    $details = $details_stmt->get_result();
} else {
    // Fallback to avoid fatal errors on bind_param if prepare fails
    $details = $conn->query("SELECT * FROM laporan_keuangan_detail WHERE id_laporan_keu = " . (int)$report_id . " ORDER BY id_detail_keu ASC");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Laporan Keuangan - PRCF INDONESIA</title>
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
                    <h1 class="text-xl font-bold text-gray-800">View Laporan Keuangan</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8 border border-gray-200">
            <!-- Header Info -->
            <div class="text-center mb-6 pb-4 border-b">
                <h1 class="text-3xl font-bold text-gray-800">LAPORAN KEUANGAN KEGIATAN</h1>
                <p class="text-gray-600">PRCF INDONESIA - Pusat Riset dan Pengembangan</p>
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i><?php echo $user_role; ?> - READ ONLY
                </p>
            </div>

            <!-- Report Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-600">Kode Proyek</label>
                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($report['kode_projek']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Nama Proyek</label>
                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($report['nama_projek']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Nama Kegiatan</label>
                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($report['nama_kegiatan']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Pelaksana</label>
                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($report['pelaksana']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Tanggal Pelaksanaan</label>
                    <p class="text-gray-800 font-medium"><?php echo date('d/m/Y', strtotime($report['tanggal_pelaksanaan'])); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Tanggal Laporan</label>
                    <p class="text-gray-800 font-medium"><?php echo date('d/m/Y', strtotime($report['tanggal_laporan'])); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Mata Uang</label>
                    <p class="text-gray-800 font-medium"><?php echo $report['mata_uang']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Exchange Rate</label>
                    <p class="text-gray-800 font-medium"><?php echo number_format($report['exrate'], 4); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Status</label>
                    <p class="text-gray-800 font-medium">
                        <?php
                        $status_badges = [
                            'draft' => '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Draft</span>',
                            'submitted' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Menunggu Validasi</span>',
                            'verified' => '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Terverifikasi</span>',
                            'approved' => '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Disetujui</span>',
                            'rejected' => '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Ditolak</span>'
                        ];
                        echo $status_badges[$report['status_lap']] ?? $report['status_lap'];
                        ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600">Dibuat Oleh</label>
                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($report['creator_name']); ?></p>
                </div>
            </div>

            <!-- Details Table -->
            <div class="mt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Detail Pengeluaran</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">No</th>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">Invoice No</th>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">Invoice Date</th>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">Item</th>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">Recipient</th>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">Place Code</th>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">Exp Code</th>
                                <th class="border border-gray-200 px-3 py-2 text-right text-xs font-medium text-gray-700">Unit Total</th>
                                <th class="border border-gray-200 px-3 py-2 text-right text-xs font-medium text-gray-700">Unit Cost</th>
                                <th class="border border-gray-200 px-3 py-2 text-right text-xs font-medium text-gray-700">Requested</th>
                                <th class="border border-gray-200 px-3 py-2 text-right text-xs font-medium text-gray-700">Actual</th>
                                <th class="border border-gray-200 px-3 py-2 text-right text-xs font-medium text-gray-700">Balance</th>
                                <th class="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-700">Explanation</th>
                                <th class="border border-gray-200 px-3 py-2 text-center text-xs font-medium text-gray-700">Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            $total_requested = 0;
                            $total_actual = 0;
                            $total_balance = 0;
                            
                            while ($detail = $details->fetch_assoc()): 
                                $total_requested += $detail['requested'];
                                $total_actual += $detail['actual'];
                                $total_balance += $detail['balance'];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo $no++; ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['invoice_no']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo $detail['invoice_date'] ? date('d/m/Y', strtotime($detail['invoice_date'])) : '-'; ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['item_desc']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['recipient']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['place_code']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['exp_code']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($detail['unit_total'], 0); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($detail['unit_cost'], 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($detail['requested'], 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($detail['actual'], 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right <?php echo $detail['balance'] < 0 ? 'text-red-600 font-semibold' : ''; ?>">
                                    <?php echo number_format($detail['balance'], 2); ?>
                                </td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['explanation']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-center text-sm">
                                    <?php if (!empty($detail['file_nota'])): ?>
                                        <?php $isImage = preg_match('/\.(jpg|jpeg|png|gif|bmp|webp|tif|tiff)$/i', $detail['file_nota']); ?>
                                        <?php if ($isImage): ?>
                                            <a href="<?php echo htmlspecialchars($detail['file_nota']); ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-500 text-white rounded-full text-xs hover:bg-blue-600">
                                                <i class="fas fa-image mr-1"></i> Preview
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($detail['file_nota']); ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-500 text-white rounded-full text-xs hover:bg-blue-600">
                                                <i class="fas fa-file-pdf mr-1"></i> Unduh
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot class="bg-gray-100 font-semibold">
                            <tr>
                                <td colspan="9" class="border border-gray-200 px-3 py-2 text-right text-sm">TOTAL:</td>
                                <td class="border border-gray-200 px-3 py-2 text-right text-sm"><?php echo number_format($total_requested, 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-right text-sm"><?php echo number_format($total_actual, 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-right text-sm <?php echo $total_balance < 0 ? 'text-red-600' : ''; ?>">
                                    <?php echo number_format($total_balance, 2); ?>
                                </td>
                                <td class="border border-gray-200"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Back Button -->
            <div class="mt-6 flex justify-center">
                <a href="<?php echo $return_dashboard; ?>" 
                    class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </main>
</body>
</html>
