<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    echo "=== Checking user_subscriptions table ===\n";
    $subscriptions = $pdo->query("SELECT * FROM user_subscriptions")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo "No records found in user_subscriptions table.\n";
    } else {
        echo "Found " . count($subscriptions) . " records in user_subscriptions table.\n";
        echo "Sample record:\n";
        print_r($subscriptions[0]);
    }
    
    echo "\n=== Checking payments table ===\n";
    $payments = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "No records found in payments table.\n";
    } else {
        echo "Found " . count($payments) . " records in payments table. Showing latest 5:\n";
        foreach ($payments as $payment) {
            echo "\nPayment ID: {$payment['id']}\n";
            echo "User ID: {$payment['user_id']}\n";
            echo "Amount: {$payment['amount']}\n";
            echo "Status: {$payment['status']}\n";
            echo "Created: {$payment['created_at']}\n";
        }
    }
    
    // Check for any users with subscription_status = 'active' in users table
    echo "\n=== Checking users with active status ===\n";
    $activeUsers = $pdo->query("SELECT id, username, subscription_status, subscription_end_date FROM users WHERE subscription_status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activeUsers)) {
        echo "No users with active subscription status found.\n";
    } else {
        echo "Found " . count($activeUsers) . " users with active status:\n";
        foreach ($activeUsers as $user) {
            echo "- ID: {$user['id']}, Username: {$user['username']}, ";
            echo "End Date: {$user['subscription_end_date']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "PDO Error: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}
?>
