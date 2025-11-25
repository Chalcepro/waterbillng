<?php
// Database configuration
$dbHost = 'localhost';
$dbName = 'waterbill_db';
$dbUser = 'root';
$dbPass = ''; // Default XAMPP password is empty

// Connect to MySQL server
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // 1. Add columns to notifications table
    try {
        $pdo->exec("ALTER TABLE `notifications` 
            ADD COLUMN IF NOT EXISTS `subject` varchar(255) DEFAULT NULL AFTER `title`,
            ADD COLUMN IF NOT EXISTS `created_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who sent the notification' AFTER `updated_at`,
            ADD COLUMN IF NOT EXISTS `recipient_count` int(11) NOT NULL DEFAULT '0' AFTER `created_by`");
        echo "✓ Updated notifications table\n";
    } catch (PDOException $e) {
        echo "ℹ️ Could not update notifications table: " . $e->getMessage() . "\n";
    }
    
    // 2. Add columns to user_notifications table
    try {
        $pdo->exec("ALTER TABLE `user_notifications` 
            ADD COLUMN IF NOT EXISTS `email_sent` tinyint(1) NOT NULL DEFAULT '0',
            ADD COLUMN IF NOT EXISTS `email_sent_at` datetime DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `read_at` datetime DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");
        echo "✓ Updated user_notifications table\n";
    } catch (PDOException $e) {
        echo "ℹ️ Could not update user_notifications table: " . $e->getMessage() . "\n";
    }
    
    // 3. Add notification preferences to users table
    try {
        $pdo->exec("ALTER TABLE `users` 
            ADD COLUMN IF NOT EXISTS `receive_email_notifications` TINYINT(1) NOT NULL DEFAULT 1
            COMMENT 'Whether the user wants to receive email notifications'");
        echo "✓ Updated users table\n";
    } catch (PDOException $e) {
        echo "ℹ️ Could not update users table: " . $e->getMessage() . "\n";
    }
    
    // 4. Add indexes if they don't exist
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_created_by` ON `notifications` (`created_by`)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_type` ON `notifications` (`type`)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_status` ON `user_notifications` (`status`)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS `idx_email_sent` ON `user_notifications` (`email_sent`)");
        echo "✓ Created indexes\n";
    } catch (PDOException $e) {
        echo "ℹ️ Could not create indexes: " . $e->getMessage() . "\n";
    }
    
    // 5. Add foreign keys if they don't exist
    try {
        // Check if foreign key exists
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = '$dbName' 
            AND TABLE_NAME = 'user_notifications' 
            AND CONSTRAINT_NAME = 'fk_notification'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $pdo->exec("
                ALTER TABLE `user_notifications` 
                ADD CONSTRAINT `fk_notification` 
                FOREIGN KEY (`notification_id`) 
                REFERENCES `notifications` (`id`) 
                ON DELETE CASCADE
            ");
            echo "✓ Added fk_notification foreign key\n";
        } else {
            echo "ℹ️ fk_notification foreign key already exists\n";
        }
        
        // Check if foreign key exists
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = '$dbName' 
            AND TABLE_NAME = 'user_notifications' 
            AND CONSTRAINT_NAME = 'fk_user_notification'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $pdo->exec("
                ALTER TABLE `user_notifications` 
                ADD CONSTRAINT `fk_user_notification` 
                FOREIGN KEY (`user_id`) 
                REFERENCES `users` (`id`) 
                ON DELETE CASCADE
            ");
            echo "✓ Added fk_user_notification foreign key\n";
        } else {
            echo "ℹ️ fk_user_notification foreign key already exists\n";
        }
    } catch (PDOException $e) {
        echo "ℹ️ Could not add foreign keys: " . $e->getMessage() . "\n";
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "\n✅ Database update completed successfully!\n";
    
} catch (PDOException $e) {
    die("\n❌ Database error: " . $e->getMessage() . "\n");
}
