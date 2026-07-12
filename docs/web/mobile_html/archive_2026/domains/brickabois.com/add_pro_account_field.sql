-- Add pro_account field to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `pro_account` tinyint(1) DEFAULT 0 AFTER `role`;

