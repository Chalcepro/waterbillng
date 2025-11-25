-- Create system_settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings if they don't exist
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('min_payment_amount', '2000', 'payment'),
('subscription_duration_days', '30', 'subscription'),
('currency', 'NGN', 'general'),
('company_name', 'WaterBill NG', 'general'),
('support_email', 'support@waterbill.ng', 'general');

-- Ensure subscriptions table has all required columns
ALTER TABLE `subscriptions` 
  ADD COLUMN IF NOT EXISTS `amount_paid` DECIMAL(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `months_covered` INT(11) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `payment_id` INT(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `auto_renew` TINYINT(1) DEFAULT 0;

-- Add index for better performance
CREATE INDEX IF NOT EXISTS `idx_subscriptions_user_status` ON `subscriptions` (`user_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_subscriptions_end_date` ON `subscriptions` (`end_date`);
