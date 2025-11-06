-- Migration: Remove Director Approval Columns
-- Description: Removes approved_by_dir and dir_approval_date columns since FM approval is final
-- Date: 2025-11-06

-- Remove approved_by_dir and dir_approval_date from proposal table
ALTER TABLE `proposal` 
DROP FOREIGN KEY IF EXISTS `fk_proposal_dir`,
DROP COLUMN IF EXISTS `approved_by_dir`,
DROP COLUMN IF EXISTS `dir_approval_date`;

-- Remove approved_by_dir and approved_dir_at from laporan_keuangan_header table
ALTER TABLE `laporan_keuangan_header` 
DROP FOREIGN KEY IF EXISTS `fk_laporan_approved_by_dir`,
DROP COLUMN IF EXISTS `approved_by_dir`,
DROP COLUMN IF EXISTS `approved_dir_at`;

-- Note: MySQL doesn't support IF EXISTS for columns, so if columns don't exist, 
-- you may need to manually check or ignore errors
-- For MySQL 8.0.19+, you can use:
-- ALTER TABLE `proposal` DROP COLUMN IF EXISTS `approved_by_dir`;
-- ALTER TABLE `proposal` DROP COLUMN IF EXISTS `dir_approval_date`;
-- ALTER TABLE `laporan_keuangan_header` DROP COLUMN IF EXISTS `approved_by_dir`;
-- ALTER TABLE `laporan_keuangan_header` DROP COLUMN IF EXISTS `approved_dir_at`;

