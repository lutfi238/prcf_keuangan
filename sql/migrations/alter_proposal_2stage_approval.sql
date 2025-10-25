-- ============================================================================
-- ALTER TABLE: Add 2-Stage Approval for Proposal
-- ============================================================================
-- Date: October 16, 2025
-- Purpose: Implement 2-stage approval (FM → Direktur)
-- 
-- New Flow:
-- 1. PM submit → status 'submitted'
-- 2. FM approve → status 'approved_fm' (waiting Dir)
-- 3. DIR approve → status 'approved' (final)
-- 4. FM/DIR reject → status 'rejected'
-- ============================================================================

-- Step 1: Modify status enum to include 'approved_fm'
ALTER TABLE `proposal` 
MODIFY COLUMN `status` ENUM('draft','submitted','approved_fm','approved','rejected') 
DEFAULT 'draft' 
COMMENT 'draft=PM draft, submitted=waiting FM, approved_fm=FM approved waiting DIR, approved=DIR approved final, rejected=rejected';

-- Step 2: Add approval tracking fields (optional, for audit trail)
ALTER TABLE `proposal` 
ADD COLUMN `approved_by_fm` INT(11) NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `approved_by_dir` INT(11) NULL DEFAULT NULL AFTER `approved_by_fm`,
ADD COLUMN `fm_approval_date` DATETIME NULL DEFAULT NULL AFTER `approved_by_dir`,
ADD COLUMN `dir_approval_date` DATETIME NULL DEFAULT NULL AFTER `fm_approval_date`;

-- Step 3: Add foreign keys for approval tracking
ALTER TABLE `proposal`
ADD CONSTRAINT `fk_proposal_fm` FOREIGN KEY (`approved_by_fm`) REFERENCES `user`(`id_user`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_proposal_dir` FOREIGN KEY (`approved_by_dir`) REFERENCES `user`(`id_user`) ON DELETE SET NULL;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check structure
DESC proposal;

-- Check current proposals
SELECT id_proposal, judul_proposal, status, approved_by_fm, approved_by_dir FROM proposal;

-- ============================================================================
-- DONE!
-- ============================================================================

