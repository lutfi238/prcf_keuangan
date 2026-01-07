<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';
require_once '../../includes/finance_functions.php';

// Check maintenance mode
check_maintenance();

// Allow only Director
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
// Default return to dashboard reports tab
$return_tab = $_GET['return_tab'] ?? 'reports'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $conn->begin_transaction();
        try {
            // 1. Get Report Data for Settlement
            $stmt_header = $conn->prepare("SELECT * FROM laporan_keuangan_header WHERE id_laporan_keu = ?");
            $stmt_header->bind_param("i", $report_id);
            $stmt_header->execute();
            $header_data = $stmt_header->get_result()->fetch_assoc();
            
            // Validate status
            if ($header_data['status_lap'] !== 'approved_fm') {
                throw new Exception("Laporan belum disetujui oleh Finance Manager atau status tidak valid.");
            }

            $stmt_details = $conn->prepare("SELECT * FROM laporan_keuangan_detail WHERE id_laporan_keu = ?");
            $stmt_details->bind_param("i", $report_id);
            $stmt_details->execute();
            $details_result = $stmt_details->get_result();
            
            // 2. Calculate Totals and Per-Place Code Balances
            $total_requested = 0;
            $total_actual = 0;
            $place_budget_adjustments = []; // place_code -> balance (diff)
            
            while ($item = $details_result->fetch_assoc()) {
                $actual_cost = ($item['unit_total'] * $item['unit_cost']);
                $balance = $item['requested'] - $actual_cost; // Positive = Surplus (Sisa), Negative = Deficit (Kurang)
                
                $total_requested += $item['requested'];
                $total_actual += $actual_cost;
                
                $pc = $item['place_code'];
                if (!isset($place_budget_adjustments[$pc])) {
                    $place_budget_adjustments[$pc] = 0;
                }
                $place_budget_adjustments[$pc] += $balance;
            }
            
            $net_balance = $total_requested - $total_actual;
            
            // 3. Bank Settlement Transaction (If Net Balance != 0)
            // Logic moved from FM approval to Director Approval (Final)
            if (abs($net_balance) > 0.01) { // Floating point safety
                $id_bank_header = get_or_create_bank_header($conn, $header_data['kode_projek'], date('Y-m-d'));
                $id_detail_bank = generate_id('BD');
                $voucher_no = "SETT-" . $id_detail_bank . "-REP" . str_pad($report_id, 6, '0', STR_PAD_LEFT);
                
                $title_activity = "Settlement Reports: " . $header_data['nama_projek'];
                $cost_desc = "Financial Report Settlement";
                $pj = $header_data['pelaksana'];
                
                $exrate = $header_data['exrate'];
                $curr = $header_data['mata_uang'];
                
                $credit_idr = 0; $credit_usd = 0;
                $debit_idr = 0; $debit_usd = 0;
                
                if ($net_balance > 0) {
                    // Surplus: Money Returns to Bank (Debit)
                    if ($curr === 'USD') {
                        $debit_usd = $net_balance;
                        $debit_idr = $net_balance * $exrate; 
                    } else {
                        $debit_idr = $net_balance;
                        $debit_usd = $net_balance / ($exrate ?: 15500);
                    }
                    $type = 'debit';
                } else {
                    // Deficit: Money Leaves Bank (Credit/Reimburse)
                    $deficit = abs($net_balance);
                    if ($curr === 'USD') {
                        $credit_usd = $deficit;
                        $credit_idr = $deficit * $exrate;
                    } else {
                        $credit_idr = $deficit;
                        $credit_usd = $deficit / ($exrate ?: 15500);
                    }
                    $type = 'credit';
                }
                
                // Insert to buku_bank_detail with status 'final'
                $bank_stmt = $conn->prepare("INSERT INTO buku_bank_detail 
                    (id_detail_bank, id_bank_header, tanggal, reff, title_activity, cost_description, 
                     recipient, place_code, exp_code, nominal_code, exrate, cost_curr, 
                     debit_idr, debit_usd, credit_idr, credit_usd, balance_idr, balance_usd, status) 
                    VALUES (?, ?, NOW(), ?, ?, ?, ?, 'GEN', 'SETT', 'Settlement', ?, ?, ?, ?, ?, ?, 0, 0, 'final')");
                    
                $bank_stmt->bind_param("ssssssdddddd", 
                    $id_detail_bank, $id_bank_header, $voucher_no, 
                    $title_activity, $cost_desc, $pj,
                    $exrate, $curr,
                    $debit_idr, $debit_usd, $credit_idr, $credit_usd);
                    
                $bank_stmt->execute();
                
                // Update header balance based on transaction type
                // False = Debit (Increase Balance), True = Credit (Decrease Balance)
                if ($net_balance > 0) { 
                    update_bank_header_balance($conn, $id_bank_header, $debit_idr, $debit_usd, false);
                } else {
                    update_bank_header_balance($conn, $id_bank_header, $credit_idr, $credit_usd, true);
                }
            }
            
            // 4. Update Project Budgets
            foreach ($place_budget_adjustments as $pc => $amt) {
                 if (abs($amt) < 0.01) continue;
                 
                 $amt_usd = ($header_data['mata_uang'] == 'USD') ? $amt : ($amt / $header_data['exrate']);
                 $amt_idr = ($header_data['mata_uang'] == 'IDR') ? $amt : ($amt * $header_data['exrate']);
                 
                 // Used decreases if surplus (amt > 0), Used increases if deficit (amt < 0)
                 $b_stmt = $conn->prepare("UPDATE project_code_budgets 
                    SET used_usd = used_usd - ?, used_idr = used_idr - ?
                    WHERE place_code = ? AND kode_proyek = ?");
                 $b_stmt->bind_param("ddss", $amt_usd, $amt_idr, $pc, $header_data['kode_projek']);
                 $b_stmt->execute();
            }

            // 5. Update Status (Final Approval)
            // Use existing `approved_by_dir` and `dir_approval_date` columns
            $stmt = $conn->prepare("UPDATE laporan_keuangan_header 
                                    SET status_lap = 'approved', 
                                    approved_by_dir = ?, 
                                    dir_approval_date = NOW(),
                                    updated_at = NOW() 
                                    WHERE id_laporan_keu = ?");
            $stmt->bind_param("ii", $user_id, $report_id);
            $stmt->execute();
            
            $conn->commit();
            
            // Notifications
            $report_stmt = $conn->prepare("SELECT lh.*, u.email, u.nama FROM laporan_keuangan_header lh LEFT JOIN user u ON lh.created_by = u.id_user WHERE id_laporan_keu = ?");
            $report_stmt->bind_param("i", $report_id);
            $report_stmt->execute();
            $report_data = $report_stmt->get_result()->fetch_assoc();
            
            send_notification_email(
                $report_data['email'],
                'Laporan Keuangan Disetujui Direktur',
                'Selamat! Laporan keuangan "' . $report_data['nama_projek'] . '" telah disetujui oleh Direktur. Proses settlement keuangan telah selesai.'
            );
            
            // Redirect with success
            header("Location: ../dashboards/dashboard_dir.php?success=report_approved&tab=" . urlencode($return_tab));
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            // Redirect with error (simulated via session or query param if simpler in this framework)
            // Since we are posting, we can't easily stay on same page without re-posting.
            // Better to go back to view with error.
            header("Location: view_report_dir.php?id=" . $report_id . "&error=" . urlencode($e->getMessage()));
            exit();
        }
    }
} else {
    // If accessed directly without POST, redirect back
    header('Location: view_report_dir.php?id=' . $report_id);
    exit();
}
?>
