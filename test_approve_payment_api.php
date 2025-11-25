<?php
// Include the database configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

// Test payment ID - replace with an existing pending payment ID
$test_payment_id = 17; // Using the test payment we just created
$action = 'approve'; // or 'reject'
$notes = 'Test approval via API - ' . date('Y-m-d H:i:s');

// Create the request data
$data = [
    'payment_id' => $test_payment_id,
    'action' => $action,
    'notes' => $notes
];

// Convert data to JSON
$json_data = json_encode($data);

// Set up the HTTP request options
$options = [
    'http' => [
        'header'  => [
            'Content-type: application/json',
            'X-Requested-With: XMLHttpRequest'
        ],
        'method'  => 'POST',
        'content' => $json_data,
        'ignore_errors' => true // To get the response even on error
    ]
];

// Create the stream context
$context = stream_context_create($options);

// The URL to the approve-payment endpoint
$url = 'http://localhost/waterbill/api/admin/approve-payment.php';

echo "Sending request to: $url\n";
echo "Payment ID: $test_payment_id\n";
echo "Action: $action\n\n";

// Send the request
$response = @file_get_contents($url, false, $context);

// Get the HTTP response code
$http_code = $http_response_header[0] ?? 'HTTP/1.1 500 Internal Server Error';

// Output the response
echo "=== Response ===\n";
echo "Status: $http_code\n";

if ($response === false) {
    $error = error_get_last();
    die("Request failed: " . ($error['message'] ?? 'Unknown error') . "\n");
}

echo "Response Body:\n";
$response_data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    // Pretty print JSON
    echo json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    // Check if the response indicates success
    if (isset($response_data['success']) && $response_data['success']) {
        echo "\n✅ Payment successfully {$action}d!\n";
    } else {
        echo "\n❌ Failed to {$action} payment. " . ($response_data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    // Not JSON, output as is
    echo $response . "\n";
}

// Verify the payment status in the database
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, status, admin_notes, updated_at FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "\n=== Database Verification ===\n";
        echo "Payment ID: " . $payment['id'] . "\n";
        echo "Status: " . $payment['status'] . "\n";
        echo "Updated At: " . $payment['updated_at'] . "\n";
        
        if (!empty($payment['admin_notes'])) {
            echo "\nAdmin Notes Preview:\n";
            $notes = explode("\n", $payment['admin_notes']);
            $preview = array_slice($notes, 0, 5);
            echo implode("\n", $preview);
            if (count($notes) > 5) {
                echo "\n... (" . (count($notes) - 5) . " more lines)";
            }
            echo "\n";
        }
    } else {
        echo "\n⚠️ Payment not found in database.\n";
    }
} catch (PDOException $e) {
    echo "\n⚠️ Database error: " . $e->getMessage() . "\n";
}
?>
