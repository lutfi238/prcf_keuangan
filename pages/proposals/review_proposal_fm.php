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
            $budget_details = $bud_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // 3. Get Headers (create if not exist)
            $id_bank_header = get_or_create_bank_header($conn, $prop_data['kode_proyek'], date('Y-m-d'));
            $id_piutang_header = get_or_create_piutang_header($conn, $prop_data['kode_proyek'], date('Y-m-d'));
            
            // Get exchange rate
            $exrate = 15500.00; // Default fallback
            $exrate_check = $conn->query("SHOW TABLES LIKE 'settings'");
            if ($exrate_check && $exrate_check->num_rows > 0) {
                $exrate_res = $conn->query("SELECT value FROM settings WHERE `key` = 'usd_idr_rate'");
                if ($exrate_res && $exrate_res->num_rows > 0) {
                    $exrate = floatval($exrate_res->fetch_assoc()['value']);
                }
            }
            
            // 4. Process each budget detail separately
            $voucher_list = [];
            foreach ($budget_details as $index => $detail) {
                // Generate unique ID and voucher for this detail
                $id_detail_bank = generate_id('BD');
                $voucher_no = "BB-" . $id_detail_bank . "-PROP" . str_pad($proposal_id, 6, '0', STR_PAD_LEFT) . "-" . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                $voucher_list[] = $voucher_no;
                
                $desc = "Advance for: " . $prop_data['judul_proposal'] . " (" . $detail['place_code'] . ")";
                $cost_curr = $prop_data['currency'];
                $credit_idr = $detail['requested_idr'];
                $credit_usd = $detail['requested_usd'];
                
                // 4a. Insert to buku_bank_detail
                $bank_stmt = $conn->prepare("INSERT INTO buku_bank_detail 
                    (id_detail_bank, id_bank_header, tanggal, reff, title_activity, cost_description, 
                     recipient, place_code, exp_code, nominal_code, exrate, cost_curr, 
                     credit_idr, credit_usd, balance_idr, balance_usd, status) 
                    VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'Adv', ?, ?, ?, ?, 0, 0, 'ongoing')");
                
                $bank_stmt->bind_param("ssssssssdsdd", 
                    $id_detail_bank, $id_bank_header, $voucher_no, 
                    $prop_data['judul_proposal'], $desc, $prop_data['pj'],
                    $detail['place_code'], $detail['exp_code'],
                    $exrate, $cost_curr, $credit_idr, $credit_usd);
                $bank_stmt->execute();
                
                // Update bank header balance
                update_bank_header_balance($conn, $id_bank_header, $credit_idr, $credit_usd, true);
                
                // 4b. Insert to buku_piutang_detail - DISABLED PER USER REQUEST
                /*
                $piutang_stmt = $conn->prepare("INSERT INTO buku_piutang_detail 
                    (id_piutang, tgl_trx, reff, description, recipient, debit_idr, debit_usd, exrate) 
                    VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
                $piutang_stmt->bind_param("isssddd", 
                    $id_piutang_header, $voucher_no, $desc, $prop_data['pj'], 
                    $credit_idr, $credit_usd, $exrate);
                $piutang_stmt->execute();
                
                // 4c. Insert to buku_piutang_unliquidated - DISABLED, ASSUMED PART OF PIUTANG FEATURE
                $unliq_stmt = $conn->prepare("INSERT INTO buku_piutang_unliquidated 
                    (id_piutang, tgl, voucher_no, name, description, nilai_idr, nilai_usd, status) 
                    VALUES (?, NOW(), ?, ?, ?, ?, ?, 'pending')");
                $unliq_stmt->bind_param("isssdd", 
                    $id_piutang_header, $voucher_no, $prop_data['pj'], $desc, 
                    $credit_idr, $credit_usd);
                $unliq_stmt->execute();
                
                // Update piutang header balance - DISABLED
                update_piutang_header_balance($conn, $id_piutang_header, $credit_idr, $credit_usd, true);
                */
                
                // 4d. Update project_code_budgets for this specific place_code
                $upd_budget_stmt = $conn->prepare("UPDATE project_code_budgets 
                    SET used_usd = used_usd + ?, 
                        used_idr = used_idr + ?
                    WHERE place_code = ? AND kode_proyek = ?");
                $upd_budget_stmt->bind_param("ddss", 
                    $credit_usd, $credit_idr, 
                    $detail['place_code'], $prop_data['kode_proyek']);
                $upd_budget_stmt->execute();
                
                // Check if update was successful
                if ($upd_budget_stmt->affected_rows === 0) {
                    throw new Exception("Failed to update budget for place_code: " . $detail['place_code']);
                }
            }
            
            // 5. Update Proposal Status
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
            
            // 6. Notify PM
            $pm_stmt = $conn->prepare("SELECT email FROM user WHERE nama = ?");
            $pm_stmt->bind_param("s", $prop_data['pemohon']);
            $pm_stmt->execute();
            $pm_res = $pm_stmt->get_result();
            if ($pm_row = $pm_res->fetch_assoc()) {
                if (function_exists('send_notification_email')) {
                    $voucher_summary = implode(", ", $voucher_list);
                    send_notification_email(
                        $pm_row['email'],
                        'Proposal Disetujui & Dana Dicairkan',
                        'Proposal "' . $prop_data['judul_proposal'] . '" telah disetujui. ' . 
                        count($voucher_list) . ' voucher telah dibuat: ' . $voucher_summary
                    );
                }
            }
            
            $success = 'Proposal disetujui! ' . count($voucher_list) . ' transaksi dicairkan dan budget telah diupdate per place code.';
            
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
            
            <!-- Budget Details Section with Availability Info -->
            <?php
            // Get budget details with availability info
            $budget_query = $conn->prepare("
                SELECT 
                    pbd.id_detail,
                    pbd.exp_code,
                    pbd.place_code,
                    pbd.requested_usd,
                    pbd.requested_idr,
                    pbd.description,
                    v.village_name,
                    v.village_abbr,
                    pcb.budget_usd,
                    pcb.budget_idr,
                    pcb.used_usd,
                    pcb.used_idr,
                    pcb.remaining_usd,
                    pcb.remaining_idr,
                    CASE 
                        WHEN pcb.remaining_usd >= pbd.requested_usd THEN 'sufficient'
                        WHEN pcb.remaining_usd >= (pbd.requested_usd * 0.8) THEN 'tight'
                        ELSE 'insufficient'
                    END as budget_status
                FROM proposal_budget_details pbd
                LEFT JOIN villages v ON pbd.id_village = v.id_village
                LEFT JOIN project_code_budgets pcb ON pcb.place_code = pbd.place_code 
                    AND pcb.kode_proyek = ?
                WHERE pbd.id_proposal = ?
                ORDER BY pbd.id_detail ASC
            ");
            $budget_query->bind_param("si", $proposal['kode_proyek'], $proposal_id);
            $budget_query->execute();
            $budget_details_result = $budget_query->get_result();
            $budget_details = $budget_details_result->fetch_all(MYSQLI_ASSOC);
            
            // Get Current Exchange Rate for "Direct Conversion" Display
            $current_exrate = 15500.00; // Default
            $exrate_check = $conn->query("SHOW TABLES LIKE 'settings'");
            if ($exrate_check && $exrate_check->num_rows > 0) {
                $exrate_res = $conn->query("SELECT value FROM settings WHERE `key` = 'usd_idr_rate'");
                if ($exrate_res && $exrate_res->num_rows > 0) {
                    $current_exrate = floatval($exrate_res->fetch_assoc()['value']);
                }
            }
            
            // Calculate totals and check overall status
            $total_requested_usd = 0;
            $total_requested_idr = 0;
            $total_available_usd = 0;
            $total_available_idr = 0;
            $has_insufficient = false;
            $has_tight = false;
            
            foreach ($budget_details as $detail) {
                $total_requested_usd += $detail['requested_usd'];
                $total_requested_idr += $detail['requested_idr'];
                $total_available_usd += $detail['remaining_usd'] ?? 0;
                // Use dynamic conversion for IDR available
                $total_available_idr += ($detail['remaining_usd'] ?? 0) * $current_exrate;
                
                if ($detail['budget_status'] === 'insufficient') $has_insufficient = true;
                if ($detail['budget_status'] === 'tight') $has_tight = true;
            }
            
            $overall_status = 'sufficient';
            if ($has_insufficient) $overall_status = 'insufficient';
            elseif ($has_tight) $overall_status = 'tight';
            ?>
            
            <div class="p-8 border-t border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-line mr-2 text-blue-600"></i>Informasi Budget & Ketersediaan
                </h3>
                
                <!-- Exchange Rate Info -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Exchange Rate saat Proposal Dibuat</p>  
                            <p class="text-lg font-bold text-gray-800">
                                1 USD = Rp <?php echo number_format($proposal['exrate_at_submission'], 2); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Mata Uang Proposal</p>
                            <p class="text-lg font-bold text-gray-800">
                                <?php echo $proposal['currency']; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Budget Availability Warning -->
                <?php if ($overall_status === 'insufficient'): ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl mt-1 mr-3"></i>
                        <div>
                            <p class="font-bold text-red-800">Peringatan: Budget Tidak Mencukupi</p>
                            <p class="text-sm text-red-700 mt-1">
                                Beberapa place code memiliki budget yang tidak mencukupi untuk request ini. 
                                Pastikan untuk mengalokasikan budget tambahan atau minta PM untuk merevisi proposal.
                            </p>
                        </div>
                    </div>
                </div>
                <?php elseif ($overall_status === 'tight'): ?>
                <div class="mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-yellow-600 text-xl mt-1 mr-3"></i>
                        <div>
                            <p class="font-bold text-yellow-800">Info: Budget Ketat</p>
                            <p class="text-sm text-yellow-700 mt-1">
                                Beberapa place code mendekati limit budget. Pertimbangkan untuk monitoring lebih ketat.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Budget Details Table -->
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Place Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exp Code</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Diminta (USD)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tersedia (USD)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Diminta (IDR)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tersedia (IDR)</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($budget_details as $detail): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($detail['village_name'] ?? '-'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm font-mono text-gray-700">
                                    <?php echo htmlspecialchars($detail['place_code']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($detail['exp_code']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                                    $<?php echo number_format($detail['requested_usd'], 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">
                                    $<?php echo number_format($detail['remaining_usd'] ?? 0, 2); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                                    Rp <?php echo number_format($detail['requested_idr'], 0, ',', '.'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">
                                    <?php 
                                    $converted_available_idr = ($detail['remaining_usd'] ?? 0) * $current_exrate;
                                    echo 'Rp ' . number_format($converted_available_idr, 0, ',', '.'); 
                                    ?>
                                    <div class="text-xs text-gray-500 italic">
                                        (Est. @ <?php echo number_format($current_exrate, 0, ',', '.'); ?>)
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $status = $detail['budget_status'];
                                    if ($status === 'sufficient') {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Cukup
                                        </span>';
                                    } elseif ($status === 'tight') {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-exclamation-circle mr-1"></i> Ketat
                                        </span>';
                                    } else {
                                        $deficit_usd = $detail['requested_usd'] - ($detail['remaining_usd'] ?? 0);
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="Kurang: $' . number_format($deficit_usd, 2) . '">
                                            <i class="fas fa-times-circle mr-1"></i> Kurang
                                        </span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php if (!empty($detail['description'])): ?>
                            <tr class="bg-gray-50">
                                <td colspan="8" class="px-4 py-2 text-sm text-gray-600">
                                    <i class="fas fa-comment text-gray-400 mr-2"></i>
                                    <?php echo htmlspecialchars($detail['description']); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right text-sm">TOTAL</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    $<?php echo number_format($total_requested_usd, 2); ?>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    $<?php echo number_format($total_available_usd, 2); ?>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    Rp <?php echo number_format($total_requested_idr, 0, ',', '.'); ?>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    Rp <?php echo number_format($total_available_idr, 0, ',', '.'); ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    if ($overall_status === 'sufficient') {
                                        echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-200 text-green-900">
                                            ✓ OK
                                        </span>';
                                    } elseif ($overall_status === 'tight') {
                                        echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-yellow-200 text-yellow-900">
                                            ⚠ Ketat
                                        </span>';
                                    } else {
                                        $total_deficit = $total_requested_usd - $total_available_usd;
                                        echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-red-200 text-red-900" title="Kurang: $' . number_format($total_deficit, 2) . '">
                                            ✗ Kurang
                                        </span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <!-- Budget Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <p class="text-sm text-blue-600 font-medium">Total Diminta</p>
                        <p class="text-2xl font-bold text-blue-900 mt-1">
                            $<?php echo number_format($total_requested_usd, 2); ?>
                        </p>
                        <p class="text-xs text-blue-700 mt-1">
                            Rp <?php echo number_format($total_requested_idr, 0, ',', '.'); ?>
                        </p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <p class="text-sm text-green-600 font-medium">Total Tersedia</p>
                        <p class="text-2xl font-bold text-green-900 mt-1">
                            $<?php echo number_format($total_available_usd, 2); ?>
                        </p>
                        <p class="text-xs text-green-700 mt-1">
                            Rp <?php echo number_format($total_available_idr, 0, ',', '.'); ?>
                        </p>
                    </div>
                    <div class="p-4 <?php echo $total_available_usd >= $total_requested_usd ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?> rounded-lg border">
                        <p class="text-sm <?php echo $total_available_usd >= $total_requested_usd ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                            Selisih
                        </p>
                        <p class="text-2xl font-bold <?php echo $total_available_usd >= $total_requested_usd ? 'text-green-900' : 'text-red-900'; ?> mt-1">
                            $<?php echo number_format($total_available_usd - $total_requested_usd, 2); ?>
                        </p>
                        <p class="text-xs <?php echo $total_available_usd >= $total_requested_usd ? 'text-green-700' : 'text-red-700'; ?> mt-1">
                            <?php echo $total_available_usd >= $total_requested_usd ? '✓ Budget mencukupi' : '✗ Budget tidak cukup'; ?>
                        </p>
                    </div>
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

