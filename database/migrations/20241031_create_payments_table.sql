-- Create payments table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','verified','failed','cancelled') NOT NULL DEFAULT 'pending',
  `method` enum('paystack','bank-transfer','opay','receipt','cash') NOT NULL,
  `reference` varchar(100) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `payment_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `method` (`method`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
