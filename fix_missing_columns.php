<?php
// Include the database configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        echo "Checking and fixing database structure...\n";
        
        // 1. Check and fix users table
        echo "Checking users table...\n";
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'id'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("Users table is missing or has incorrect structure");
        }
        
        // 2. Check and fix payments table
        echo "Checking payments table...\n";
        $columns_to_add = [
            'user_id' => "INT(11) NOT NULL AFTER `id`",
            'amount' => "DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `user_id`",
            'status' => "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `amount`",
            'method' => "VARCHAR(50) DEFAULT NULL AFTER `status`",
            'payment_method' => "VARCHAR(50) DEFAULT NULL AFTER `method`",
            'payment_type' => "ENUM('water_bill','subscription','other') DEFAULT 'water_bill' AFTER `payment_method`",
            'transaction_id' => "VARCHAR(100) DEFAULT NULL AFTER `payment_type`",
            'receipt_image' => "VARCHAR(255) DEFAULT NULL AFTER `transaction_id`",
            'receipt_path' => "VARCHAR(255) DEFAULT NULL AFTER `receipt_image`",
            'bank_name' => "VARCHAR(100) DEFAULT NULL AFTER `receipt_path`",
            'transaction_date' => "DATETIME DEFAULT NULL AFTER `bank_name`",
            'notes' => "TEXT DEFAULT NULL AFTER `transaction_date`",
            'admin_notes' => "TEXT DEFAULT NULL AFTER `notes`"
        ];
        
        // Check each column and add if missing
        foreach ($columns_to_add as $column => $definition) {
            $stmt = $pdo->prepare("
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'payments' 
                AND COLUMN_NAME = ?
            ");
            $stmt->execute([DB_NAME, $column]);
            
            if ($stmt->rowCount() === 0) {
                echo "Adding column '$column' to payments table...\n";
                $pdo->exec("ALTER TABLE payments ADD COLUMN `$column` $definition");
            }
        }
        
        // 3. Create notifications table if it doesn't exist
        echo "Checking notifications table...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11) NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY is_read (is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // 4. Create user_subscriptions table if it doesn't exist
        echo "Checking user_subscriptions table...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_subscriptions (
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11) NOT NULL,
                payment_id INT(11) DEFAULT NULL,
                plan_name VARCHAR(100) NOT NULL,
                start_date DATETIME NOT NULL,
                end_date DATETIME NOT NULL,
                status ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY payment_id (payment_id),
                KEY status (status),
                KEY end_date (end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Commit the transaction
        $pdo->commit();
        
        echo "\n✅ Database structure verified and updated successfully!\n";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
