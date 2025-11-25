<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the request method to POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Set required headers
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost';

// Test data
$testData = [
    'username' => 'testuser' . time(),
    'first_name' => 'Test',
    'surname' => 'User',
    'flat_no' => 'T' . rand(100, 999),
    'email' => 'test' . time() . '@example.com',
    'phone' => '080' . rand(10000000, 99999999),
    'password' => 'Test@1234',
    'confirm_password' => 'Test@1234',
    'role' => 'user'
];

// Set POST data
$_POST = $testData;

// Start output buffering
ob_start();

// Include the register script
require_once __DIR__ . '/auth/register.php';

// Get the output
$output = ob_get_clean();

// Output the result
echo "<pre>Test Data:\n";
print_r($testData);
echo "\n\nResponse:\n";
echo $output;
?>
