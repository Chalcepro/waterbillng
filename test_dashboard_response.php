<?php
/**
 * Test script to check dashboard API response
 */

// Start session and set user ID for testing
session_start();
$_SESSION['user_id'] = 2; // Testing with user ID 2
$_SESSION['role'] = 'user';

// Include necessary files
require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

// Include the dashboard data script to get the response
ob_start();
include 'api/user/dashboard-data.php';
$response = json_decode(ob_get_clean(), true);

// Output the response in a readable format
echo "<h2>Dashboard API Response</h2>";
echo "<pre>";
print_r($response);
echo "</pre>";

// Check for required fields
$requiredFields = [
    'success',
    'subscription_status',
    'subscription_start',
    'subscription_end',
    'raw_subscription_start',
    'raw_subscription_end',
    'days_remaining',
    'is_expired',
    'has_subscription'
];

echo "<h3>Field Check</h3>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Exists</th><th>Value</th></tr>";

foreach ($requiredFields as $field) {
    $exists = array_key_exists($field, $response);
    $value = $exists ? htmlspecialchars(print_r($response[$field], true)) : 'NOT FOUND';
    $color = $exists ? 'green' : 'red';
    echo "<tr>";
    echo "<td><strong>$field</strong></td>";
    echo "<td style='color: $color'>" . ($exists ? '✓' : '✗') . "</td>";
    echo "<td>$value</td>";
    echo "</tr>";
}

echo "</table>";

// Check subscription data
if (isset($response['subscription'])) {
    echo "<h3>Subscription Data</h3>";
    echo "<pre>";
    print_r($response['subscription']);
    echo "</pre>";
}
?>
