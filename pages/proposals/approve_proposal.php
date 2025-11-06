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

// Only Finance Manager can approve - Direktur should use view page
if ($_SESSION['user_role'] !== 'Finance Manager') {
    if ($_SESSION['user_role'] === 'Direktur') {
        // Redirect Direktur to view page - they only view, don't approve
        $proposal_id = $_GET['id'] ?? 0;
        header('Location: view_proposal.php?id=' . $proposal_id);
        exit();
    }
    header('Location: ../../auth/unauthorized.php');
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$proposal_id = $_GET['id'] ?? 0;
$return_tab = $_GET['return_tab'] ?? 'proposals'; // Default to proposals if not specified

// Handle approval - 2-STAGE APPROVAL SYSTEM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve'])) {
    
    // Check if 2-stage approval is enabled (check if column exists)
    $check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");
    $two_stage_enabled = ($check_column && $check_column->num_rows > 0);
    
    // Get current proposal status
    $check_stmt = $conn->prepare("SELECT status FROM proposal WHERE id_proposal = ?");
    $check_stmt->bind_param("i", $proposal_id);
    $check_stmt->execute();
    $current = $check_stmt->get_result()->fetch_assoc();
    
    if ($user_role === 'Finance Manager' && $current['status'] === 'submitted') {
        // STAGE 1: FM Approve
        if ($two_stage_enabled) {
            // 2-stage approval: status → 'approved_fm' (waiting DIR)
            $stmt = $conn->prepare("UPDATE proposal SET status = 'approved_fm', approved_by_fm = ?, fm_approval_date = NOW() WHERE id_proposal = ?");
            $stmt->bind_param("ii", $user_id, $proposal_id);
        } else {
            // Fallback: direct approval (1-stage)
            $stmt = $conn->prepare("UPDATE proposal SET status = 'approved' WHERE id_proposal = ?");
            $stmt->bind_param("i", $proposal_id);
            
            $error = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded">
                <strong>⚠️ Warning:</strong> 2-stage approval belum diaktifkan.<br>
                <small>Import file <code>alter_proposal_2stage_approval.sql</code> di phpMyAdmin untuk mengaktifkan fitur 2-stage approval.</small>
            </div>';
        }
        
        if ($stmt->execute()) {
            // Get proposal details
            $prop_stmt = $conn->prepare("SELECT p.*, u.email, u.nama FROM proposal p LEFT JOIN user u ON p.pemohon = u.nama WHERE id_proposal = ?");
            $prop_stmt->bind_param("i", $proposal_id);
            $prop_stmt->execute();
            $prop_data = $prop_stmt->get_result()->fetch_assoc();
            
            if ($two_stage_enabled) {
                // Notify PM - Stage 1 approval
                send_notification_email(
                    $prop_data['email'],
                    'Proposal Disetujui oleh Finance Manager (1/2)',
                    'Proposal Anda "' . $prop_data['judul_proposal'] . '" telah disetujui oleh Finance Manager. Menunggu approval Direktur untuk final approval.'
                );
                
                // Notify Direktur
                $dir_stmt = $conn->query("SELECT email FROM user WHERE role = 'Direktur'");
                while ($dir = $dir_stmt->fetch_assoc()) {
                    send_notification_email(
                        $dir['email'],
                        'Proposal Menunggu Approval Direktur (Stage 2)',
                        'Proposal "' . $prop_data['judul_proposal'] . '" telah disetujui FM. Mohon review untuk final approval (Stage 2/2).'
                    );
                }
                
                header('Location: ../dashboards/dashboard_fm.php?success=proposal_approved_stage1');
            } else {
                // Fallback: notify as fully approved
                send_notification_email(
                    $prop_data['email'],
                    'Proposal Disetujui oleh Finance Manager',
                    'Proposal Anda "' . $prop_data['judul_proposal'] . '" telah disetujui.'
                );
                
                header('Location: ../dashboards/dashboard_fm.php?success=proposal_approved&warning=2stage_disabled');
            }
            exit();
        } else {
            $error = 'Gagal menyetujui proposal. Error: ' . $stmt->error;
        }
        
    } else {
        $error = 'Status proposal tidak valid untuk approval Anda';
    }
}

