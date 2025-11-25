<?php
// Test script for payment approval
require_once __DIR__ . '/api/includes/db_connect.php';

// Get a pending payment
$stmt = $pdo->query("SELECT * FROM payments WHERE status = 'pending' LIMIT 1");
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("No pending payments found to test with.\n");
}

echo "Testing approval for payment ID: " . $payment['id'] . "\n";

// Include the subscription manager
require_once __DIR__ . '/api/includes/subscription_manager.php';

// Test getSystemSetting
echo "Testing getSystemSetting...\n";
$minAmount = getSystemSetting($pdo, 'min_payment_amount', 2000);
echo "Minimum payment amount: " . $minAmount . "\n";

// Test updateUserSubscription
echo "\nTesting updateUserSubscription...\n";
try {
    $result = updateUserSubscription($pdo, [
        'id' => $payment['id'],
        'user_id' => $payment['user_id'],
        'amount' => $payment['amount'],
        'created_at' => $payment['created_at']
    ]);
    
    echo "updateUserSubscription result: " . ($result ? 'Success' : 'Failed') . "\n";
    
    // Check the user's subscription status
    $stmt = $pdo->prepare("SELECT subscription_status, subscription_end_date FROM users WHERE id = ?");
    $stmt->execute([$payment['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User subscription status: " . ($user['subscription_status'] ?? 'unknown') . "\n";
    echo "Subscription end date: " . ($user['subscription_end_date'] ?? 'not set') . "\n";
    
} catch (Exception $e) {
    echo "Error in updateUserSubscription: " . $e->getMessage() . "\n";
}

// Test approve-payment.php via HTTP
$url = 'http://' . $_SERVER['HTTP_HOST'] . '/waterbill/api/admin/approve-payment.php';
$data = [
    'payment_id' => $payment['id'],
    'action' => 'approve',
    'notes' => 'Test approval from script'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n" .
                    "X-Requested-With: XMLHttpRequest\r\n" .
                    "Cookie: " . session_name() . '=' . session_id() . "\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "\nError calling approve-payment.php\n";
    print_r(error_get_last());
} else {
    echo "\nResponse from approve-payment.php:\n";
    print_r(json_decode($result, true));
}
