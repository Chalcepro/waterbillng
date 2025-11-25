-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL COMMENT 'info, warning, important, update, payment, maintenance, emergency',
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who sent the notification',
  `recipient_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create user_notifications table
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('sent','delivered','read') NOT NULL DEFAULT 'sent',
  `email_sent` tinyint(1) NOT NULL DEFAULT '0',
  `email_sent_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notification_id` (`notification_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_email_sent` (`email_sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add foreign keys separately to avoid issues if tables don't exist yet
ALTER TABLE `user_notifications` 
  ADD CONSTRAINT `fk_notification` 
  FOREIGN KEY (`notification_id`) 
  REFERENCES `notifications` (`id`) 
  ON DELETE CASCADE;

ALTER TABLE `user_notifications` 
  ADD CONSTRAINT `fk_user_notification` 
  FOREIGN KEY (`user_id`) 
  REFERENCES `users` (`id`) 
  ON DELETE CASCADE;

-- Add notification preferences to users table if not exists
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `receive_email_notifications` TINYINT(1) NOT NULL DEFAULT 1
COMMENT 'Whether the user wants to receive email notifications';
