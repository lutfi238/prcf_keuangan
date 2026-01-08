                            <?php
                            $no = 1;
                            if (!empty($reports_array) && count($reports_array) > 0):
                            foreach ($reports_array as $report):
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $no++; ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['nama_projek'] ?? ''); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['kode_projek'] ?? ''); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($report['creator_name'] ?? ''); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo isset($report['tanggal_laporan']) ? date('d/m/Y', strtotime($report['tanggal_laporan'])) : ''; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($report['status_lap'] === 'approved'): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check-double mr-1"></i> Final Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <i class="fas fa-check-circle mr-1"></i> Approved by FM
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="../reports/view_report.php?id=<?php echo $report['id_laporan_keu']; ?>&return_tab=reports" 
                                        class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i> View
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
                                        <i class="fas fa-file-invoice text-gray-400 text-5xl mb-4"></i>
                                        <p class="text-gray-500 text-lg font-medium mb-2">Belum ada laporan</p>
                                        <p class="text-gray-400 text-sm">Belum ada laporan keuangan yang disetujui.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
