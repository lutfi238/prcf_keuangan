<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';
require_once '../../includes/date_helper.php';
require_once '../../includes/csrf_helper.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    // CSRF validation
    if (!csrf_validate()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $conn->begin_transaction();

        try {
            $kode_projek = $_POST['kode_projek'];
            $id_proposal = $_POST['id_proposal']; // Changed from nama_projek to id_proposal
            $pelaksana = $_POST['pelaksana'];
            $tanggal_pelaksanaan = parseDateID($_POST['tanggal_pelaksanaan']);
            $tanggal_laporan = parseDateID($_POST['tanggal_laporan']);
            $mata_uang = $_POST['mata_uang'];
            $exrate = $_POST['exrate'];

        // Get proposal title for nama_projek
        $proposal_stmt = $conn->prepare("SELECT judul_proposal FROM proposal WHERE id_proposal = ?");
        $proposal_stmt->bind_param("i", $id_proposal);
        $proposal_stmt->execute();
        $proposal_data = $proposal_stmt->get_result()->fetch_assoc();
        $nama_projek = $proposal_data['judul_proposal'] ?? '';

        if (empty($nama_projek)) {
            throw new Exception('Proposal tidak ditemukan.');
        }

        // Insert header (no id_proposal column in header table)
        $stmt = $conn->prepare("INSERT INTO laporan_keuangan_header (kode_projek, nama_projek, pelaksana, tanggal_pelaksanaan, tanggal_laporan, mata_uang, exrate, created_by, status_lap) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'submitted')");
        if (!$stmt) {
            throw new Exception('Gagal menyiapkan header laporan: ' . $conn->error);
        }
        $stmt->bind_param("ssssssdi", $kode_projek, $nama_projek, $pelaksana, $tanggal_pelaksanaan, $tanggal_laporan, $mata_uang, $exrate, $user_id);

        if (!$stmt->execute()) {
            throw new Exception('Gagal menyimpan header laporan.');
        }

        $id_laporan_keu = $conn->insert_id;

        // Insert details
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $stmt_detail = $conn->prepare("INSERT INTO laporan_keuangan_detail (id_laporan_keu, invoice_date, item_desc, recipient, place_code, exp_code, unit_total, unit_cost, requested, actual, balance, explanation, file_nota) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt_detail) {
                throw new Exception('Gagal menyiapkan statement detail laporan.');
            }

            $upload_dir = '../../uploads/receipts/';
            if (!file_exists($upload_dir) && !mkdir($upload_dir, 0777, true)) {
                throw new Exception('Gagal membuat folder penyimpanan nota.');
            }

            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif', 'webp'];
            $max_size = 5 * 1024 * 1024; // 5MB

            foreach ($_POST['items'] as $index => $item) {
                $invoice_date = parseDateID(trim($item['invoice_date'] ?? ''));
                $item_desc = trim($item['item_desc'] ?? '');
                $recipient = trim($item['recipient'] ?? '');
                $place_code = trim($item['place_code'] ?? '');
                $exp_code = trim($item['exp_code'] ?? '');
                $unit_total = (int) ($item['unit_total'] ?? 0);
                $unit_cost = (float) ($item['unit_cost'] ?? 0);
                $requested = (float) ($item['requested'] ?? 0);
                $actual = (float) ($item['actual'] ?? 0);
                $balance = $requested - $actual;
                $explanation = trim($item['explanation'] ?? '');
                $file_path = null;

                if (!empty($_FILES['items']['name'][$index]['file_nota'])) {
                    $file_error = $_FILES['items']['error'][$index]['file_nota'];
                    $file_size = $_FILES['items']['size'][$index]['file_nota'];
                    $file_tmp = $_FILES['items']['tmp_name'][$index]['file_nota'];
                    $original_name = $_FILES['items']['name'][$index]['file_nota'];
                    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                    if ($file_error !== UPLOAD_ERR_OK) {
                        throw new Exception('Gagal mengunggah file nota: ' . $original_name);
                    }

                    if ($file_size > $max_size) {
                        throw new Exception('Ukuran file nota melebihi 5MB: ' . $original_name);
                    }

                    if (!in_array($extension, $allowed_extensions, true)) {
                        throw new Exception('Format file nota tidak didukung: ' . $original_name);
                    }

                    // SECURITY FIX: Use random filename to prevent path traversal
                    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
                    
                    // SECURITY FIX: Validate destination path
                    $real_upload_dir = realpath($upload_dir);
                    if ($real_upload_dir === false) {
                        throw new Exception('Upload directory tidak valid');
                    }
                    $destination = $real_upload_dir . DIRECTORY_SEPARATOR . $filename;
                    
                    // Double-check path is still within upload directory
                    if (strpos($destination, $real_upload_dir) !== 0) {
                        throw new Exception('Path file tidak valid');
                    }

                    if (!move_uploaded_file($file_tmp, $destination)) {
                        throw new Exception('Gagal menyimpan file nota: ' . $original_name);
                    }

                    $file_path = $destination;
                }

                $stmt_detail->bind_param(
                    "isssssiddddss",
                    $id_laporan_keu,
                    $invoice_date,
                    $item_desc,
                    $recipient,
                    $place_code,
                    $exp_code,
                    $unit_total,
                    $unit_cost,
                    $requested,
                    $actual,
                    $balance,
                    $explanation,
                    $file_path
                );

                if (!$stmt_detail->execute()) {
                    throw new Exception('Gagal menyimpan detail laporan.');
                }
            }
        }

        $conn->commit();

        // Send notification to Staff Accounting
        $sa_stmt = $conn->prepare("SELECT email, nama FROM user WHERE role = 'Staff Accountant'");
        $sa_stmt->execute();
        $sa_result = $sa_stmt->get_result();

        while ($sa = $sa_result->fetch_assoc()) {
            send_notification_email(
                $sa['email'],
                'Laporan Keuangan Baru dari ' . $user_name,
                'Laporan keuangan untuk kegiatan "' . $nama_projek . '" telah dikirimkan oleh ' . $user_name . '. Mohon segera divalidasi.'
            );
        }

        // Redirect to dashboard with success message (auto-update data)
        header('Location: ../dashboards/dashboard_pm.php?success=report_created');
        exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Gagal mengirimkan laporan keuangan: ' . $e->getMessage();
        }
    } // Close else block for CSRF validation
}

