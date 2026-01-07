<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/finance_functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting Integration Test...\n";

// 1. Setup Test Data
$test_proj = 'TEST-PROJ-' . time();
$test_place_code = 'TEST-PLACE-' . time();
$amount_usd = 100.00;
$amount_idr = 1500000.00;
$exrate = 15000;

echo "Creating test project budget for $test_proj...\n";
// Insert dummy project
$conn->query("INSERT INTO proyek (kode_proyek, nama_proyek, status_proyek, donor, nilai_anggaran, periode_mulai, periode_selesai) VALUES ('$test_proj', 'Test Project', 'ongoing', 'Test Donor', 100000, NOW(), NOW())");

// Insert dummy village
$conn->query("INSERT IGNORE INTO villages (id_village, village_code, village_name, village_abbr) VALUES (999, 'TEST', 'Test Village', 'TV')");

// Insert project budget
$stmt = $conn->prepare("INSERT INTO project_code_budgets (place_code, kode_proyek, id_village, exp_code, budget_usd, budget_idr, used_usd, used_idr) VALUES (?, ?, 999, 'TEST-EXP', 1000, 15000000, 0, 0)");
$stmt->bind_param("ss", $test_place_code, $test_proj);
if (!$stmt->execute()) die("Failed to create budget: " . $stmt->error . "\n");

// Insert proposal
echo "Creating test proposal...\n";
$stmt = $conn->prepare("INSERT INTO proposal (judul_proposal, pj, date, pemohon, kode_proyek, currency, exrate_at_submission, total_budget_usd, total_budget_idr, status, tor, file_budget) VALUES ('Test Proposal', 'Test PJ', NOW(), 'Test User', ?, 'USD', ?, ?, ?, 'submitted', 'dummy_tor.pdf', 'dummy_budget.xlsx')");
$stmt->bind_param("sddd", $test_proj, $exrate, $amount_usd, $amount_idr);
if (!$stmt->execute()) die("Failed to create proposal: " . $stmt->error . "\n");
$proposal_id = $conn->insert_id;
echo "Proposal ID: $proposal_id\n";

// Insert budget details
echo "Adding budget details...\n";
$stmt = $conn->prepare("INSERT INTO proposal_budget_details (id_proposal, id_village, exp_code, place_code, requested_usd, requested_idr, description) VALUES (?, 999, 'TEST-EXP', ?, ?, ?, 'Test Item')");
$stmt->bind_param("isdd", $proposal_id, $test_place_code, $amount_usd, $amount_idr);
if (!$stmt->execute()) die("Failed to add budget details: " . $stmt->error . "\n");

