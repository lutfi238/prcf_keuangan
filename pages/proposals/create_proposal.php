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
        $file_budget = $upload_dir . time() . '_' . $_FILES['file_budget']['name'];
        move_uploaded_file($_FILES['file_budget']['tmp_name'], $file_budget);
    }
    
    $stmt = $conn->prepare("INSERT INTO proposal (judul_proposal, pj, date, pemohon, kode_proyek, tor, file_budget, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')");
    $stmt->bind_param("sssssss", $judul, $pj, $date, $pemohon, $kode_proyek, $tor, $file_budget);
    
        if ($stmt->execute()) {
            // Get Finance Manager email
            $fm_stmt = $conn->prepare("SELECT email, nama FROM user WHERE role = 'Finance Manager'");
            $fm_stmt->execute();
            $fm_result = $fm_stmt->get_result();
            
            // Try to send notifications, but don't fail if email sending fails
            if (function_exists('send_notification_email')) {
                while ($fm = $fm_result->fetch_assoc()) {
                    // Send notification
                    send_notification_email(
                        $fm['email'],
                        'Proposal Baru dari ' . $user_name,
                        'Proposal baru dengan judul "' . $judul . '" telah dikirimkan oleh ' . $user_name . '. Mohon segera di-review.'
                    );
                }
            }
            
            $success = 'Proposal berhasil dikirimkan ke Finance Manager!';
        } else {
            $error = 'Gagal mengirimkan proposal';
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

                <!-- File Upload -->
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2">II. LAMPIRAN</h3>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">File Terms of Reference (TOR) *</label>
                        <input type="file" name="file_tor" accept=".pdf,.doc,.docx" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <p class="text-xs text-gray-500 mt-1">Format: PDF, Word (Max 10MB)</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">File Budget/RAB</label>
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
</body>
</html>