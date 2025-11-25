<?php
// Test script to verify the approve-payment.php endpoint

// Test payment ID - make sure this is a pending payment in your database
$test_payment_id = 17; // Replace with an actual pending payment ID
$action = 'approve'; // or 'reject'
$notes = 'Test approval via API - ' . date('Y-m-d H:i:s');

// Start session to simulate admin login
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // Set to true in production with HTTPS
    'cookie_samesite' => 'Lax'
]);

// Simulate admin login
$_SESSION['user_id'] = 1; // Assuming 1 is an admin user ID
$_SESSION['role'] = 'admin';

// Save the session ID for the HTTP request
$session_id = session_id();

// End the session to avoid session locking
session_write_close();

// Prepare the request data
$data = [
    'payment_id' => $test_payment_id,
    'action' => $action,
    'notes' => $notes
];

// Use cURL for better error handling
$ch = curl_init('http://localhost/waterbill/api/admin/approve-payment.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Cookie: PHPSESSID=' . $session_id
]);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Create a stream to capture verbose output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Execute the request
$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get the verbose output
rewind($verbose);
$verboseLog = stream_get_contents($verbose);

// Close resources
curl_close($ch);
fclose($verbose);

// Output the results
echo "=== HTTP Status: $httpCode ===\n\n";

echo "=== Response Headers ===\n";
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
echo $headers . "\n";

echo "=== Response Body ===\n";
$body = substr($response, $header_size);
echo $body . "\n";

if ($error) {
    echo "\n=== cURL Error ===\n";
    echo $error . "\n";
}

echo "\n=== cURL Verbose Log ===\n";
echo $verboseLog . "\n";

// If we got a JSON response, decode it
$jsonResponse = json_decode($body, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\n=== Decoded JSON Response ===\n";
    print_r($jsonResponse);
}
?>
