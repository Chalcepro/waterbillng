<?php
// Include the database configuration
require_once __DIR__ . '/config.php';

try {
    // Connect to the database using PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "Successfully connected to the database.\n";

    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Add payment_method column if it doesn't exist
        $pdo->exec("ALTER TABLE `payments` 
                   ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(50) NULL DEFAULT NULL AFTER `method`,
                   ADD COLUMN IF NOT EXISTS `receipt_path` VARCHAR(255) NULL DEFAULT NULL AFTER `receipt_image`,
                   ADD COLUMN IF NOT EXISTS `admin_notes` TEXT NULL DEFAULT NULL AFTER `notes`");
        
        // If method column exists but payment_method doesn't, copy data from method to payment_method
        $pdo->exec("UPDATE `payments` SET `payment_method` = `method` WHERE `payment_method` IS NULL AND `method` IS NOT NULL");
        
        // Commit the transaction
        $pdo->commit();
        
        echo "Successfully updated the payments table structure.\n";
        
        // Verify the changes
        $stmt = $pdo->query("
            SELECT 
                COLUMN_NAME, 
                COLUMN_TYPE, 
                IS_NULLABLE, 
                COLUMN_DEFAULT, 
                COLUMN_COMMENT 
            FROM 
                INFORMATION_SCHEMA.COLUMNS 
            WHERE 
                TABLE_SCHEMA = '" . DB_NAME . "' 
                AND TABLE_NAME = 'payments'
                AND COLUMN_NAME IN ('payment_method', 'receipt_path', 'admin_notes')
        ");
        
        $added_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($added_columns) > 0) {
            echo "\nNewly added/verified columns:\n";
            echo str_pad("Column", 20) . str_pad("Type", 30) . str_pad("Nullable", 10) . "Default\n";
            echo str_repeat("-", 70) . "\n";
            
            foreach ($added_columns as $col) {
                echo str_pad($col['COLUMN_NAME'], 20) . 
                     str_pad($col['COLUMN_TYPE'], 30) . 
                     str_pad($col['IS_NULLABLE'], 10) . 
                     ($col['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
            }
        }
        
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
