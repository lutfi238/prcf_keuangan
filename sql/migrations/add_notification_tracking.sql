-- Add notification tracking column to user table
ALTER TABLE `user` ADD COLUMN `last_notification_check` DATETIME DEFAULT NULL AFTER `updated_at`;

-- Set initial value to now for existing users
UPDATE `user` SET `last_notification_check` = NOW() WHERE `last_notification_check` IS NULL;

