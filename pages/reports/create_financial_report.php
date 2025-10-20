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
    $nama_projek = $proposal_data['judul_proposal'];
    
    // Insert header
    $stmt = $conn->prepare("INSERT INTO laporan_keuangan_header (kode_projek, nama_projek, nama_kegiatan, pelaksana, tanggal_pelaksanaan, tanggal_laporan, mata_uang, exrate, created_by, status_lap, id_proposal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?)");
    $stmt->bind_param("sssssssdii", $kode_projek, $nama_projek, $nama_kegiatan, $pelaksana, $tanggal_pelaksanaan, $tanggal_laporan, $mata_uang, $exrate, $user_id, $id_proposal);
    
    if ($stmt->execute()) {
        $id_laporan_keu = $conn->insert_id;
        
        // Insert details
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $stmt_detail = $conn->prepare("INSERT INTO laporan_keuangan_detail (id_laporan_keu, invoice_no, invoice_date, item_desc, recipient, place_code, exp_code, unit_total, unit_cost, requested, actual, balance, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['items'] as $item) {
                $balance = $item['requested'] - $item['actual'];
                $stmt_detail->bind_param("issssssidddds", 
                    $id_laporan_keu,
                    $item['invoice_no'],
                    $item['invoice_date'],
                    $item['item_desc'],
                    $item['recipient'],
                    $item['place_code'],
                    $item['exp_code'],
                    $item['unit_total'],
                    $item['unit_cost'],
                    $item['requested'],
                    $item['actual'],
                    $balance,
                    $item['explanation']
                );
                $stmt_detail->execute();
            }
        }
        
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
    } else {
        $error = 'Gagal mengirimkan laporan keuangan';
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

            <form method="POST" id="reportForm" class="space-y-6">
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
                        <h3 class="text-lg font-bold text-gray-800">RINCIAN PENGELUARAN</h3>
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
            itemDiv.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50';
            itemDiv.innerHTML = `
                <div class="flex justify-between items-center mb-4">
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
            `;
            container.appendChild(itemDiv);
        }

        function removeItem(button) {
            button.closest('.border').remove();
        }

        // Add first item on page load
        addItem();
    </script>
</body>
</html>