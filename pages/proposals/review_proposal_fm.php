<?php
session_start();

// Prevent browser caching to fix back button session issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';
require_once '../../includes/finance_functions.php';

// Check maintenance mode
check_maintenance();

// Only Finance Manager can access
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Finance Manager') {
    header('Location: ../dashboards/dashboard_fm.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$proposal_id = $_GET['id'] ?? 0;
$return_tab = $_GET['return_tab'] ?? 'proposals'; // Default to proposals if not specified

// Handle FM Approval (Stage 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $conn->begin_transaction();
        try {
            // 1. Get Full Proposal Data
            $prop_stmt = $conn->prepare("SELECT * FROM proposal WHERE id_proposal = ?");
            $prop_stmt->bind_param("i", $proposal_id);
            $prop_stmt->execute();
            $prop_data = $prop_stmt->get_result()->fetch_assoc();
            
            // 2. Get Budget Details
            $bud_stmt = $conn->prepare("SELECT * FROM proposal_budget_details WHERE id_proposal = ?");
            $bud_stmt->bind_param("i", $proposal_id);
            $bud_stmt->execute();
            $budget_details = $bud_stmt->get_result();
            
            // 3. Bank Transaction (Credit)
            $id_bank_header = get_or_create_bank_header($conn, $prop_data['kode_proyek'], date('Y-m-d'));
            $id_detail_bank = generate_id('BD');
            
            // Generate Voucher No: BB-[id_bank]-PROP-[id_proposal]
            // Using id_detail_bank as the Bank Transaction ID
            $voucher_no = "BB-" . $id_detail_bank . "-PROP-" . str_pad($proposal_id, 6, '0', STR_PAD_LEFT);
            
            $bank_stmt = $conn->prepare("INSERT INTO buku_bank_detail (id_detail_bank, id_bank_header, tanggal, reff, title_activity, cost_description, recipient, place_code, exp_code, nominal_code, exrate, cost_curr, credit_idr, credit_usd, balance_idr, balance_usd, status) VALUES (?, ?, NOW(), ?, ?, ?, ?, '-', '-', 'Adv', ?, ?, ?, ?, 0, 0, 'ongoing')");
            
            $desc = "Advance for: " . $prop_data['judul_proposal'];
            $cost_curr = $prop_data['currency'];
            $exrate = $prop_data['exchange_rate'];
            $credit_idr = $prop_data['total_budget_idr'];
            $credit_usd = $prop_data['total_budget_usd'];
            
            $bank_stmt->bind_param("ssssssssdddd", $id_detail_bank, $id_bank_header, $voucher_no, $prop_data['judul_proposal'], $desc, $prop_data['pj'], $exrate, $cost_curr, $credit_idr, $credit_usd);
            $bank_stmt->execute();
            
            update_bank_header_balance($conn, $id_bank_header, $credit_idr, $credit_usd, true);
            
            // 5. Piutang Transaction (Debit)
            $id_piutang_header = get_or_create_piutang_header($conn, $prop_data['kode_proyek'], date('Y-m-d'));
            
            // Insert Detail
            $piutang_stmt = $conn->prepare("INSERT INTO buku_piutang_detail (id_piutang, tgl_trx, reff, description, recipient, debit_idr, debit_usd, exrate) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
            $piutang_stmt->bind_param("isssddd", $id_piutang_header, $voucher_no, $desc, $prop_data['pj'], $credit_idr, $credit_usd, $exrate);
            $piutang_stmt->execute();
            
            // Insert Unliquidated
            $unliq_stmt = $conn->prepare("INSERT INTO buku_piutang_unliquidated (id_piutang, tgl, voucher_no, name, description, nilai_idr, nilai_usd, status) VALUES (?, NOW(), ?, ?, ?, ?, ?, 'pending')");
            $unliq_stmt->bind_param("isssdd", $id_piutang_header, $voucher_no, $prop_data['pj'], $desc, $credit_idr, $credit_usd);
            $unliq_stmt->execute();
            
            update_piutang_header_balance($conn, $id_piutang_header, $credit_idr, $credit_usd, true);
            
            // 6. Update Budget Availability
            $upd_budget_stmt = $conn->prepare("UPDATE project_code_budgets SET used_usd = used_usd + ?, remaining_usd = remaining_usd - ?, used_idr = used_idr + ?, remaining_idr = remaining_idr - ? WHERE place_code = ?");
            
            while ($row = $budget_details->fetch_assoc()) {
                $upd_budget_stmt->bind_param("dddds", $row['requested_usd'], $row['requested_usd'], $row['requested_idr'], $row['requested_idr'], $row['place_code']);
                $upd_budget_stmt->execute();
            }
            
            // 7. Update Proposal Status
            $check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");
            $is_2stage = ($check_column && $check_column->num_rows > 0);
            
            if ($is_2stage) {
                $stmt = $conn->prepare("UPDATE proposal SET status = 'approved_fm', approved_by_fm = ?, fm_approval_date = NOW() WHERE id_proposal = ?");
                $stmt->bind_param("ii", $user_id, $proposal_id);
            } else {
                $stmt = $conn->prepare("UPDATE proposal SET status = 'approved' WHERE id_proposal = ?");
                $stmt->bind_param("i", $proposal_id);
            }
            $stmt->execute();
            
            $conn->commit();
            
            // Notify PM
            // Get PM email
            $pm_stmt = $conn->prepare("SELECT email FROM user WHERE nama = ?");
            $pm_stmt->bind_param("s", $prop_data['pemohon']);
            $pm_stmt->execute();
            $pm_res = $pm_stmt->get_result();
            if ($pm_row = $pm_res->fetch_assoc()) {
                if (function_exists('send_notification_email')) {
                    send_notification_email(
                        $pm_row['email'],
                        'Proposal Disetujui & Dana Dicairkan',
                        'Proposal "' . $prop_data['judul_proposal'] . '" telah disetujui. Voucher No: ' . $voucher_no
                    );
                }
            }
            
            $success = 'Proposal disetujui, dana dicairkan (Voucher: ' . $voucher_no . '), dan budget diupdate.';
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal memproses approval: " . $e->getMessage();
        }
    } elseif (isset($_POST['request_revision'])) {
        $catatan = $_POST['catatan'] ?? '';
        
        // Update proposal status to rejected and save revision notes
        $stmt = $conn->prepare("UPDATE proposal SET status = 'rejected', catatan_fm = ? WHERE id_proposal = ?");
        $stmt->bind_param("si", $catatan, $proposal_id);
        
        if ($stmt->execute()) {
            // Get proposal and PM details
            $prop_stmt = $conn->prepare("SELECT p.*, u.email FROM proposal p LEFT JOIN user u ON p.pemohon = u.nama WHERE id_proposal = ?");
            $prop_stmt->bind_param("i", $proposal_id);
            $prop_stmt->execute();
            $prop_data = $prop_stmt->get_result()->fetch_assoc();
            
            // Notify PM
            send_notification_email(
                $prop_data['email'],
                'Proposal Perlu Revisi',
                'Proposal Anda "' . $prop_data['judul_proposal'] . '" memerlukan perbaikan. Catatan: ' . $catatan
            );
            
            $success = 'Permintaan revisi berhasil dikirim!';
        }
    }
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
    error_log("⚠️ review_proposal_fm.php - Proposal not found: ID = $proposal_id");
    header('Location: ../dashboards/dashboard_fm.php?tab=' . urlencode($return_tab));
    exit();
}

