<?php
// Include the database configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Create a test payment
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            user_id, 
            amount, 
            status, 
            method, 
            payment_type, 
            transaction_id, 
            notes, 
            created_at, 
            updated_at
        ) VALUES (?, ?, 'pending', 'bank_transfer', 'water_bill', ?, CONCAT('Test payment created at ', NOW()), NOW(), NOW())
    ");
    
    // Use user ID 1 (assuming it's an admin) or get the first available user
    $user_id = 1;
    $amount = 5000.00;
    $transaction_id = 'TEST_' . time();
    
    $stmt->execute([$user_id, $amount, $transaction_id]);
    
    $payment_id = $pdo->lastInsertId();
    
    echo "✅ Created test payment with ID: $payment_id\n";
    echo "User ID: $user_id\n";
    echo "Amount: $amount\n";
    echo "Status: pending\n";
    echo "Transaction ID: $transaction_id\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
