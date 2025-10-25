-- ============================================
-- QUICK FIX: Manual Update for Testing DIR Approval
-- ============================================
-- Run this SQL in phpMyAdmin to create a test proposal for DIR approval
-- ============================================

-- Option 1: Update proposal ID 2 (currently 'submitted') to 'approved_fm'
-- This will make it appear in DIR dashboard with approve buttons

UPDATE proposal 
SET 
    status = 'approved_fm',
    approved_by_fm = 1,  -- ⚠️ CHANGE THIS to actual FM user ID from your 'user' table
    fm_approval_date = NOW()
WHERE id_proposal = 2;

-- ============================================
-- To find your FM user ID, run this query first:
-- ============================================

SELECT id_user, nama, email, role 
FROM user 
WHERE role = 'Finance Manager';

-- Copy the 'id_user' from result and replace the '1' in UPDATE query above

-- ============================================
-- After running UPDATE, verify the change:
-- ============================================

SELECT id_proposal, judul_proposal, status, approved_by_fm, fm_approval_date
FROM proposal 
WHERE id_proposal = 2;

-- Expected result:
-- status = 'approved_fm'
-- approved_by_fm = (FM user ID, NOT NULL)
-- fm_approval_date = (current timestamp)

-- ============================================
-- Now test as Direktur:
-- ============================================
-- 1. Login as Direktur
-- 2. Go to dashboard
-- 3. You should see proposal ID 2 with "1/2 Approved (FM)" badge
-- 4. Click the proposal
-- 5. You should now see:
--    - Purple box "Review Proposal (Stage 2/2)"
--    - Button "Minta Revisi" (yellow)
--    - Button "Approve Final (2/2)" (purple)
--    - TOR download button (green)
--    - Budget download button (blue)

-- ============================================
-- CLEANUP: Reset to original state (optional)
-- ============================================

UPDATE proposal 
SET 
    status = 'submitted',
    approved_by_fm = NULL,
    fm_approval_date = NULL
WHERE id_proposal = 2;

