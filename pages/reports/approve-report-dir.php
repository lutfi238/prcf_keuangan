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

// Handle final approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_approve'])) {
    // Update to final approved status
    $stmt = $conn->prepare("UPDATE laporan_keuangan_header SET status_lap = 'approved' WHERE id_laporan_keu = ? AND approved_by IS NOT NULL");
    $stmt->bind_param("i", $report_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Get report details
        $report_stmt = $conn->prepare("SELECT lh.*, u.email as pm_email, u.nama as pm_name, 
            u2.email as fm_email, u2.nama as fm_name,
            u3.email as sa_email, u3.nama as sa_name
            FROM laporan_keuangan_header lh 
            LEFT JOIN user u ON lh.created_by = u.id_user 
            LEFT JOIN user u2 ON lh.approved_by = u2.id_user
            LEFT JOIN user u3 ON lh.verified_by = u3.id_user
            WHERE id_laporan_keu = ?");
        $report_stmt->bind_param("i", $report_id);
        $report_stmt->execute();
        $report_data = $report_stmt->get_result()->fetch_assoc();
        
        // Notify PM, FM, and SA
        send_notification_email(
            $report_data['pm_email'],
            'Laporan Keuangan Telah Disetujui Direktur',
            'Laporan keuangan Anda untuk kegiatan "' . $report_data['nama_projek'] . '" telah disetujui oleh Direktur. Laporan telah final.'
        );
        
        if ($report_data['fm_email']) {
            send_notification_email(
                $report_data['fm_email'],
                'Laporan Keuangan Telah Disetujui Direktur',
                'Laporan keuangan untuk kegiatan "' . $report_data['nama_projek'] . '" telah disetujui oleh Direktur.'
            );
        }
        
        if ($report_data['sa_email']) {
            send_notification_email(
                $report_data['sa_email'],
                'Laporan Keuangan Telah Disetujui Direktur',
                'Laporan keuangan untuk kegiatan "' . $report_data['nama_projek'] . '" telah disetujui oleh Direktur.'
            );
        }
        
        $success = 'Laporan berhasil di-approve! Status final telah ditetapkan.';
    } else {
        $error = 'Laporan belum di-approve oleh Finance Manager atau sudah di-approve sebelumnya.';
    }
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
    <title>Approve Laporan - Direktur</title>
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
                    <h1 class="text-xl font-bold text-gray-800">Final Approval - Direktur</h1>
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
                            <div class="w-16 h-1 <?php echo $report['approved_by'] ? 'bg-green-400' : 'bg-gray-300'; ?>"></div>
                            
                            <!-- DIR Approval -->
                            <div class="flex items-center">
                                <?php if ($report['status_lap'] === 'approved' && $report['approved_by']): ?>
                                    <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white">
                                        <i class="fas fa-check"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-purple-400 flex items-center justify-center text-white animate-pulse">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Direktur</p>
                                    <p class="text-xs text-gray-600">
                                        <?php echo ($report['status_lap'] === 'approved' && $report['approved_by']) ? 'Approved' : 'Pending'; ?>
                                    </p>
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

            <!-- Final Approval Section -->
            <?php if ($report['approved_by'] && $report['status_lap'] !== 'approved'): ?>
            <div class="p-8 border-t border-gray-200 bg-purple-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Final Approval - Direktur (Stage 2/2)</h3>
                
                <form method="POST" class="space-y-4">
                    <div class="bg-white p-6 rounded-lg border border-purple-200">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-signature text-purple-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 mb-2">Tanda Tangan Digital - Direktur</p>
                                <p class="text-sm text-gray-600 mb-4">Sebagai Direktur, approval Anda akan menjadikan laporan ini berstatus FINAL dan tidak dapat diubah lagi.</p>
                                <div class="flex items-center space-x-2 text-sm text-gray-600">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo $user_name; ?></span>
                                    <span>•</span>
                                    <span><?php echo date('d/m/Y H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium mb-1">Informasi Penting:</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Laporan telah divalidasi oleh Staff Accounting</li>
                                    <li>Laporan telah di-approve oleh Finance Manager</li>
                                    <li>Approval Anda akan finalisasi dokumen ini</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="final_approve"
                            class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-200 font-medium text-lg shadow-lg"
                            onclick="return confirm('KONFIRMASI FINAL APPROVAL (Stage 2/2)\n\nApakah Anda yakin ingin memberikan approval final?\n\nSetelah di-approve, laporan tidak dapat diubah lagi.')">
                            <i class="fas fa-check-double mr-2"></i> Approve (Stage 2/2)
                        </button>
                    </div>
                </form>
            </div>
            <?php elseif ($report['status_lap'] === 'approved'): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-double text-3xl mr-3"></i>
                    <div>
                        <p class="font-bold text-lg">Laporan Telah Mendapat Final Approval</p>
                        <p class="text-sm">Status: FINAL - Laporan tidak dapat diubah lagi.</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-8 border-t border-gray-200 bg-yellow-50">
                <div class="flex items-center text-yellow-700">
                    <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Menunggu Approval Finance Manager</p>
                        <p class="text-sm">Laporan belum dapat di-approve karena masih menunggu approval dari Finance Manager.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>