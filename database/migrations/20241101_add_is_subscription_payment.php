<?php
/**
 * Migration to add is_subscription_payment column to payments table
 */

// Include database connection
require_once __DIR__ . '/../../api/includes/db_connect.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'is_subscription_payment'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add the column
        $pdo->exec("
            ALTER TABLE `payments`
            ADD COLUMN `is_subscription_payment` TINYINT(1) NOT NULL DEFAULT 0
            COMMENT '1 if this payment is for subscription, 0 otherwise'
            AFTER `status`
        ");
        
        echo "Successfully added is_subscription_payment column to payments table.\n";
        
        // Update existing records where amount matches subscription amounts
        $minAmount = 2000; // Default minimum amount
        $pdo->exec("
            UPDATE payments 
            SET is_subscription_payment = 1 
            WHERE amount >= $minAmount
        ");
        
        echo "Updated existing payment records to mark subscription payments.\n";
    } else {
        echo "The is_subscription_payment column already exists.\n";
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "Migration completed.\n";
