-- =============================================
-- Add WhatsApp Column to User Table
-- =============================================
-- Run this SQL in phpMyAdmin or MySQL client
-- Database: prcf_keuangan

USE prcf_keuangan;

-- Add whatsapp column after email
ALTER TABLE user 
ADD COLUMN whatsapp VARCHAR(20) NULL AFTER email,
ADD INDEX idx_whatsapp (whatsapp);

-- Update existing test users with WhatsApp numbers (optional)
UPDATE user SET whatsapp = '6281234567890' WHERE username = 'pm_test';
UPDATE user SET whatsapp = '6281234567891' WHERE username = 'sa_test';
UPDATE user SET whatsapp = '6281234567892' WHERE username = 'fm_test';
UPDATE user SET whatsapp = '6281234567893' WHERE username = 'dir_test';

-- Verify changes
SELECT id_user, username, email, whatsapp, role FROM user;

-- =============================================
-- NOTES:
-- - Format: 628xxxxxxxxxx (Indonesia, without +)
-- - VARCHAR(20) untuk support international numbers
-- - NULL allowed (optional for now)
-- - Index untuk faster lookup
-- =============================================

