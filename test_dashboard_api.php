<?php
/**
 * Test script to check the dashboard API response
 */

// Start session to simulate being logged in
session_start();
$_SESSION['user_id'] = 2; // Hardcoded for testing
$_SESSION['role'] = 'user'; // Make sure role is set

// Include the dashboard data script
require_once 'api/user/dashboard-data.php';

// The dashboard-data.php will output JSON, but let's make it more readable
echo "<pre>";
$response = json_decode(ob_get_clean(), true);
print_r($response);
?>
