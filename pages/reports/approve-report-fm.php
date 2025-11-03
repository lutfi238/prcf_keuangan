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

// Allow Finance Manager to approve, PM can view only
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if (!in_array($_SESSION['user_role'], ['Finance Manager', 'Project Manager'])) {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_role = $_SESSION['user_role'];

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$report_id = $_GET['id'] ?? 0;
$return_tab = $_GET['return_tab'] ?? 'proposals'; // Default to proposals if not specified

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    $stmt = $conn->prepare("UPDATE laporan_keuangan_header SET status_lap = 'approved', approved_by = ?, updated_at = NOW() WHERE id_laporan_keu = ?");
    $stmt->bind_param("ii", $user_id, $report_id);
    
    if ($stmt->execute()) {
        // Get report details
        $report_stmt = $conn->prepare("SELECT lh.*, u.email, u.nama FROM laporan_keuangan_header lh LEFT JOIN user u ON lh.created_by = u.id_user WHERE id_laporan_keu = ?");
        $report_stmt->bind_param("i", $report_id);
        $report_stmt->execute();
        $report_data = $report_stmt->get_result()->fetch_assoc();
        
        // Notify PM and SA
        send_notification_email(
            $report_data['email'],
            'Laporan Keuangan Telah Diapprove oleh FM',
            'Laporan keuangan Anda untuk kegiatan "' . $report_data['nama_projek'] . '" telah disetujui oleh Finance Manager.'
        );
        
        $success = 'Laporan berhasil di-approve!';
    }
}


