<?php
// Include the database configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

// Start session for authentication
session_start();

// Simulate admin login for testing
$_SESSION['user_id'] = 1; // Assuming 1 is the admin user ID
$_SESSION['role'] = 'admin';

// Test data - replace with an existing payment ID from your database
$test_payment_id = 1; // Change this to an existing payment ID

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // 1. First, get payment details
    echo "1. Fetching payment details for payment ID: $test_payment_id\n";
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        die("Error: Payment with ID $test_payment_id not found.\n");
    }
    
    echo "Payment found:\n";
    echo "- User ID: " . ($payment['user_id'] ?? 'N/A') . "\n";
    echo "- Amount: " . ($payment['amount'] ?? 'N/A') . "\n";
    echo "- Status: " . ($payment['status'] ?? 'N/A') . "\n";
    echo "- Method: " . ($payment['method'] ?? 'N/A') . "\n";
    echo "- Payment Method: " . ($payment['payment_method'] ?? 'N/A') . "\n";
    echo "- Transaction ID: " . ($payment['transaction_id'] ?? 'N/A') . "\n";
    echo "- Notes: " . ($payment['notes'] ?? 'N/A') . "\n";
    echo "- Admin Notes: " . ($payment['admin_notes'] ?? 'N/A') . "\n";
    
    // 2. Test approving the payment
    echo "\n2. Testing payment approval...\n";
    
    // Include the approve-payment.php file
    require_once __DIR__ . '/api/admin/approve-payment.php';
    
    // The approve-payment.php should now handle the approval
    // It will output JSON that we can capture and display
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
