-- Migration: Sync proposal.pemohon names with user.nama
-- This fixes proposals that were created before the cascade update was added
-- Run this once to sync existing data

-- ============================================================================
-- AUTOMATIC SYNC: Update all proposals where pemohon matches any user name
-- This query will sync proposal.pemohon with the latest user.nama
-- ============================================================================

-- First, verify what needs to be synced (optional - for verification):
-- SELECT p.id_proposal, p.pemohon as old_pemohon, u.nama as current_name
-- FROM proposal p
-- LEFT JOIN user u ON p.pemohon = u.nama
-- WHERE p.pemohon IS NOT NULL;

-- ============================================================================
-- MANUAL SYNC: If you know the old name -> new name mapping
-- ============================================================================
-- Example: If you changed "Chandra" to "Chandra Wijaya", run:
-- UPDATE proposal SET pemohon = 'Chandra Wijaya' WHERE pemohon = 'Chandra';

-- ============================================================================
-- FUTURE IMPROVEMENT: Add created_by column for proper user ID tracking
-- ============================================================================
-- This is recommended for new implementations to avoid name mismatch issues:
--
-- Step 1: Add the column
-- ALTER TABLE proposal ADD COLUMN created_by INT NULL AFTER pemohon;
-- ALTER TABLE proposal ADD CONSTRAINT fk_proposal_created_by FOREIGN KEY (created_by) REFERENCES user(id_user) ON DELETE SET NULL;
--
-- Step 2: Populate from existing pemohon data
-- UPDATE proposal p
-- JOIN user u ON p.pemohon = u.nama
-- SET p.created_by = u.id_user;
--
-- Step 3: Update dashboard queries to use created_by instead of pemohon
-- WHERE created_by = ? (using user_id instead of name)
