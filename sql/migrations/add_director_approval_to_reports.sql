-- Migration: Add Director Approval Column to Financial Reports
-- Description: Ensures laporan_keuangan_header has approved_by_dir for final director approval
-- Date: 2025-10-29

-- Check if approved_by_dir column exists, if not, add it
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'laporan_keuangan_header' 
  AND COLUMN_NAME = 'approved_by_dir';

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `laporan_keuangan_header` 
   ADD COLUMN `approved_by_dir` INT UNSIGNED NULL COMMENT "Director who gave final approval" AFTER `approved_by`,
   ADD COLUMN `approved_dir_at` TIMESTAMP NULL COMMENT "Director approval timestamp" AFTER `approved_by_dir`,
   ADD CONSTRAINT `fk_laporan_approved_by_dir` 
   FOREIGN KEY (`approved_by_dir`) REFERENCES `user`(`id_user`) ON DELETE SET NULL',
  'SELECT "Column approved_by_dir already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status_lap enum to include more granular statuses if needed
-- This ensures we have all necessary statuses for the workflow
ALTER TABLE `laporan_keuangan_header` 
MODIFY COLUMN `status_lap` ENUM('draft', 'submitted', 'verified', 'approved_fm', 'approved', 'revision_requested', 'rejected') 
DEFAULT 'draft' 
COMMENT 'Report status: draft, submitted, verified (by SA), approved_fm (by FM), approved (final by Director), revision_requested, rejected';

-- Note: The workflow should be:
-- 1. PM creates report (draft/submitted)
-- 2. SA validates (verified)
-- 3. FM approves (approved_fm) - stores in approved_by
-- 4. Director final approval (approved) - stores in approved_by_dir
