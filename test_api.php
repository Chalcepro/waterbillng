<?php
/**
 * Test script to check the subscription status
 */

require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

// Get user ID from query parameter or default to 2
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 2;

// Get user data
$userStmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

// Get subscription data
$subStmt = $pdo->prepare("
    SELECT * FROM subscriptions 
    WHERE user_id = ? 
    ORDER BY end_date DESC 
    LIMIT 1
");
$subStmt->execute([$userId]);
$subscription = $subStmt->fetch(PDO::FETCH_ASSOC);

// Get total paid
$paymentStmt = $pdo->prepare("
    SELECT SUM(amount) as total_paid 
    FROM payments 
    WHERE user_id = ? 
    AND status = 'approved'
");
$paymentStmt->execute([$userId]);
$totalPaid = (float)$paymentStmt->fetchColumn() ?: 0;

// Get system settings
$settingsStmt = $pdo->query("SELECT * FROM system_settings");
$settings = [];
while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Calculate subscription status
$subscriptionStatus = [
    'has_subscription' => false,
    'status' => 'inactive',
    'days_remaining' => 0,
    'progress' => 0,
    'is_expired' => true,
    'start_date' => null,
    'end_date' => null,
    'amount_paid' => $totalPaid,
    'months_covered' => 0,
    'min_payment_amount' => (float)($settings['min_payment_amount'] ?? 2000),
    'currency' => $settings['currency'] ?? 'NGN',
    'currency_symbol' => $settings['currency_symbol'] ?? '₦'
];

if ($subscription) {
    $now = new DateTime();
    $startDate = new DateTime($subscription['start_date']);
    $endDate = new DateTime($subscription['end_date']);
    $isExpired = ($endDate < $now);
    
    // Calculate days remaining and progress
    $totalDays = $startDate->diff($endDate)->days;
    $daysElapsed = $now->diff($startDate)->days;
    $daysRemaining = max(0, $now->diff($endDate)->days);
    $progress = min(100, max(0, ($daysElapsed / $totalDays) * 100));
    
    $subscriptionStatus = [
        'has_subscription' => true,
        'status' => $isExpired ? 'expired' : 'active',
        'days_remaining' => $daysRemaining,
        'progress' => round($progress, 2),
        'is_expired' => $isExpired,
        'start_date' => $subscription['start_date'],
        'end_date' => $subscription['end_date'],
        'amount_paid' => (float)$subscription['amount_paid'],
        'months_covered' => (int)$subscription['months_covered'],
        'min_payment_amount' => (float)($settings['min_payment_amount'] ?? 2000),
        'currency' => $settings['currency'] ?? 'NGN',
        'currency_symbol' => $settings['currency_symbol'] ?? '₦',
        'payment_id' => $subscription['payment_id']
    ];
}

// Output the data
echo "<h2>User #{$user['id']}: {$user['full_name']} ({$user['email']})</h2>";

echo "<h3>Subscription Status</h3>";
echo "<pre>";
print_r($subscriptionStatus);
echo "</pre>";

echo "<h3>Raw Subscription Data</h3>";
echo "<pre>";
print_r($subscription ?: 'No subscription found');
echo "</pre>";

echo "<h3>System Settings</h3>";
echo "<pre>";
print_r($settings);
echo "</pre>";
?>
