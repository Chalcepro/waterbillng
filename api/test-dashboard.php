<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/includes/db_connect.php';

// Start session for testing
session_start();

// Simulate a logged-in user (replace with actual user ID you want to test)
$_SESSION['user_id'] = 2; // Change this to the user ID you registered with
$_SESSION['role'] = 'user';

// Include the dashboard data file
ob_start();
include __DIR__ . '/user/dashboard-data.php';
$output = ob_get_clean();

// Output the raw response
echo "=== Raw Dashboard Data ===\n";
echo $output;

// Try to decode and pretty print JSON if possible
$json = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\n=== Formatted JSON ===\n";
    print_r($json);
} else {
    echo "\n=== Not a valid JSON response ===\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

// Show session data
echo "\n=== Session Data ===\n";
print_r($_SESSION);

// Show any PHP errors that might have occurred
echo "\n=== PHP Errors ===\n";
print_r(error_get_last());
?>
