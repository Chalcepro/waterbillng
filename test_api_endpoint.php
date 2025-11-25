<?php
// Test script to verify the approve-payment.php endpoint via HTTP POST

// Test payment ID - make sure this is a pending payment in your database
$test_payment_id = 17; // Replace with an actual pending payment ID
$action = 'approve'; // or 'reject'
$notes = 'Test approval via API - ' . date('Y-m-d H:i:s');

// Create a stream context for the HTTP request
$url = 'http://localhost/waterbill/api/admin/approve-payment.php';
$data = [
    'payment_id' => $test_payment_id,
    'action' => $action,
    'notes' => $notes
];

$options = [
    'http' => [
        'header'  => [
            'Content-type: application/x-www-form-urlencoded',
            'Cookie: PHPSESSID=' . session_id()
        ],
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];

// Start session to simulate admin login
session_start();
$_SESSION['user_id'] = 1; // Assuming 1 is an admin user ID
$_SESSION['role'] = 'admin';

// Save session data
session_write_close();

// Make the request
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

// Output the result
echo "=== API Response ===\n";
echo $result;

// Check for errors
if ($result === FALSE) {
    echo "\n=== Error Details ===\n";
    print_r(error_get_last());
}
?>
