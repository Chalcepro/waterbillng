<?php
// Script to find a pending payment ID for testing

// Include database connection
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

// Find a pending payment
$stmt = $pdo->query("SELECT id, user_id, amount, reference, created_at FROM payments WHERE status = 'pending' LIMIT 1");
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($payment) {
    echo "Found pending payment:\n";
    echo "ID: " . $payment['id'] . "\n";
    echo "User ID: " . $payment['user_id'] . "\n";
    echo "Amount: " . $payment['amount'] . "\n";
    echo "Reference: " . $payment['reference'] . "\n";
    echo "Created At: " . $payment['created_at'] . "\n";
    
    // Output the command to test approval
    echo "\nTo test approval, run:\n";
    echo "php test_approve_direct.php " . $payment['id'] . "\n";
} else {
    echo "No pending payments found in the database.\n";
    
    // Output the command to create a test payment
    echo "\nTo create a test payment, run:\n";
    echo "php create_test_payment.php\n";
}
?>
