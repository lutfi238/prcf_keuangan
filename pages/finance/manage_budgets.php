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

if ($_SESSION['user_role'] !== 'Finance Manager') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_budget'])) {
    $kode_proyek = $_POST['kode_proyek'];
    $id_village = $_POST['id_village'];
    $exp_code = $_POST['exp_code'];
    $currency = $_POST['currency']; // USD or IDR
    $amount = floatval($_POST['amount']);
    $exrate = floatval($_POST['exrate']);
    
    // Get village abbr for place code generation
    $stmt = $conn->prepare("SELECT village_abbr FROM villages WHERE id_village = ?");
    $stmt->bind_param("i", $id_village);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $error = "Desa tidak valid.";
    } else {
        $village_abbr = $res->fetch_assoc()['village_abbr'];
        $place_code = $exp_code . '-' . $village_abbr . '-01';
        
        // Calculate amounts
        if ($currency === 'USD') {
            $budget_usd = $amount;
            $budget_idr = $amount * $exrate;
        } else {
            $budget_idr = $amount;
            $budget_usd = $amount / $exrate;
        }
        
        // Insert or Update
        $stmt = $conn->prepare("INSERT INTO project_code_budgets 
            (kode_proyek, id_village, exp_code, place_code, budget_usd, budget_idr, exrate) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            budget_usd = VALUES(budget_usd), 
            budget_idr = VALUES(budget_idr), 
            exrate = VALUES(exrate)");
            
        $stmt->bind_param("sisssdd", $kode_proyek, $id_village, $exp_code, $place_code, $budget_usd, $budget_idr, $exrate);
        
        if ($stmt->execute()) {
            $success = "Budget berhasil disimpan! Place Code: " . $place_code;
        } else {
            $error = "Gagal menyimpan budget: " . $conn->error;
        }
    }
}

// Get Projects
$projects = $conn->query("SELECT kode_proyek, nama_proyek FROM proyek WHERE status_proyek != 'cancelled'");

// Get Villages (exclude deleted)
$villages = $conn->query("SELECT * FROM villages WHERE is_deleted = 0 ORDER BY village_name ASC");

// Get Budgets List
$filter_proyek = $_GET['filter_proyek'] ?? '';
$filter_village = $_GET['filter_village'] ?? '';

$query = "SELECT b.*, v.village_name, p.nama_proyek 
          FROM project_code_budgets b 
          JOIN villages v ON b.id_village = v.id_village 
          JOIN proyek p ON b.kode_proyek = p.kode_proyek 
          WHERE 1=1";

if (!empty($filter_proyek)) {
    $query .= " AND b.kode_proyek = '$filter_proyek'";
}
if (!empty($filter_village)) {
    $query .= " AND b.id_village = '$filter_village'";
}

$query .= " ORDER BY b.created_at DESC";
$budgets = $conn->query($query);

