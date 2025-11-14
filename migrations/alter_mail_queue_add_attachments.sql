-- Add attachments column to mail_queue to store JSON array of file paths
ALTER TABLE `mail_queue`
  ADD COLUMN `attachments` TEXT DEFAULT NULL;