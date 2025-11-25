<?php
/**
 * Migration to update notifications table with additional fields and indexes
 */

class UpdateNotificationsTable {
    public function up($pdo) {
        try {
            // Add indexes if they don't exist
            $queries = [
                "ALTER TABLE `notifications` 
                 ADD COLUMN IF NOT EXISTS `is_duplicate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `recipient_count`,
                 ADD COLUMN IF NOT EXISTS `original_notification_id` INT NULL AFTER `is_duplicate`,
                 ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
                 ADD INDEX IF NOT EXISTS `idx_is_duplicate` (`is_duplicate`);",
                
                "ALTER TABLE `notification_recipients` 
                 ADD INDEX IF NOT EXISTS `idx_is_read` (`is_read`),
                 ADD INDEX IF NOT EXISTS `idx_read_at` (`read_at`);"
            ];

            foreach ($queries as $query) {
                $pdo->exec($query);
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Migration error: " . $e->getMessage());
            return false;
        }
    }

    public function down($pdo) {
        try {
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
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Migration rollback error: " . $e->getMessage());
            return false;
        }
    }
}

// Check if this file is being executed directly (for testing)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0])) {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../includes/db_connect.php';
    
    $migration = new UpdateNotificationsTable();
    $result = $migration->up($pdo);
    
    if ($result) {
        echo "Migration completed successfully.\n";
    } else {
        echo "Migration failed. Check error log for details.\n";
    }
}
?>
