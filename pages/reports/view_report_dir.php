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

// Allow only Direktur to view
if ($_SESSION['user_role'] !== 'Direktur') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$report_id = $_GET['id'] ?? 0;

// Determine return dashboard
$return_dashboard = '../dashboards/dashboard_dir.php';

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <p class="text-gray-800 font-medium"><?php echo htmlspecialchars($report['nama_projek']); ?></p>
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
                            'submitted' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Pending Validation</span>',
                            'verified' => '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Verified</span>',
                            'revision_requested' => '<span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">Needs Revision (FM)</span>',
                            'approved' => '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Approved</span>',
                            'rejected' => '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Rejected</span>'
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
                                // Calculate actual cost as Unit Total × Unit Cost
                                $calculated_actual = $detail['unit_total'] * $detail['unit_cost'];
                                $total_requested += $detail['requested'];
                                $total_actual += $calculated_actual;
                                $total_balance += ($detail['requested'] - $calculated_actual);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo $no++; ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo $detail['invoice_date'] ? date('d/m/Y', strtotime($detail['invoice_date'])) : '-'; ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['item_desc']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['recipient']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['place_code']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['exp_code']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($detail['unit_total'], 0); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($detail['unit_cost'], 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($detail['requested'], 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right"><?php echo number_format($calculated_actual, 2); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-sm text-right <?php echo ($detail['requested'] - $calculated_actual) < 0 ? 'text-red-600 font-semibold' : ''; ?>">
                                    <?php echo number_format($detail['requested'] - $calculated_actual, 2); ?>
                                </td>
                                <td class="border border-gray-200 px-3 py-2 text-sm"><?php echo htmlspecialchars($detail['explanation']); ?></td>
                                <td class="border border-gray-200 px-3 py-2 text-center text-sm">
                                    <?php if (!empty($detail['file_nota'])): ?>
                                        <?php $isImage = preg_match('/\.(jpg|jpeg|png|gif|bmp|webp|tif|tiff)$/i', $detail['file_nota']); ?>
                                        <button onclick="previewReceipt('<?php echo htmlspecialchars($detail['file_nota']); ?>', <?php echo $isImage ? 'true' : 'false'; ?>)" 
                                            class="inline-flex items-center px-3 py-1 bg-blue-500 text-white rounded-full text-xs hover:bg-blue-600">
                                            <i class="fas fa-<?php echo $isImage ? 'image' : 'file-pdf'; ?> mr-1"></i> Preview
                                        </button>
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

            <!-- Approval Section -->
            <?php if ($report['status_lap'] === 'approved_fm' && !$report['approved_by_dir']): ?>
            <div class="p-8 border-t border-gray-200 bg-blue-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-clipboard-check mr-2 text-blue-600"></i>Final Approval - Direktur
                </h3>
                
                <div class="mb-4 p-3 bg-blue-100 border border-blue-300 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Info:</strong> Menyetujui laporan ini akan memproses <strong>Financial Settlement</strong> (Debit/Credit Bank dan Adjustment Budget) secara otomatis.
                    </p>
                </div>
                
                <form action="authorize-report-dir.php?id=<?php echo $report_id; ?>&return_tab=reports" method="POST" class="space-y-4" onsubmit="confirmApproval(event)">
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-signature text-green-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 mb-2">Tanda Tangan Digital - Direktur</p>
                                <p class="text-sm text-gray-600 mb-4">Dengan menekan tombol "Final Approve", Anda menyetujui laporan keuangan ini.</p>
                                <div class="flex items-center space-x-2 text-sm text-gray-600">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo $user_name; ?></span>
                                    <span>•</span>
                                    <span><?php echo date('d/m/Y H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="submit" name="approve"
                            class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-medium text-lg shadow-lg">
                            <i class="fas fa-check-double mr-2"></i> Final Approve & Settle
                        </button>
                    </div>
                </form>
            </div>
            <?php elseif ($report['status_lap'] === 'approved'): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Laporan Telah Disetujui Sepenuhnya (Settled)</p>
                        <p class="text-sm">Settlement keuangan telah diproses dan budget telah disesuaikan.</p>
                        <?php if ($report['dir_approval_date']): ?>
                            <p class="text-xs mt-1">Disetujui Direktur pada: <?php echo date('d/m/Y H:i', strtotime($report['dir_approval_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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

        function confirmApproval(e) {
            e.preventDefault();
            const form = e.target;
            
            Swal.fire({
                title: 'Final Approve & Settle?',
                html: `<div class="text-left text-sm">
                        <p class="mb-3">Apakah Anda yakin ingin menyetujui laporan ini? Tindakan ini akan:</p>
                        <ol class="list-decimal ml-5 space-y-1">
                            <li>Memfinalisasi laporan secara permanen.</li>
                            <li>Memproses settlement keuangan (Bank & Budget).</li>
                            <li>Mengirim notifikasi ke PM dan FM.</li>
                        </ol>
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563EB', // blue-600
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Final Approve!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Inject hidden input to ensure 'approve' key exists
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'approve';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>