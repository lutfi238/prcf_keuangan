<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';
require_once '../../includes/date_helper.php';

// Check maintenance mode
check_maintenance();

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Finance Manager') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Handle delete header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_header'])) {
    $id_bank_header = $_POST['id_bank_header'];
    
    $stmt = $conn->prepare("DELETE FROM buku_bank_header WHERE id_bank_header = ?");
    $stmt->bind_param("s", $id_bank_header);
    
    if ($stmt->execute()) {
        header('Location: buku_bank.php?success=header_deleted');
        exit();
    } else {
        $error = 'Gagal menghapus header: ' . $conn->error;
    }
}

// Handle delete detail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_detail'])) {
    $id_detail_bank = $_POST['id_detail_bank'];
    $id_bank_header = $_POST['id_bank_header'];
    
    $stmt = $conn->prepare("DELETE FROM buku_bank_detail WHERE id_detail_bank = ?");
    $stmt->bind_param("s", $id_detail_bank);
    
    if ($stmt->execute()) {
        // Recalculate balances after deletion
        // This is a simplified approach - in production, you'd want to recalculate all balances properly
        header('Location: buku_bank.php?success=detail_deleted');
        exit();
    } else {
        $error = 'Gagal menghapus detail: ' . $conn->error;
    }
}

// Handle success messages from URL
$success = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'header_created':
            $success = 'Header buku bank berhasil dibuat!';
            break;
        case 'detail_added':
            $success = 'Detail transaksi berhasil ditambahkan!';
            break;
        case 'header_deleted':
            $success = 'Header berhasil dihapus!';
            break;
        case 'detail_deleted':
            $success = 'Detail transaksi berhasil dihapus!';
            break;
    }
}

// Handle create new bank header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_header'])) {
    $kode_proyek = $_POST['kode_proyek'];
    $account_name = $_POST['account_name'];
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $exrate = $_POST['exrate'] ?? 1.00; // Exchange rate
    $currency = $_POST['currency'];
    $periode_bulan = $_POST['periode_bulan'];
    $periode_tahun = $_POST['periode_tahun'];
    $saldo_awal_idr = $_POST['saldo_awal_idr'] ?? 0;
    $saldo_awal_usd = $_POST['saldo_awal_usd'] ?? 0;
    
    // Automatic conversion based on which field was filled
    // If one is zero and the other is filled, calculate the conversion
    if ($exrate > 0) {
        if ($saldo_awal_idr > 0 && $saldo_awal_usd == 0) {
            // Convert IDR to USD
            $saldo_awal_usd = $saldo_awal_idr / $exrate;
        } elseif ($saldo_awal_usd > 0 && $saldo_awal_idr == 0) {
            // Convert USD to IDR
            $saldo_awal_idr = $saldo_awal_usd * $exrate;
        }
    }
    
    // Generate unique ID for header (format: BH-YYYYMMDD-HHMMSS-RAND)
    $id_bank_header = 'BH-' . date('Ymd-His') . '-' . substr(uniqid(), -4);
    
    $stmt = $conn->prepare("INSERT INTO buku_bank_header (id_bank_header, kode_proyek, account_name, bank_name, account_number, exrate, currency, periode_bulan, periode_tahun, saldo_awal_idr, saldo_awal_usd, saldo_akhir_idr, saldo_akhir_usd, current_period_change_idr, current_period_change_usd, prepared_by, status_laporan, tanggal_pembuatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, 'draft', CURDATE())");
    $stmt->bind_param("sssssdssddddds", $id_bank_header, $kode_proyek, $account_name, $bank_name, $account_number, $exrate, $currency, $periode_bulan, $periode_tahun, $saldo_awal_idr, $saldo_awal_usd, $saldo_awal_idr, $saldo_awal_usd, $user_name);
    
    if ($stmt->execute()) {
        // Redirect to prevent form resubmission (PRG pattern)
        header('Location: buku_bank.php?success=header_created');
        exit();
    } else {
        $error = 'Gagal membuat header buku bank: ' . $conn->error;
    }
}

