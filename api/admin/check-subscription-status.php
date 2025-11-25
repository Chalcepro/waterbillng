<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    echo "=== Subscription and Payment Status Report ===\n\n";
    
    // Get all users with their subscription and payment status
    $query = "
        SELECT 
            u.id as user_id,
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.flat_no,
            u.subscription_status,
            u.subscription_end_date,
            us.status as user_subscription_status,
            us.end_date as user_subscription_end,
            (
                SELECT status 
                FROM payments 
                WHERE user_id = u.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_payment_status,
            (
                SELECT created_at 
                FROM payments 
                WHERE user_id = u.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_payment_date,
            (
                SELECT amount 
                FROM payments 
                WHERE user_id = u.id 
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_payment_amount
        FROM users u
        LEFT JOIN user_subscriptions us ON u.id = us.user_id
        WHERE u.role = 'user'
        ORDER BY u.id
    ";
    
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $activeSubscriptions = 0;
    
    foreach ($users as $user) {
        echo "\n=== User ID: {$user['user_id']} ===\n";
        echo "Name: {$user['full_name']} ({$user['username']})\n";
        echo "Flat No: {$user['flat_no']}\n";
        
        // Check subscription status from users table
        echo "\nUser Table Status:\n";
        echo "- Subscription: {$user['subscription_status']}\n";
        echo "- End Date: {$user['subscription_end_date']}\n";
        
        // Check user_subscriptions table
        echo "\nUser Subscriptions Table:\n";
        if ($user['user_subscription_status']) {
            echo "- Status: {$user['user_subscription_status']}\n";
            echo "- End Date: {$user['user_subscription_end']}\n";
            
            // Check if subscription is active
            $endDate = new DateTime($user['user_subscription_end']);
            $now = new DateTime();
            $isActive = ($endDate > $now);
            
            if ($isActive) {
                $activeSubscriptions++;
                echo "- ACTIVE SUBSCRIPTION (Expires in: " . $now->diff($endDate)->format('%a days') . ")\n";
            } else {
                echo "- EXPIRED SUBSCRIPTION\n";
            }
        } else {
            echo "- No subscription record found\n";
        }
        
        // Check payment status
        echo "\nLatest Payment:\n";
        if ($user['last_payment_status']) {
            echo "- Status: {$user['last_payment_status']}\n";
            echo "- Date: {$user['last_payment_date']}\n";
            echo "- Amount: {$user['last_payment_amount']}\n";
        } else {
            echo "- No payment history found\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
    }
    
    // Summary
    echo "\n=== Summary ===\n";
    echo "Total Users: " . count($users) . "\n";
    echo "Active Subscriptions: $activeSubscriptions\n";
    
    // Check for any inconsistencies
    $inconsistent = array_filter($users, function($user) {
        $endDate = $user['user_subscription_end'] ? new DateTime($user['user_subscription_end']) : null;
        $now = new DateTime();
        $isActive = $endDate && ($endDate > $now);
        
        return ($user['subscription_status'] === 'active') !== $isActive;
    });
    
    if (count($inconsistent) > 0) {
        echo "\n⚠️ Warning: Found " . count($inconsistent) . " users with inconsistent subscription status\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "PDO Error: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}
?>
