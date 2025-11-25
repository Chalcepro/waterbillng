<?php
// Test script to verify session and database operations

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax'
]);

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Include database connection
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

// Test payment ID
$test_payment_id = 17;
$action = 'approve';
$notes = 'Test approval - ' . date('Y-m-d H:i:s');

// Log session data
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'test_payment_id' => $test_payment_id,
    'action' => $action,
    'notes' => $notes
];

// 1. First, check if the payment exists
try {
    $pdo = getDBConnection();
    
    // Check payment
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        die("Error: Payment #{$test_payment_id} not found.\n");
    }
    
    echo "=== Current Payment Status ===\n";
    echo "Payment ID: " . $payment['id'] . "\n";
    echo "Status: " . $payment['status'] . "\n";
    echo "User ID: " . $payment['user_id'] . "\n";
    echo "Amount: " . $payment['amount'] . "\n\n";
    
    // 2. Update payment status directly
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $now = date('Y-m-d H:i:s');
    
    // Prepare the admin notes
    $admin_notes = "[{$now}] {$action}d by admin: {$notes}";
    if (!empty($payment['admin_notes'])) {
        $admin_notes = $payment['admin_notes'] . "\n" . $admin_notes;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Update payment status
        $update_sql = "
            UPDATE payments 
            SET status = :status,
                admin_notes = :admin_notes,
                updated_at = :updated_at
            WHERE id = :id
        ";
        
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([
            ':status' => $new_status,
            ':admin_notes' => $admin_notes,
            ':updated_at' => $now,
            ':id' => $test_payment_id
        ]);
        
        // Commit the transaction
        $pdo->commit();
        
        echo "✅ Payment status updated to: {$new_status}\n";
        
        // Verify the update
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$test_payment_id]);
        $updated_payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n=== Updated Payment ===\n";
        echo "Status: " . $updated_payment['status'] . "\n";
        echo "Updated At: " . $updated_payment['updated_at'] . "\n";
        
        if (!empty($updated_payment['admin_notes'])) {
            echo "\nAdmin Notes Preview:\n";
            $notes = explode("\n", $updated_payment['admin_notes']);
            $preview = array_slice($notes, 0, 5);
            echo implode("\n", $preview);
            if (count($notes) > 5) {
                echo "\n... (" . (count($notes) - 5) . " more lines)";
            }
            echo "\n";
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

// Log the result
$logData['result'] = 'success';
file_put_contents(__DIR__ . '/logs/payment_test.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

echo "\nTest completed. Check logs for details.\n";
?>
