<?php
// Include the database configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Get all payments
    $stmt = $pdo->query("SELECT id, user_id, amount, status, method, payment_type, created_at FROM payments ORDER BY id DESC LIMIT 10");
    $payments = $stmt->fetchAll();
    
    if (empty($payments)) {
        die("No payments found in the database.\n");
    }
    
    echo "=== Recent Payments ===\n";
    echo str_pad("ID", 5) . str_pad("User ID", 10) . str_pad("Amount", 15) . 
         str_pad("Status", 15) . str_pad("Method", 15) . str_pad("Type", 15) . "Created At\n";
    echo str_repeat("-", 90) . "\n";
    
    foreach ($payments as $payment) {
        echo str_pad($payment['id'], 5) . 
             str_pad($payment['user_id'], 10) . 
             str_pad($payment['amount'], 15) . 
             str_pad($payment['status'], 15) . 
             str_pad($payment['method'] ?? 'N/A', 15) . 
             str_pad($payment['payment_type'] ?? 'N/A', 15) . 
             $payment['created_at'] . "\n";
    }
    
    // Get pending payments count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
    $pending_count = $stmt->fetch()['count'];
    
    echo "\nTotal pending payments: $pending_count\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
