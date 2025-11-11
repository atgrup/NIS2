-- Create history table for file state changes and table for email action tokens

CREATE TABLE IF NOT EXISTS `archivo_estado_historial` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `archivo_id` INT NOT NULL,
  `proveedor_id` INT DEFAULT NULL,
  `previous_state` VARCHAR(50) DEFAULT NULL,
  `new_state` VARCHAR(50) NOT NULL,
  `comentario` TEXT DEFAULT NULL,
  `changed_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`archivo_id`),
  INDEX (`proveedor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `email_actions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(128) NOT NULL UNIQUE,
  `queue_id` INT DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `archivo_id` INT DEFAULT NULL,
  `meta` TEXT DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`archivo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
