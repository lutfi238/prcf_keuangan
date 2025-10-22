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

if ($_SESSION['user_role'] !== 'Staff Accountant') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$report_id = $_GET['id'] ?? 0;

// Handle validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['validate'])) {
        $catatan = $_POST['catatan_finance'];
        $stmt = $conn->prepare("UPDATE laporan_keuangan_header SET status_lap = 'verified', verified_by = ?, catatan_finance = ? WHERE id_laporan_keu = ?");
        $stmt->bind_param("isi", $user_id, $catatan, $report_id);
        
        if ($stmt->execute()) {
            // Get report details and creator
            $report_stmt = $conn->prepare("SELECT lh.*, u.email, u.nama FROM laporan_keuangan_header lh LEFT JOIN user u ON lh.created_by = u.id_user WHERE id_laporan_keu = ?");
            $report_stmt->bind_param("i", $report_id);
            $report_stmt->execute();
            $report_data = $report_stmt->get_result()->fetch_assoc();
            
            // Notify PM
            send_notification_email(
                $report_data['email'],
                'Laporan Keuangan Telah Divalidasi',
                'Laporan keuangan Anda untuk kegiatan "' . $report_data['nama_kegiatan'] . '" telah divalidasi oleh Staff Accounting.'
            );
            
            // Notify FM and Director
            $fm_dir = $conn->query("SELECT email, nama FROM user WHERE role IN ('Finance Manager', 'Direktur')");
            while ($user = $fm_dir->fetch_assoc()) {
                send_notification_email(
                    $user['email'],
                    'Laporan Keuangan Siap untuk Review',
                    'Laporan keuangan untuk kegiatan "' . $report_data['nama_kegiatan'] . '" telah divalidasi dan siap untuk di-review.'
                );
            }
            
            $success = 'Laporan berhasil divalidasi!';
        }
    } elseif (isset($_POST['request_revision'])) {
        $catatan = $_POST['catatan_finance'];
        $stmt = $conn->prepare("UPDATE laporan_keuangan_header SET status_lap = 'rejected', verified_by = ?, catatan_finance = ? WHERE id_laporan_keu = ?");
        $stmt->bind_param("isi", $user_id, $catatan, $report_id);
        
        if ($stmt->execute()) {
            // Get report details and creator
            $report_stmt = $conn->prepare("SELECT lh.*, u.email FROM laporan_keuangan_header lh LEFT JOIN user u ON lh.created_by = u.id_user WHERE id_laporan_keu = ?");
            $report_stmt->bind_param("i", $report_id);
            $report_stmt->execute();
            $report_data = $report_stmt->get_result()->fetch_assoc();
            
            // Notify PM
            send_notification_email(
                $report_data['email'],
                'Laporan Keuangan Perlu Revisi',
                'Laporan keuangan Anda untuk kegiatan "' . $report_data['nama_kegiatan'] . '" memerlukan perbaikan. Catatan: ' . $catatan
            );
            
            $success = 'Permintaan revisi berhasil dikirim!';
        }
    }
}

