<?php
// Verify payment and subscription status
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    $pdo = getDBConnection();
    
    // Get the latest payment
    $stmt = $pdo->query("SELECT * FROM payments ORDER BY id DESC LIMIT 1");
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        die("No payments found in the database.\n");
    }
    
    echo "Latest Payment Details:\n";
    echo "- ID: " . $payment['id'] . "\n";
    echo "- User ID: " . $payment['user_id'] . "\n";
    echo "- Amount: " . $payment['amount'] . "\n";
    echo "- Status: " . $payment['status'] . "\n";
    echo "- Method: " . $payment['method'] . "\n";
    echo "- Is Subscription Payment: " . ($payment['is_subscription_payment'] ? 'Yes' : 'No') . "\n";
    echo "- Created: " . $payment['created_at'] . "\n";
    echo "- Updated: " . $payment['updated_at'] . "\n";
    
    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$payment['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "\nUser Details:\n";
        echo "- Name: " . ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') . "\n";
        echo "- Email: " . $user['email'] . "\n";
        echo "- Subscription Status: " . ($user['subscription_status'] ?? 'N/A') . "\n";
        echo "- Subscription End Date: " . ($user['subscription_end_date'] ?? 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nVerification complete.\n";
?>
