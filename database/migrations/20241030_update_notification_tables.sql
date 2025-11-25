-- Add missing columns to notifications table if they don't exist
ALTER TABLE `notifications`
ADD COLUMN IF NOT EXISTS `subject` varchar(255) DEFAULT NULL AFTER `title`,
ADD COLUMN IF NOT EXISTS `created_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who sent the notification' AFTER `updated_at`,
ADD COLUMN IF NOT EXISTS `recipient_count` int(11) NOT NULL DEFAULT '0' AFTER `created_by`,
ADD KEY IF NOT EXISTS `idx_created_by` (`created_by`),
ADD KEY IF NOT EXISTS `idx_type` (`type`);

-- Add missing columns to user_notifications table if they don't exist
ALTER TABLE `user_notifications`
ADD COLUMN IF NOT EXISTS `email_sent` tinyint(1) NOT NULL DEFAULT '0',
ADD COLUMN IF NOT EXISTS `email_sent_at` datetime DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `read_at` datetime DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `created_at` datetime NOT NULL,
ADD KEY IF NOT EXISTS `idx_status` (`status`),
ADD KEY IF NOT EXISTS `idx_email_sent` (`email_sent`);

-- Add notification preferences to users table if not exists
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `receive_email_notifications` TINYINT(1) NOT NULL DEFAULT 1
COMMENT 'Whether the user wants to receive email notifications';

-- Add foreign keys if they don't exist
SET @dbname = DATABASE();
SET @tablename = 'user_notifications';
SET @constraint_name = 'fk_notification';

SELECT COUNT(*) INTO @constraint_exists
FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND CONSTRAINT_NAME = @constraint_name;

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE `user_notifications` 
     ADD CONSTRAINT `fk_notification` 
     FOREIGN KEY (`notification_id`) 
     REFERENCES `notifications` (`id`) 
     ON DELETE CASCADE',
    'SELECT ''Foreign key fk_notification already exists'''
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_name = 'fk_user_notification';

SELECT COUNT(*) INTO @constraint_exists
FROM information_schema.TABLE_CONSTRAINTS 
WHERE CONSTRAINT_SCHEMA = @dbname 
AND TABLE_NAME = @tablename 
AND CONSTRAINT_NAME = @constraint_name;

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE `user_notifications` 
     ADD CONSTRAINT `fk_user_notification` 
     FOREIGN KEY (`user_id`) 
     REFERENCES `users` (`id`) 
     ON DELETE CASCADE',
    'SELECT ''Foreign key fk_user_notification already exists'''
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