// 2. Simulate Approval Logic
echo "Simulating FM Approval...\n";
$conn->begin_transaction();
try {
    // Fetch data
    $prop_stmt = $conn->prepare("SELECT * FROM proposal WHERE id_proposal = ?");
    $prop_stmt->bind_param("i", $proposal_id);
    $prop_stmt->execute();
    $prop_data = $prop_stmt->get_result()->fetch_assoc();
    
    $bud_stmt = $conn->prepare("SELECT * FROM proposal_budget_details WHERE id_proposal = ?");
    $bud_stmt->bind_param("i", $proposal_id);
    $bud_stmt->execute();
    $budget_details = $bud_stmt->get_result();
    
    // Generate Voucher
    $id_detail_bank = generate_id('BD');
    $voucher_no = "BB-" . $id_detail_bank . "-PROP-" . str_pad($proposal_id, 6, '0', STR_PAD_LEFT);
    echo "Generated Voucher: $voucher_no\n";
    
    // Bank Transaction
    $id_bank_header = get_or_create_bank_header($conn, $prop_data['kode_proyek'], date('Y-m-d'));
    
    $bank_stmt = $conn->prepare("INSERT INTO buku_bank_detail (id_detail_bank, id_bank_header, tanggal, reff, title_activity, cost_description, recipient, place_code, exp_code, nominal_code, exrate, cost_curr, credit_idr, credit_usd, balance_idr, balance_usd, status) VALUES (?, ?, NOW(), ?, ?, ?, ?, '-', '-', 'Adv', ?, ?, ?, ?, 0, 0, 'ongoing')");
    
    $desc = "Advance for: " . $prop_data['judul_proposal'];
    $bank_stmt->bind_param("ssssssdsdd", $id_detail_bank, $id_bank_header, $voucher_no, $prop_data['judul_proposal'], $desc, $prop_data['pj'], $prop_data['exrate_at_submission'], $prop_data['currency'], $prop_data['total_budget_idr'], $prop_data['total_budget_usd']);
    $bank_stmt->execute();
    
    update_bank_header_balance($conn, $id_bank_header, $prop_data['total_budget_idr'], $prop_data['total_budget_usd'], true);
    
    // Piutang Transaction
    $id_piutang_header = get_or_create_piutang_header($conn, $prop_data['kode_proyek'], date('Y-m-d'));
    
    $piutang_stmt = $conn->prepare("INSERT INTO buku_piutang_detail (id_piutang, tgl_trx, reff, description, recipient, debit_idr, debit_usd, exrate) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
    $piutang_stmt->bind_param("isssddd", $id_piutang_header, $voucher_no, $desc, $prop_data['pj'], $prop_data['total_budget_idr'], $prop_data['total_budget_usd'], $prop_data['exchange_rate']);
    $piutang_stmt->execute();
    
    $unliq_stmt = $conn->prepare("INSERT INTO buku_piutang_unliquidated (id_piutang, tgl, voucher_no, name, description, nilai_idr, nilai_usd, status) VALUES (?, NOW(), ?, ?, ?, ?, ?, 'pending')");
    $unliq_stmt->bind_param("isssdd", $id_piutang_header, $voucher_no, $prop_data['pj'], $desc, $prop_data['total_budget_idr'], $prop_data['total_budget_usd']);
    $unliq_stmt->execute();
    
    update_piutang_header_balance($conn, $id_piutang_header, $prop_data['total_budget_idr'], $prop_data['total_budget_usd'], true);
    
    // Update Budget
    $upd_budget_stmt = $conn->prepare("UPDATE project_code_budgets SET used_usd = used_usd + ?, remaining_usd = remaining_usd - ?, used_idr = used_idr + ?, remaining_idr = remaining_idr - ? WHERE place_code = ?");
    while ($row = $budget_details->fetch_assoc()) {
        $upd_budget_stmt->bind_param("dddds", $row['requested_usd'], $row['requested_usd'], $row['requested_idr'], $row['requested_idr'], $row['place_code']);
        $upd_budget_stmt->execute();
    }
    
    // Update Proposal
    $stmt = $conn->prepare("UPDATE proposal SET status = 'approved_fm' WHERE id_proposal = ?");
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    
    $conn->commit();
    echo "Transaction Committed.\n";
    
} catch (Exception $e) {
    $conn->rollback();
    die("Transaction Failed: " . $e->getMessage() . "\n");
}

// 3. Verify Results
echo "Verifying Results...\n";

// Check Proposal Status
$res = $conn->query("SELECT status FROM proposal WHERE id_proposal = $proposal_id");
$row = $res->fetch_assoc();
if ($row['status'] !== 'approved_fm') die("FAIL: Proposal status is " . $row['status'] . "\n");
echo "PASS: Proposal status updated.\n";

// Check Bank Detail
$res = $conn->query("SELECT * FROM buku_bank_detail WHERE reff = '$voucher_no'");
if ($res->num_rows === 0) die("FAIL: No bank detail found.\n");
$row = $res->fetch_assoc();
if ($row['credit_usd'] != $amount_usd) die("FAIL: Bank credit USD mismatch. Expected $amount_usd, got " . $row['credit_usd'] . "\n");
echo "PASS: Bank detail created.\n";

// Check Piutang Detail
$res = $conn->query("SELECT * FROM buku_piutang_detail WHERE reff = '$voucher_no'");
if ($res->num_rows === 0) die("FAIL: No piutang detail found.\n");
echo "PASS: Piutang detail created.\n";

// Check Budget Update
$res = $conn->query("SELECT * FROM project_code_budgets WHERE place_code = '$test_place_code'");
$row = $res->fetch_assoc();
if (!$row) die("FAIL: Budget row not found for $test_place_code\n");
print_r($row);

if ($row['remaining_usd'] != 900) die("FAIL: Remaining USD mismatch. Expected 900, got " . $row['remaining_usd'] . "\n");
if ($row['used_usd'] != 100) die("FAIL: Used USD mismatch. Expected 100, got " . $row['used_usd'] . "\n");
echo "PASS: Budget updated.\n";

// 4. Cleanup
echo "Cleaning up...\n";
$conn->query("DELETE FROM proposal WHERE id_proposal = $proposal_id");
$conn->query("DELETE FROM proposal_budget_details WHERE id_proposal = $proposal_id");
$conn->query("DELETE FROM buku_bank_detail WHERE reff = '$voucher_no'");
$conn->query("DELETE FROM buku_bank_header WHERE id_bank_header = '$id_bank_header'"); // Careful
$conn->query("DELETE FROM buku_piutang_detail WHERE reff = '$voucher_no'");
$conn->query("DELETE FROM buku_piutang_unliquidated WHERE voucher_no = '$voucher_no'");
$conn->query("DELETE FROM project_code_budgets WHERE place_code = '$test_place_code'");

echo "Test Completed Successfully!\n";
?>
