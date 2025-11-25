<?php
/**
 * Migration to ensure notifications table has the correct structure
 */

// Include database connection
require_once __DIR__ . '/../../api/includes/db_connect.php';

try {
    // Check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create notifications table if it doesn't exist
        $pdo->exec("
            CREATE TABLE `notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `type` enum('payment','system','announcement') NOT NULL DEFAULT 'system',
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT '0',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `read_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `is_read` (`is_read`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        echo "Created notifications table.\n";
    } else {
        // Check if we need to add any missing columns
        $columns = [
            'user_id' => "ADD COLUMN `user_id` INT(11) NOT NULL AFTER `id`",
            'type' => "MODIFY COLUMN `type` ENUM('payment','system','announcement') NOT NULL DEFAULT 'system'",
            'title' => "ADD COLUMN IF NOT EXISTS `title` VARCHAR(255) NOT NULL AFTER `type`",
            'message' => "MODIFY COLUMN `message` TEXT NOT NULL",
            'is_read' => "ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) NOT NULL DEFAULT '0'",
            'created_at' => "ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'read_at' => "ADD COLUMN IF NOT EXISTS `read_at` TIMESTAMP NULL DEFAULT NULL"
        ];
        
        foreach ($columns as $column => $sql) {
            try {
                $check = $pdo->query("SHOW COLUMNS FROM `notifications` LIKE '$column'");
                if ($check->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `notifications` $sql");
                    echo "Added column: $column\n";
                }
            } catch (Exception $e) {
                echo "Error with column $column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Add foreign key if it doesn't exist
    try {
        $pdo->exec("
            ALTER TABLE `notifications`
            ADD CONSTRAINT `fk_notifications_user_id`
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
        echo "Added foreign key constraint.\n";
    } catch (Exception $e) {
        // Ignore if foreign key already exists
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            echo "Error adding foreign key: " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "Migration completed.\n";
