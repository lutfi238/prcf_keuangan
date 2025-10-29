-- Migration: Add Admin Role to User Table
-- Description: Adds 'Admin' role option to user table for administrative access
-- Date: 2025-10-29

-- Modify the user table role column to include Admin
ALTER TABLE `user` 
MODIFY COLUMN `role` ENUM('Project Manager', 'Finance Manager', 'Staff Accountant', 'Direktur', 'Admin') NOT NULL 
COMMENT 'User role: PM, FM, SA, Direktur, or Admin';

-- Optional: Create a default admin user (CHANGE PASSWORD AFTER FIRST LOGIN!)
-- Uncomment and modify the lines below if you want to create an admin account

-- INSERT INTO `user` (nama, email, phone, role, password, otp, otp_expiry, created_at) 
-- VALUES (
--   'System Administrator',
--   'admin@example.com',
--   '+6281234567890',
--   'Admin',
--   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password" - CHANGE THIS!
--   NULL,
--   NULL,
--   NOW()
-- );

-- Note: For security, create admin users through a secure registration process
-- or manually in phpMyAdmin with a strong hashed password.
