<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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
$user_id = $_SESSION['user_id'];

// Auto-create project_village_expcodes table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS `project_village_expcodes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `kode_proyek` varchar(50) NOT NULL,
      `id_village` int(11) NOT NULL,
      `exp_code` varchar(20) NOT NULL,
      `place_code` varchar(10) NOT NULL,
      `description` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_project_village_exp` (`kode_proyek`, `id_village`, `exp_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Get villages for dropdown
$villages_result = $conn->query("SELECT id_village, village_code, village_name, village_abbr FROM villages WHERE is_deleted = 0 ORDER BY village_name ASC");
$villages_list = [];
while ($v = $villages_result->fetch_assoc()) {
    $villages_list[] = $v;
}


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_project'])) {
        $kode_proyek = strtoupper(trim($_POST['kode_proyek']));
        $nama_proyek = $_POST['nama_proyek'];
        $status_proyek = $_POST['status_proyek'];
        $donor = $_POST['donor'];
        $nilai_anggaran = str_replace(['.', ','], '', $_POST['nilai_anggaran']);
        $periode_mulai = $_POST['periode_mulai'];
        $periode_selesai = $_POST['periode_selesai'];
        $rekening_khusus = $_POST['rekening_khusus'];
        
        // Check if kode_proyek already exists
        $check = $conn->prepare("SELECT kode_proyek FROM proyek WHERE kode_proyek = ?");
        $check->bind_param("s", $kode_proyek);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Kode proyek sudah ada! Gunakan kode yang berbeda.';
        } else {
            $stmt = $conn->prepare("INSERT INTO proyek (kode_proyek, nama_proyek, status_proyek, donor, nilai_anggaran, periode_mulai, periode_selesai, rekening_khusus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssdsss", $kode_proyek, $nama_proyek, $status_proyek, $donor, $nilai_anggaran, $periode_mulai, $periode_selesai, $rekening_khusus);
            
            if ($stmt->execute()) {
                header('Location: manage_projects.php?success=created');
                exit();
            } else {
                $error = 'Gagal membuat proyek: ' . $stmt->error;
            }
        }
    } elseif (isset($_POST['update_project'])) {
        $kode_proyek = $_POST['kode_proyek'];
        $nama_proyek = $_POST['nama_proyek'];
        $status_proyek = $_POST['status_proyek'];
        $donor = $_POST['donor'];
        $nilai_anggaran = str_replace(['.', ','], '', $_POST['nilai_anggaran']);
        $periode_mulai = $_POST['periode_mulai'];
        $periode_selesai = $_POST['periode_selesai'];
        $rekening_khusus = $_POST['rekening_khusus'];
        
        $stmt = $conn->prepare("UPDATE proyek SET nama_proyek = ?, status_proyek = ?, donor = ?, nilai_anggaran = ?, periode_mulai = ?, periode_selesai = ?, rekening_khusus = ? WHERE kode_proyek = ?");
        $stmt->bind_param("sssdssss", $nama_proyek, $status_proyek, $donor, $nilai_anggaran, $periode_mulai, $periode_selesai, $rekening_khusus, $kode_proyek);
        
        if ($stmt->execute()) {
            header('Location: manage_projects.php?success=updated');
            exit();
        } else {
            $error = 'Gagal mengupdate proyek: ' . $stmt->error;
        }
    } elseif (isset($_POST['delete_project'])) {
        $kode_proyek = $_POST['kode_proyek'];
        
        // Check if project is used in proposals or reports
        $check_usage = $conn->prepare("SELECT COUNT(*) as proposal_count FROM proposal WHERE kode_proyek = ?");
        $check_usage->bind_param("s", $kode_proyek);
        $check_usage->execute();
        $usage = $check_usage->get_result()->fetch_assoc();
        
        if ($usage['proposal_count'] > 0) {
            // Don't delete, just set to cancelled
            $stmt = $conn->prepare("UPDATE proyek SET status_proyek = 'cancelled' WHERE kode_proyek = ?");
            $stmt->bind_param("s", $kode_proyek);
            $stmt->execute();
            header('Location: manage_projects.php?info=cancelled');
            exit();
        } else {
            // Safe to delete
            $stmt = $conn->prepare("DELETE FROM proyek WHERE kode_proyek = ?");
            $stmt->bind_param("s", $kode_proyek);
            
            if ($stmt->execute()) {
                header('Location: manage_projects.php?success=deleted');
                exit();
            } else {
                $error = 'Gagal menghapus proyek: ' . $stmt->error;
            }
        }
    }
    // Handle Add Exp Code Assignment
    elseif (isset($_POST['add_expcode'])) {
        $kode_proyek = $_POST['exp_kode_proyek'];
        $id_village = intval($_POST['exp_id_village']);
        $exp_code = trim($_POST['exp_code']);
        $description = trim($_POST['exp_description'] ?? '');
        
        // Get village abbr for place_code
        $v_stmt = $conn->prepare("SELECT village_abbr FROM villages WHERE id_village = ?");
        $v_stmt->bind_param("i", $id_village);
        $v_stmt->execute();
        $v_result = $v_stmt->get_result()->fetch_assoc();
        $village_abbr = $v_result['village_abbr'] ?? '';
        $suffix = trim($_POST['exp_suffix'] ?? '01');
        
        // Construct full place code: ExpCode-VillageAbbr-Suffix
        // Example: 20100-TJ-01
        $place_code = '';
        if ($village_abbr) {
            $place_code = $exp_code . '-' . $village_abbr . '-' . $suffix;
        }
        
        if (empty($place_code)) {
            $error = 'Desa tidak ditemukan!';
        } else {
            $stmt = $conn->prepare("INSERT INTO project_village_expcodes (kode_proyek, id_village, exp_code, place_code, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisss", $kode_proyek, $id_village, $exp_code, $place_code, $description);
            
            if ($stmt->execute()) {
                header('Location: manage_projects.php?success=expcode_added#expcode-section');
                exit();
            } else {
                if (strpos($stmt->error, 'Duplicate') !== false) {
                    $error = 'Kombinasi Proyek + Desa + Exp Code sudah ada!';
                } else {
                    $error = 'Gagal menambahkan exp code: ' . $stmt->error;
                }
            }
        }
    }
    // Handle Delete Exp Code Assignment
    elseif (isset($_POST['delete_expcode'])) {
        $exp_id = intval($_POST['expcode_id']);
        
        $stmt = $conn->prepare("DELETE FROM project_village_expcodes WHERE id = ?");
        $stmt->bind_param("i", $exp_id);
        
        if ($stmt->execute()) {
            header('Location: manage_projects.php?success=expcode_deleted#expcode-section');
            exit();
        } else {
            $error = 'Gagal menghapus exp code: ' . $stmt->error;
        }
    }
}