// Handle add detail entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_detail'])) {
    $id_bank_header = $_POST['id_bank_header'];
    $tanggal = parseDateID($_POST['tanggal']); // Convert DD/MM/YYYY to YYYY-MM-DD
    $reff = $_POST['reff'];
    $title_activity = $_POST['title_activity'];
    $cost_description = $_POST['cost_description'];
    $recipient = $_POST['recipient'];
    $place_code = $_POST['place_code'];
    $exp_code = $_POST['exp_code'];
    $nominal_code = $_POST['nominal_code'];
    $exrate = $_POST['exrate'];
    $cost_curr = $_POST['cost_curr'];
    $debit_idr = $_POST['debit_idr'] ?? 0;
    $debit_usd = $_POST['debit_usd'] ?? 0;
    $credit_idr = $_POST['credit_idr'] ?? 0;
    $credit_usd = $_POST['credit_usd'] ?? 0;
    
    // Get last balance from this header
    $balance_stmt = $conn->prepare("SELECT balance_idr, balance_usd FROM buku_bank_detail WHERE id_bank_header = ? ORDER BY id_detail_bank DESC LIMIT 1");
    $balance_stmt->bind_param("s", $id_bank_header);
    $balance_stmt->execute();
    $balance_result = $balance_stmt->get_result();
    
    if ($balance_result->num_rows > 0) {
        $last_balance = $balance_result->fetch_assoc();
        $balance_idr = $last_balance['balance_idr'] + $debit_idr - $credit_idr;
        $balance_usd = $last_balance['balance_usd'] + $debit_usd - $credit_usd;
    } else {
        // Get beginning balance from header
        $header_stmt = $conn->prepare("SELECT saldo_awal_idr, saldo_awal_usd FROM buku_bank_header WHERE id_bank_header = ?");
        $header_stmt->bind_param("s", $id_bank_header);
        $header_stmt->execute();
        $header_result = $header_stmt->get_result();
        $header = $header_result->fetch_assoc();
        $balance_idr = $header['saldo_awal_idr'] + $debit_idr - $credit_idr;
        $balance_usd = $header['saldo_awal_usd'] + $debit_usd - $credit_usd;
    }
    
    // Generate unique ID for detail (format: BD-YYYYMMDD-HHMMSS-RAND)
    $id_detail_bank = 'BD-' . date('Ymd-His') . '-' . substr(uniqid(), -4);
    
    $stmt = $conn->prepare("INSERT INTO buku_bank_detail (id_detail_bank, id_bank_header, tanggal, reff, title_activity, cost_description, recipient, place_code, exp_code, nominal_code, exrate, cost_curr, debit_idr, debit_usd, credit_idr, credit_usd, balance_idr, balance_usd, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ongoing')");
    $stmt->bind_param("sssssssssssddddddd", $id_detail_bank, $id_bank_header, $tanggal, $reff, $title_activity, $cost_description, $recipient, $place_code, $exp_code, $nominal_code, $exrate, $cost_curr, $debit_idr, $debit_usd, $credit_idr, $credit_usd, $balance_idr, $balance_usd);
    
    if ($stmt->execute()) {
        // Update header: saldo_akhir and current_period_change
        $change_idr = $debit_idr - $credit_idr;
        $change_usd = $debit_usd - $credit_usd;
        
        $update_stmt = $conn->prepare("UPDATE buku_bank_header SET 
            saldo_akhir_idr = ?,
            saldo_akhir_usd = ?,
            current_period_change_idr = current_period_change_idr + ?,
            current_period_change_usd = current_period_change_usd + ?
            WHERE id_bank_header = ?");
        $update_stmt->bind_param("dddds", $balance_idr, $balance_usd, $change_idr, $change_usd, $id_bank_header);
        $update_stmt->execute();
        
        // Redirect to prevent form resubmission (PRG pattern)
        header('Location: buku_bank.php?success=detail_added');
        exit();
    } else {
        $error = 'Gagal menambahkan detail transaksi: ' . $conn->error;
    }
}

// Get hierarchical data: Project > Year > Month (Header) > Details
$query = "SELECT 
    bh.*,
    p.nama_proyek,
    (SELECT COUNT(*) FROM buku_bank_detail bd WHERE bd.id_bank_header = bh.id_bank_header) as total_transactions
FROM buku_bank_header bh
LEFT JOIN proyek p ON bh.kode_proyek = p.kode_proyek
ORDER BY bh.kode_proyek, bh.periode_tahun DESC, bh.periode_bulan DESC";

$all_headers = $conn->query($query);

// Organize data hierarchically
$hierarchical_data = [];
while ($header = $all_headers->fetch_assoc()) {
    $project_code = $header['kode_proyek'];
    $year = $header['periode_tahun'];
    $month = $header['periode_bulan'];
    
    if (!isset($hierarchical_data[$project_code])) {
        $hierarchical_data[$project_code] = [
            'nama_proyek' => $header['nama_proyek'],
            'years' => []
        ];
    }
    
    if (!isset($hierarchical_data[$project_code]['years'][$year])) {
        $hierarchical_data[$project_code]['years'][$year] = [
            'months' => []
        ];
    }
    
    // Get all details for this header
    $details_query = "SELECT * FROM buku_bank_detail WHERE id_bank_header = ? ORDER BY tanggal DESC, id_detail_bank DESC";
    $details_stmt = $conn->prepare($details_query);
    $details_stmt->bind_param("s", $header['id_bank_header']);
    $details_stmt->execute();
    $details_result = $details_stmt->get_result();
    
    $details = [];
    while ($detail = $details_result->fetch_assoc()) {
        $details[] = $detail;
    }
    
    $hierarchical_data[$project_code]['years'][$year]['months'][$month][] = [
        'header' => $header,
        'details' => $details
    ];
}

// Get projects for form
$projects = $conn->query("SELECT kode_proyek, nama_proyek FROM proyek WHERE status_proyek != 'cancelled'");

