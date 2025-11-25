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

    // Function to add column if it doesn't exist
    function addColumnIfNotExists($pdo, $table, $column, $definition) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            echo "Adding column '$column' to table '$table'...\n";
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Successfully added column '$column'.\n";
            return true;
        } else {
            echo "Column '$column' already exists in table '$table'.\n";
            return false;
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Add payment_method column if it doesn't exist
        addColumnIfNotExists($pdo, 'payments', 'payment_method', 'VARCHAR(50) NULL DEFAULT NULL AFTER `method`');
        
        // Add receipt_path column if it doesn't exist
        addColumnIfNotExists($pdo, 'payments', 'receipt_path', 'VARCHAR(255) NULL DEFAULT NULL AFTER `receipt_image`');
        
        // Add admin_notes column if it doesn't exist
        addColumnIfNotExists($pdo, 'payments', 'admin_notes', 'TEXT NULL DEFAULT NULL AFTER `notes`');
        
        // If method column exists but payment_method is empty, copy data
        $pdo->exec("UPDATE `payments` SET `payment_method` = `method` WHERE (`payment_method` IS NULL OR `payment_method` = '') AND `method` IS NOT NULL");
        
        // Commit the transaction
        $pdo->commit();
        echo "\nDatabase update completed successfully.\n";
        
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