// Log access for debugging
error_log("✅ review_proposal_fm.php - FM viewing proposal: ID = $proposal_id, Status = " . $proposal['status']);

// Close session writing
session_write_close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Proposal (FM) - PRCF INDONESIA</title>
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
                    <h1 class="text-xl font-bold text-gray-800">Review Proposal (Finance Manager)</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="../dashboards/dashboard_fm.php?tab=<?php echo urlencode($return_tab); ?>" class="block mt-2 text-green-800 underline">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
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
                            <p class="text-lg font-bold text-gray-800">
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
                        <p class="text-gray-800 font-medium"><?php echo $proposal['pemohon']; ?></p>
                    </div>
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
            </div>
            
            <!-- Budget Details Section -->
            <div class="p-8 border-t border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Rincian Budget</h3>
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Place Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exp Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (USD)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (IDR)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $bud_stmt = $conn->prepare("SELECT * FROM proposal_budget_details WHERE id_proposal = ?");
                            $bud_stmt->bind_param("i", $proposal_id);
                            $bud_stmt->execute();
                            $budget_res = $bud_stmt->get_result();
                            while ($row = $budget_res->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-700"><?php echo $row['place_code']; ?></td>
                                <td class="px-4 py-2 text-sm text-gray-700"><?php echo $row['exp_code']; ?></td>
                                <td class="px-4 py-2 text-sm text-gray-700"><?php echo $row['description']; ?></td>
                                <td class="px-4 py-2 text-sm text-right text-gray-700"><?php echo number_format($row['requested_usd'], 2); ?></td>
                                <td class="px-4 py-2 text-sm text-right text-gray-700"><?php echo number_format($row['requested_idr'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right">TOTAL</td>
                                <td class="px-4 py-3 text-right">$<?php echo number_format($proposal['total_budget_usd'], 2); ?></td>
                                <td class="px-4 py-3 text-right">Rp <?php echo number_format($proposal['total_budget_idr'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- FM Review Form - Only for 'submitted' status -->
            <?php if ($proposal['status'] === 'submitted'): ?>
            <div class="p-8 border-t border-gray-200 bg-blue-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-clipboard-check mr-2 text-blue-600"></i>Review Proposal
                </h3>
                
                <div class="mb-4 p-3 bg-blue-100 border border-blue-300 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Info:</strong> Approval oleh Finance Manager adalah final. Setelah disetujui, proposal akan tersedia untuk Direktur review.
                    </p>
                </div>
                
                <form method="POST" class="space-y-4" id="reviewForm">
                    <div id="revisionNotesContainer" class="hidden">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Catatan untuk Project Manager *</label>
                        <textarea name="catatan" id="catatanField" rows="4" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Berikan catatan atau komentar terkait proposal ini..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="button" id="revisionBtn"
                            class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition duration-200 font-medium"
                            onclick="toggleRevisionMode()">
                            <i class="fas fa-edit mr-2"></i> <span id="revisionBtnText">Minta Revisi</span>
                        </button>
                        <button type="submit" name="approve" id="approveBtn"
                            class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200 font-medium"
                            onclick="return confirm('Setujui proposal ini? (Final Approval)')">
                            <i class="fas fa-check-circle mr-2"></i> <span id="approveBtnText">Setujui (Final)</span>
                        </button>
                    </div>
                </form>
                
                <script>
                let revisionMode = false;
                
                function toggleRevisionMode() {
                    revisionMode = !revisionMode;
                    const container = document.getElementById('revisionNotesContainer');
                    const revisionBtn = document.getElementById('revisionBtn');
                    const approveBtn = document.getElementById('approveBtn');
                    const revisionBtnText = document.getElementById('revisionBtnText');
                    const approveBtnText = document.getElementById('approveBtnText');
                    const catatanField = document.getElementById('catatanField');
                    const form = document.getElementById('reviewForm');
                    
                    if (revisionMode) {
                        // Show revision notes
                        container.classList.remove('hidden');
                        catatanField.required = true;
                        
                        // Change button labels
                        revisionBtnText.textContent = 'Batal';
                        revisionBtn.classList.remove('bg-yellow-500', 'hover:bg-yellow-600');
                        revisionBtn.classList.add('bg-gray-500', 'hover:bg-gray-600');
                        
                        approveBtnText.textContent = 'Kirim Revisi ke PM';
                        approveBtn.setAttribute('name', 'request_revision');
                        approveBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
                        approveBtn.classList.add('bg-red-500', 'hover:bg-red-600');
                        approveBtn.onclick = function() { return confirm('Kirim permintaan revisi ke Project Manager?'); };
                    } else {
                        // Hide revision notes
                        container.classList.add('hidden');
                        catatanField.required = false;
                        catatanField.value = '';
                        
                        // Restore button labels
                        revisionBtnText.textContent = 'Minta Revisi';
                        revisionBtn.classList.remove('bg-gray-500', 'hover:bg-gray-600');
                        revisionBtn.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
                        
                        approveBtnText.textContent = 'Setujui (Final)';
                        approveBtn.setAttribute('name', 'approve');
                        approveBtn.classList.remove('bg-red-500', 'hover:bg-red-600');
                        approveBtn.classList.add('bg-green-500', 'hover:bg-green-600');
                        approveBtn.onclick = function() { return confirm('Setujui proposal ini? (Final Approval)'); };
                    }
                }
                </script>
            </div>
            <?php elseif ($proposal['status'] === 'approved_fm'): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Proposal Sudah Anda Setujui (Final)</p>
                        <p class="text-sm">Proposal telah disetujui dan tersedia untuk Direktur review.</p>
                    </div>
                </div>
            </div>
            <?php elseif ($proposal['status'] === 'approved'): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-double text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Proposal Telah Disetujui (Final)</p>
                        <p class="text-sm">Proposal telah mendapat final approval dari Direktur.</p>
                    </div>
                </div>
            </div>
            <?php elseif ($proposal['status'] === 'rejected'): ?>
            <div class="p-8 border-t border-gray-200 bg-red-50">
                <div class="flex items-center text-red-700">
                    <i class="fas fa-times-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Proposal Ditolak / Perlu Revisi</p>
                        <p class="text-sm">Proposal ini telah ditolak dan perlu perbaikan oleh Project Manager.</p>
                    </div>
                </div>
            </div>
            <?php elseif ($proposal['status'] === 'draft'): ?>
            <div class="p-8 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center text-gray-700">
                    <i class="fas fa-file text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Proposal Masih Draft</p>
                        <p class="text-sm">Proposal ini masih dalam tahap draft dan belum disubmit.</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-8 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center text-gray-700">
                    <i class="fas fa-info-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-medium">Proposal <?php echo $status_text[$proposal['status']] ?? $proposal['status']; ?></p>
                        <p class="text-sm">Tidak ada aksi yang tersedia untuk status ini.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
        console.log('✅ review_proposal_fm.php loaded - Proposal ID: <?php echo $proposal_id; ?>, Status: <?php echo $proposal['status']; ?>');
    </script>
</body>
</html>

