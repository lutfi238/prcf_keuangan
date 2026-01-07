<?php if (empty($proposals_array)): ?>
    <tr>
        <td colspan="7" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center justify-center">
                <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg font-medium mb-2">Belum ada proposal masuk</p>
                <p class="text-gray-400 text-sm">Tidak ada proposal yang perlu direview saat ini.</p>
            </div>
        </td>
    </tr>
<?php else: ?>
    <?php 
    $no = 1;
    foreach ($proposals_array as $proposal): 
    ?>
    <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
        <td class="px-6 py-4 text-sm text-gray-900"><?php echo $no++; ?></td>
        <td class="px-6 py-4 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($proposal['judul_proposal']); ?></td>
        <td class="px-6 py-4 text-sm text-gray-900">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-500 mr-3">
                    <i class="fas fa-user text-xs"></i>
                </div>
                <span><?php echo htmlspecialchars($proposal['pemohon']); ?></span>
            </div>
        </td>
        <td class="px-6 py-4 text-sm text-gray-900">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <?php echo htmlspecialchars($proposal['kode_proyek']); ?>
            </span>
        </td>
        <td class="px-6 py-4 text-sm text-gray-900">
            <div class="flex items-center text-gray-500">
                <i class="far fa-calendar-alt mr-2 text-xs"></i>
                <?php echo date('d/m/Y', strtotime($proposal['date'])); ?>
            </div>
        </td>
        <td class="px-6 py-4">
            <?php if ($proposal['status'] === 'submitted'): ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 flex items-center w-fit">
                    <span class="w-2 h-2 bg-yellow-400 rounded-full mr-1 animate-pulse"></span>
                    Pending Review
                </span>
            <?php elseif ($proposal['status'] === 'approved'): ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 flex items-center w-fit">
                    <i class="fas fa-check-circle mr-1"></i> Approved
                </span>
             <?php elseif ($proposal['status'] === 'rejected'): ?>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 flex items-center w-fit">
                    <i class="fas fa-times-circle mr-1"></i> Rejected
                </span>
            <?php endif; ?>
        </td>
        <td class="px-6 py-4 text-sm">
            <a href="../proposals/review_proposal_fm.php?id=<?php echo $proposal['id_proposal']; ?>&return_tab=proposals" 
                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <i class="fas fa-eye mr-1"></i> Review
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
