-- Add status field to user table for account activation/deactivation
-- Migration: Add user status field
-- Date: 2025-11-03

ALTER TABLE `user` ADD COLUMN `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'inactive' AFTER `role`;

-- Update existing users to active status (they were active before this change)
UPDATE `user` SET `status` = 'active' WHERE `status` = 'inactive';

-- Add index for performance
ALTER TABLE `user` ADD INDEX `idx_user_status` (`status`);

-- Add comments
ALTER TABLE `user` MODIFY COLUMN `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'inactive' COMMENT 'Account status: active=can login, inactive=deactivated by admin, pending=awaiting admin approval';
