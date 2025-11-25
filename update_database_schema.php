<?php
/**
 * Database Schema Update Script
 * 
 * This script updates the database schema to include all required columns and tables
 * for the subscription and payment functionality.
 */

require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

try {
    // Start transaction
    $pdo->beginTransaction();
    
    echo "Starting database schema update...\n\n";
    
    // 1. Update users table
    echo "Updating users table...\n";
    $alterUsersSql = [
        "ALTER TABLE `users` 
         ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(255) NULL AFTER `email`,
         ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20) NULL AFTER `full_name`,
         ADD COLUMN IF NOT EXISTS `address` TEXT NULL AFTER `phone`,
         ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    foreach ($alterUsersSql as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Updated users table\n";
        } catch (Exception $e) {
            echo "⚠️  Error updating users table: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Update subscriptions table
    echo "\nUpdating subscriptions table...\n";
    $alterSubsSql = [
        "ALTER TABLE `subscriptions`
         ADD COLUMN IF NOT EXISTS `amount_paid` DECIMAL(10,2) DEFAULT NULL,
         ADD COLUMN IF NOT EXISTS `months_covered` INT(11) DEFAULT 1,
         ADD COLUMN IF NOT EXISTS `payment_id` INT(11) DEFAULT NULL,
         ADD COLUMN IF NOT EXISTS `auto_renew` TINYINT(1) DEFAULT 0,
         ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
         ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    foreach ($alterSubsSql as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Updated subscriptions table\n";
        } catch (Exception $e) {
            echo "⚠️  Error updating subscriptions table: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Create system_settings table if it doesn't exist
    echo "\nChecking system_settings table...\n";
    $createSettingsTable = [
        "CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text,
            `setting_group` varchar(50) DEFAULT 'general',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    foreach ($createSettingsTable as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Created/verified system_settings table\n";
        } catch (Exception $e) {
            echo "⚠️  Error creating system_settings table: " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Insert default settings
    echo "\nInserting default settings...\n";
    $defaultSettings = [
        ['min_payment_amount', '2000', 'payment'],
        ['subscription_duration_days', '30', 'subscription'],
        ['currency', 'NGN', 'general'],
        ['currency_symbol', '₦', 'general'],
        ['company_name', 'WaterBill NG', 'general'],
        ['support_email', 'support@waterbill.ng', 'general']
    ];
    
    $inserted = 0;
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_group)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            setting_group = VALUES(setting_group),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    foreach ($defaultSettings as $setting) {
        try {
            $stmt->execute($setting);
            if ($stmt->rowCount() > 0) {
                $inserted++;
                echo "✅ Added/updated setting: {$setting[0]} = {$setting[1]}\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Error inserting setting {$setting[0]}: " . $e->getMessage() . "\n";
        }
    }
    
    // 5. Create indexes for better performance
    echo "\nCreating indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_subscriptions_user_status ON subscriptions (user_id, status)",
        "CREATE INDEX IF NOT EXISTS idx_subscriptions_end_date ON subscriptions (end_date)",
        "CREATE INDEX IF NOT EXISTS idx_payments_user_status ON payments (user_id, status)",
        "CREATE INDEX IF NOT EXISTS idx_payments_created_at ON payments (created_at)",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users (email)"
    ];
    
    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
            $indexName = explode(' ', $sql)[4]; // Extract index name from SQL
            echo "✅ Created/verified index: $indexName\n";
        } catch (Exception $e) {
            echo "⚠️  Error creating index: " . $e->getMessage() . "\n";
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n✅ Database schema update completed successfully!\n";
    echo "   - Tables updated: users, subscriptions, system_settings\n";
    echo "   - Default settings added: $inserted\n\n";
    
    // Display current settings
    echo "Current System Settings:\n";
    $settings = $pdo->query("SELECT setting_key, setting_value, setting_group FROM system_settings ORDER BY setting_group, setting_key")->fetchAll(PDO::FETCH_ASSOC);
    
    $currentGroup = '';
    foreach ($settings as $setting) {
        if ($setting['setting_group'] !== $currentGroup) {
            echo "\n[{$setting['setting_group']}]\n";
            $currentGroup = $setting['setting_group'];
        }
        echo str_pad($setting['setting_key'], 30) . " = " . $setting['setting_value'] . "\n";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n❌ Database update failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
?>
