<?php
// Test script to verify dashboard API endpoint
require 'config.php';

// Set test user ID
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';

// Include the dashboard data script
ob_start();
require 'api/user/dashboard-data.php';
$output = ob_get_clean();

// Output the result
echo "<h2>Dashboard API Test</h2>";
echo "<pre>";
$data = json_decode($output, true);
print_r($data);

echo "\n\n<hr>\n<h3>Raw Response:</h3>";
var_dump($output);
?>
