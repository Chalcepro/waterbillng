<?php
// Include the database configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if payments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    if ($stmt->rowCount() === 0) {
        die("The 'payments' table does not exist in the database.\n");
    }
    
    // Get the structure of the payments table
    $stmt = $pdo->query("DESCRIBE payments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Payments Table Structure ===\n";
    echo str_pad("Column", 30) . str_pad("Type", 30) . str_pad("Null", 10) . "Default\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 30) . 
             str_pad($column['Type'], 30) . 
             str_pad($column['Null'], 10) . 
             ($column['Default'] ?? 'NULL') . "\n";
    }
    
    // Check for required columns
    $required_columns = [
        'id' => false,
        'user_id' => false,
        'amount' => false,
        'status' => false,
        'method' => false,
        'payment_type' => false,
        'transaction_id' => false,
        'notes' => false,
        'admin_notes' => false,
        'created_at' => false,
        'updated_at' => false
    ];
    
    $missing_columns = [];
    
    foreach ($columns as $column) {
        $col_name = $column['Field'];
        if (array_key_exists($col_name, $required_columns)) {
            $required_columns[$col_name] = true;
        }
    }
    
    // Check which required columns are missing
    foreach ($required_columns as $col => $exists) {
        if (!$exists) {
            $missing_columns[] = $col;
        }
    }
    
    if (!empty($missing_columns)) {
        echo "\n⚠️  Missing required columns: " . implode(', ', $missing_columns) . "\n";
    } else {
        echo "\n✅ All required columns exist in the payments table.\n";
    }
    
    // Check if there are any pending payments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
    $pending_count = $stmt->fetch()['count'];
    
    echo "\nNumber of pending payments: $pending_count\n";
    
    if ($pending_count > 0) {
        $stmt = $pdo->query("SELECT id, user_id, amount, status, method, payment_type, created_at FROM payments WHERE status = 'pending' LIMIT 5");
        $pending_payments = $stmt->fetchAll();
        
        echo "\nSample pending payments:\n";
        foreach ($pending_payments as $payment) {
            echo "- ID: {$payment['id']}, User ID: {$payment['user_id']}, Amount: {$payment['amount']}, Method: {$payment['method']}, Created: {$payment['created_at']}\n";
        }
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