// Get projects with budget summary for validation
$projects_budget_summary = $conn->query("
    SELECT 
        p.kode_proyek,
        p.nama_proyek,
        p.nilai_anggaran as total_budget,
        COALESCE(SUM(b.budget_idr), 0) as allocated_idr,
        COALESCE(SUM(b.budget_usd), 0) as allocated_usd,
        (p.nilai_anggaran - COALESCE(SUM(b.budget_idr), 0)) as remaining_idr
    FROM proyek p
    LEFT JOIN project_code_budgets b ON p.kode_proyek = b.kode_proyek
    WHERE p.status_proyek != 'cancelled'
    GROUP BY p.kode_proyek, p.nama_proyek, p.nilai_anggaran
    ORDER BY p.kode_proyek
");
$budget_summaries = [];
if ($projects_budget_summary) {
    while ($row = $projects_budget_summary->fetch_assoc()) {
        $budget_summaries[$row['kode_proyek']] = $row;
    }
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Budget - PRCF Keuangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_fm.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Kelola Budget Desa</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Input -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <!-- Budget Info Card -->
                    <div id="budgetInfoCard" class="hidden mb-4 p-4 rounded-lg border-2">
                        <h3 class="text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-chart-pie mr-1"></i>Info Budget Proyek
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Anggaran:</span>
                                <span id="totalBudget" class="font-bold">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Teralokasi:</span>
                                <span id="allocatedBudget" class="font-bold">-</span>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <span class="text-gray-600">Sisa:</span>
                                <span id="remainingBudget" class="font-bold">-</span>
                            </div>
                        </div>
                        <div id="budgetWarning" class="hidden mt-3 p-2 bg-yellow-100 border border-yellow-400 rounded text-xs text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <span id="warningText">Perhatian!</span>
                        </div>
                    </div>
                    
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Input / Update Budget</h2>
                    <form method="POST" id="budgetForm" class="space-y-4" onsubmit="return validateBudget()">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proyek</label>
                            <select name="kode_proyek" id="kode_proyek" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                                <option value="">Pilih Proyek</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['kode_proyek']; ?>"><?php echo $p['kode_proyek'] . ' - ' . $p['nama_proyek']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Desa 
                                <a href="../admin/manage_villages.php" class="text-blue-600 text-xs hover:text-blue-800 ml-2">
                                    [<i class="fas fa-cog"></i> Kelola Desa]
                                </a>
                            </label>
                            <select name="id_village" id="id_village" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                                <option value="">Pilih Desa</option>
                                <?php foreach ($villages as $v): ?>
                                    <option value="<?php echo $v['id_village']; ?>" data-abbr="<?php echo $v['village_abbr']; ?>">
                                        <?php echo $v['village_name']; ?> (<?php echo $v['village_abbr']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Exp Code</label>
                            <select name="exp_code" id="exp_code" required 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                                <option value="">Pilih proyek terlebih dahulu</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pilih proyek untuk memuat daftar exp code</p>
                        </div>

                        <div class="bg-gray-50 p-3 rounded border border-gray-200">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Place Code Preview</label>
                            <input type="text" id="place_code_preview" readonly class="w-full bg-transparent border-none font-mono text-lg font-bold text-blue-600 focus:ring-0 p-0" value="-">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mata Uang</label>
                                <select name="currency" id="currency" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                                    <option value="USD">USD</option>
                                    <option value="IDR">IDR</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Exchange Rate</label>
                                <input type="number" step="0.01" name="exrate" id="exrate" value="15500" required 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Budget</label>
                            <input type="number" step="0.01" name="amount" id="amount" required 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                        </div>

                        <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
                            <p>Estimasi Konversi:</p>
                            <p id="conversion_preview" class="font-bold">-</p>
                        </div>

                        <button type="submit" name="save_budget" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition duration-200">
                            Simpan Budget
                        </button>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Daftar Budget</h2>
                        <div class="flex space-x-2">
                            <!-- Filters could go here -->
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Place Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Desa</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Budget (USD)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Used (USD)</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if ($budgets->num_rows > 0): ?>
                                    <?php while ($row = $budgets->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $row['place_code']; ?>
                                            <div class="text-xs text-gray-500"><?php echo $row['kode_proyek']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $row['village_name']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                            $<?php echo number_format($row['budget_usd'], 2); ?>
                                            <div class="text-xs text-gray-500">Rp <?php echo number_format($row['budget_idr'], 0, ',', '.'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                            $<?php echo number_format($row['used_usd'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            <span class="<?php echo $row['remaining_usd'] < 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold'; ?>">
                                                $<?php echo number_format($row['remaining_usd'], 2); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada data budget.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/budget_management.js"></script>
    <script>
        // Local script for page interactions
        document.addEventListener('DOMContentLoaded', function() {
            const kodeProyekSelect = document.getElementById('kode_proyek');
            const idVillageSelect = document.getElementById('id_village');
            const expCodeSelect = document.getElementById('exp_code');
            const placeCodePreview = document.getElementById('place_code_preview');
            const amountInput = document.getElementById('amount');
            const currencySelect = document.getElementById('currency');
            const exrateInput = document.getElementById('exrate');
            const conversionPreview = document.getElementById('conversion_preview');

            // Load exp codes when project changes
            kodeProyekSelect.addEventListener('change', async function() {
                const kodeProyek = this.value;
                expCodeSelect.innerHTML = '<option value="">Loading...</option>';
                expCodeSelect.disabled = true;
                
                if (!kodeProyek) {
                    expCodeSelect.innerHTML = '<option value="">Pilih proyek terlebih dahulu</option>';
                    expCodeSelect.disabled = false;
                    return;
                }
                
                try {
                    const expCodes = await fetchExpCodes(kodeProyek);
                    
                    if (expCodes.length === 0) {
                        expCodeSelect.innerHTML = '<option value="">Tidak ada exp code untuk proyek ini</option>';
                    } else {
                        let options = '<option value="">Pilih Exp Code</option>';
                        expCodes.forEach(ec => {
                            options += `<option value="${ec.exp_code}">${ec.exp_code}</option>`;
                        });
                        expCodeSelect.innerHTML = options;
                    }
                    expCodeSelect.disabled = false;
                } catch (error) {
                    console.error('Error loading exp codes:', error);
                    expCodeSelect.innerHTML = '<option value="">Error loading exp codes</option>';
                    expCodeSelect.disabled = false;
                }
            });

            function updatePlaceCode() {
                const expCode = expCodeSelect.value;
                const selectedOption = idVillageSelect.options[idVillageSelect.selectedIndex];
                const villageAbbr = selectedOption.getAttribute('data-abbr');
                
                if (expCode && villageAbbr) {
                    placeCodePreview.value = generatePlaceCode(expCode, villageAbbr);
                } else {
                    placeCodePreview.value = '-';
                }
            }

            function updateConversion() {
                const amount = parseFloat(amountInput.value) || 0;
                const exrate = parseFloat(exrateInput.value) || 1;
                const currency = currencySelect.value;
                
                if (currency === 'USD') {
                    const idr = calculateIDR(amount, exrate);
                    conversionPreview.textContent = formatCurrency(idr, 'IDR');
                } else {
                    const usd = calculateUSD(amount, exrate);
                    conversionPreview.textContent = formatCurrency(usd, 'USD');
                }
            }

            idVillageSelect.addEventListener('change', updatePlaceCode);
            expCodeSelect.addEventListener('change', updatePlaceCode);
            
            amountInput.addEventListener('input', updateConversion);
            exrateInput.addEventListener('input', updateConversion);
            currencySelect.addEventListener('change', updateConversion);
            
            // Budget validation data from PHP
            const budgetSummaries = <?php echo json_encode($budget_summaries); ?>;
            
            // Budget info display
            function updateBudgetInfo(kodeProyek) {
                const infoCard = document.getElementById('budgetInfoCard');
                const totalEl = document.getElementById('totalBudget');
                const allocatedEl = document.getElementById('allocatedBudget');
                const remainingEl = document.getElementById('remainingBudget');
                const warningEl = document.getElementById('budgetWarning');
                const warningText = document.getElementById('warningText');
                
                if (!kodeProyek || !budgetSummaries[kodeProyek]) {
                    infoCard.classList.add('hidden');
                    return;
                }
                
                const summary = budgetSummaries[kodeProyek];
                const total = parseFloat(summary.total_budget) || 0;
                const allocated = parseFloat(summary.allocated_idr) || 0;
                const remaining = total - allocated;
                
                totalEl.textContent = formatCurrency(total, 'IDR');
                allocatedEl.textContent = formatCurrency(allocated, 'IDR');
                remainingEl.textContent = formatCurrency(remaining, 'IDR');
                
                // Update card color and warning
                if (remaining < 0) {
                    infoCard.className = 'mb-4 p-4 rounded-lg border-2 bg-red-50 border-red-300';
                    remainingEl.className = 'font-bold text-red-600';
                    warningEl.classList.remove('hidden');
                    warningText.textContent = 'Budget proyek sudah melebihi anggaran!';
                } else if (remaining < total * 0.1) {
                    infoCard.className = 'mb-4 p-4 rounded-lg border-2 bg-yellow-50 border-yellow-300';
                    remainingEl.className = 'font-bold text-yellow-600';
                    warningEl.classList.remove('hidden');
                    warningText.textContent = 'Sisa budget kurang dari 10%';
                } else {
                    infoCard.className = 'mb-4 p-4 rounded-lg border-2 bg-green-50 border-green-300';
                    remainingEl.className = 'font-bold text-green-600';
                    warningEl.classList.add('hidden');
                }
                
                infoCard.classList.remove('hidden');
            }
            
            // Add budget info update to project change
            kodeProyekSelect.addEventListener('change', function() {
                updateBudgetInfo(this.value);
            });
            
        });
        
        // Validate budget before submit
        function validateBudget() {
            const kodeProyek = document.getElementById('kode_proyek').value;
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const currency = document.getElementById('currency').value;
            const exrate = parseFloat(document.getElementById('exrate').value) || 15500;
            
            // Convert to IDR for comparison
            let amountIDR = currency === 'USD' ? amount * exrate : amount;
            
            const budgetSummaries = <?php echo json_encode($budget_summaries); ?>;
            
            if (budgetSummaries[kodeProyek]) {
                const summary = budgetSummaries[kodeProyek];
                const total = parseFloat(summary.total_budget) || 0;
                const allocated = parseFloat(summary.allocated_idr) || 0;
                const remaining = total - allocated;
                
                if (amountIDR > remaining) {
                    const deficit = amountIDR - remaining;
                    const message = `⚠️ PERINGATAN: Budget yang diinput (Rp ${amountIDR.toLocaleString('id-ID')}) melebihi sisa budget proyek (Rp ${remaining.toLocaleString('id-ID')}).\n\nSelisih: Rp ${deficit.toLocaleString('id-ID')}\n\nLanjutkan tetap simpan?`;
                    return confirm(message);
                }
            }
            
            return true;
        }
    </script>
</body>
</html>
