<?php
/**
 * Migration to update notifications schema for better performance and functionality
 */

class UpdateNotificationsSchema {
    public function up($pdo) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // 1. Add new columns to notifications table
            $queries = [
                // Add is_duplicate and original_notification_id columns
                "ALTER TABLE `notifications` 
                 ADD COLUMN IF NOT EXISTS `is_duplicate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `recipient_count`,
                 ADD COLUMN IF NOT EXISTS `original_notification_id` INT NULL AFTER `is_duplicate`,
                 ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
                 ADD INDEX IF NOT EXISTS `idx_is_duplicate` (`is_duplicate`);",
                
                // Add indexes to notification_recipients
                "ALTER TABLE `notification_recipients` 
                 ADD INDEX IF NOT EXISTS `idx_is_read` (`is_read`),
                 ADD INDEX IF NOT EXISTS `idx_read_at` (`read_at`);",
                
                // Add foreign key for original_notification_id
                "ALTER TABLE `notifications` 
                 ADD CONSTRAINT `fk_original_notification` 
                 FOREIGN KEY IF NOT EXISTS (`original_notification_id`) 
                 REFERENCES `notifications`(`id`) ON DELETE SET NULL;"
            ];

            foreach ($queries as $query) {
                $pdo->exec($query);
            }
            
            // Commit transaction
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Migration error: " . $e->getMessage());
            return false;
        }
    }

    public function down($pdo) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Drop foreign key first
            $pdo->exec("ALTER TABLE `notifications` DROP FOREIGN KEY IF EXISTS `fk_original_notification`;");
            
            // Remove the columns and indexes we added
            $queries = [
                "ALTER TABLE `notifications` 
                 DROP COLUMN IF EXISTS `is_duplicate`,
                 DROP COLUMN IF EXISTS `original_notification_id`,
                 DROP INDEX IF EXISTS `idx_created_at`,
                 DROP INDEX IF EXISTS `idx_is_duplicate`;",
                
                "ALTER TABLE `notification_recipients` 
                 DROP INDEX IF EXISTS `idx_is_read`,
                 DROP INDEX IF EXISTS `idx_read_at`;"
            ];

            foreach ($queries as $query) {
                $pdo->exec($query);
            }
            
            // Commit transaction
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Migration rollback error: " . $e->getMessage());
            return false;
        }
    }
}

// Run the migration if executed directly (for testing)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0])) {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../api/includes/db_connect.php';
    
    $migration = new UpdateNotificationsSchema();
    $result = $migration->up($pdo);
    
    if ($result) {
        echo "Migration completed successfully.\n";
    } else {
        echo "Migration failed. Check error log for details.\n";
    }
}
?>
