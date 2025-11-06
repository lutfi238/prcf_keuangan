<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check 2-Stage Approval Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            <i class="fas fa-clipboard-check mr-3 text-blue-600"></i>
            Check 2-Stage Approval Status
        </h1>

        <?php
        require_once '../includes/config.php';

        // Check if columns exist
        $columns_to_check = ['approved_by_fm', 'fm_approval_date', 'approved_by_dir', 'dir_approval_date'];
        $results = [];
        
        foreach ($columns_to_check as $col) {
            $check = $conn->query("SHOW COLUMNS FROM proposal LIKE '$col'");
            $results[$col] = ($check && $check->num_rows > 0);
        }
        
        // Check if status enum includes 'approved_fm'
        $status_check = $conn->query("SHOW COLUMNS FROM proposal LIKE 'status'");
        $status_row = $status_check->fetch_assoc();
        $has_approved_fm = (strpos($status_row['Type'], 'approved_fm') !== false);
        
        $all_good = ($results['approved_by_fm'] && $results['fm_approval_date'] && 
                     $results['approved_by_dir'] && $results['dir_approval_date'] && $has_approved_fm);
        ?>

        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <?php if ($all_good): ?>
                <div class="flex items-center mb-6 p-6 bg-green-50 rounded-lg border-2 border-green-500">
                    <i class="fas fa-check-circle text-5xl text-green-500 mr-4"></i>
                    <div>
                        <h2 class="text-2xl font-bold text-green-800">✅ 2-Stage Approval AKTIF!</h2>
                        <p class="text-green-700 mt-1">Semua kolom database sudah tersedia</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex items-center mb-6 p-6 bg-red-50 rounded-lg border-2 border-red-500">
                    <i class="fas fa-exclamation-triangle text-5xl text-red-500 mr-4"></i>
                    <div>
                        <h2 class="text-2xl font-bold text-red-800">❌ 2-Stage Approval BELUM AKTIF</h2>
                        <p class="text-red-700 mt-1">Beberapa kolom database belum ada</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Details -->
            <h3 class="text-lg font-bold text-gray-800 mb-4 mt-6">Detail Kolom Database:</h3>
            <div class="space-y-3">
                <?php foreach ($results as $col => $exists): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <?php if ($exists): ?>
                                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-red-500 text-xl mr-3"></i>
                            <?php endif; ?>
                            <span class="font-mono text-sm font-medium"><?php echo $col; ?></span>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $exists ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $exists ? 'EXISTS' : 'NOT FOUND'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <?php if ($has_approved_fm): ?>
                            <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle text-red-500 text-xl mr-3"></i>
                        <?php endif; ?>
                        <span class="font-mono text-sm font-medium">status ENUM('approved_fm')</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $has_approved_fm ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $has_approved_fm ? 'EXISTS' : 'NOT FOUND'; ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!$all_good): ?>
            <!-- Instructions Card -->
            <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-6">
                <h3 class="text-lg font-bold text-yellow-800 mb-4">
                    <i class="fas fa-tools mr-2"></i>Cara Mengaktifkan 2-Stage Approval:
                </h3>
                <ol class="space-y-3 text-yellow-800">
                    <li class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">1</span>
                        <div class="flex-1">
                            <p class="font-medium">Buka phpMyAdmin</p>
                            <p class="text-sm text-yellow-700">http://localhost/phpmyadmin</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">2</span>
                        <div class="flex-1">
                            <p class="font-medium">Pilih database <code class="bg-yellow-200 px-2 py-1 rounded">prcf_keuangan</code></p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">3</span>
                        <div class="flex-1">
                            <p class="font-medium">Klik tab "SQL"</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">4</span>
                        <div class="flex-1">
                            <p class="font-medium">Import file <code class="bg-yellow-200 px-2 py-1 rounded">alter_proposal_2stage_approval.sql</code></p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">5</span>
                        <div class="flex-1">
                            <p class="font-medium">Klik "Go" / "Kirim"</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">6</span>
                        <div class="flex-1">
                            <p class="font-medium">Refresh halaman ini untuk verify</p>
                        </div>
                    </li>
                </ol>
            </div>

            <!-- SQL Preview -->
            <div class="bg-gray-800 text-gray-100 rounded-lg p-6 mt-6 overflow-x-auto">
                <h3 class="text-sm font-bold text-gray-300 mb-3">SQL Migration Preview:</h3>
                <pre class="text-xs"><code>-- Add 2-stage approval columns
ALTER TABLE `proposal`
MODIFY COLUMN `status` ENUM('draft','submitted','approved_fm','approved','rejected') DEFAULT 'draft',
ADD COLUMN `approved_by_fm` INT(11) DEFAULT NULL AFTER `pemohon`,
ADD COLUMN `fm_approval_date` DATETIME DEFAULT NULL AFTER `approved_by_fm`,
ADD COLUMN `approved_by_dir` INT(11) DEFAULT NULL AFTER `fm_approval_date`,
ADD COLUMN `dir_approval_date` DATETIME DEFAULT NULL AFTER `approved_by_dir`;

-- Add foreign keys
ALTER TABLE `proposal`
ADD CONSTRAINT `fk_approved_by_fm` FOREIGN KEY (`approved_by_fm`) REFERENCES `user` (`id_user`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_approved_by_dir` FOREIGN KEY (`approved_by_dir`) REFERENCES `user` (`id_user`) ON DELETE SET NULL;</code></pre>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex justify-between mt-8">
            <a href="dashboard_fm.php" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
            </a>
            <button onclick="location.reload()" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-sync-alt mr-2"></i>Refresh Status
            </button>
        </div>
    </div>
</body>
</html>

