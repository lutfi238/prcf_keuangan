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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $conn->begin_transaction();

    try {
        $kode_projek = $_POST['kode_projek'];
        $id_proposal = $_POST['id_proposal']; // Changed from nama_projek to id_proposal
        $nama_kegiatan = $_POST['nama_kegiatan'];
        $pelaksana = $_POST['pelaksana'];
        $tanggal_pelaksanaan = $_POST['tanggal_pelaksanaan'];
        $tanggal_laporan = $_POST['tanggal_laporan'];
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
        $stmt = $conn->prepare("INSERT INTO laporan_keuangan_header (kode_projek, nama_projek, nama_kegiatan, pelaksana, tanggal_pelaksanaan, tanggal_laporan, mata_uang, exrate, created_by, status_lap) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')");
        if (!$stmt) {
            throw new Exception('Gagal menyiapkan header laporan: ' . $conn->error);
        }
        $stmt->bind_param("sssssssdi", $kode_projek, $nama_projek, $nama_kegiatan, $pelaksana, $tanggal_pelaksanaan, $tanggal_laporan, $mata_uang, $exrate, $user_id);

        if (!$stmt->execute()) {
            throw new Exception('Gagal menyimpan header laporan.');
        }

        $id_laporan_keu = $conn->insert_id;

        // Insert details
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $stmt_detail = $conn->prepare("INSERT INTO laporan_keuangan_detail (id_laporan_keu, invoice_no, invoice_date, item_desc, recipient, place_code, exp_code, unit_total, unit_cost, requested, actual, balance, explanation, file_nota) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
                $invoice_no = trim($item['invoice_no'] ?? '');
                $invoice_date = trim($item['invoice_date'] ?? '');
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

                    $safe_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
                    $filename = time() . '_' . $index . '_' . ($safe_name ?: 'nota');
                    if ($extension) {
                        $filename .= '.' . $extension;
                    }
                    $destination = $upload_dir . $filename;

                    if (!move_uploaded_file($file_tmp, $destination)) {
                        throw new Exception('Gagal menyimpan file nota: ' . $original_name);
                    }

                    $file_path = $destination;
                }

                $stmt_detail->bind_param(
                    "issssssiddddss",
                    $id_laporan_keu,
                    $invoice_no,
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
                'Laporan keuangan untuk kegiatan "' . $nama_kegiatan . '" telah dikirimkan oleh ' . $user_name . '. Mohon segera divalidasi.'
            );
        }

        // Redirect to dashboard with success message (auto-update data)
        header('Location: ../dashboards/dashboard_pm.php?success=report_created');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Gagal mengirimkan laporan keuangan: ' . $e->getMessage();
    }
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
                                <i class="fas fa-info-circle mr-1"></i>Pilih proposal yang terkait dengan kode proyek
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nama Kegiatan *</label>
                        <input type="text" name="nama_kegiatan" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Pelaksana *</label>
                            <input type="text" name="pelaksana" required value="<?php echo $user_name; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Pelaksanaan *</label>
                            <input type="date" name="tanggal_pelaksanaan" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Laporan *</label>
                            <input type="date" name="tanggal_laporan" required value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                            proposalSelect.innerHTML = '<option value="">Tidak ada proposal untuk proyek ini</option>';
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

        function addItem() {
            itemCount++;
            const container = document.getElementById('itemsContainer');
            const itemDiv = document.createElement('div');
            itemDiv.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-4';
            itemDiv.innerHTML = `
                <div class="flex justify-between items-center">
                    <h4 class="font-medium text-gray-800">Item #${itemCount}</h4>
                    <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">No. Invoice</label>
                        <input type="text" name="items[${itemCount}][invoice_no]" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Tanggal Invoice</label>
                        <input type="date" name="items[${itemCount}][invoice_date]" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Deskripsi Item *</label>
                        <input type="text" name="items[${itemCount}][item_desc]" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Penerima</label>
                        <input type="text" name="items[${itemCount}][recipient]" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kode Tempat</label>
                        <input type="text" name="items[${itemCount}][place_code]" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kode Pengeluaran</label>
                        <input type="text" name="items[${itemCount}][exp_code]" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Total Unit</label>
                        <input type="number" name="items[${itemCount}][unit_total]" value="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Biaya per Unit</label>
                        <input type="number" name="items[${itemCount}][unit_cost]" step="0.01" value="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Budget Diajukan *</label>
                        <input type="number" name="items[${itemCount}][requested]" step="0.01" value="0" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Realisasi *</label>
                        <input type="number" name="items[${itemCount}][actual]" step="0.01" value="0" required
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
    </script>
</body>
</html>