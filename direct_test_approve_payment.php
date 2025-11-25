<?php
// Include the database configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test payment ID - replace with an existing pending payment ID
$test_payment_id = 17; // Using the test payment we created earlier
$action = 'approve'; // or 'reject'
$notes = 'Direct test approval - ' . date('Y-m-d H:i:s');

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // 1. First, check if the payment exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        die("Error: Payment #{$test_payment_id} not found.\n");
    }
    
    echo "=== Payment Details ===\n";
    echo "Payment ID: " . $payment['id'] . "\n";
    echo "User ID: " . $payment['user_id'] . "\n";
    echo "Amount: " . $payment['amount'] . "\n";
    echo "Status: " . $payment['status'] . "\n";
    echo "Method: " . ($payment['method'] ?? 'N/A') . "\n";
    echo "Payment Type: " . ($payment['payment_type'] ?? 'N/A') . "\n";
    echo "Transaction ID: " . ($payment['transaction_id'] ?? 'N/A') . "\n";
    echo "Created At: " . $payment['created_at'] . "\n";
    echo "Updated At: " . $payment['updated_at'] . "\n\n";
    
    // 2. Update the payment status directly
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $now = date('Y-m-d H:i:s');
    
    // Prepare the admin notes
    $admin_notes = "[{$now}] {$action}d by admin: {$notes}";
    if (!empty($payment['admin_notes'])) {
        $admin_notes = $payment['admin_notes'] . "\n" . $admin_notes;
    }
    
    // Update the payment
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = :status,
            admin_notes = :admin_notes,
            updated_at = :updated_at
        WHERE id = :id
    ");
    
    $params = [
        ':status' => $new_status,
        ':admin_notes' => $admin_notes,
        ':updated_at' => $now,
        ':id' => $test_payment_id
    ];
    
    echo "=== Executing Update ===\n";
    echo "Status: {$new_status}\n";
    echo "Admin Notes: " . substr($admin_notes, 0, 100) . (strlen($admin_notes) > 100 ? '...' : '') . "\n\n";
    
    // Execute the update
    $result = $stmt->execute($params);
    $rows_affected = $stmt->rowCount();
    
    if ($result === false) {
        $error = $stmt->errorInfo();
        throw new Exception("Database error: " . ($error[2] ?? 'Unknown error'));
    }
    
    echo "=== Update Result ===\n";
    echo "Success: " . ($result ? 'Yes' : 'No') . "\n";
    echo "Rows affected: " . $rows_affected . "\n\n";
    
    // 3. Verify the update
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $updated_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== Updated Payment ===\n";
    echo "Status: " . $updated_payment['status'] . "\n";
    echo "Updated At: " . $updated_payment['updated_at'] . "\n";
    
    if ($updated_payment['status'] === $new_status) {
        echo "\n✅ Payment successfully {$action}d!\n";
    } else {
        echo "\n❌ Failed to update payment status.\n";
    }
    
    // Show admin notes preview
    if (!empty($updated_payment['admin_notes'])) {
        echo "\n=== Admin Notes Preview ===\n";
        $notes = explode("\n", $updated_payment['admin_notes']);
        $preview = array_slice($notes, 0, 5);
        echo implode("\n", $preview);
        if (count($notes) > 5) {
            echo "\n... (" . (count($notes) - 5) . " more lines)";
        }
        echo "\n";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