// Handle success messages
$success_message = '';
$info_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $success_message = 'Proyek baru berhasil dibuat!';
            break;
        case 'updated':
            $success_message = 'Proyek berhasil diupdate!';
            break;
        case 'deleted':
            $success_message = 'Proyek berhasil dihapus!';
            break;
        case 'expcode_added':
            $success_message = 'Exp Code berhasil ditambahkan!';
            break;
        case 'expcode_deleted':
            $success_message = 'Exp Code berhasil dihapus!';
            break;
    }
}
if (isset($_GET['info'])) {
    if ($_GET['info'] === 'cancelled') {
        $info_message = 'Proyek tidak dapat dihapus karena sudah digunakan. Status diubah menjadi Cancelled.';
    }
}

// Get all projects
$projects = $conn->query("SELECT * FROM proyek ORDER BY created_at DESC");

// Get all exp code assignments with project and village info
$expcode_assignments = $conn->query("
    SELECT pve.*, p.nama_proyek, v.village_name, v.village_abbr 
    FROM project_village_expcodes pve 
    LEFT JOIN proyek p ON pve.kode_proyek = p.kode_proyek 
    LEFT JOIN villages v ON pve.id_village = v.id_village 
    ORDER BY pve.kode_proyek, v.village_name, pve.exp_code
");
$expcode_list = [];
if ($expcode_assignments) {
    while ($row = $expcode_assignments->fetch_assoc()) {
        $expcode_list[] = $row;
    }
}

// Store projects for dropdown
$projects_for_dropdown = [];
$projects->data_seek(0);
while ($p = $projects->fetch_assoc()) {
    $projects_for_dropdown[] = $p;
}
$projects->data_seek(0);

// NOTE: JANGAN pakai session_write_close() di sini. Jika digunakan, tombol back browser akan menyebabkan session hilang dan user bisa logout/401.
// End of session-php logic
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Proyek - PRCF INDONESIA Financial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="javascript:history.back();" class="text-gray-600 hover:text-gray-800 transition" id="back-button">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Kelola Proyek</h1>
                        <p class="text-xs text-gray-500">Manajemen Kode Proyek</p>
                    </div>
                </div>
                <span class="text-gray-700 font-medium">
                    <i class="fas fa-user-circle mr-2"></i><?php echo $user_name; ?>
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success/Info Messages -->
        <?php if ($success_message): ?>
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative fade-in" role="alert">
            <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($info_message): ?>
        <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative fade-in" role="alert">
            <span class="block sm:inline"><i class="fas fa-info-circle mr-2"></i><?php echo $info_message; ?></span>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative fade-in" role="alert">
            <span class="block sm:inline"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Action Bar -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-project-diagram mr-2 text-blue-600"></i>Daftar Proyek
            </h2>
            <button id="toggleCreateButton" type="button" onclick="toggleCreateForm()" 
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-medium shadow-md">
                <i class="fas fa-plus mr-2"></i> Buat Proyek Baru
            </button>
        </div>

        <!-- Create Form (Hidden by default) -->
        <div id="createForm" class="hidden mb-6 bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-600 fade-in">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-folder-plus mr-2 text-blue-600"></i>Buat Proyek Baru
                </h3>
                <button type="button" onclick="toggleCreateForm(false)" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kode Proyek *</label>
                        <input type="text" name="kode_proyek" required maxlength="50"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 uppercase"
                            placeholder="Contoh: PRJ-2024-001">
                        <p class="text-xs text-gray-500 mt-1">Format: PRJ-YYYY-XXX (huruf kapital)</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nama Proyek *</label>
                        <input type="text" name="nama_proyek" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Nama lengkap proyek">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Status Proyek *</label>
                        <select name="status_proyek" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="ongoing" selected>Ongoing (Default)</option>
                            <option value="planning">Planning</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Donor</label>
                        <input type="text" name="donor" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Nama donor/pemberi dana">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nilai Anggaran (Rp)</label>
                        <input type="text" name="nilai_anggaran" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="0"
                            oninput="formatCurrency(this)">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Rekening Khusus</label>
                        <input type="text" name="rekening_khusus" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Nomor rekening khusus proyek">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Periode Mulai</label>
                        <input type="date" name="periode_mulai" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Periode Selesai</label>
                        <input type="date" name="periode_selesai" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="toggleCreateForm(false)" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200 font-medium">
                        Batal
                    </button>
                    <button type="submit" name="create_project"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                        <i class="fas fa-save mr-2"></i> Simpan Proyek
                    </button>
                </div>
            </form>
        </div>

        <!-- Projects Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Proyek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anggaran</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($projects->num_rows > 0): ?>
                            <?php while ($project = $projects->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-sm font-bold text-blue-600"><?php echo $project['kode_proyek']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $project['nama_proyek']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_colors = [
                                        'planning' => 'bg-yellow-100 text-yellow-800',
                                        'ongoing' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_icons = [
                                        'planning' => 'fa-calendar-alt',
                                        'ongoing' => 'fa-spinner',
                                        'completed' => 'fa-check-circle',
                                        'cancelled' => 'fa-times-circle'
                                    ];
                                    $status_labels = [
                                        'planning' => 'Planning',
                                        'ongoing' => 'Ongoing',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled'
                                    ];
                                    $status = $project['status_proyek'];
                                    ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_colors[$status]; ?>">
                                        <i class="fas <?php echo $status_icons[$status]; ?> mr-1"></i>
                                        <?php echo $status_labels[$status]; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $project['donor'] ?: '-'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $project['nilai_anggaran'] ? 'Rp ' . number_format($project['nilai_anggaran'], 0, ',', '.') : '-'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-600">
                                        <?php if ($project['periode_mulai'] || $project['periode_selesai']): ?>
                                            <?php echo $project['periode_mulai'] ? date('d/m/Y', strtotime($project['periode_mulai'])) : '-'; ?><br>
                                            <span class="text-gray-400">s/d</span><br>
                                            <?php echo $project['periode_selesai'] ? date('d/m/Y', strtotime($project['periode_selesai'])) : '-'; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button onclick="editProject('<?php echo htmlspecialchars(json_encode($project), ENT_QUOTES); ?>')" 
                                        class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirmDelete('<?php echo $project['kode_proyek']; ?>')" 
                                        class="text-red-600 hover:text-red-900" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-folder-open text-4xl mb-2"></i>
                                    <p>Belum ada proyek. Klik "Buat Proyek Baru" untuk memulai.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Exp Code Management Section -->
        <div id="expcode-section" class="mt-8 bg-white rounded-lg shadow-lg overflow-hidden border-l-4 border-green-600">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-green-100">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-code mr-2 text-green-600"></i>Kelola Desa & Exp Code
                </h2>
                <p class="text-sm text-gray-600 mt-1">Assign desa dan expense code ke proyek</p>
            </div>

            <!-- Add ExpCode Form -->
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <form method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Proyek *</label>
                        <select name="exp_kode_proyek" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="">-- Pilih Proyek --</option>
                            <?php foreach ($projects_for_dropdown as $proj): ?>
                                <option value="<?php echo $proj['kode_proyek']; ?>">
                                    <?php echo $proj['kode_proyek']; ?> - <?php echo $proj['nama_proyek']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Desa *</label>
                        <select name="exp_id_village" required id="villageSelect"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="">-- Pilih Desa --</option>
                            <?php foreach ($villages_list as $village): ?>
                                <option value="<?php echo $village['id_village']; ?>" data-abbr="<?php echo $village['village_abbr']; ?>">
                                    <?php echo $village['village_name']; ?> (<?php echo $village['village_abbr']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Exp Code *</label>
                        <input type="text" name="exp_code" required placeholder="10101"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 font-mono">
                    </div>
                     <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Suffix</label>
                        <input type="text" name="exp_suffix" required value="01" placeholder="01"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 font-mono">
                    </div>
                    <!-- Description on new row or adjusted -->
                    <div class="md:col-span-4">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Deskripsi</label>
                        <input type="text" name="exp_description" placeholder="Contoh: LPHD Staff Salary" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" name="add_expcode"
                            class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200 font-medium">
                            <i class="fas fa-plus mr-2"></i>Tambah Exp Code
                        </button>
                    </div>
                </form>
            </div>

            <!-- ExpCode List Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proyek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Desa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exp Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Place Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($expcode_list)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-list text-4xl mb-2"></i>
                                    <p>Belum ada exp code yang di-assign. Tambahkan menggunakan form di atas.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expcode_list as $exp): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-bold text-blue-600"><?php echo $exp['kode_proyek']; ?></span>
                                    <br><span class="text-xs text-gray-500"><?php echo $exp['nama_proyek'] ?? ''; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900"><?php echo $exp['village_name']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-bold text-green-600"><?php echo $exp['exp_code']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-bold">
                                        <?php echo $exp['place_code']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($exp['description'] ?? '-')); ?></div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form method="POST" class="inline" onsubmit="return confirm('Hapus exp code ini?')">
                                        <input type="hidden" name="expcode_id" value="<?php echo $exp['id']; ?>">
                                        <button type="submit" name="delete_expcode" 
                                            class="text-red-600 hover:text-red-900" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Village Management Section -->
        <div id="village-section" class="mt-8 bg-white rounded-lg shadow-lg overflow-hidden border-l-4 border-blue-600">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-map-marker-alt mr-2 text-blue-600"></i>Kelola Desa
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Master data desa untuk budget allocation</p>
                </div>
                <button onclick="openVillageModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                    <i class="fas fa-plus mr-2"></i>Tambah Desa
                </button>
            </div>

            <!-- Search -->
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <input type="text" id="villageSearchInput" placeholder="Cari berdasarkan nama, kode, atau singkatan..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                    onkeyup="searchVillages()">
            </div>

            <!-- Villages Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="villagesTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Desa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Singkatan</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $villages_result_all = $conn->query("SELECT * FROM villages WHERE is_deleted = 0 ORDER BY village_name ASC");
                        if ($villages_result_all && $villages_result_all->num_rows > 0): 
                            while ($vill = $villages_result_all->fetch_assoc()): 
                        ?>
                        <tr class="hover:bg-gray-50 transition village-row" data-search="<?php echo strtolower($vill['village_code'] . ' ' . $vill['village_name'] . ' ' . $vill['village_abbr']); ?>">
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-bold text-blue-600"><?php echo $vill['village_code']; ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($vill['village_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-mono font-semibold">
                                    <?php echo htmlspecialchars($vill['village_abbr']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm">
                                <button onclick='openEditVillageModal(<?php echo json_encode($vill); ?>)'
                                    class="text-blue-600 hover:text-blue-900 mr-2" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick='confirmDeleteVillage(<?php echo $vill['id_village']; ?>, "<?php echo htmlspecialchars($vill['village_name']); ?>")'
                                    class="text-red-600 hover:text-red-900" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-map-marked-alt text-4xl mb-2"></i>
                                <p>Belum ada desa. Klik "Tambah Desa" untuk menambahkan.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Village Create/Edit Modal -->
    <div id="villageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 id="villageModalTitle" class="text-xl font-bold text-gray-800 mb-6">Tambah Desa Baru</h3>
            <form id="villageForm" onsubmit="saveVillage(event)">
                <input type="hidden" id="villageId" name="village_id">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Kode Desa *</label>
                    <input type="text" id="villageCode" name="village_code" required 
                        placeholder="ABCD" maxlength="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 font-mono uppercase">
                    <p class="text-xs text-gray-500 mt-1">Maksimal 4 karakter (contoh: V001, AJ01, NNJ)</p>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Nama Desa *</label>
                    <input type="text" id="villageName" name="village_name" required 
                        placeholder="Nanga Jemah"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Singkatan *</label>
                    <input type="text" id="villageAbbr" name="village_abbr" required pattern="[A-Z]{2,5}"
                        placeholder="NJ" maxlength="5" style="text-transform: uppercase;"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 font-mono uppercase">
                    <p class="text-xs text-gray-500 mt-1">2-5 huruf kapital (contoh: NJ, PR, SW)</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeVillageModal()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" id="villageSubmitBtn" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Tambah Desa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Village Delete Confirmation Modal -->
    <div id="villageDeleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-1/3 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Hapus Desa</h3>
                <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus desa <strong id="deleteVillageName"></strong>?</p>
                <p class="text-xs text-gray-500 mb-6">Data desa yang sudah digunakan di exp code tidak dapat dihapus.</p>
                
                <form onsubmit="deleteVillageHandler(event)" id="villageDeleteForm">
                    <input type="hidden" name="delete_village" value="1">
                    <input type="hidden" name="village_id" id="deleteVillageId">
                    <div class="flex justify-center space-x-3">
                        <button type="button" onclick="closeVillageDeleteModal()" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200 font-medium">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 font-medium">
                            <i class="fas fa-trash mr-2"></i> Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4 pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-edit mr-2 text-blue-600"></i>Edit Proyek
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editForm" class="space-y-4">
                <input type="hidden" name="kode_proyek" id="edit_kode_proyek">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Kode Proyek</label>
                        <input type="text" id="edit_kode_display" disabled
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 font-mono font-bold">
                        <p class="text-xs text-gray-500 mt-1">Kode proyek tidak dapat diubah</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nama Proyek *</label>
                        <input type="text" name="nama_proyek" id="edit_nama_proyek" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Status Proyek *</label>
                        <select name="status_proyek" id="edit_status_proyek" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="planning">Planning</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Donor</label>
                        <input type="text" name="donor" id="edit_donor" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Nilai Anggaran (Rp)</label>
                        <input type="text" name="nilai_anggaran" id="edit_nilai_anggaran" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            oninput="formatCurrency(this)">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Rekening Khusus</label>
                        <input type="text" name="rekening_khusus" id="edit_rekening_khusus" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Periode Mulai</label>
                        <input type="date" name="periode_mulai" id="edit_periode_mulai" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Periode Selesai</label>
                        <input type="date" name="periode_selesai" id="edit_periode_selesai" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200 font-medium">
                        Batal
                    </button>
                    <button type="submit" name="update_project"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                        <i class="fas fa-save mr-2"></i> Update Proyek
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-1/3 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Hapus Proyek</h3>
                <p class="text-sm text-gray-600 mb-4">Apakah Anda yakin ingin menghapus proyek ini?</p>
                <p class="text-xs text-gray-500 mb-6">Proyek yang sudah digunakan tidak dapat dihapus, hanya akan diubah statusnya menjadi Cancelled.</p>
                
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="kode_proyek" id="delete_kode_proyek">
                    <div class="flex justify-center space-x-3">
                        <button type="button" onclick="closeDeleteModal()" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200 font-medium">
                            Batal
                        </button>
                        <button type="submit" name="delete_project"
                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200 font-medium">
                            <i class="fas fa-trash mr-2"></i> Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleCreateForm(forceState) {
            const form = document.getElementById('createForm');
            const toggleButton = document.getElementById('toggleCreateButton');
            const willShow = typeof forceState === 'boolean' ? forceState : form.classList.contains('hidden');

            if (willShow) {
                form.classList.remove('hidden');
                toggleButton.innerHTML = '<i class="fas fa-list mr-2"></i> Kembali ke Daftar Proyek';
                toggleButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                toggleButton.classList.add('bg-gray-600', 'hover:bg-gray-700');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                form.classList.add('hidden');
                toggleButton.innerHTML = '<i class="fas fa-plus mr-2"></i> Buat Proyek Baru';
                toggleButton.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                toggleButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }
        }

        function editProject(projectJson) {
            const project = JSON.parse(projectJson);
            
            document.getElementById('edit_kode_proyek').value = project.kode_proyek;
            document.getElementById('edit_kode_display').value = project.kode_proyek;
            document.getElementById('edit_nama_proyek').value = project.nama_proyek;
            document.getElementById('edit_status_proyek').value = project.status_proyek;
            document.getElementById('edit_donor').value = project.donor || '';
            document.getElementById('edit_nilai_anggaran').value = project.nilai_anggaran ? formatNumber(project.nilai_anggaran) : '';
            document.getElementById('edit_rekening_khusus').value = project.rekening_khusus || '';
            document.getElementById('edit_periode_mulai').value = project.periode_mulai || '';
            document.getElementById('edit_periode_selesai').value = project.periode_selesai || '';
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function confirmDelete(kodeProyek) {
            document.getElementById('delete_kode_proyek').value = kodeProyek;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function formatCurrency(input) {
            let value = input.value.replace(/[^\d]/g, '');
            input.value = formatNumber(value);
        }

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        // Clean URL after showing message and replace POST in history
        (function() {
            // Immediately replace current history entry to remove POST from history
            // This must happen BEFORE checking URL params to ensure POST is replaced
            const currentUrl = window.location.protocol + "//" + 
                              window.location.host + 
                              window.location.pathname + 
                              window.location.search;
            window.history.replaceState({}, document.title, currentUrl);
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success') || urlParams.has('info')) {
                // Close the create form if it's open (since project was created)
                const createForm = document.getElementById('createForm');
                if (createForm && !createForm.classList.contains('hidden')) {
                    toggleCreateForm(false);
                }
                
                // Then clean query parameters after showing message
                setTimeout(function() {
                    const finalCleanUrl = window.location.protocol + "//" + 
                                        window.location.host + 
                                        window.location.pathname;
                    window.history.replaceState({}, document.title, finalCleanUrl);
                }, 3000);
            }
        })();

        // Village Management Functions
        function openVillageModal() {
            document.getElementById('villageForm').reset();
            document.getElementById('villageId').value = '';
            document.getElementById('villageModalTitle').textContent = 'Tambah Desa Baru';
            document.getElementById('villageSubmitBtn').textContent = 'Tambah Desa';
            document.getElementById('villageModal').classList.remove('hidden');
        }

        function openEditVillageModal(village) {
            document.getElementById('villageId').value = village.id_village;
            document.getElementById('villageCode').value = village.village_code;
            document.getElementById('villageName').value = village.village_name;
            document.getElementById('villageAbbr').value = village.village_abbr;
            document.getElementById('villageModalTitle').textContent = 'Edit Desa';
            document.getElementById('villageSubmitBtn').textContent = 'Update Desa';
            document.getElementById('villageModal').classList.remove('hidden');
        }

        function closeVillageModal() {
            document.getElementById('villageModal').classList.add('hidden');
        }

        function confirmDeleteVillage(id, name) {
            document.getElementById('deleteVillageId').value = id;
            document.getElementById('deleteVillageName').textContent = name;
            document.getElementById('villageDeleteModal').classList.remove('hidden');
        }

        function closeVillageDeleteModal() {
            document.getElementById('villageDeleteModal').classList.add('hidden');
        }

        function searchVillages() {
            const input = document.getElementById('villageSearchInput');
            const filter = input.value.toLowerCase();
            const rows = document.getElementsByClassName('village-row');

            for (let i = 0; i < rows.length; i++) {
                const searchText = rows[i].getAttribute('data-search');
                if (searchText.includes(filter)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        async function saveVillage(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('villageSubmitBtn');
            // Check if button exists before proceeding
            if (!submitBtn) {
                console.error('Village submit button not found');
                return;
            }

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';

                const villageId = document.getElementById('villageId') ? document.getElementById('villageId').value : '';
                const villageCodeElement = document.getElementById('villageCode');
                const villageNameElement = document.getElementById('villageName');
                const villageAbbrElement = document.getElementById('villageAbbr');

                if (!villageCodeElement || !villageNameElement || !villageAbbrElement) {
                    throw new Error('Required form elements not found');
                }

                const villageCode = villageCodeElement.value.toUpperCase();
                const villageName = villageNameElement.value;
                const villageAbbr = villageAbbrElement.value.toUpperCase();

                // Create form data
                const formData = new FormData();
                if (villageId) {
                    formData.append('edit_village', '1');
                    formData.append('village_id', villageId);
                } else {
                    formData.append('save_village', '1');
                }
                formData.append('village_code', villageCode);
                formData.append('village_name', villageName);
                formData.append('village_abbr', villageAbbr);

                // Submit via fetch
                const response = await fetch('../../api/manage_village.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Handle non-OK responses
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error:', text);
                    throw new Error('Invalid server response');
                }
                
                if (result.success) {
                    closeVillageModal();
                    window.location.reload();
                } else {
                    alert('Gagal menyimpan desa: ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = villageId ? 'Update Desa' : 'Tambah Desa';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem: ' + error.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    // Restore text based on context or default
                    const villageIdVal = document.getElementById('villageId') ? document.getElementById('villageId').value : '';
                    submitBtn.textContent = villageIdVal ? 'Update Desa' : 'Tambah Desa';
                }
            }
        }

        async function deleteVillageHandler(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menghapus...';

            try {
                const response = await fetch('../../api/manage_village.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeVillageDeleteModal();
                    window.location.reload();
                } else {
                    alert('Gagal menghapus desa: ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-trash mr-2"></i> Hapus';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem saat menghapus: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-trash mr-2"></i> Hapus';
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const villageModal = document.getElementById('villageModal');
            const villageDeleteModal = document.getElementById('villageDeleteModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
            if (event.target === villageModal) {
                closeVillageModal();
            }
            if (event.target === villageDeleteModal) {
                closeVillageDeleteModal();
            }
        }
    </script>
</body>
</html>

