<?php if (empty($reports_array)): ?>
    <tr>
        <td colspan="7" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center justify-center">
                <i class="fas fa-file-invoice text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg font-medium mb-2">Belum ada laporan keuangan</p>
                <p class="text-gray-400 text-sm">Tidak ada laporan keuangan yang perlu diapprove saat ini.</p>
            </div>
        </td>
    </tr>
<?php else: ?>
    <?php 
    $no = 1;
    foreach ($reports_array as $report): 
    ?>
    <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
        <td class="px-6 py-4 text-sm text-gray-900"><?php echo $no++; ?></td>
        <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($report['nama_projek']); ?></td>
        <td class="px-6 py-4 text-sm text-gray-900">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                <?php echo htmlspecialchars($report['kode_projek']); ?>
            </span>
        </td>
        <td class="px-6 py-4 text-sm text-gray-900">
            <div class="flex items-center">
                 <div class="flex-shrink-0 h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mr-2">
                    <i class="fas fa-user-circle text-xs"></i>
                </div>
                <?php echo htmlspecialchars($report['creator_name']); ?>
            </div>
        </td>
        <td class="px-6 py-4 text-sm text-gray-900">
             <div class="flex items-center text-gray-500">
                <i class="far fa-calendar-alt mr-2 text-xs"></i>
                <?php echo date('d/m/Y', strtotime($report['tanggal_laporan'])); ?>
            </div>
        </td>
        <td class="px-6 py-4">
            <?php
            $status = trim($report['status_lap']);
            switch ($status) {
                case 'draft':
                    echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>';
                    break;
                case 'submitted':
                    echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i>Pending SA</span>';
                    break;
                case 'verified':
                    echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i>Tervalidasi SA</span>';
                    break;
                case 'approved_fm':
                    echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><i class="fas fa-check-double mr-1"></i>Approved FM</span>';
                    break;
                case 'approved':
                    echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800"><i class="fas fa-thumbs-up mr-1"></i>Approved Final</span>';
                    break;
                default:
                    echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Unknown</span>';
            }
            ?>
        </td>
        <td class="px-6 py-4 text-sm">
            <?php if ($status === 'verified' || $status === 'submitted'): ?>
                <a href="../reports/approve-report-fm.php?id=<?php echo $report['id_laporan_keu']; ?>&return_tab=reports" 
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition-colors">
                    <i class="fas fa-check-circle mr-1"></i> Approve
                </a>
            <?php else: ?>
                <a href="../reports/view_report_fm.php?id=<?php echo $report['id_laporan_keu']; ?>&return_tab=reports" 
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    <i class="fas fa-eye mr-1"></i> View
                </a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
