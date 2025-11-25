<?php
/**
 * Test script to check dashboard API response
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set test user ID (user ID 2 for testing)
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'user';

// Include the dashboard data script
ob_start();
include 'api/user/dashboard-data.php';
$response = ob_get_clean();
$data = json_decode($response, true);

// Output the response in a readable format
echo "<h2>Dashboard API Response</h2>";
echo "<pre>";
print_r($data);
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
    'has_subscription',
    'subscription_progress'
];

echo "<h3>Field Check</h3>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Exists</th><th>Value</th></tr>";

foreach ($requiredFields as $field) {
    $exists = array_key_exists($field, $data);
    $value = $exists ? htmlspecialchars(print_r($data[$field], true)) : 'NOT FOUND';
    $color = $exists ? 'green' : 'red';
    echo "<tr>";
    echo "<td><strong>$field</strong></td>";
    echo "<td style='color: $color'>" . ($exists ? '✓' : '✗') . "</td>";
    echo "<td>$value</td>";
    echo "</tr>";
}

echo "</table>";

// Check subscription data
if (isset($data['subscription'])) {
    echo "<h3>Subscription Data</h3>";
    echo "<pre>";
    print_r($data['subscription']);
    echo "</pre>";
}
?>
