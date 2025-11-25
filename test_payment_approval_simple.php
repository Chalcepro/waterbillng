<?php
// Simple test for payment approval
require_once __DIR__ . '/api/includes/db_connect.php';

// Start session for admin authentication
session_start();

// Simulate admin login for testing
$_SESSION['user_id'] = 1; // Assuming 1 is the admin user ID
$_SESSION['role'] = 'admin';

try {
    $pdo = getDBConnection();
    
    // 1. Get an existing user
    $stmt = $pdo->query("SELECT id, email FROM users WHERE role = 'user' LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("No regular users found in the database. Please create a user first.\n");
    }
    
    $user_id = $user['id'];
    echo "Using user ID: $user_id\n";
    
    // 2. Create a test payment
    $stmt = $pdo->prepare("INSERT INTO payments 
        (user_id, amount, status, method, notes) 
        VALUES (?, 5000.00, 'pending', 'test', 'Test payment for approval')");
    $stmt->execute([$user_id]);
    $payment_id = $pdo->lastInsertId();
    
    echo "Created Test Payment ID: $payment_id\n";
    
    // 3. Test approving the payment
    echo "\nTesting payment approval...\n";
    
    // Prepare the request data
    $data = [
        'payment_id' => $payment_id,
        'action' => 'approve',
        'notes' => 'Test approval from script'
    ];
    
    // Call the approve-payment.php script
    $_POST = $data;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Start output buffering to capture the output
    ob_start();
    require __DIR__ . '/api/admin/approve-payment.php';
    $output = ob_get_clean();
    
    // Parse the JSON response
    $response = json_decode($output, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error in JSON response: " . json_last_error_msg() . "\n";
        echo "Raw output: $output\n";
        exit(1);
    }
    
    // Display the response
    echo "Response from approve-payment.php:\n";
    print_r($response);
    
    // 4. Verify the payment was updated
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $updated_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUpdated Payment Status: " . ($updated_payment['status'] ?? 'N/A') . "\n";
    
    // 5. Check the user's subscription status
    $stmt = $pdo->prepare("SELECT subscription_status, subscription_end_date FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUser Subscription Status: " . ($user['subscription_status'] ?? 'N/A') . "\n";
    echo "Subscription End Date: " . ($user['subscription_end_date'] ?? 'N/A') . "\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nTest completed.\n";
?>
