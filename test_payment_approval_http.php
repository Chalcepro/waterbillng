<?php
// Test script to verify payment approval via HTTP request

// URL of the approve-payment endpoint
$url = 'http://localhost/waterbill/api/admin/approve-payment.php';

// Test payment ID - replace with an existing pending payment ID from your database
$test_payment_id = 1; // Change this to an existing pending payment ID

// Test data
$test_data = [
    'payment_id' => $test_payment_id,
    'action' => 'approve', // or 'reject' to test rejection
    'notes' => 'Test approval via script - ' . date('Y-m-d H:i:s')
];

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($test_data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest'
    ],
    CURLOPT_HEADER => true
]);

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get response headers and body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Close cURL
curl_close($ch);

// Output results
echo "=== Payment Approval Test ===\n";
echo "Payment ID: {$test_payment_id}\n";
echo "Action: {$test_data['action']}\n";
echo "HTTP Status: {$http_code}\n";
echo "Response Headers:\n{$headers}\n";
echo "Response Body:\n";
print_r(json_decode($body, true));

// Check if the payment was updated in the database
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/api/includes/db_connect.php';
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, status, admin_notes, updated_at FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        echo "\nUpdated Payment Record:\n";
        echo "ID: {$payment['id']}\n";
        echo "Status: {$payment['status']}\n";
        echo "Admin Notes: {$payment['admin_notes']}\n";
        echo "Last Updated: {$payment['updated_at']}\n";
    } else {
        echo "\nPayment not found in database.\n";
    }
    
} catch (PDOException $e) {
    echo "\nDatabase error: " . $e->getMessage() . "\n";
}
?>
