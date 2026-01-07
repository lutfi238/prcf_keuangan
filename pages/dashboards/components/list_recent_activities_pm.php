                <?php 
                if (isset($recent_activities) && count($recent_activities) > 0): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <?php
                        // Determine icon and color based on type and status
                        if ($activity['type'] === 'proposal') {
                            $icon = 'fa-file-alt';
                            $color = 'blue';
                            $status_text = [
                                'draft' => 'Draft',
                                'submitted' => 'Pending FM Approval',
                                'approved_fm' => 'Approved by FM (Final)',
                                'approved' => 'Approved by FM (Final)',
                                'rejected' => 'Rejected'
                            ];
                            $link = '../proposals/view_proposal.php?id=' . ($activity['id'] ?? 0); // READ-ONLY for PM
                        } else {
                            $icon = 'fa-chart-line';
                            $color = 'green';
                            $status_text = [
                                'draft' => 'Draft',
                                'submitted' => 'Pending SA Validation',
                                'verified' => 'Verified',
                                'revision_requested' => 'Needs Revision from FM',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected'
                            ];
                            $link = '../reports/view_report_pm.php?id=' . ($activity['id'] ?? 0); // READ-ONLY for PM
                        }
                        $current_status = $status_text[$activity['status'] ?? ''] ?? $activity['status'] ?? 'Unknown';
                        ?>
                        <a href="<?php echo $link; ?>" class="block">
                            <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="bg-<?php echo $color; ?>-500 p-2 rounded-full flex-shrink-0">
                                    <i class="fas <?php echo $icon; ?> text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($activity['title'] ?? ''); ?></h4>
                                    <p class="text-sm text-gray-600">Status: <?php echo $current_status; ?></p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i><?php echo time_elapsed_string($activity['activity_date'] ?? 'now'); ?>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="fas fa-chevron-right text-gray-400"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="fas fa-history text-gray-400 text-5xl mb-4"></i>
                        <p class="text-gray-500 text-lg font-medium mb-2">Belum ada aktivitas</p>
                        <p class="text-gray-400 text-sm">Mulai dengan membuat proposal atau laporan keuangan</p>
                    </div>
                <?php endif; ?>
