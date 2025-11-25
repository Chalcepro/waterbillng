-- Create password_resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index on email for faster lookups
ALTER TABLE `password_resets` ADD INDEX `idx_email` (`email`);

-- Add index on token for faster lookups
ALTER TABLE `password_resets` ADD UNIQUE INDEX `idx_token` (`token`);
