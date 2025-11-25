<?php
// Include database connection
require 'config.php';

// Get user ID from session or use default for testing
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

// Get subscription data
$stmt = $pdo->prepare("
    SELECT * FROM subscriptions 
    WHERE user_id = ?
    ORDER BY end_date DESC 
    LIMIT 1
");
$stmt->execute([$userId]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get payment data
$stmt = $pdo->prepare("
    SELECT * FROM payments 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$userId]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Output the data
echo "<h2>User Data</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h2>Subscription Data</h2>";
echo "<pre>";
print_r($subscription ?: 'No subscription found');
echo "</pre>";

echo "<h2>Latest Payment</h2>";
echo "<pre>";
print_r($payment ?: 'No payment found');
echo "</pre>";

// Output the current time for reference
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
?>