// Get report data
$stmt = $conn->prepare("SELECT lh.*, u.nama as creator_name, u.email as creator_email 
    FROM laporan_keuangan_header lh 
    LEFT JOIN user u ON lh.created_by = u.id_user 
    WHERE lh.id_laporan_keu = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header('Location: ../dashboards/dashboard_sa.php');
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
    <title>Validasi Laporan - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_sa.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Validasi Laporan Keuangan</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="../dashboards/dashboard_sa.php" class="block mt-2 text-green-800 underline">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg border border-gray-200">
            <!-- Report Header -->
            <div class="p-8 border-b border-gray-200">
                <div class="text-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">LAPORAN KEUANGAN KEGIATAN</h1>
                    <p class="text-gray-600">PRCFI - Pusat Riset dan Pengembangan</p>
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
                        <p class="font-medium text-gray-800"><?php echo $report['nama_kegiatan']; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Pelaksana:</p>
                        <p class="font-medium text-gray-800"><?php echo $report['pelaksana']; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Tanggal Pelaksanaan:</p>
                        <p class="font-medium text-gray-800"><?php echo date('d/m/Y', strtotime($report['tanggal_pelaksanaan'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Tanggal Laporan:</p>
                        <p class="font-medium text-gray-800"><?php echo date('d/m/Y', strtotime($report['tanggal_laporan'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Mata Uang:</p>
                        <p class="font-medium text-gray-800"><?php echo $report['mata_uang']; ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Exchange Rate:</p>
                        <p class="font-medium text-gray-800"><?php echo number_format($report['exrate'], 4); ?></p>
                    </div>
                </div>
            </div>

            <!-- Report Details -->
            <div class="p-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Pengeluaran</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs border border-gray-300">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="border border-gray-300 px-2 py-2 text-left font-semibold">Invoice No</th>
                                <th class="border border-gray-300 px-2 py-2 text-left font-semibold">Invoice Date</th>
                                <th class="border border-gray-300 px-2 py-2 text-left font-semibold">Item Description</th>
                                <th class="border border-gray-300 px-2 py-2 text-left font-semibold">Recipient</th>
                                <th class="border border-gray-300 px-2 py-2 text-left font-semibold">Place Code</th>
                                <th class="border border-gray-300 px-2 py-2 text-left font-semibold">Exp Code</th>
                                <th class="border border-gray-300 px-2 py-2 text-right font-semibold">Unit Total</th>
                                <th class="border border-gray-300 px-2 py-2 text-right font-semibold">Unit Cost</th>
                                <th class="border border-gray-300 px-2 py-2 text-right font-semibold">Requested</th>
                                <th class="border border-gray-300 px-2 py-2 text-right font-semibold">Actual</th>
                                <th class="border border-gray-300 px-2 py-2 text-right font-semibold">Balance</th>
                                <th class="border border-gray-300 px-2 py-2 text-left font-semibold">Explanation</th>
                                <th class="border border-gray-300 px-2 py-2 text-center font-semibold">Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_requested = 0;
                            $total_actual = 0;
                            $total_balance = 0;
                            while ($item = $items->fetch_assoc()): 
                                $total_requested += $item['requested'];
                                $total_actual += $item['actual'];
                                $total_balance += $item['balance'];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border border-gray-300 px-2 py-2"><?php echo htmlspecialchars($item['invoice_no'] ?? '-'); ?></td>
                                <td class="border border-gray-300 px-2 py-2">
                                    <?php echo $item['invoice_date'] ? date('d-M-y', strtotime($item['invoice_date'])) : '-'; ?>
                                </td>
                                <td class="border border-gray-300 px-2 py-2"><?php echo htmlspecialchars($item['item_desc']); ?></td>
                                <td class="border border-gray-300 px-2 py-2"><?php echo htmlspecialchars($item['recipient'] ?? '-'); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-center"><?php echo htmlspecialchars($item['place_code'] ?? '-'); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-center"><?php echo htmlspecialchars($item['exp_code'] ?? '-'); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-right"><?php echo number_format($item['unit_total'] ?? 0, 0); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-right"><?php echo number_format($item['unit_cost'] ?? 0, 2); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-right"><?php echo number_format($item['requested'], 2); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-right"><?php echo number_format($item['actual'], 2); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-right <?php echo $item['balance'] < 0 ? 'text-red-600 font-semibold' : ''; ?>">
                                    <?php echo number_format($item['balance'], 2); ?>
                                </td>
                                <td class="border border-gray-300 px-2 py-2"><?php echo htmlspecialchars($item['explanation'] ?? '-'); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-center">
                                    <?php if (!empty($item['file_nota'])): ?>
                                        <?php $isImage = preg_match('/\.(jpg|jpeg|png|gif|bmp|webp|tif|tiff)$/i', $item['file_nota']); ?>
                                        <?php if ($isImage): ?>
                                            <a href="<?php echo htmlspecialchars($item['file_nota']); ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-500 text-white rounded-full text-xs hover:bg-blue-600">
                                                <i class="fas fa-image mr-1"></i> Preview
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($item['file_nota']); ?>" target="_blank" class="inline-flex items-center px-3 py-1 bg-blue-500 text-white rounded-full text-xs hover:bg-blue-600">
                                                <i class="fas fa-file-pdf mr-1"></i> Unduh
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <tr class="bg-gray-100 font-bold">
                                <td colspan="8" class="border border-gray-300 px-2 py-2 text-right">TOTAL:</td>
                                <td class="border border-gray-300 px-2 py-2 text-right"><?php echo number_format($total_requested, 2); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-right"><?php echo number_format($total_actual, 2); ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-right <?php echo $total_balance < 0 ? 'text-red-600' : ''; ?>">
                                    <?php echo number_format($total_balance, 2); ?>
                                </td>
                                <td class="border border-gray-300"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Validation Form -->
            <?php if ($report['status_lap'] === 'submitted'): ?>
            <div class="p-8 border-t border-gray-200 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Validasi Laporan</h3>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Catatan untuk Project Manager</label>
                        <textarea name="catatan_finance" rows="4" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Berikan catatan atau komentar terkait laporan ini..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="submit" name="request_revision"
                            class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition duration-200 font-medium"
                            onclick="return confirm('Apakah Anda yakin ingin meminta revisi laporan ini?')">
                            <i class="fas fa-edit mr-2"></i> Minta Revisi
                        </button>
                        <button type="submit" name="validate"
                            class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200 font-medium"
                            onclick="return confirm('Apakah Anda yakin laporan ini sudah valid?')">
                            <i class="fas fa-check-circle mr-2"></i> Validasi & Kirim ke FM
                        </button>
                    </div>
                </form>
            </div>
            <?php elseif ($report['status_lap'] === 'verified'): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Laporan Telah Divalidasi</p>
                        <p class="text-sm">Laporan ini telah divalidasi dan dikirim ke Finance Manager untuk approval.</p>
                    </div>
                </div>
                <?php if ($report['catatan_finance']): ?>
                <div class="mt-4 p-4 bg-white rounded border border-green-200">
                    <p class="text-sm font-medium text-gray-700 mb-2">Catatan:</p>
                    <p class="text-sm text-gray-600"><?php echo nl2br($report['catatan_finance']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>