// Get report data
$stmt = $conn->prepare("SELECT lh.*, u.nama as creator_name, u2.nama as verifier_name 
    FROM laporan_keuangan_header lh 
    LEFT JOIN user u ON lh.created_by = u.id_user 
    LEFT JOIN user u2 ON lh.verified_by = u2.id_user
    WHERE lh.id_laporan_keu = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    header('Location: ../dashboards/dashboard_fm.php?tab=' . urlencode($return_tab));
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
    <title>Approve Laporan - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_fm.php?tab=<?php echo urlencode($return_tab); ?>" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Approve Laporan Keuangan</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="../dashboards/dashboard_fm.php?tab=<?php echo urlencode($return_tab); ?>" class="block mt-2 text-green-800 underline">Kembali ke Dashboard</a>
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
                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm font-medium text-gray-700 mb-3">Status Approval:</p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Staff Accounting</p>
                                    <p class="text-xs text-gray-600"><?php echo $report['verifier_name']; ?></p>
                                </div>
                            </div>
                            <div class="w-16 h-1 bg-gray-300"></div>
                            <div class="flex items-center">
                                <?php if ($report['approved_by']): ?>
                                    <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white">
                                        <i class="fas fa-check"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center text-white">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Finance Manager</p>
                                    <p class="text-xs text-gray-600"><?php echo $report['approved_by'] ? 'Approved' : 'Pending'; ?></p>
                                </div>
                            </div>
                            <div class="w-16 h-1 bg-gray-300"></div>
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Direktur</p>
                                    <p class="text-xs text-gray-600">Pending</p>
                                </div>
                            </div>
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
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Rincian Pengeluaran</h3>
                    <div class="flex items-center space-x-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                        <i class="fas fa-calculator text-blue-600"></i>
                        <span class="text-xs text-blue-800 font-medium">Formula: Actual Cost = Unit Total × Unit Cost</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">No</th>
                                <th class="px-4 py-2 text-left">Invoice Date</th>
                                <th class="px-4 py-2 text-left">Deskripsi</th>
                                <th class="px-4 py-2 text-left">Penerima</th>
                                <th class="px-4 py-2 text-right">Unit Total</th>
                                <th class="px-4 py-2 text-right">Unit Cost</th>
                                <th class="px-4 py-2 text-right">Budget</th>
                                <th class="px-4 py-2 text-right">Actual Cost</th>
                                <th class="px-4 py-2 text-right">Balance</th>
                                <th class="px-4 py-2 text-center">Nota</th>
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
                                <td class="px-4 py-2"><?php echo $item['invoice_date'] ? date('d/m/Y', strtotime($item['invoice_date'])) : '-'; ?></td>
                                <td class="px-4 py-2"><?php echo $item['item_desc']; ?></td>
                                <td class="px-4 py-2"><?php echo $item['recipient']; ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['unit_total'] ?? 0, 0); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['unit_cost'] ?? 0, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['requested'], 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($calculated_actual, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['requested'] - $calculated_actual, 2); ?></td>
                                <td class="px-4 py-2 text-center">
                                    <?php if (!empty($item['file_nota'])): ?>
                                        <?php $isImage = preg_match('/\.(jpg|jpeg|png|gif|bmp|webp|tif|tiff)$/i', $item['file_nota']); ?>
                                        <button onclick="previewReceipt('<?php echo htmlspecialchars($item['file_nota']); ?>', <?php echo $isImage ? 'true' : 'false'; ?>)" 
                                            class="inline-flex items-center px-3 py-1 bg-blue-500 text-white rounded-full text-xs hover:bg-blue-600">
                                            <i class="fas fa-<?php echo $isImage ? 'image' : 'file-pdf'; ?> mr-1"></i> Preview
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <tr class="bg-gray-50 font-bold">
                                <td colspan="3" class="px-4 py-2 text-right">TOTAL:</td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($total_budget, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($total_actual, 2); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($total_budget - $total_actual, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if ($report['catatan_finance']): ?>
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm font-medium text-gray-700 mb-2">Catatan dari Staff Accounting:</p>
                    <p class="text-sm text-gray-600"><?php echo nl2br($report['catatan_finance']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Approval Section -->
            <?php if (in_array($report['status_lap'], ['submitted', 'verified']) && !$report['approved_by']): ?>
                <?php if ($user_role === 'Finance Manager'): ?>
            <div class="p-8 border-t border-gray-200 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Final Approval - Finance Manager</h3>
                
                <form method="POST" class="space-y-4">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-signature text-green-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 mb-2">Tanda Tangan Digital - Finance Manager</p>
                                <p class="text-sm text-gray-600 mb-4">Dengan menekan tombol "Approve", Anda menyetujui laporan keuangan ini dan memberikan tanda tangan digital sebagai Finance Manager. Laporan akan dikirim ke Direktur untuk approval final.</p>
                                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 mb-4">
                                    <div class="flex items-start">
                                        <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-2"></i>
                                        <div class="text-sm text-blue-800">
                                            <p class="font-medium">Informasi:</p>
                                            <p>Laporan telah divalidasi oleh Staff Accounting. Jika laporan memerlukan revisi, silakan hubungi Staff Accounting untuk mengirimkan kembali ke Project Manager.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 text-sm text-gray-600">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo $user_name; ?></span>
                                    <span>•</span>
                                    <span><?php echo date('d/m/Y H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="approve"
                            class="px-8 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200 font-medium text-lg shadow-lg"
                            onclick="return confirm('Apakah Anda yakin ingin meng-approve laporan ini?\n\nLaporan akan dikirim ke Direktur untuk approval final.')">
                            <i class="fas fa-check-circle mr-2"></i> Approve
                        </button>
                    </div>
                </form>
            </div>
                <?php else: ?>
            <!-- PM View Only -->
            <div class="p-8 border-t border-gray-200 bg-blue-50">
                <div class="flex items-center text-blue-700">
                    <i class="fas fa-info-circle text-2xl mr-3"></i>
                    <p class="text-sm">Laporan ini sedang menunggu approval dari Finance Manager.</p>
                </div>
            </div>
                <?php endif; ?>
            </div>
            <?php elseif ($report['approved_by']): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Laporan Telah Di-Approve oleh Finance Manager</p>
                        <p class="text-sm">Menunggu approval dari Direktur untuk finalisasi.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Receipt Preview Modal -->
    <div id="receiptPreviewModal" class="fixed z-50 inset-0 bg-black/80 hidden items-center justify-center">
        <div class="flex flex-col items-center max-w-7xl max-h-screen p-4">
            <button onclick="closeReceiptPreview()" class="mb-4 self-end text-white bg-black/60 hover:bg-black/80 px-4 py-2 rounded-lg">
                <i class="fas fa-times text-lg mr-2"></i>Close
            </button>
            <img id="modalReceiptImage" src="" alt="Preview" class="hidden max-h-[85vh] max-w-full rounded shadow-2xl" />
            <embed id="modalReceiptPDF" src="" type="application/pdf" class="hidden w-full h-[85vh] rounded shadow-2xl" />
        </div>
    </div>

    <script>
        function previewReceipt(filePath, isImage) {
            const modal = document.getElementById('receiptPreviewModal');
            const imgElement = document.getElementById('modalReceiptImage');
            const pdfElement = document.getElementById('modalReceiptPDF');
            
            imgElement.classList.add('hidden');
            pdfElement.classList.add('hidden');
            
            if (isImage) {
                imgElement.src = filePath;
                imgElement.classList.remove('hidden');
            } else {
                pdfElement.src = filePath;
                pdfElement.classList.remove('hidden');
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeReceiptPreview() {
            const modal = document.getElementById('receiptPreviewModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('modalReceiptImage').src = '';
            document.getElementById('modalReceiptPDF').src = '';
        }
        
        document.getElementById('receiptPreviewModal').addEventListener('click', function(e) {
            if (e.target === this) closeReceiptPreview();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeReceiptPreview();
        });
    </script>
</body>
</html>