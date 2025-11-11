-- Add audit columns to email_actions for tracking token usage
ALTER TABLE `email_actions`
  ADD COLUMN `used_by` INT DEFAULT NULL,
  ADD COLUMN `used_at` DATETIME DEFAULT NULL,
  ADD COLUMN `used_ip` VARCHAR(45) DEFAULT NULL,
  ADD COLUMN `used_user_agent` VARCHAR(255) DEFAULT NULL;
