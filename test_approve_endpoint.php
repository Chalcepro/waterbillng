<?php
// Test script to verify the approve-payment.php endpoint

// Test payment ID - make sure this is a pending payment in your database
$test_payment_id = 17; // Replace with an actual pending payment ID
$action = 'approve'; // or 'reject'
$notes = 'Test approval via API - ' . date('Y-m-d H:i:s');

// Start session to simulate admin login
session_start();
$_SESSION['user_id'] = 1; // Assuming 1 is an admin user ID
$_SESSION['role'] = 'admin';

// Include the approve-payment.php file directly
require_once __DIR__ . '/api/admin/approve-payment.php';

// The script will terminate with exit, so we won't reach here
// But we'll add this just in case
echo "\nIf you see this, something went wrong. Check the output above for errors.\n";
?>
