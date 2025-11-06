<?php
session_start();

// Prevent browser caching to fix back button session issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../../includes/config.php';
require_once '../../includes/maintenance_config.php';

// Check maintenance mode
check_maintenance();

// Allow Finance Manager, Direktur, and Project Manager (PM can view only)
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../../auth/login.php');
    exit();
}

if (!in_array($_SESSION['user_role'], ['Finance Manager', 'Direktur', 'Project Manager'])) {
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_role = $_SESSION['user_role'];

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$proposal_id = $_GET['id'] ?? 0;

// Determine return dashboard based on role
$return_dashboard = 'dashboard_fm.php'; // default
switch ($user_role) {
    case 'Project Manager':
        $return_dashboard = 'dashboard_pm.php';
        break;
    case 'Staff Accountant':
        $return_dashboard = 'dashboard_sa.php';
        break;
    case 'Finance Manager':
        $return_dashboard = 'dashboard_fm.php';
        break;
    case 'Direktur':
        $return_dashboard = 'dashboard_dir.php';
        break;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_status = $_POST['current_status'] ?? '';
    
    if (isset($_POST['approve'])) {
        // ✅ 2-STAGE APPROVAL LOGIC
        $user_id = $_SESSION['user_id'];
        
        // Check if 2-stage approval is enabled
        $check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");
        $is_2stage = ($check_column && $check_column->num_rows > 0);
        
        if ($user_role === 'Finance Manager' && $current_status === 'submitted') {
            // STAGE 1: FM Approve → status 'approved_fm'
            if ($is_2stage) {
                $stmt = $conn->prepare("UPDATE proposal SET status = 'approved_fm', approved_by_fm = ?, fm_approval_date = NOW() WHERE id_proposal = ?");
                $stmt->bind_param("ii", $user_id, $proposal_id);
            } else {
                // Fallback: 1-stage approval
                $stmt = $conn->prepare("UPDATE proposal SET status = 'approved' WHERE id_proposal = ?");
                $stmt->bind_param("i", $proposal_id);
            }
            
            if ($stmt->execute()) {
                // Get proposal and PM details
                $prop_stmt = $conn->prepare("SELECT p.*, u.email, u.nama FROM proposal p LEFT JOIN user u ON p.pemohon = u.nama WHERE id_proposal = ?");
                $prop_stmt->bind_param("i", $proposal_id);
                $prop_stmt->execute();
                $prop_data = $prop_stmt->get_result()->fetch_assoc();
                
                // Notify PM
                send_notification_email(
                    $prop_data['email'],
                    'Proposal Disetujui oleh Finance Manager (Stage 1/2)',
                    'Proposal Anda "' . $prop_data['judul_proposal'] . '" telah disetujui oleh Finance Manager. Menunggu approval dari Direktur.'
                );
                
                // Notify DIR
                $dir_stmt = $conn->query("SELECT email FROM user WHERE role = 'Direktur'");
                while ($dir = $dir_stmt->fetch_assoc()) {
                    send_notification_email(
                        $dir['email'],
                        'Proposal Menunggu Approval Direktur (Stage 2/2)',
                        'Proposal "' . $prop_data['judul_proposal'] . '" telah disetujui FM dan menunggu approval final Anda.'
                    );
                }
                
                $success = 'Proposal berhasil disetujui (Stage 1/2). Menunggu approval Direktur.';
            }
        } elseif ($user_role === 'Direktur' && $current_status === 'approved_fm') {
            // STAGE 2: DIR Approve → status 'approved' (FINAL)
            if ($is_2stage) {
                $stmt = $conn->prepare("UPDATE proposal SET status = 'approved', approved_by_dir = ?, dir_approval_date = NOW() WHERE id_proposal = ?");
                $stmt->bind_param("ii", $user_id, $proposal_id);
            } else {
                $stmt = $conn->prepare("UPDATE proposal SET status = 'approved' WHERE id_proposal = ?");
                $stmt->bind_param("i", $proposal_id);
            }
            
            if ($stmt->execute()) {
                // Get proposal and PM details
                $prop_stmt = $conn->prepare("SELECT p.*, u.email, u.nama FROM proposal p LEFT JOIN user u ON p.pemohon = u.nama WHERE id_proposal = ?");
                $prop_stmt->bind_param("i", $proposal_id);
                $prop_stmt->execute();
                $prop_data = $prop_stmt->get_result()->fetch_assoc();
                
                // Notify PM
                send_notification_email(
                    $prop_data['email'],
                    'Proposal Disetujui FINAL oleh Direktur (2/2)',
                    'Proposal Anda "' . $prop_data['judul_proposal'] . '" telah mendapat approval final dari Direktur. Status: APPROVED.'
                );
                
                // Notify FM
                $fm_stmt = $conn->query("SELECT email FROM user WHERE role = 'Finance Manager'");
                while ($fm = $fm_stmt->fetch_assoc()) {
                    send_notification_email(
                        $fm['email'],
                        'Proposal Disetujui Final oleh Direktur',
                        'Proposal "' . $prop_data['judul_proposal'] . '" telah mendapat final approval dari Direktur.'
                    );
                }
                
                $success = 'Proposal berhasil disetujui FINAL (2/2)!';
            }
        }
    } elseif (isset($_POST['request_revision'])) {
        $catatan = $_POST['catatan'];
        
        // Change status to rejected with note
        $stmt = $conn->prepare("UPDATE proposal SET status = 'rejected' WHERE id_proposal = ?");
        $stmt->bind_param("i", $proposal_id);
        
        if ($stmt->execute()) {
            // Get proposal and PM details
            $prop_stmt = $conn->prepare("SELECT p.*, u.email FROM proposal p LEFT JOIN user u ON p.pemohon = u.nama WHERE id_proposal = ?");
            $prop_stmt->bind_param("i", $proposal_id);
            $prop_stmt->execute();
            $prop_data = $prop_stmt->get_result()->fetch_assoc();
            
            // Notify PM
            send_notification_email(
                $prop_data['email'],
                'Proposal Perlu Revisi',
                'Proposal Anda "' . $prop_data['judul_proposal'] . '" memerlukan perbaikan. Catatan: ' . $catatan
            );
            
            $success = 'Permintaan revisi berhasil dikirim!';
        }
    }
}

// Get proposal data with FM approval info (if 2-stage approval enabled)
$check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");
if ($check_column && $check_column->num_rows > 0) {
    // 2-stage approval is active
    $stmt = $conn->prepare("SELECT p.*, u.nama as creator_name, u.email as creator_email,
        u2.nama as fm_name
        FROM proposal p 
        LEFT JOIN user u ON p.pemohon = u.nama 
        LEFT JOIN user u2 ON p.approved_by_fm = u2.id_user
        WHERE p.id_proposal = ?");
} else {
    // Fallback: 2-stage approval not yet enabled
    $stmt = $conn->prepare("SELECT p.*, u.nama as creator_name, u.email as creator_email
        FROM proposal p 
        LEFT JOIN user u ON p.pemohon = u.nama 
        WHERE p.id_proposal = ?");
}
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    header('Location: ' . $return_dashboard);
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Proposal - PRCFI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo $return_dashboard; ?>" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Review Proposal</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="<?php echo $return_dashboard; ?>" class="block mt-2 text-green-800 underline">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg border border-gray-200">
            <!-- Proposal Header -->
            <div class="p-8 border-b border-gray-200">
                <div class="text-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">PROPOSAL KEGIATAN</h1>
                    <p class="text-gray-600">PRCFI - Pusat Riset dan Pengembangan</p>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600">Status Proposal</p>
                            <p class="text-lg font-bold text-gray-800">
                                <?php 
                                $status_text = [
                                    'draft' => 'Draft',
                                    'submitted' => 'Menunggu Review FM',
                                    'approved_fm' => '1/2 Approved (Menunggu Direktur)',
                                    'approved' => '2/2 Approved (Final)',
                                    'rejected' => 'Ditolak'
                                ];
                                $status_class = [
                                    'draft' => 'text-gray-800',
                                    'submitted' => 'text-yellow-800',
                                    'approved_fm' => 'text-blue-800',
                                    'approved' => 'text-green-800',
                                    'rejected' => 'text-red-800'
                                ];
                                $status_icon = [
                                    'draft' => 'fa-file',
                                    'submitted' => 'fa-clock',
                                    'approved_fm' => 'fa-check',
                                    'approved' => 'fa-check-double',
                                    'rejected' => 'fa-times'
                                ];
                                ?>
                                <i class="fas <?php echo $status_icon[$proposal['status']] ?? 'fa-question'; ?> mr-2"></i>
                                <span class="<?php echo $status_class[$proposal['status']] ?? 'text-gray-800'; ?>">
                                    <?php echo $status_text[$proposal['status']] ?? $proposal['status']; ?>
                                </span>
                                <?php if ($proposal['status'] === 'approved_fm' && !empty($proposal['fm_name'])): ?>
                                <span class="block text-sm text-gray-600 mt-1">
                                    Approved by: <?php echo $proposal['fm_name']; ?> 
                                    <?php if ($proposal['fm_approval_date']): ?>
                                    (<?php echo date('d/m/Y H:i', strtotime($proposal['fm_approval_date'])); ?>)
                                    <?php endif; ?>
                                </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Tanggal Pengajuan</p>
                            <p class="text-lg font-bold text-gray-800">
                                <?php echo date('d/m/Y', strtotime($proposal['date'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Proposal Content -->
            <div class="p-8 space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Judul Proposal</label>
                        <p class="text-gray-800 font-medium"><?php echo $proposal['judul_proposal']; ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Kode Proyek</label>
                        <p class="text-gray-800 font-medium"><?php echo $proposal['kode_proyek']; ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Penanggung Jawab</label>
                        <p class="text-gray-800 font-medium"><?php echo $proposal['pj']; ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Pemohon</label>
                        <p class="text-gray-800 font-medium"><?php echo $proposal['pemohon']; ?></p>
                    </div>
                </div>

                <?php if (!empty($proposal['tor']) && file_exists($proposal['tor'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">Terms of Reference (TOR)</label>
                    <div class="flex items-center space-x-4 p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="bg-green-500 p-3 rounded flex-shrink-0">
                            <i class="fas fa-file-pdf text-white text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800">File TOR</p>
                            <p class="text-sm text-gray-600 truncate"><?php echo basename($proposal['tor']); ?></p>
                            <p class="text-xs text-gray-500 mt-1 truncate">
                                <i class="fas fa-folder mr-1"></i><?php echo $proposal['tor']; ?>
                            </p>
                        </div>
                        <a href="<?php echo $proposal['tor']; ?>" target="_blank" download
                            class="flex-shrink-0 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200"
                            onclick="event.stopPropagation();">
                            <i class="fas fa-download mr-2"></i> Download
                        </a>
                    </div>
                </div>
                <?php elseif (!empty($proposal['tor'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">Terms of Reference (TOR)</label>
                    <div class="flex items-center space-x-4 p-4 bg-red-50 rounded-lg border border-red-200">
                        <div class="bg-red-500 p-3 rounded flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-red-800">File TOR Tidak Ditemukan</p>
                            <p class="text-sm text-red-600">Path: <?php echo $proposal['tor']; ?></p>
                            <p class="text-xs text-red-500 mt-1">File mungkin sudah dihapus atau dipindahkan</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($proposal['file_budget']) && file_exists($proposal['file_budget'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">Lampiran Budget/RAB</label>
                    <div class="flex items-center space-x-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="bg-blue-500 p-3 rounded flex-shrink-0">
                            <i class="fas fa-file-pdf text-white text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800">File Budget</p>
                            <p class="text-sm text-gray-600 truncate"><?php echo basename($proposal['file_budget']); ?></p>
                        </div>
                        <a href="<?php echo $proposal['file_budget']; ?>" target="_blank" download
                            class="flex-shrink-0 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200"
                            onclick="event.stopPropagation();">
                            <i class="fas fa-download mr-2"></i> Download
                        </a>
                    </div>
                </div>
                <?php elseif (!empty($proposal['file_budget'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">Lampiran Budget/RAB</label>
                    <div class="flex items-center space-x-4 p-4 bg-red-50 rounded-lg border border-red-200">
                        <div class="bg-red-500 p-3 rounded flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-red-800">File Budget Tidak Ditemukan</p>
                            <p class="text-sm text-red-600">Path: <?php echo $proposal['file_budget']; ?></p>
                            <p class="text-xs text-red-500 mt-1">File mungkin sudah dihapus atau dipindahkan</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Review Form -->
            <?php if ($proposal['status'] === 'submitted' && $user_role === 'Finance Manager'): ?>
            <!-- FM Review Form - Stage 1 Approval -->
            <div class="p-8 border-t border-gray-200 bg-blue-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-clipboard-check mr-2 text-blue-600"></i>Review Proposal (Stage 1/2)
                </h3>
                
                <div class="mb-4 p-3 bg-blue-100 border border-blue-300 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Info:</strong> Approval Stage 1 oleh Finance Manager. Setelah disetujui, akan menunggu approval Direktur (Stage 2).
                    </p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="current_status" value="<?php echo $proposal['status']; ?>">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Catatan untuk Project Manager (Opsional)</label>
                        <textarea name="catatan" rows="4" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                            placeholder="Berikan catatan atau komentar terkait proposal ini..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="submit" name="request_revision"
                            class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition duration-200 font-medium"
                            onclick="return confirm('Apakah Anda yakin ingin meminta revisi proposal ini?')">
                            <i class="fas fa-edit mr-2"></i> Minta Revisi
                        </button>
                        <button type="submit" name="approve"
                            class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200 font-medium"
                            onclick="return confirm('Setujui proposal ini? (Stage 1/2 - menunggu Direktur)')">
                            <i class="fas fa-check-circle mr-2"></i> Setujui (Stage 1/2)
                        </button>
                    </div>
                </form>
            </div>
            <?php elseif ($proposal['status'] === 'approved_fm' && $user_role === 'Direktur'): ?>
            <!-- DIR Review Form - Stage 2 Approval -->
            <div class="p-8 border-t border-gray-200 bg-purple-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-clipboard-check mr-2 text-purple-600"></i>Review Proposal (Stage 2/2)
                </h3>
                
                <div class="mb-4 p-3 bg-purple-100 border border-purple-300 rounded-lg">
                    <p class="text-sm text-purple-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Info:</strong> Proposal telah disetujui oleh Finance Manager (<?php echo $proposal['fm_name'] ?? 'FM'; ?>) pada <?php echo $proposal['fm_approval_date'] ? date('d/m/Y H:i', strtotime($proposal['fm_approval_date'])) : '-'; ?>. 
                        <br>Anda dapat memberikan final approval (Stage 2/2).
                    </p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="current_status" value="<?php echo $proposal['status']; ?>">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Catatan untuk Project Manager (Opsional)</label>
                        <textarea name="catatan" rows="4" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400"
                            placeholder="Berikan catatan atau komentar terkait proposal ini..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="submit" name="request_revision"
                            class="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition duration-200 font-medium"
                            onclick="return confirm('Apakah Anda yakin ingin meminta revisi proposal ini?')">
                            <i class="fas fa-edit mr-2"></i> Minta Revisi
                        </button>
                        <button type="submit" name="approve"
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-200 font-medium"
                            onclick="return confirm('Setujui proposal ini? (Stage 2/2 - Final Approval)')">
                            <i class="fas fa-check-double mr-2"></i> Approve Final (2/2)
                        </button>
                    </div>
                </form>
            </div>
            <?php elseif ($proposal['status'] === 'submitted' && $user_role === 'Direktur'): ?>
            <!-- DIR View for 'submitted' - waiting for FM Stage 1 -->
            <div class="p-8 border-t border-gray-200 bg-yellow-50">
                <div class="flex items-center text-yellow-700">
                    <i class="fas fa-clock text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Menunggu Approval Finance Manager (Stage 1/2)</p>
                        <p class="text-sm">Proposal ini sedang menunggu approval dari Finance Manager terlebih dahulu.</p>
                        <p class="text-xs mt-2 text-yellow-600">
                            <i class="fas fa-info-circle mr-1"></i>Sistem 2-stage approval: FM approve dulu (1/2), baru Direktur approve final (2/2).
                        </p>
                    </div>
                </div>
            </div>
            <?php elseif (in_array($user_role, ['Project Manager', 'Finance Manager', 'Direktur']) && in_array($proposal['status'], ['submitted', 'approved_fm'])): ?>
            <!-- View Only - No permission to approve at this stage -->
            <div class="p-8 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center text-gray-700">
                    <i class="fas fa-info-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-medium">View Only</p>
                        <p class="text-sm">
                            <?php if ($proposal['status'] === 'submitted' && $user_role !== 'Finance Manager'): ?>
                                Proposal sedang menunggu approval dari Finance Manager (Stage 1/2).
                            <?php elseif ($proposal['status'] === 'approved_fm' && $user_role !== 'Direktur'): ?>
                                Proposal telah disetujui FM dan sedang menunggu approval dari Direktur (Stage 2/2).
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php elseif ($proposal['status'] === 'approved'): ?>
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Proposal Telah Disetujui (Final)</p>
                        <p class="text-sm">Proposal ini telah mendapat final approval dan Project Manager telah diberitahu.</p>
                        <?php if (empty($proposal['approved_by_fm']) && empty($proposal['approved_by_dir'])): ?>
                        <p class="text-xs mt-2 text-green-600">
                            <i class="fas fa-info-circle mr-1"></i>Note: Proposal ini menggunakan old approval system (before 2-stage).
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Fallback: Unknown state -->
            <div class="p-8 border-t border-gray-200 bg-red-50">
                <div class="flex items-center text-red-700">
                    <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Status Tidak Dikenal</p>
                        <p class="text-sm">Status: <?php echo $proposal['status']; ?> | Role: <?php echo $user_role; ?></p>
                        <p class="text-xs mt-2">Silakan hubungi administrator jika ada masalah.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>