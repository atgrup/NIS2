-- Create mail_queue and mail_logs tables

CREATE TABLE IF NOT EXISTS `mail_queue` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipient_email` VARCHAR(255) NOT NULL,
  `recipient_name` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` TEXT,
  `body_text` TEXT,
  `log_info` TEXT,
  `include_log` TINYINT(1) DEFAULT 0,
  `attempts` INT DEFAULT 0,
  `max_attempts` INT DEFAULT 3,
  `status` ENUM('pending','sending','sent','failed') DEFAULT 'pending',
  `error` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mail_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `queue_id` INT DEFAULT NULL,
  `recipient` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('ok','error') DEFAULT 'ok',
  `error` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