$projects = $conn->query("SELECT kode_proyek, nama_proyek FROM proyek WHERE status_proyek != 'cancelled'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan Keuangan - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../../assets/js/currency_format.js"></script>
    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_pm.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Buat Laporan Keuangan</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
            <div class="text-center mb-8 pb-6 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">LAPORAN KEUANGAN KEGIATAN</h1>
                <p class="text-gray-600">PRCFI - Pusat Riset dan Pengembangan</p>
            </div>

            <form method="POST" id="reportForm" enctype="multipart/form-data" class="space-y-6">
                <?php echo csrf_field(); ?>
                <!-- Informasi Umum -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">INFORMASI UMUM</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Kode Proyek *</label>
                            <select name="kode_projek" id="kode_projek" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">Pilih Proyek</option>
                                <?php while ($project = $projects->fetch_assoc()): ?>
                                    <option value="<?php echo $project['kode_proyek']; ?>" data-nama="<?php echo $project['nama_proyek']; ?>">
                                        <?php echo $project['kode_proyek'] . ' - ' . $project['nama_proyek']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Nama Proposal *</label>
                            <select name="id_proposal" id="id_proposal" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="">Pilih Proposal</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>Hanya proposal yang belum digunakan untuk laporan keuangan yang akan muncul
                            </p>
                        </div>
                    </div>

                    <!-- Proposal Budget Summary -->
                    <div id="proposalBudgetSummary" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-2">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-bold text-blue-800"><i class="fas fa-file-invoice-dollar mr-2"></i>Budget Approval Diterima</h4>
                            <span id="proposalTotalAmount" class="text-sm font-bold text-blue-700"></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-blue-100 text-blue-800">
                                    <tr>
                                        <th class="px-2 py-1">Deskripsi</th>
                                        <th class="px-2 py-1">Place Code</th>
                                        <th class="px-2 py-1 text-right">USD</th>
                                        <th class="px-2 py-1 text-right">IDR</th>
                                    </tr>
                                </thead>
                                <tbody id="proposalBudgetBody" class="divide-y divide-blue-200">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <p class="text-[10px] text-blue-600 mt-2 italic">* Rincian pengeluaran akan otomatis diisi berdasarkan budget di atas</p>
                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Pelaksana *</label>
                            <input type="text" name="pelaksana" required value="<?php echo $user_name; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Pelaksanaan *</label>
                            <input type="text" name="tanggal_pelaksanaan" id="tanggal_pelaksanaan" required placeholder="DD/MM/YYYY"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Laporan *</label>
                            <input type="text" name="tanggal_laporan" id="tanggal_laporan" required value="<?php echo date('d/m/Y'); ?>" readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Mata Uang *</label>
                            <select name="mata_uang" id="mata_uang" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="IDR">IDR (Rupiah)</option>
                                <option value="USD">USD (Dollar)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Exchange Rate *</label>
                            <input type="number" name="exrate" step="0.0001" value="1.0000" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                </div>

                <!-- Detail Pengeluaran -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center border-b pb-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">RINCIAN PENGELUARAN</h3>
                            <p class="text-xs text-gray-500 mt-1">Unggah nota/kwitansi dalam format PDF atau gambar (maksimal 5MB) untuk setiap item jika tersedia.</p>
                        </div>
                        <button type="button" onclick="addItem()" 
                            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200 text-sm">
                            <i class="fas fa-plus mr-1"></i> Tambah Item
                        </button>
                    </div>

                    <div id="itemsContainer" class="space-y-4">
                        <!-- Items will be added here dynamically -->
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="../dashboards/dashboard_pm.php" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200 font-medium">
                        Batal
                    </a>
                    <button type="submit" name="submit_report"
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200 font-medium">
                        <i class="fas fa-paper-plane mr-2"></i> Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Modal Preview Image -->
    <div id="receiptPreviewModal" class="fixed z-50 inset-0 bg-black/60 hidden justify-center items-center">
        <div class="flex flex-col items-center">
            <button onclick="closePreviewModal()" class="mb-2 block self-end mr-2 text-white p-2 rounded-full bg-black/60 hover:bg-black/80 focus:outline-none">
                <i class="fas fa-times text-lg"></i>
            </button>
            <img id="modalReceiptPreview" src="" alt="Preview Nota" class="max-h-[90vh] max-w-[95vw] rounded shadow-lg bg-white" />
        </div>
    </div>

    <script>
        let itemCount = 0;

        // Fetch proposals when project code is selected
        document.getElementById('kode_projek').addEventListener('change', function() {
            const kode_projek = this.value;
            const proposalSelect = document.getElementById('id_proposal');
            
            // Reset proposal dropdown
            proposalSelect.innerHTML = '<option value="">Loading...</option>';
            proposalSelect.disabled = true;
            
            if (kode_projek) {
                // Fetch proposals via AJAX
                fetch(`../../api/get_proposals.php?kode_proyek=${encodeURIComponent(kode_projek)}`)
                    .then(response => response.json())
                    .then(data => {
                        proposalSelect.innerHTML = '<option value="">Pilih Proposal</option>';
                        
                        if (data.success && data.proposals.length > 0) {
                            data.proposals.forEach(proposal => {
                                const option = document.createElement('option');
                                option.value = proposal.id_proposal;
                                option.textContent = proposal.judul_proposal;
                                proposalSelect.appendChild(option);
                            });
                            proposalSelect.disabled = false;
                        } else {
                            // Check if there are any proposals at all for this project
                            fetch(`../../api/get_proposals.php?kode_proyek=${encodeURIComponent(kode_projek)}&include_used=1`)
                                .then(response => response.json())
                                .then(allData => {
                                    if (allData.success && allData.proposals.length > 0) {
                                        proposalSelect.innerHTML = '<option value="">Semua proposal telah digunakan untuk laporan keuangan</option>';
                                    } else {
                                        proposalSelect.innerHTML = '<option value="">Tidak ada proposal untuk proyek ini</option>';
                                    }
                                })
                                .catch(error => {
                                    proposalSelect.innerHTML = '<option value="">Tidak ada proposal untuk proyek ini</option>';
                                });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching proposals:', error);
                        proposalSelect.innerHTML = '<option value="">Error loading proposals</option>';
                    });
            } else {
                proposalSelect.innerHTML = '<option value="">Pilih Kode Proyek Terlebih Dahulu</option>';
                proposalSelect.disabled = true;
            }
        });

        // Fetch proposal details and budget items when proposal is selected
        document.getElementById('id_proposal').addEventListener('change', function() {
            const id_proposal = this.value;
            const summaryDiv = document.getElementById('proposalBudgetSummary');
            const summaryBody = document.getElementById('proposalBudgetBody');
            const itemsContainer = document.getElementById('itemsContainer');
            
            if (id_proposal) {
                summaryDiv.classList.remove('hidden');
                summaryBody.innerHTML = '<tr><td colspan="4" class="text-center py-2 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat budget...</td></tr>';
                
                // Show loading indicator in items container
                itemsContainer.innerHTML = '<div class="text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Memasukkan data budget ke rincian...</div>';
                
                fetch(`../../api/get_proposal_details.php?id_proposal=${id_proposal}`)
                    .then(response => response.json())
                    .then(data => {
                        summaryBody.innerHTML = '';
                        itemsContainer.innerHTML = '';
                        itemCount = 0;
                        
                        if (data.success) {
                            if (data.items.length > 0) {
                                data.items.forEach(item => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td class="px-2 py-1 text-gray-700">${item.description || '-'}</td>
                                        <td class="px-2 py-1 font-mono text-gray-600">${item.place_code}</td>
                                        <td class="px-2 py-1 text-right text-indigo-600">$${parseFloat(item.requested_usd).toLocaleString()}</td>
                                        <td class="px-2 py-1 text-right text-green-600">Rp ${parseFloat(item.requested_idr).toLocaleString()}</td>
                                    `;
                                    summaryBody.appendChild(row);
                                    addItem(item);
                                });
                            } else {
                                summaryBody.innerHTML = '<tr><td colspan="4" class="text-center py-2 text-gray-500">Tidak ada rincian budget.</td></tr>';
                                addItem();
                            }
                        } else {
                            summaryBody.innerHTML = `<tr><td colspan="4" class="text-center py-2 text-red-500">${data.message}</td></tr>`;
                            addItem();
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching proposal details:', error);
                        summaryBody.innerHTML = '<tr><td colspan="4" class="text-center py-2 text-red-500">Gagal memuat budget.</td></tr>';
                        itemsContainer.innerHTML = '';
                        addItem();
                    });
            } else {
                summaryDiv.classList.add('hidden');
                itemsContainer.innerHTML = '';
                itemCount = 0;
                addItem();
            }
        });

        // Handle currency change to re-trigger population based on new currency
        document.getElementById('mata_uang').addEventListener('change', function() {
            const id_proposal = document.getElementById('id_proposal').value;
            if (id_proposal) {
                document.getElementById('id_proposal').dispatchEvent(new Event('change'));
            }
        });

        function addItem(item = null) {
            itemCount++;
            const container = document.getElementById('itemsContainer');
            const itemDiv = document.createElement('div');
            itemDiv.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-4';

            // Extract values if item is provided
            const desc = item ? (item.description || '') : '';
            const place = item ? (item.place_code || '') : '';
            const exp = item ? (item.exp_code || '') : '';
            
            // For budget, determine value based on current report currency
            const currentCurrency = document.getElementById('mata_uang').value;
            let amount = 0;
            if (item) {
                if (currentCurrency === 'USD') {
                    amount = parseFloat(item.requested_usd || 0);
                } else {
                    amount = parseFloat(item.requested_idr || 0);
                }
            }

            itemDiv.innerHTML = `
                <div class="flex justify-between items-center">
                    <h4 class="font-medium text-gray-800">Item #${itemCount}</h4>
                    <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Invoice</label>
                        <input type="text" name="items[${itemCount}][invoice_date]" id="invoice_date_${itemCount}"
                            class="invoice-date-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="DD/MM/YYYY">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Deskripsi Item *</label>
                        <input type="text" name="items[${itemCount}][item_desc]" required value="${desc}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Penerima</label>
                        <input type="text" name="items[${itemCount}][recipient]"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="relative">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kode Tempat</label>
                        <input type="text" name="items[${itemCount}][place_code]" value="${place}"
                            class="place-code-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            data-item-index="${itemCount}"
                            autocomplete="off"
                            placeholder="Ketik untuk mencari...">
                        <div class="autocomplete-suggestions hidden absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto"></div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kode Pengeluaran</label>
                        <input type="text" name="items[${itemCount}][exp_code]" value="${exp}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Total Unit</label>
                        <input type="number" name="items[${itemCount}][unit_total]" value="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Biaya per Unit</label>
                        <input type="text" name="items[${itemCount}][unit_cost]" value="${amount}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Budget Diajukan *</label>
                        <input type="text" name="items[${itemCount}][requested]" value="${amount}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Realisasi *</label>
                        <input type="text" name="items[${itemCount}][actual]" value="${amount}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Keterangan</label>
                        <textarea name="items[${itemCount}][explanation]" rows="2"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2">Upload Nota/Kwitansi</label>
                    <div class="flex items-center space-x-4">
                        <input type="file" name="items[${itemCount}][file_nota]" accept=".pdf,.jpg,.jpeg,.png,.gif,.bmp,.tiff,.tif,.webp"
                            class="w-full text-sm text-gray-600" onchange="handleFileChange(this)" />
                        <button type="button" title="Preview Nota" onclick="previewNota(this)" class="eye-preview-button hidden ml-2 rounded-full p-2 bg-gray-200 hover:bg-blue-500 hover:text-white text-gray-500 transition">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" onclick="removeReceipt(this)" class="receipt-remove hidden ml-2 px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600">
                            <i class="fas fa-trash-alt mr-1"></i>
                        </button>
                    </div>
                    <div class="receipt-placeholder text-xs text-gray-400 mt-1">Belum ada file</div>
                </div>
            `;
            container.appendChild(itemDiv);

            // Apply currency formatting to the newly added item
            applyCurrencyFormattingToItem(itemDiv);
        }

        function applyCurrencyFormattingToItem(itemDiv) {
            // Logic for calculating Realisasi (actual) = unit_total * unit_cost
            const unitTotalInput = itemDiv.querySelector('input[name*="unit_total"]');
            const unitCostInput = itemDiv.querySelector('input[name*="unit_cost"]');
            const actualInput = itemDiv.querySelector('input[name*="actual"]');

            const calculateActual = () => {
                const total = parseFloat(unitTotalInput.value) || 0;
                const cost = parseCurrency(unitCostInput.value) || 0;
                const result = total * cost;
                actualInput.value = formatCurrency(result);
            };

            if (unitTotalInput) {
                unitTotalInput.addEventListener('input', calculateActual);
            }

            // Find all currency input fields in the newly added item
            const currencyInputs = itemDiv.querySelectorAll('input[name*="unit_cost"], input[name*="requested"], input[name*="actual"]');

            currencyInputs.forEach(input => {
                // Format on input
                input.addEventListener('input', function(e) {
                    const cursorPosition = e.target.selectionStart;
                    const oldValue = e.target.value;

                    // Get numeric value
                    const numericValue = parseCurrency(e.target.value);

                    // Format the value
                    const formatted = formatCurrency(numericValue);

                    // Update input value
                    e.target.value = formatted;

                    // If this is unit_cost, recalculate actual
                    if (input === unitCostInput) {
                        calculateActual();
                    }

                    // Adjust cursor position more carefully
                    // Calculate how many separator characters were added before cursor
                    const oldPart = oldValue.substring(0, cursorPosition);
                    const newPart = formatted.substring(0, cursorPosition);

                    // Count separators in both parts
                    const oldSeparators = (oldPart.match(/\./g) || []).length;
                    const newSeparators = (newPart.match(/\./g) || []).length;

                    // Adjust cursor position based on separator difference
                    let newCursorPosition = cursorPosition + (newSeparators - oldSeparators);

                    // Ensure cursor doesn't go beyond string length
                    newCursorPosition = Math.min(newCursorPosition, formatted.length);

                    // Set cursor position
                    e.target.setSelectionRange(newCursorPosition, newCursorPosition);
                });

                // Format on blur
                input.addEventListener('blur', function(e) {
                    const numericValue = parseCurrency(e.target.value);
                    e.target.value = formatCurrency(numericValue);
                });

                // Initial format if value exists
                if (input.value) {
                    input.value = formatCurrency(input.value);
                }
            });
        }

        function removeItem(button) {
            const card = button.closest('.border');
            if (card) {
                card.remove();
            }
        }

        function handleFileChange(input) {
            const card = input.closest('.border');
            if (!card) return;

            const placeholder = card.querySelector('.receipt-placeholder');
            const removeButton = card.querySelector('.receipt-remove');
            const previewButton = card.querySelector('.eye-preview-button');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                // Only show eye for image or supported pdf
                let canPreview = false;
                if (file.type.startsWith('image/')) {
                    canPreview = true;
                } else if (file.type === "application/pdf") {
                    canPreview = true;
                }

                reader.onload = function (e) {
                    if (placeholder) {
                        placeholder.textContent = file.name;
                        placeholder.classList.remove('text-gray-400');
                        placeholder.classList.add('text-gray-600');
                    }
                    if (removeButton) {
                        removeButton.classList.remove('hidden');
                    }
                    if (previewButton) {
                        if (canPreview) {
                            previewButton.classList.remove('hidden');
                            previewButton.dataset.previewType = file.type.startsWith('image/') ? 'image' : (file.type === 'application/pdf' ? 'pdf' : '');
                            previewButton.dataset.fileUrl = e.target.result;
                        } else {
                            previewButton.classList.add('hidden');
                            previewButton.dataset.fileUrl = '';
                            previewButton.dataset.previewType = '';
                        }
                    }
                };

                // Actually load for preview
                if (file.type.startsWith('image/') || file.type === 'application/pdf') {
                    if (file.type.startsWith('image/')) {
                        reader.readAsDataURL(file);
                    } else if (file.type === 'application/pdf') {
                        // For PDF, DataURL preview in <embed>
                        reader.readAsDataURL(file);
                    }
                } else {
                    if (placeholder) {
                        placeholder.textContent = file.name + " (tidak bisa preview)";
                        placeholder.classList.remove('text-gray-400');
                        placeholder.classList.add('text-gray-600');
                    }
                    if (removeButton) {
                        removeButton.classList.remove('hidden');
                    }
                    if (previewButton) {
                        previewButton.classList.add('hidden');
                        previewButton.dataset.fileUrl = '';
                        previewButton.dataset.previewType = '';
                    }
                }

            } else {
                if (placeholder) {
                    placeholder.textContent = 'Belum ada file';
                    placeholder.classList.remove('text-gray-600');
                    placeholder.classList.add('text-gray-400');
                }
                if (removeButton) {
                    removeButton.classList.add('hidden');
                }
                if (previewButton) {
                    previewButton.classList.add('hidden');
                    previewButton.dataset.fileUrl = '';
                    previewButton.dataset.previewType = '';
                }
            }
        }

        function removeReceipt(button) {
            const card = button.closest('.border');
            if (!card) return;

            const fileInput = card.querySelector('input[type="file"]');
            const placeholder = card.querySelector('.receipt-placeholder');
            const previewButton = card.querySelector('.eye-preview-button');

            if (fileInput) {
                fileInput.value = '';
            }
            if (placeholder) {
                placeholder.textContent = 'Belum ada file';
                placeholder.classList.remove('text-gray-600');
                placeholder.classList.add('text-gray-400');
            }
            if (previewButton) {
                previewButton.classList.add('hidden');
                previewButton.dataset.fileUrl = '';
                previewButton.dataset.previewType = '';
            }
            button.classList.add('hidden');
        }

        function previewNota(btn) {
            // This button is eye icon beside file
            const fileUrl = btn.dataset.fileUrl;
            const type = btn.dataset.previewType;

            if (!fileUrl) return;

            const modal = document.getElementById('receiptPreviewModal');
            const modalImg = document.getElementById('modalReceiptPreview');
            
            // For PDF, show pdf in embed or object
            if (type === 'image') {
                modalImg.src = fileUrl;
                modalImg.classList.remove('hidden');
                modalImg.style.display = "block";
                modalImg.style.maxHeight = "90vh";
            } else if (type === 'pdf') {
                // Render pdf as <embed>
                modalImg.src = '';
                modalImg.style.display = "none";
                // Show embed PDF directly
                if (!document.getElementById('modalPdfEmbed')) {
                    const embed = document.createElement('embed');
                    embed.id = 'modalPdfEmbed';
                    embed.type = 'application/pdf';
                    embed.style.maxHeight = '90vh';
                    embed.style.maxWidth = '95vw';
                    embed.className = "rounded shadow-lg bg-white";
                    document.getElementById('receiptPreviewModal').querySelector('.flex.flex-col').appendChild(embed);
                }
                const pdfEmbed = document.getElementById('modalPdfEmbed');
                pdfEmbed.src = fileUrl;
                pdfEmbed.style.display = "block";
            }
            // Hide all other pdf viewers if needed
            if (type !== 'pdf' && document.getElementById('modalPdfEmbed')) {
                document.getElementById('modalPdfEmbed').style.display = "none";
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePreviewModal() {
            const modal = document.getElementById('receiptPreviewModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            // Clear image and pdf src
            const modalImg = document.getElementById('modalReceiptPreview');
            if (modalImg) modalImg.src = '';
            if (document.getElementById('modalPdfEmbed')) {
                document.getElementById('modalPdfEmbed').src = '';
                document.getElementById('modalPdfEmbed').style.display = "none";
            }
        }

        // Add first item on page load
        addItem();

        // Apply currency formatting to the first item as well
        setTimeout(() => {
            const firstItem = document.querySelector('#itemsContainer > .border');
            if (firstItem) {
                applyCurrencyFormattingToItem(firstItem);
            }
        }, 100);
        
        // Prepare currency fields for submission
        prepareCurrencyForSubmit('reportForm');
        
        // Place Code Autocomplete
        let autocompleteTimer = null;
        
        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('place-code-input')) {
                document.querySelectorAll('.autocomplete-suggestions').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });
        
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('place-code-input')) {
                const input = e.target;
                const searchTerm = input.value.trim();
                const suggestionBox = input.nextElementSibling;
                const kodeProyek = document.getElementById('kode_projek').value;
                
                if (!kodeProyek) {
                    suggestionBox.classList.add('hidden');
                    return;
                }
                
                // Clear previous timer
                if (autocompleteTimer) {
                    clearTimeout(autocompleteTimer);
                }
                
                // Wait 300ms before searching
                autocompleteTimer = setTimeout(() => {
                    if (searchTerm.length > 0) {
                        fetchPlaceCodes(kodeProyek, searchTerm, input, suggestionBox);
                    } else {
                        suggestionBox.classList.add('hidden');
                    }
                }, 300);
            }
        });
        
        function fetchPlaceCodes(kodeProyek, searchTerm, inputField, suggestionBox) {
            fetch(`../../api/get_place_codes.php?kode_proyek=${encodeURIComponent(kodeProyek)}&search_term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.codes.length > 0) {
                        suggestionBox.innerHTML = '';
                        data.codes.forEach(code => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                            div.innerHTML = `
                                <div class="font-medium text-gray-800">${code.place_code}</div>
                                <div class="text-xs text-gray-600">${code.description || 'No description'}</div>
                            `;
                            div.addEventListener('click', function() {
                                inputField.value = code.place_code;
                                // Auto-fill exp_code if there's a corresponding field
                                const expCodeField = inputField.closest('.border').querySelector('[name*="exp_code"]');
                                if (expCodeField && code.exp_code) {
                                    expCodeField.value = code.exp_code;
                                }
                                suggestionBox.classList.add('hidden');
                            });
                            suggestionBox.appendChild(div);
                        });
                        suggestionBox.classList.remove('hidden');
                    } else {
                        suggestionBox.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">Tidak ada kode yang cocok</div>';
                        suggestionBox.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching place codes:', error);
                    suggestionBox.classList.add('hidden');
                });
        }
        
        // Initialize Flatpickr for static date inputs
        const flatpickrConfig = {
            dateFormat: "d/m/Y",
            locale: "id",
            allowInput: true
        };
        
        flatpickr("#tanggal_pelaksanaan", flatpickrConfig);
        flatpickr("#tanggal_laporan", { ...flatpickrConfig, clickOpens: false });
        
        // Initialize Flatpickr for dynamically added invoice dates
        const originalAddItem = window.addItem;
        const addItemWrapper = function(item = null) {
            originalAddItem(item);
            // Initialize Flatpickr on the newly added invoice date input
            const newInput = document.querySelector(`#invoice_date_${itemCount}`);
            if (newInput) {
                flatpickr(newInput, flatpickrConfig);
            }
        };
        window.addItem = addItemWrapper;
    </script>
</body>
</html>