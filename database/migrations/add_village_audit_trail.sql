-- ============================================
-- Village Management Module - Database Migration
-- Date: 2025-11-23
-- Purpose: Add audit trail columns to villages table
-- ============================================

-- Add audit trail columns
ALTER TABLE villages 
ADD COLUMN created_by INT NULL COMMENT 'User ID yang membuat record' AFTER description,
ADD COLUMN updated_by INT NULL COMMENT 'User ID yang terakhir update record' AFTER created_by,
ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 COMMENT 'Soft delete flag: 0=active, 1=deleted' AFTER updated_by;

-- Add foreign key constraints for audit trail
ALTER TABLE villages
ADD CONSTRAINT fk_village_creator FOREIGN KEY (created_by) REFERENCES user(id_user) ON DELETE SET NULL,
ADD CONSTRAINT fk_village_updater FOREIGN KEY (updated_by) REFERENCES user(id_user) ON DELETE SET NULL;

-- Add index for performance (filtering deleted villages)
CREATE INDEX idx_is_deleted ON villages(is_deleted);

-- Verification queries
-- Run these after migration to verify success:

-- 1. Check new columns exist
-- DESCRIBE villages;

-- 2. Check foreign keys created
-- SHOW CREATE TABLE villages;

-- 3. Count active vs deleted villages
-- SELECT is_deleted, COUNT(*) FROM villages GROUP BY is_deleted;
