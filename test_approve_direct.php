<?php
/**
 * Direct test for approve-payment.php
 * This script tests the payment approval functionality directly
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // Set to true in production with HTTPS
    'cookie_samesite' => 'Lax'
]);

// Simulate admin login
$_SESSION['user_id'] = 1; // Assuming 1 is an admin user ID
$_SESSION['role'] = 'admin';

// Test payment ID - using the pending payment ID we found
$test_payment_id = 14; // This is the pending payment ID from the database
$action = 'approve'; // or 'reject'
$notes = 'Test approval via direct script - ' . date('Y-m-d H:i:s');

// Set up the input data as if it came from a form/API
$_POST = [
    'payment_id' => $test_payment_id,
    'action' => $action,
    'notes' => $notes
];

// Include the approve-payment.php file directly
require_once __DIR__ . '/api/admin/approve-payment.php';

// The script will terminate with exit, so we won't reach here
echo "\nIf you see this, something went wrong. Check the output above for errors.\n";
?>
