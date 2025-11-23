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

if ($_SESSION['user_role'] !== 'Project Manager') {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proposal'])) {
    $judul = $_POST['judul_proposal'];
    $pj = $_POST['pj'];
    $date = $_POST['date'];
    $pemohon = $_POST['pemohon'];
    $kode_proyek = $_POST['kode_proyek'];
    $currency = $_POST['currency'];
    $exrate = $_POST['exrate'];
    $total_budget_usd = $_POST['total_budget_usd'];
    $total_budget_idr = $_POST['total_budget_idr'];
    
    // Handle TOR file upload
    $tor = '';
    if (isset($_FILES['file_tor']) && $_FILES['file_tor']['error'] === 0) {
        $upload_dir = '../../uploads/tor/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $tor = $upload_dir . time() . '_' . $_FILES['file_tor']['name'];
        move_uploaded_file($_FILES['file_tor']['tmp_name'], $tor);
    }
    
    // Handle file upload
    $file_budget = '';
    if (isset($_FILES['file_budget']) && $_FILES['file_budget']['error'] === 0) {
        $upload_dir = '../../uploads/budgets/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $stmt->close();
    }
}

// Get list of projects
$projects = $conn->query("SELECT kode_proyek, nama_proyek FROM proyek WHERE status_proyek != 'cancelled'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Proposal - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_pm.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Buat Proposal</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="../dashboards/dashboard_pm.php" class="block mt-2 text-green-800 underline">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg p-8 border border-gray-200">
            <!-- Proposal Header -->
            <div class="text-center mb-8 pb-6 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">PROPOSAL KEGIATAN</h1>
                <p class="text-gray-600">PRCFI - Pusat Riset dan Pengembangan</p>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Informasi Dasar -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">I. INFORMASI DASAR</h3>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Judul Proposal *</label>
                        <input type="text" name="judul_proposal" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Penanggung Jawab *</label>
                            <input type="text" name="pj" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Pengajuan *</label>
                            <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Pemohon *</label>
                            <input type="text" name="pemohon" required value="<?php echo $user_name; ?>" readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Kode Proyek *</label>
                            <select name="kode_proyek" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">Pilih Proyek</option>
                                <?php while ($project = $projects->fetch_assoc()): ?>
                                    <option value="<?php echo $project['kode_proyek']; ?>">
                                        <?php echo $project['kode_proyek'] . ' - ' . $project['nama_proyek']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Budget Proposal Section -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">II. BUDGET PROPOSAL</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Mata Uang Proposal</label>
                            <select name="currency" id="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="USD">USD - US Dollar</option>
                                <option value="IDR">IDR - Rupiah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Exchange Rate (Estimasi)</label>
                            <div class="flex space-x-2">
                                <input type="number" step="0.01" name="exrate" id="exrate" value="15500" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <button type="button" onclick="fetchLatestExrate().then(rate => document.getElementById('exrate').value = rate)" 
                                    class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200" id="budgetTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desa</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exp Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Place Code</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (USD)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount (IDR)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="budgetTableBody">
                                <!-- Rows will be added here -->
                            </tbody>
                            <tfoot class="bg-gray-50 font-bold">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right">TOTAL</td>
                                    <td class="px-4 py-3 text-right" id="totalUSD">$0.00</td>
                                    <td class="px-4 py-3 text-right" id="totalIDR">Rp 0</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <button type="button" onclick="addBudgetRow()" class="mt-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition duration-200 font-medium text-sm">
                        <i class="fas fa-plus mr-1"></i> Tambah Baris Budget
                    </button>
                    
                    <!-- Hidden inputs for totals -->
                    <input type="hidden" name="total_budget_usd" id="inputTotalUSD" value="0">
                    <input type="hidden" name="total_budget_idr" id="inputTotalIDR" value="0">
                </div>

                <!-- File Upload -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">III. LAMPIRAN</h3>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">File Terms of Reference (TOR) *</label>
                        <input type="file" name="file_tor" accept=".pdf,.doc,.docx" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <p class="text-xs text-gray-500 mt-1">Format: PDF, Word (Max 10MB)</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">File Budget/RAB (Opsional)</label>
                        <input type="file" name="file_budget" accept=".pdf,.xlsx,.xls,.doc,.docx"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <p class="text-xs text-gray-500 mt-1">Format: PDF, Excel, Word (Max 5MB)</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="../dashboards/dashboard_pm.php" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200 font-medium">
                        Batal
                    </a>
                    <button type="submit" name="submit_proposal"
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                        <i class="fas fa-paper-plane mr-2"></i> Ajukan Proposal
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="../../assets/js/budget_management.js"></script>
    <script>
        let rowCount = 0;
        let villages = [];
        
        // Load villages on start
        fetchVillages().then(data => {
            villages = data;
            addBudgetRow(); // Add first row
        });

        function addBudgetRow() {
            const tbody = document.getElementById('budgetTableBody');
            const row = document.createElement('tr');
            row.id = `row-${rowCount}`;
            
            let villageOptions = '<option value="">Pilih Desa</option>';
            villages.forEach(v => {
                villageOptions += `<option value="${v.id_village}" data-abbr="${v.village_abbr}">${v.village_name}</option>`;
            });

            row.innerHTML = `
                <td class="px-2 py-2">
                    <select name="budget[${rowCount}][id_village]" class="w-full text-sm border-gray-300 rounded" onchange="updateRowPlaceCode(${rowCount})" required>
                        ${villageOptions}
                    </select>
                </td>
                <td class="px-2 py-2">
                    <input type="text" name="budget[${rowCount}][exp_code]" class="w-full text-sm border-gray-300 rounded" oninput="updateRowPlaceCode(${rowCount})" required>
                </td>
                <td class="px-2 py-2">
                    <input type="text" name="budget[${rowCount}][place_code]" id="place_code_${rowCount}" class="w-full text-sm bg-gray-100 border-none rounded" readonly>
                </td>
                <td class="px-2 py-2">
                    <input type="number" step="0.01" name="budget[${rowCount}][amount_usd]" id="usd_${rowCount}" class="w-full text-sm border-gray-300 rounded text-right" oninput="calculateRow(${rowCount}, 'USD')">
                </td>
                <td class="px-2 py-2">
                    <input type="number" step="0.01" name="budget[${rowCount}][amount_idr]" id="idr_${rowCount}" class="w-full text-sm border-gray-300 rounded text-right" oninput="calculateRow(${rowCount}, 'IDR')">
                </td>
                <td class="px-2 py-2">
                    <input type="text" name="budget[${rowCount}][description]" class="w-full text-sm border-gray-300 rounded">
                </td>
                <td class="px-2 py-2 text-center">
                    <button type="button" onclick="removeRow(${rowCount})" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
            rowCount++;
        }

        function removeRow(id) {
            const row = document.getElementById(`row-${id}`);
            if (row) row.remove();
            calculateTotals();
        }

        function updateRowPlaceCode(id) {
            const row = document.getElementById(`row-${id}`);
            const villageSelect = row.querySelector(`select[name="budget[${id}][id_village]"]`);
            const expInput = row.querySelector(`input[name="budget[${id}][exp_code]"]`);
            const placeInput = document.getElementById(`place_code_${id}`);
            
            const abbr = villageSelect.options[villageSelect.selectedIndex].getAttribute('data-abbr');
            const exp = expInput.value;
            
            if (abbr && exp) {
                placeInput.value = generatePlaceCode(exp, abbr);
            } else {
                placeInput.value = '';
            }
        }

        function calculateRow(id, changed) {
            const exrate = parseFloat(document.getElementById('exrate').value) || 1;
            const usdInput = document.getElementById(`usd_${id}`);
            const idrInput = document.getElementById(`idr_${id}`);
            
            if (changed === 'USD') {
                const usd = parseFloat(usdInput.value) || 0;
                idrInput.value = (usd * exrate).toFixed(2);
            } else {
                const idr = parseFloat(idrInput.value) || 0;
                usdInput.value = (idr / exrate).toFixed(2);
            }
            calculateTotals();
        }

        function calculateTotals() {
            let totalUSD = 0;
            let totalIDR = 0;
            
            document.querySelectorAll('input[id^="usd_"]').forEach(input => {
                totalUSD += parseFloat(input.value) || 0;
            });
            
            document.querySelectorAll('input[id^="idr_"]').forEach(input => {
                totalIDR += parseFloat(input.value) || 0;
            });
            
            document.getElementById('totalUSD').textContent = formatCurrency(totalUSD, 'USD');
            document.getElementById('totalIDR').textContent = formatCurrency(totalIDR, 'IDR');
            
            document.getElementById('inputTotalUSD').value = totalUSD;
            document.getElementById('inputTotalIDR').value = totalIDR;
        }
        
        // Recalculate all if exrate changes
        document.getElementById('exrate').addEventListener('input', function() {
            document.querySelectorAll('input[id^="usd_"]').forEach(input => {
                const id = input.id.split('_')[1];
                calculateRow(id, 'USD');
            });
        });
    </script>
</body>
</html>