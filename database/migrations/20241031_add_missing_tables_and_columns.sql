-- Add missing columns to payments table
ALTER TABLE payments
ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER `method`,
ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) NULL AFTER `receipt_image`,
ADD COLUMN IF NOT EXISTS transaction_date DATETIME NULL AFTER `bank_name`,
ADD COLUMN IF NOT EXISTS payment_type ENUM('water_bill', 'subscription', 'other') DEFAULT 'water_bill' AFTER `transaction_date`,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create subscriptions table if it doesn't exist
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_id` (`payment_id`),
  KEY `status` (`status`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
