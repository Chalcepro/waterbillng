<?php
// Include config and start session
require 'config.php';
session_start();

// Set test user
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';

// Include dashboard data
ob_start();
require 'api/user/dashboard-data.php';
$output = ob_get_clean();

// Output the result
echo "<pre>";
print_r(json_decode($output, true));
echo "</pre>";