// Get proposal data with FM approval info (if 2-stage)
$check_column = $conn->query("SHOW COLUMNS FROM proposal LIKE 'approved_by_fm'");
if ($check_column && $check_column->num_rows > 0) {
    $stmt = $conn->prepare("SELECT p.*, u.nama as fm_name, u.email as fm_email
        FROM proposal p 
        LEFT JOIN user u ON p.approved_by_fm = u.id_user
        WHERE p.id_proposal = ?");
} else {
    $stmt = $conn->prepare("SELECT p.* FROM proposal p WHERE p.id_proposal = ?");
}
$stmt->bind_param("i", $proposal_id);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    header('Location: ../dashboards/dashboard_dir.php?tab=' . urlencode($return_tab));
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Proposal - Direktur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../dashboards/dashboard_dir.php?tab=<?php echo urlencode($return_tab); ?>" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-gray-800">Approve Proposal</h1>
                </div>
                <span class="text-gray-700 font-medium"><?php echo $user_name; ?></span>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
                <a href="../dashboards/dashboard_dir.php?tab=<?php echo urlencode($return_tab); ?>" class="block mt-2 text-green-800 underline">Kembali ke Dashboard</a>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg border border-gray-200">
            <div class="p-8 border-b border-gray-200">
                <div class="text-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">PROPOSAL KEGIATAN</h1>
                    <p class="text-gray-600">PRCFI - Pusat Riset dan Pengembangan</p>
                </div>

                <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Status Proposal</p>
                            <p class="text-lg font-bold text-gray-800">
                                <?php 
                                $status_text = [
                                    'draft' => 'Draft',
                                    'submitted' => 'Menunggu Review FM (1/2)',
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
                                    Approved by FM: <?php echo $proposal['fm_name']; ?> 
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
                        </div>
                        <a href="<?php echo $proposal['tor']; ?>" target="_blank" download
                            class="flex-shrink-0 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-200">
                            <i class="fas fa-download mr-2"></i> Download TOR
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
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($proposal['file_budget']) && file_exists($proposal['file_budget'])): ?>
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-600 mb-3">Lampiran Budget/RAB</label>
                    <div class="flex items-center space-x-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="bg-blue-500 p-3 rounded flex-shrink-0">
                            <i class="fas fa-file-excel text-white text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800">File Budget</p>
                            <p class="text-sm text-gray-600 truncate"><?php echo basename($proposal['file_budget']); ?></p>
                        </div>
                        <a href="<?php echo $proposal['file_budget']; ?>" target="_blank" download
                            class="flex-shrink-0 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200">
                            <i class="fas fa-download mr-2"></i> Download Budget
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($proposal['status'] === 'approved_fm' && $user_role === 'Direktur'): ?>
            <!-- STAGE 2: Direktur Final Approval -->
            <div class="p-8 border-t border-gray-200 bg-purple-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-clipboard-check mr-2 text-purple-600"></i>Final Approval Direktur (Stage 2/2)
                </h3>
                
                <?php if (!empty($proposal['fm_name'])): ?>
                <div class="mb-4 p-4 bg-purple-100 border border-purple-300 rounded-lg">
                    <p class="text-sm text-purple-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Info:</strong> Proposal telah disetujui oleh Finance Manager 
                        <strong>(<?php echo $proposal['fm_name']; ?>)</strong>
                        <?php if ($proposal['fm_approval_date']): ?>
                        pada <strong><?php echo date('d/m/Y H:i', strtotime($proposal['fm_approval_date'])); ?></strong>
                        <?php endif; ?>.
                        <br>Anda dapat memberikan final approval (Stage 2/2).
                    </p>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4">
                    <div class="bg-white p-6 rounded-lg border border-purple-200">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-signature text-purple-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-800 mb-2">Tanda Tangan Digital - Direktur</p>
                                <p class="text-sm text-gray-600 mb-4">Dengan menekan tombol "Approve Final", Anda memberikan persetujuan final sebagai Direktur.</p>
                                <div class="flex items-center space-x-2 text-sm text-gray-600">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo $user_name; ?></span>
                                    <span>•</span>
                                    <span><?php echo date('d/m/Y H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="approve"
                            class="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-200 font-medium text-lg shadow-lg"
                            onclick="return confirm('Setujui proposal ini sebagai FINAL APPROVAL (2/2)?')">
                            <i class="fas fa-check-double mr-2"></i> Approve Final (2/2)
                        </button>
                    </div>
                </form>
            </div>
            <?php elseif ($proposal['status'] === 'submitted'): ?>
            <!-- Waiting FM Approval (Stage 1) -->
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
            <?php elseif ($proposal['status'] === 'approved'): ?>
            <!-- Final Approved -->
            <div class="p-8 border-t border-gray-200 bg-green-50">
                <div class="flex items-center text-green-700">
                    <i class="fas fa-check-double text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">Proposal Telah Disetujui (Final)</p>
                        <p class="text-sm">Proposal ini telah mendapat final approval dan Project Manager telah diberitahu.</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Unknown Status -->
            <div class="p-8 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center text-gray-700">
                    <i class="fas fa-info-circle text-2xl mr-3"></i>
                    <div>
                        <p class="font-medium">Status: <?php echo $proposal['status']; ?></p>
                        <p class="text-sm">Tidak ada aksi yang tersedia untuk status ini.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>