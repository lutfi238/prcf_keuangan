<?php
// includes/finance_functions.php

function generate_id($prefix) {
    return $prefix . '-' . date('Ymd-His') . '-' . substr(uniqid(), -4);
}

function generate_voucher_no($conn, $project_code) {
    $month = date('m');
    $year = date('Y');
    // Simple counter - in production this should be more robust
    // Check last voucher no for this project/month
    // Format: YYYY/MM/PROJ/001
    
    // Try to find max number from unliquidated table
    $prefix = "$year/$month/$project_code/";
    $len = strlen($prefix);
    
    $stmt = $conn->prepare("SELECT voucher_no FROM buku_piutang_unliquidated WHERE voucher_no LIKE CONCAT(?, '%') ORDER BY voucher_no DESC LIMIT 1");
    $stmt->bind_param("s", $prefix);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $next_num = 1;
    if ($row = $res->fetch_assoc()) {
        $last_no = $row['voucher_no'];
        $num_part = substr($last_no, $len);
        if (is_numeric($num_part)) {
            $next_num = intval($num_part) + 1;
        }
    }
    
    return $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}

function get_or_create_bank_header($conn, $project_code, $date) {
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    
    // Check existing
    $stmt = $conn->prepare("SELECT id_bank_header FROM buku_bank_header WHERE kode_proyek = ? AND periode_bulan = ? AND periode_tahun = ?");
    $stmt->bind_param("sss", $project_code, $month, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        return $row['id_bank_header'];
    }
    
    // Create new
    // Get previous balance and Account Details
    $prev_month = date('m', strtotime("-1 month", strtotime($date)));
    $prev_year = date('Y', strtotime("-1 month", strtotime($date)));
    
    $stmt = $conn->prepare("SELECT saldo_akhir_idr, saldo_akhir_usd, account_name, bank_name, account_number, currency FROM buku_bank_header WHERE kode_proyek = ? AND periode_bulan = ? AND periode_tahun = ? LIMIT 1");
    $stmt->bind_param("sss", $project_code, $prev_month, $prev_year);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $saldo_awal_idr = $row['saldo_akhir_idr'];
        $saldo_awal_usd = $row['saldo_akhir_usd'];
        $account_name = $row['account_name'];
        $bank_name = $row['bank_name'];
        $account_number = $row['account_number'];
        $currency = $row['currency'];
    } else {
        throw new Exception("Buku Bank bulan lalu tidak ditemukan untuk Proyek $project_code. Harap buat Buku Bank manual terlebih dahulu sebelum transaksi.");
    }
    
    $id_header = generate_id('BH');
    $stmt = $conn->prepare("INSERT INTO buku_bank_header 
        (id_bank_header, kode_proyek, periode_bulan, periode_tahun, 
         account_name, bank_name, account_number, currency,
         saldo_awal_idr, saldo_awal_usd, saldo_akhir_idr, saldo_akhir_usd, 
         status_laporan, tanggal_pembuatan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW())");
        
    $stmt->bind_param("ssssssssdddd", 
        $id_header, $project_code, $month, $year, 
        $account_name, $bank_name, $account_number, $currency,
        $saldo_awal_idr, $saldo_awal_usd, $saldo_awal_idr, $saldo_awal_usd);
    
    if ($stmt->execute()) {
        return $id_header;
    }
    
    throw new Exception("Failed to create Bank Header: " . $stmt->error);
}

function get_or_create_piutang_header($conn, $project_code, $date) {
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    
    // Check existing
    $stmt = $conn->prepare("SELECT id_piutang FROM buku_piutang_header WHERE kode_proyek = ? AND periode_bulan = ? AND periode_tahun = ?");
    $stmt->bind_param("sss", $project_code, $month, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        return $row['id_piutang'];
    }
    
    // Create new
    // Get previous balance
    $prev_month = date('m', strtotime("-1 month", strtotime($date)));
    $prev_year = date('Y', strtotime("-1 month", strtotime($date)));
    
    $stmt = $conn->prepare("SELECT ending_balance_idr, ending_balance_usd FROM buku_piutang_header WHERE kode_proyek = ? AND periode_bulan = ? AND periode_tahun = ?");
    $stmt->bind_param("sss", $project_code, $prev_month, $prev_year);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $saldo_awal_idr = 0;
    $saldo_awal_usd = 0;
    
    if ($row = $res->fetch_assoc()) {
        $saldo_awal_idr = $row['ending_balance_idr'];
        $saldo_awal_usd = $row['ending_balance_usd'];
    }
    
    $stmt = $conn->prepare("INSERT INTO buku_piutang_header (kode_proyek, periode_bulan, periode_tahun, beginning_balance_idr, beginning_balance_usd, ending_balance_idr, ending_balance_usd, status, tgl_pembuatan) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', NOW())");
    $stmt->bind_param("sssdddd", $project_code, $month, $year, $saldo_awal_idr, $saldo_awal_usd, $saldo_awal_idr, $saldo_awal_usd);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    throw new Exception("Failed to create Piutang Header");
}

function update_bank_header_balance($conn, $id_header, $amount_idr, $amount_usd, $is_credit = true) {
    // If credit (money out), balance decreases
    // If debit (money in), balance increases
    // Wait, Bank Book: Debit = In, Credit = Out.
    // So Credit reduces balance.
    
    $factor = $is_credit ? -1 : 1;
    $change_idr = $amount_idr * $factor;
    $change_usd = $amount_usd * $factor;
    
    $stmt = $conn->prepare("UPDATE buku_bank_header SET 
        current_period_change_idr = current_period_change_idr + ?,
        current_period_change_usd = current_period_change_usd + ?,
        saldo_akhir_idr = saldo_akhir_idr + ?,
        saldo_akhir_usd = saldo_akhir_usd + ?
        WHERE id_bank_header = ?");
    $stmt->bind_param("dddds", $change_idr, $change_usd, $change_idr, $change_usd, $id_header);
    $stmt->execute();
}

function update_piutang_header_balance($conn, $id_header, $amount_idr, $amount_usd, $is_debit = true) {
    // Piutang: Debit = New Debt (Increase Balance), Credit = Paid (Decrease Balance)
    
    $factor = $is_debit ? 1 : -1;
    $change_idr = $amount_idr * $factor;
    $change_usd = $amount_usd * $factor;
    
    $stmt = $conn->prepare("UPDATE buku_piutang_header SET 
        ending_balance_idr = ending_balance_idr + ?,
        ending_balance_usd = ending_balance_usd + ?
        WHERE id_piutang = ?");
    $stmt->bind_param("ddi", $change_idr, $change_usd, $id_header);
    $stmt->execute();
}
?>
