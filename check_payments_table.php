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

    // Check if payments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    if ($stmt->rowCount() > 0) {
        echo "\nPayments table exists. Checking structure...\n";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE `payments`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nPayments table structure:\n";
        echo str_pad("Column", 30) . "| Type\n";
        echo str_repeat("-", 60) . "\n";
        
        $required_columns = [
            'id' => false,
            'user_id' => false,
            'amount' => false,
            'payment_method' => false,
            'transaction_id' => false,
            'status' => false,
            'created_at' => false,
            'updated_at' => false,
            'receipt_path' => false,
            'notes' => false,
            'admin_notes' => false,
            'bank_name' => false,
            'transaction_date' => false,
            'payment_type' => false
        ];
        
        foreach ($columns as $column) {
            $col_name = $column['Field'];
            $col_type = $column['Type'];
            echo str_pad($col_name, 30) . "| $col_type\n";
            
            if (array_key_exists($col_name, $required_columns)) {
                $required_columns[$col_name] = true;
            }
        }
        
        // Check for missing columns
        $missing_columns = array_filter($required_columns, function($exists) { return !$exists; });
        
        if (count($missing_columns) > 0) {
            echo "\nMissing required columns:\n";
            foreach (array_keys($missing_columns) as $col) {
                echo "- $col\n";
            }
        } else {
            echo "\nAll required columns exist in the payments table.\n";
        }
    } else {
        echo "\nPayments table does not exist.\n";
    }
    
    // Check if subscriptions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'subscriptions'");
    if ($stmt->rowCount() > 0) {
        echo "\nSubscriptions table exists.\n";
    } else {
        echo "\nSubscriptions table does not exist.\n";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
