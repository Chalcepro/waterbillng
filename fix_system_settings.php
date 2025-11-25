<?php
/**
 * Script to fix the system_settings table
 */

require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

try {
    // Drop the existing table if it exists
    echo "Dropping existing system_settings table if it exists...\n";
    $pdo->exec("DROP TABLE IF EXISTS `system_settings`");
    
    // Create the table with correct structure
    echo "Creating new system_settings table...\n";
    $createTableSql = "
    CREATE TABLE `system_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(100) NOT NULL,
        `setting_value` text,
        `setting_group` varchar(50) DEFAULT 'general',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($createTableSql);
    echo "✅ Created system_settings table with correct structure\n";
    
    // Insert default settings
    $defaultSettings = [
        ['min_payment_amount', '2000', 'payment'],
        ['subscription_duration_days', '30', 'subscription'],
        ['currency', 'NGN', 'general'],
        ['currency_symbol', '₦', 'general'],
        ['company_name', 'WaterBill NG', 'general'],
        ['support_email', 'support@waterbill.ng', 'general']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_group)
        VALUES (?, ?, ?)
    ");
    
    $inserted = 0;
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
        $inserted++;
        echo "✅ Added setting: {$setting[0]} = {$setting[1]}\n";
    }
    
    echo "\n✅ Successfully inserted $inserted default settings\n";
    
    // Verify the table structure
    $stmt = $pdo->query("DESCRIBE system_settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nTable structure:\n";
    echo str_pad("Field", 20) . str_pad("Type", 30) . str_pad("Null", 10) . "Key\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 30) . 
             str_pad($column['Null'], 10) . 
             $column['Key'] . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    exit(1);
}
?>
