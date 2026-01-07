                            <?php 
                            $no = 1;
                            if (!empty($proposals_array) && count($proposals_array) > 0):
                            foreach ($proposals_array as $proposal): 
                            ?>
                            <tr class="hover:bg-gray-50 proposal-row"
                                data-title="<?php echo strtolower(htmlspecialchars($proposal['judul_proposal'] ?? '')); ?>"
                                data-pj="<?php echo strtolower(htmlspecialchars($proposal['pj'] ?? '')); ?>"
                                data-project="<?php echo strtolower(trim(htmlspecialchars($proposal['kode_proyek'] ?? $proposal['kode_projek'] ?? ''))); ?>">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $no++; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($proposal['judul_proposal'] ?? ''); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($proposal['pj'] ?? ''); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($proposal['kode_proyek'] ?? $proposal['kode_projek'] ?? ''); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo !empty($proposal['date']) ? date('d/m/Y', strtotime($proposal['date'])) : (!empty($proposal['created_at']) ? date('d/m/Y', strtotime($proposal['created_at'])) : '-'); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($proposal['status'] === 'approved_fm'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Approved by FM
                                        </span>
                                    <?php elseif ($proposal['status'] === 'approved'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <i class="fas fa-check-double mr-1"></i> Final Approved
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="<?php echo ($proposal['status'] === 'approved_fm') ? '../proposals/approve_proposal.php' : '../proposals/view_proposal.php'; ?>?id=<?php echo $proposal['id_proposal'] ?? 0; ?>&return_tab=proposals" 
                                        class="<?php echo ($proposal['status'] === 'approved_fm') ? 'text-purple-600 hover:text-purple-900 font-bold' : 'text-blue-600 hover:text-blue-900'; ?>">
                                        <?php if ($proposal['status'] === 'approved_fm'): ?>
                                            <i class="fas fa-edit mr-1"></i> Review
                                        <?php else: ?>
                                            <i class="fas fa-eye mr-1"></i> View
                                        <?php endif; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-inbox text-gray-400 text-5xl mb-4"></i>
                                        <p class="text-gray-500 text-lg font-medium mb-2">Belum ada proposal</p>
                                        <p class="text-gray-400 text-sm">Belum ada proposal yang disetujui oleh Finance Manager</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
