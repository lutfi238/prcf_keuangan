<?php 
if ($reports && $reports->num_rows > 0):
    $no = 1;
    // Reset pointer if it's being reused, though usually query is fresh
    if ($reports instanceof mysqli_result) $reports->data_seek(0);
    
    while ($report = $reports->fetch_assoc()): 
?>
<tr class="hover:bg-gray-50">
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $no++; ?></td>
    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['nama_projek'] ?? ''); ?></td>
    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['kode_projek'] ?? ''); ?></td>
    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['creator_name'] ?? ''); ?></td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
        <?php echo isset($report['tanggal_laporan']) ? date('d/m/Y', strtotime($report['tanggal_laporan'])) : ''; ?>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <?php 
        $status = $report['status_lap'] ?? '';
        if ($status === 'submitted'): ?>
            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                Pending Validation
            </span>
        <?php elseif ($status === 'verified'): ?>
            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                Validated
            </span>
        <?php else: ?>
            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                <?php echo htmlspecialchars($status); ?>
            </span>
        <?php endif; ?>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm">
        <a href="../reports/approve-report-sa.php?id=<?php echo $report['id_laporan_keu']; ?>" 
            class="text-blue-600 hover:text-blue-900 mr-3">
            <i class="fas fa-eye mr-1"></i> Review
        </a>
    </td>
</tr>
<?php 
    endwhile;
else:
?>
<tr>
    <td colspan="7" class="px-6 py-12 text-center">
        <div class="flex flex-col items-center justify-center">
            <i class="fas fa-file-invoice text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-500 text-lg font-medium mb-2">Belum ada laporan</p>
            <p class="text-gray-400 text-sm">Tidak ada laporan keuangan yang perlu divalidasi saat ini.</p>
        </div>
    </td>
</tr>
<?php endif; ?>