// Get bank headers for adding details
$bank_headers = $conn->query("SELECT bh.id_bank_header, bh.kode_proyek, bh.account_name, bh.periode_bulan, bh.periode_tahun, p.nama_proyek 
    FROM buku_bank_header bh 
    LEFT JOIN proyek p ON bh.kode_proyek = p.kode_proyek 
    ORDER BY bh.tanggal_pembuatan DESC");

// Get distinct bank details per project for autocomplete
$bank_details_query = "SELECT DISTINCT kode_proyek, bank_name, account_name, account_number 
    FROM buku_bank_header 
    ORDER BY kode_proyek, bank_name, account_name";
$bank_details_result = $conn->query($bank_details_query);

// Organize bank details by project
$bank_details_by_project = [];
while ($detail = $bank_details_result->fetch_assoc()) {
    $project_code = $detail['kode_proyek'];
    if (!isset($bank_details_by_project[$project_code])) {
        $bank_details_by_project[$project_code] = [];
    }
    $bank_details_by_project[$project_code][] = [
        'bank_name' => $detail['bank_name'],
        'account_name' => $detail['account_name'],
        'account_number' => $detail['account_number']
    ];
}

// Month names
$month_names = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Bank - PRCF Keuangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .accordion-content.active {
            max-height: 10000px;
            transition: max-height 0.5s ease-in;
        }
        .rotate-icon {
            transition: transform 0.3s ease;
        }
        .rotate-icon.active {
            transform: rotate(90deg);
        }
    </style>
    <script src="../../assets/js/toast.js"></script>
    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_fm.php" class="text-gray-600 hover:text-gray-800 transition duration-200">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-university text-blue-600 mr-2"></i>Buku Bank
                        </h1>
                        <p class="text-xs text-gray-500">Kelola transaksi bank per periode</p>
                    </div>
                </div>
                <span class="text-gray-700 font-medium">
                    <i class="fas fa-user-circle mr-2 text-gray-500"></i><?php echo $user_name; ?>
                </span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success) && $success): ?>
            <div id="successMessage" class="bg-green-50 border-l-4 border-green-500 text-green-800 px-6 py-4 rounded-lg mb-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <span class="font-medium"><?php echo $success; ?></span>
                    </div>
                    <button onclick="closeSuccessMessage()" class="text-green-700 hover:text-green-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error) && $error): ?>
            <div id="errorMessage" class="bg-red-50 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-lg mb-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                        <span class="font-medium"><?php echo $error; ?></span>
                    </div>
                    <button onclick="closeErrorMessage()" class="text-red-700 hover:text-red-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Bar -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center space-x-4">
                <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-200">
                    <span class="text-sm text-gray-600">Total Proyek:</span>
                    <span class="ml-2 text-lg font-bold text-blue-600"><?php echo count($hierarchical_data); ?></span>
                </div>
            </div>

            <div class="flex space-x-3">
                <button onclick="toggleAddDetailForm()" 
                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition duration-200 font-medium shadow-md hover:shadow-lg">
                    <i class="fas fa-plus mr-2"></i> Tambah Transaksi
                </button>
                <button onclick="toggleCreateForm()" 
                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition duration-200 font-medium shadow-md hover:shadow-lg">
                    <i class="fas fa-folder-plus mr-2"></i> Buat Header Periode Baru
                </button>
            </div>
        </div>

        <!-- Add Detail Form -->
        <div id="addDetailForm" class="hidden mb-8 bg-white rounded-xl shadow-lg p-8 border border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                    Tambah Transaksi Bank
                </h3>
                <button onclick="toggleAddDetailForm()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-file-alt text-blue-500 mr-1"></i> Pilih Periode (Header) *
                        </label>
                        <select name="id_bank_header" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                            <option value="">Pilih Periode Bank</option>
                        <?php 
                            $bank_headers->data_seek(0);
                            while ($bh = $bank_headers->fetch_assoc()): 
                        ?>
                                <option value="<?php echo $bh['id_bank_header']; ?>">
                                    <?php echo $bh['kode_proyek'] . ' - ' . $bh['account_name'] . ' (' . $month_names[$bh['periode_bulan']] . ' ' . $bh['periode_tahun'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-calendar text-blue-500 mr-1"></i> Tanggal *
                        </label>
                        <input type="text" name="tanggal" id="bank_date" required value="<?php echo date('d/m/Y'); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
                            placeholder="DD/MM/YYYY">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-hashtag text-blue-500 mr-1"></i> Referensi
                        </label>
                        <input type="text" name="reff" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-tasks text-blue-500 mr-1"></i> Judul Aktivitas
                        </label>
                        <input type="text" name="title_activity" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-align-left text-blue-500 mr-1"></i> Deskripsi Biaya
                        </label>
                        <textarea name="cost_description" rows="2"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-user text-blue-500 mr-1"></i> Penerima
                        </label>
                        <input type="text" name="recipient" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Place Code</label>
                        <input type="text" name="place_code" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Exp Code</label>
                        <input type="text" name="exp_code" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Nominal Code</label>
                        <input type="text" name="nominal_code" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-dollar-sign text-blue-500 mr-1"></i> Mata Uang *
                        </label>
                        <select name="cost_curr" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                            <option value="IDR">IDR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">Exchange Rate *</label>
                        <input type="number" name="exrate" step="0.01" value="1.00" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-arrow-up text-green-500 mr-1"></i> Debit IDR
                            <span class="text-xs text-gray-500 font-normal">(auto-convert ke USD)</span>
                        </label>
                        <input type="number" name="debit_idr" step="0.01" value="0" placeholder="Isi salah satu, yang lain otomatis"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-arrow-up text-green-500 mr-1"></i> Debit USD
                            <span class="text-xs text-gray-500 font-normal">(auto-convert ke IDR)</span>
                        </label>
                        <input type="number" name="debit_usd" step="0.01" value="0" placeholder="Isi salah satu, yang lain otomatis"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-arrow-down text-red-500 mr-1"></i> Credit IDR
                            <span class="text-xs text-gray-500 font-normal">(auto-convert ke USD)</span>
                        </label>
                        <input type="number" name="credit_idr" step="0.01" value="0" placeholder="Isi salah satu, yang lain otomatis"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-arrow-down text-red-500 mr-1"></i> Credit USD
                            <span class="text-xs text-gray-500 font-normal">(auto-convert ke IDR)</span>
                        </label>
                        <input type="number" name="credit_usd" step="0.01" value="0" placeholder="Isi salah satu, yang lain otomatis"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="toggleAddDetailForm()"
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-medium">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                    <button type="submit" name="add_detail"
                        class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition duration-200 font-medium shadow-md">
                        <i class="fas fa-save mr-2"></i> Simpan Transaksi
                    </button>
                </div>
            </form>
            </div>

        <!-- Create Header Form -->
        <div id="createForm" class="hidden mb-8 bg-white rounded-xl shadow-lg p-8 border border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-folder-plus text-green-600 mr-2"></i>
                    Buat Header Periode Bank Baru
                </h3>
                <button onclick="toggleCreateForm()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
            </button>
        </div>

            <form method="POST" class="space-y-6" id="createHeaderForm" onsubmit="return handleFormSubmit(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-project-diagram text-green-500 mr-1"></i> Kode Proyek *
                        </label>
                        <select name="kode_proyek" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                            <option value="">Pilih Proyek</option>
                            <?php 
                            $projects->data_seek(0);
                            while ($project = $projects->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $project['kode_proyek']; ?>">
                                    <?php echo $project['kode_proyek'] . ' - ' . $project['nama_proyek']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-user-tag text-green-500 mr-1"></i> Nama Akun *
                        </label>
                        <select name="account_name" id="account_name" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                            <option value="">Pilih Nama Akun</option>
                            <option value="__custom__">+ Tambah Akun Baru (Ketik Manual)</option>
                            <!-- Options will be populated dynamically based on selected project -->
                        </select>
                        <input type="text" name="account_name_custom" id="account_name_custom" style="display:none;" placeholder="Ketik nama akun baru"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent mt-2">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-building-columns text-green-500 mr-1"></i> Nama Bank *
                        </label>
                        <select name="bank_name" id="bank_name" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                            <option value="">Pilih Nama Bank</option>
                            <option value="__custom__">+ Tambah Bank Baru (Ketik Manual)</option>
                            <!-- Options will be populated dynamically based on selected project -->
                        </select>
                        <input type="text" name="bank_name_custom" id="bank_name_custom" style="display:none;" placeholder="Ketik nama bank baru"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent mt-2">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-credit-card text-green-500 mr-1"></i> Nomor Rekening *
                        </label>
                        <select name="account_number" id="account_number" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                            <option value="">Pilih Nomor Rekening</option>
                            <option value="__custom__">+ Tambah Rekening Baru (Ketik Manual)</option>
                            <!-- Options will be populated dynamically based on selected project -->
                        </select>
                        <input type="text" name="account_number_custom" id="account_number_custom" style="display:none;" placeholder="Ketik nomor rekening baru"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent mt-2">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-dollar-sign text-green-500 mr-1"></i> Mata Uang *
                        </label>
                        <select name="currency" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                            <option value="IDR">IDR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-exchange-alt text-green-500 mr-1"></i> Kurs (Exchange Rate) *
                        </label>
                        <input type="number" name="exrate" id="exrate" step="0.01" value="1.00" min="0.01" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>1 USD = ... IDR (untuk konversi otomatis)</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-calendar-alt text-green-500 mr-1"></i> Periode Bulan *
                        </label>
                        <select name="periode_bulan" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                            <?php foreach ($month_names as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo $num == date('m') ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-calendar text-green-500 mr-1"></i> Periode Tahun *
                        </label>
                        <input type="text" name="periode_tahun" required value="<?php echo date('Y'); ?>" pattern="[0-9]{4}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-money-bill-wave text-green-500 mr-1"></i> Saldo Awal IDR
                        </label>
                        <input type="number" name="saldo_awal_idr" id="saldo_awal_idr" step="0.01" value="0" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Isi salah satu, yang lain otomatis dikonversi</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            <i class="fas fa-dollar-sign text-green-500 mr-1"></i> Saldo Awal USD
                        </label>
                        <input type="number" name="saldo_awal_usd" id="saldo_awal_usd" step="0.01" value="0" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Isi salah satu, yang lain otomatis dikonversi</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="toggleCreateForm()"
                        class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-medium">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                    <button type="submit" name="create_header"
                        class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition duration-200 font-medium shadow-md">
                        <i class="fas fa-save mr-2"></i> Buat Header
                    </button>
                </div>
            </form>
        </div>

        <!-- Hierarchical Bank Book Display -->
        <div class="space-y-6">
            <?php if (empty($hierarchical_data)): ?>
                <div class="bg-white rounded-xl shadow-md p-12 text-center border border-gray-200">
                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Belum Ada Data</h3>
                    <p class="text-gray-500">Buat header periode dan tambahkan transaksi untuk memulai pencatatan buku bank</p>
                </div>
            <?php else: ?>
                <?php foreach ($hierarchical_data as $project_code => $project_data): ?>
                    <!-- Level 1: PROJECT -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 cursor-pointer hover:from-blue-700 hover:to-blue-800 transition duration-200"
                             onclick="toggleAccordion('project-<?php echo $project_code; ?>')">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-4">
                                    <i class="fas fa-chevron-right rotate-icon text-white text-xl" id="icon-project-<?php echo $project_code; ?>"></i>
                                    <div>
                                        <h2 class="text-xl font-bold text-white">
                                            <i class="fas fa-folder-open mr-2"></i><?php echo $project_code; ?>
                                        </h2>
                                        <p class="text-blue-100 text-sm"><?php echo $project_data['nama_proyek']; ?></p>
                                    </div>
                                </div>
                                <div class="text-white text-right">
                                    <p class="text-xs text-blue-100 mb-1">Total Periode</p>
                                    <p class="text-2xl font-bold"><?php echo count($project_data['years']); ?> Tahun</p>
                                </div>
                            </div>
                        </div>

                        <!-- Years Container -->
                        <div id="project-<?php echo $project_code; ?>" class="accordion-content bg-gray-50">
                            <div class="p-6 space-y-4">
                                <?php 
                                krsort($project_data['years']); // Sort years descending
                                foreach ($project_data['years'] as $year => $year_data): 
                                ?>
                                    <!-- Level 2: YEAR -->
                                    <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                                        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-3 cursor-pointer hover:from-indigo-600 hover:to-indigo-700 transition duration-200"
                                             onclick="toggleAccordion('year-<?php echo $project_code . '-' . $year; ?>')">
                                            <div class="flex justify-between items-center">
                                                <div class="flex items-center space-x-3">
                                                    <i class="fas fa-chevron-right rotate-icon text-white" id="icon-year-<?php echo $project_code . '-' . $year; ?>"></i>
                                                    <h3 class="text-lg font-bold text-white">
                                                        <i class="fas fa-calendar-alt mr-2"></i>Tahun <?php echo $year; ?>
                                                    </h3>
                                                </div>
                                                <div class="text-white text-sm">
                                                    <span class="bg-indigo-700 px-3 py-1 rounded-full">
                                                        <?php echo count($year_data['months']); ?> Periode
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Months Container -->
                                        <div id="year-<?php echo $project_code . '-' . $year; ?>" class="accordion-content bg-gray-50">
                                            <div class="p-4 space-y-3">
                                                <?php 
                                                krsort($year_data['months']); // Sort months descending
                                                foreach ($year_data['months'] as $month => $month_headers): 
                                                ?>
                                                    <?php foreach ($month_headers as $idx => $data): 
                                                        $header = $data['header'];
                                                        $details = $data['details'];
                                                    ?>
                                                        <!-- Level 3: HEADER (Per Period) -->
                                                        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                                                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-5 py-4">
                                                                <div class="flex justify-between items-start">
                                                                    <div class="flex-1">
                                                                        <div class="flex items-center space-x-3 mb-2">
                                                                            <h4 class="font-bold text-white text-lg">
                                                                                <i class="fas fa-file-invoice-dollar mr-2"></i>
                                                                                <?php echo $header['account_name']; ?>
                                                                            </h4>
                                                                            <span class="bg-purple-700 text-white text-xs px-3 py-1 rounded-full">
                                                                                <?php echo $month_names[$month] . ' ' . $year; ?>
                                                                            </span>
                                                                            <span class="bg-white text-purple-600 text-xs px-3 py-1 rounded-full font-semibold">
                                                                                <?php echo count($details); ?> transaksi
                                                                            </span>
                                                                        </div>
                                                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-white text-sm">
                                                                            <div>
                                                                                <p class="text-purple-100 text-xs">Bank</p>
                                                                                <p class="font-medium"><?php echo $header['bank_name']; ?></p>
                                                                            </div>
                                                                            <div>
                                                                                <p class="text-purple-100 text-xs">No. Rekening</p>
                                                                                <p class="font-medium"><?php echo $header['account_number']; ?></p>
                                                                            </div>
                                                                            <div>
                                                                                <p class="text-purple-100 text-xs">Mata Uang</p>
                                                                                <p class="font-medium"><?php echo $header['currency']; ?></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <button onclick="toggleAccordion('header-<?php echo $header['id_bank_header']; ?>')" 
                                                                        class="ml-4 bg-purple-700 hover:bg-purple-800 text-white px-4 py-2 rounded-lg text-sm transition duration-200">
                                                                        <i class="fas fa-chevron-down rotate-icon" id="icon-header-<?php echo $header['id_bank_header']; ?>"></i>
                                                                        <span class="ml-2">Detail</span>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <!-- Summary Bar -->
                                                            <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-5 py-3 border-b border-purple-200">
                                                                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
                                                                    <div>
                                                                        <p class="text-purple-700 font-semibold text-xs mb-1">Saldo Awal</p>
                                                                        <p class="text-purple-900 font-bold">
                                                                            <?php echo $header['currency'] == 'IDR' ? 'Rp' : '$'; ?> 
                                                                            <?php echo number_format($header['currency'] == 'IDR' ? $header['saldo_awal_idr'] : $header['saldo_awal_usd'], 2); ?>
                                                                        </p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-purple-700 font-semibold text-xs mb-1">Perubahan</p>
                                                                        <p class="text-purple-900 font-bold">
                                                                            <?php 
                                                                            $change = $header['currency'] == 'IDR' ? $header['current_period_change_idr'] : $header['current_period_change_usd'];
                                                                            $color = $change >= 0 ? 'text-green-600' : 'text-red-600';
                                                                            ?>
                                                                            <span class="<?php echo $color; ?>">
                                                                                <?php echo $change >= 0 ? '+' : ''; ?>
                                                                                <?php echo $header['currency'] == 'IDR' ? 'Rp' : '$'; ?> 
                                                                                <?php echo number_format($change, 2); ?>
                                                                            </span>
                                                                        </p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-purple-700 font-semibold text-xs mb-1">Saldo Akhir</p>
                                                                        <p class="text-blue-600 font-bold text-lg">
                                                                            <?php echo $header['currency'] == 'IDR' ? 'Rp' : '$'; ?> 
                                                                            <?php echo number_format($header['currency'] == 'IDR' ? $header['saldo_akhir_idr'] : $header['saldo_akhir_usd'], 2); ?>
                                                                        </p>
                                                                    </div>
                                                                    <div>
                                                                        <p class="text-purple-700 font-semibold text-xs mb-1">Status</p>
                                                                        <span class="inline-block px-3 py-1 text-xs font-bold rounded-full 
                                                                            <?php 
                                                                            echo match($header['status_laporan']) {
                                                                                'draft' => 'bg-gray-200 text-gray-800',
                                                                                'submitted' => 'bg-yellow-200 text-yellow-800',
                                                                                'approved' => 'bg-green-200 text-green-800',
                                                                                default => 'bg-blue-200 text-blue-800'
                                                                            };
                                                                            ?>">
                                                                            <?php echo strtoupper($header['status_laporan']); ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex flex-col md:flex-row items-start md:items-center justify-end gap-2">
                                                                        <!-- Export to Excel Button -->
                                                                        <a href="export_bank_excel.php?id=<?php echo $header['id_bank_header']; ?>" 
                                                                            target="_blank"
                                                                            class="whitespace-nowrap inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200 text-xs font-semibold">
                                                                            <i class="fas fa-file-excel mr-1"></i> Export to Excel
                                                                        </a>
                                                                        <!-- Delete Button -->
                                                                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus header ini? Semua detail transaksi akan ikut terhapus!');" class="inline">
                                                                            <input type="hidden" name="id_bank_header" value="<?php echo $header['id_bank_header']; ?>">
                                                                            <button type="submit" name="delete_header" 
                                                                                class="whitespace-nowrap inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-200 text-xs font-semibold">
                                                                                <i class="fas fa-trash mr-1"></i> Hapus
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Details Table -->
                                                            <div id="header-<?php echo $header['id_bank_header']; ?>" class="accordion-content">
                                                                <?php if (empty($details)): ?>
                                                                    <div class="p-8 text-center text-gray-500">
                                                                        <i class="fas fa-inbox text-4xl mb-3"></i>
                                                                        <p>Belum ada transaksi. Klik "Tambah Transaksi" untuk menambah.</p>
                                                                    </div>
                                                                <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                                                                            <thead class="bg-gray-100 border-b-2 border-gray-200">
                                                                                <tr>
                                                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Tanggal</th>
                                                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Aktivitas / Deskripsi</th>
                                                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Penerima</th>
                                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Debit IDR</th>
                                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Credit IDR</th>
                                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Balance IDR</th>
                                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Debit USD</th>
                                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Credit USD</th>
                                                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700">Balance USD</th>
                        </tr>
                    </thead>
                                                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                                                <?php foreach ($details as $detail): ?>
                                                                                <tr class="hover:bg-gray-50 transition duration-150">
                                                                                    <td class="px-4 py-3">
                                                                                        <div class="text-gray-700 font-medium"><?php echo date('d/m/Y', strtotime($detail['tanggal'])); ?></div>
                                                                                        <?php if ($detail['reff']): ?>
                                                                                            <div class="text-xs text-gray-500">Ref: <?php echo $detail['reff']; ?></div>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3">
                                                                                        <?php if ($detail['title_activity']): ?>
                                                                                            <div class="font-medium text-gray-800"><?php echo $detail['title_activity']; ?></div>
                                                                                        <?php endif; ?>
                                                                                        <?php if ($detail['cost_description']): ?>
                                                                                            <div class="text-xs text-gray-600 mt-1"><?php echo $detail['cost_description']; ?></div>
                                                                                        <?php endif; ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-gray-700"><?php echo $detail['recipient'] ?: '-'; ?></td>
                                                                                    <td class="px-4 py-3 text-right <?php echo $detail['debit_idr'] > 0 ? 'text-green-600 font-semibold' : 'text-gray-400'; ?>">
                                                                                        <?php echo $detail['debit_idr'] > 0 ? 'Rp ' . number_format($detail['debit_idr'], 2) : '-'; ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right <?php echo $detail['credit_idr'] > 0 ? 'text-red-600 font-semibold' : 'text-gray-400'; ?>">
                                                                                        <?php echo $detail['credit_idr'] > 0 ? 'Rp ' . number_format($detail['credit_idr'], 2) : '-'; ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right font-bold text-blue-600">
                                                                                        Rp <?php echo number_format($detail['balance_idr'], 2); ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right <?php echo $detail['debit_usd'] > 0 ? 'text-green-600 font-semibold' : 'text-gray-400'; ?>">
                                                                                        <?php echo $detail['debit_usd'] > 0 ? '$ ' . number_format($detail['debit_usd'], 2) : '-'; ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right <?php echo $detail['credit_usd'] > 0 ? 'text-red-600 font-semibold' : 'text-gray-400'; ?>">
                                                                                        <?php echo $detail['credit_usd'] > 0 ? '$ ' . number_format($detail['credit_usd'], 2) : '-'; ?>
                                                                                    </td>
                                                                                    <td class="px-4 py-3 text-right font-bold text-blue-600">
                                                                                        $ <?php echo number_format($detail['balance_usd'], 2); ?>
                                                                                    </td>
                        </tr>
                                                                                <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Bank details data by project (from PHP)
        const bankDetailsByProject = <?php echo json_encode($bank_details_by_project); ?>;

        function toggleCreateForm() {
            const form = document.getElementById('createForm');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                document.getElementById('addDetailForm').classList.add('hidden');
            }
        }

        function toggleAddDetailForm() {
            const form = document.getElementById('addDetailForm');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                document.getElementById('createForm').classList.add('hidden');
            }
        }

        function toggleAccordion(id) {
            const content = document.getElementById(id);
            const icon = document.getElementById('icon-' + id);
            
            content.classList.toggle('active');
            icon.classList.toggle('active');
        }

        // Update bank details autocomplete when project is selected
        function updateBankDetailsOptions() {
            const projectSelect = document.querySelector('select[name="kode_proyek"]');
            const selectedProject = projectSelect.value;
            
            // Get select elements
            const bankNameSelect = document.getElementById('bank_name');
            const accountNameSelect = document.getElementById('account_name');
            const accountNumberSelect = document.getElementById('account_number');
            
            // Clear existing options (keep first two: placeholder and custom option)
            bankNameSelect.innerHTML = '<option value="">Pilih Nama Bank</option><option value="__custom__">+ Tambah Bank Baru (Ketik Manual)</option>';
            accountNameSelect.innerHTML = '<option value="">Pilih Nama Akun</option><option value="__custom__">+ Tambah Akun Baru (Ketik Manual)</option>';
            accountNumberSelect.innerHTML = '<option value="">Pilih Nomor Rekening</option><option value="__custom__">+ Tambah Rekening Baru (Ketik Manual)</option>';
            
            if (selectedProject && bankDetailsByProject[selectedProject]) {
                const details = bankDetailsByProject[selectedProject];
                
                // Use Set to avoid duplicates
                const bankNames = new Set();
                const accountNames = new Set();
                const accountNumbers = new Set();
                
                details.forEach(detail => {
                    bankNames.add(detail.bank_name);
                    accountNames.add(detail.account_name);
                    accountNumbers.add(detail.account_number);
                });
                
                // Populate select dropdowns
                bankNames.forEach(name => {
                    const option = document.createElement('option');
                    option.value = name;
                    option.textContent = name;
                    bankNameSelect.appendChild(option);
                });
                
                accountNames.forEach(name => {
                    const option = document.createElement('option');
                    option.value = name;
                    option.textContent = name;
                    accountNameSelect.appendChild(option);
                });
                
                accountNumbers.forEach(number => {
                    const option = document.createElement('option');
                    option.value = number;
                    option.textContent = number;
                    accountNumberSelect.appendChild(option);
                });
            }
        }

        // Auto-fill related bank details when user selects from dropdown
        function setupBankDetailsAutoFill() {
            const projectSelect = document.querySelector('select[name="kode_proyek"]');
            const bankNameSelect = document.getElementById('bank_name');
            const accountNameSelect = document.getElementById('account_name');
            const accountNumberSelect = document.getElementById('account_number');
            
            const bankNameCustom = document.getElementById('bank_name_custom');
            const accountNameCustom = document.getElementById('account_name_custom');
            const accountNumberCustom = document.getElementById('account_number_custom');
            
            // Handle custom input toggle for bank name
            bankNameSelect.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    bankNameCustom.style.display = 'block';
                    bankNameCustom.required = true;
                    this.required = false;
                } else {
                    bankNameCustom.style.display = 'none';
                    bankNameCustom.required = false;
                    this.required = true;
                    
                    // Auto-fill matching details
                    const selectedProject = projectSelect.value;
                    const selectedBankName = this.value;
                    
                    if (selectedProject && selectedBankName && bankDetailsByProject[selectedProject]) {
                        const matchingDetails = bankDetailsByProject[selectedProject].filter(
                            detail => detail.bank_name === selectedBankName
                        );
                        
                        // If only one match, auto-fill
                        if (matchingDetails.length === 1) {
                            accountNameSelect.value = matchingDetails[0].account_name;
                            accountNumberSelect.value = matchingDetails[0].account_number;
                        }
                    }
                }
            });
            
            // Handle custom input toggle for account name
            accountNameSelect.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    accountNameCustom.style.display = 'block';
                    accountNameCustom.required = true;
                    this.required = false;
                } else {
                    accountNameCustom.style.display = 'none';
                    accountNameCustom.required = false;
                    this.required = true;
                    
                    // Auto-fill matching details
                    const selectedProject = projectSelect.value;
                    const selectedAccountName = this.value;
                    
                    if (selectedProject && selectedAccountName && bankDetailsByProject[selectedProject]) {
                        const matchingDetails = bankDetailsByProject[selectedProject].filter(
                            detail => detail.account_name === selectedAccountName
                        );
                        
                        // If only one match, auto-fill
                        if (matchingDetails.length === 1) {
                            bankNameSelect.value = matchingDetails[0].bank_name;
                            accountNumberSelect.value = matchingDetails[0].account_number;
                        }
                    }
                }
            });
            
            // Handle custom input toggle for account number
            accountNumberSelect.addEventListener('change', function() {
                if (this.value === '__custom__') {
                    accountNumberCustom.style.display = 'block';
                    accountNumberCustom.required = true;
                    this.required = false;
                } else {
                    accountNumberCustom.style.display = 'none';
                    accountNumberCustom.required = false;
                    this.required = true;
                    
                    // Auto-fill matching details
                    const selectedProject = projectSelect.value;
                    const selectedAccountNumber = this.value;
                    
                    if (selectedProject && selectedAccountNumber && bankDetailsByProject[selectedProject]) {
                        const matchingDetails = bankDetailsByProject[selectedProject].filter(
                            detail => detail.account_number === selectedAccountNumber
                        );
                        
                        // If only one match, auto-fill
                        if (matchingDetails.length === 1) {
                            bankNameSelect.value = matchingDetails[0].bank_name;
                            accountNameSelect.value = matchingDetails[0].account_name;
                        }
                    }
                }
            });
            
            // Listen to project change
            projectSelect.addEventListener('change', updateBankDetailsOptions);
        }

        // Auto-convert currency based on exchange rate
        function convertCurrency() {
            const exrate = parseFloat(document.querySelector('input[name="exrate"]').value) || 1;
            
            // Get all input fields
            const debitIdr = document.querySelector('input[name="debit_idr"]');
            const debitUsd = document.querySelector('input[name="debit_usd"]');
            const creditIdr = document.querySelector('input[name="credit_idr"]');
            const creditUsd = document.querySelector('input[name="credit_usd"]');
            
            // Debit IDR -> USD conversion
            debitIdr.addEventListener('input', function() {
                if (this.value && exrate > 0) {
                    debitUsd.value = (parseFloat(this.value) / exrate).toFixed(2);
                }
            });
            
            // Debit USD -> IDR conversion
            debitUsd.addEventListener('input', function() {
                if (this.value && exrate > 0) {
                    debitIdr.value = (parseFloat(this.value) * exrate).toFixed(2);
                }
            });
            
            // Credit IDR -> USD conversion
            creditIdr.addEventListener('input', function() {
                if (this.value && exrate > 0) {
                    creditUsd.value = (parseFloat(this.value) / exrate).toFixed(2);
                }
            });
            
            // Credit USD -> IDR conversion
            creditUsd.addEventListener('input', function() {
                if (this.value && exrate > 0) {
                    creditIdr.value = (parseFloat(this.value) * exrate).toFixed(2);
                }
            });
            
            // Recalculate when exchange rate changes
            document.querySelector('input[name="exrate"]').addEventListener('input', function() {
                // Recalculate based on which field has value
                if (debitIdr.value > 0) {
                    debitUsd.value = (parseFloat(debitIdr.value) / parseFloat(this.value)).toFixed(2);
                } else if (debitUsd.value > 0) {
                    debitIdr.value = (parseFloat(debitUsd.value) * parseFloat(this.value)).toFixed(2);
                }
                
                if (creditIdr.value > 0) {
                    creditUsd.value = (parseFloat(creditIdr.value) / parseFloat(this.value)).toFixed(2);
                } else if (creditUsd.value > 0) {
                    creditIdr.value = (parseFloat(creditUsd.value) * parseFloat(this.value)).toFixed(2);
                }
            });
        }
        
        // Initialize conversion on page load
        document.addEventListener('DOMContentLoaded', function() {
            convertCurrency();
            setupBankDetailsAutoFill();
            setupInitialBalanceConversion();
            
            // Auto-hide success message after 5 seconds and clean URL
            const successMsg = document.getElementById('successMessage');
            if (successMsg) {
                // Clean URL immediately (remove ?success=... parameter)
                if (window.location.search.includes('success=')) {
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
                
                // Auto-hide after 5 seconds
                setTimeout(function() {
                    successMsg.style.transition = 'opacity 0.5s ease-out';
                    successMsg.style.opacity = '0';
                    setTimeout(function() {
                        successMsg.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
        
        // Handle form submission to use custom inputs when needed
        function handleFormSubmit(event) {
            const bankNameSelect = document.getElementById('bank_name');
            const accountNameSelect = document.getElementById('account_name');
            const accountNumberSelect = document.getElementById('account_number');
            
            const bankNameCustom = document.getElementById('bank_name_custom');
            const accountNameCustom = document.getElementById('account_name_custom');
            const accountNumberCustom = document.getElementById('account_number_custom');
            
            // Replace select values with custom input values if custom option is selected
            if (bankNameSelect.value === '__custom__') {
                if (!bankNameCustom.value.trim()) {
                    Toast.warning('Silakan masukkan nama bank!');
                    bankNameCustom.focus();
                    return false;
                }
                // Create hidden input with the custom value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'bank_name';
                hiddenInput.value = bankNameCustom.value.trim();
                event.target.appendChild(hiddenInput);
                bankNameSelect.removeAttribute('name'); // Remove name from select to avoid conflict
            }
            
            if (accountNameSelect.value === '__custom__') {
                if (!accountNameCustom.value.trim()) {
                    Toast.warning('Silakan masukkan nama akun!');
                    accountNameCustom.focus();
                    return false;
                }
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'account_name';
                hiddenInput.value = accountNameCustom.value.trim();
                event.target.appendChild(hiddenInput);
                accountNameSelect.removeAttribute('name');
            }
            
            if (accountNumberSelect.value === '__custom__') {
                if (!accountNumberCustom.value.trim()) {
                    Toast.warning('Silakan masukkan nomor rekening!');
                    accountNumberCustom.focus();
                    return false;
                }
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'account_number';
                hiddenInput.value = accountNumberCustom.value.trim();
                event.target.appendChild(hiddenInput);
                accountNumberSelect.removeAttribute('name');
            }
            
            return true;
        }

        // Auto-convert initial balance between IDR and USD
        function setupInitialBalanceConversion() {
            const exrateInput = document.getElementById('exrate');
            const saldoIdrInput = document.getElementById('saldo_awal_idr');
            const saldoUsdInput = document.getElementById('saldo_awal_usd');
            
            // When IDR is entered, convert to USD
            saldoIdrInput.addEventListener('input', function() {
                const exrate = parseFloat(exrateInput.value) || 1;
                const idrValue = parseFloat(this.value) || 0;
                
                if (idrValue > 0 && exrate > 0) {
                    saldoUsdInput.value = (idrValue / exrate).toFixed(2);
                }
            });
            
            // When USD is entered, convert to IDR
            saldoUsdInput.addEventListener('input', function() {
                const exrate = parseFloat(exrateInput.value) || 1;
                const usdValue = parseFloat(this.value) || 0;
                
                if (usdValue > 0 && exrate > 0) {
                    saldoIdrInput.value = (usdValue * exrate).toFixed(2);
                }
            });
            
            // When exchange rate changes, recalculate conversion
            exrateInput.addEventListener('input', function() {
                const exrate = parseFloat(this.value) || 1;
                const idrValue = parseFloat(saldoIdrInput.value) || 0;
                const usdValue = parseFloat(saldoUsdInput.value) || 0;
                
                // Recalculate based on which field has value
                if (idrValue > 0 && exrate > 0) {
                    saldoUsdInput.value = (idrValue / exrate).toFixed(2);
                } else if (usdValue > 0 && exrate > 0) {
                    saldoIdrInput.value = (usdValue * exrate).toFixed(2);
                }
            });
        }
        
        // Close success message manually
        function closeSuccessMessage() {
            const successMsg = document.getElementById('successMessage');
            if (successMsg) {
                successMsg.style.transition = 'opacity 0.3s ease-out';
                successMsg.style.opacity = '0';
                setTimeout(function() {
                    successMsg.style.display = 'none';
                }, 300);
            }
        }
        
        // Close error message manually
        function closeErrorMessage() {
            const errorMsg = document.getElementById('errorMessage');
            if (errorMsg) {
                errorMsg.style.transition = 'opacity 0.3s ease-out';
                errorMsg.style.opacity = '0';
                setTimeout(function() {
                    errorMsg.style.display = 'none';
                }, 300);
            }
        }
        
        // Initialize Flatpickr for date input
        flatpickr("#bank_date", {
            dateFormat: "d/m/Y",
            locale: "id",
            allowInput: true,
            defaultDate: "today"
        });
    </script>
</body>
</html>
