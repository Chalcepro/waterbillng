-- Create manual_payments table
CREATE TABLE IF NOT EXISTS `manual_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','online_payment') NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `manual_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
