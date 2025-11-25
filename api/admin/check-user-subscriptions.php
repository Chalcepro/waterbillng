<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    echo "=== User Subscription Status ===\n\n";
    
    // Get all users with their subscription info
    $query = "
        SELECT 
            u.id,
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.flat_no,
            u.subscription_status,
            u.subscription_end_date,
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
        WHERE u.role = 'user'
        ORDER BY u.id
    ";
    
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "No users found.\n";
    } else {
        foreach ($users as $user) {
            echo "\n=== User ID: {$user['id']} ===\n";
            echo "Name: {$user['full_name']} ({$user['username']})\n";
            echo "Flat No: {$user['flat_no']}\n";
            echo "Subscription Status: {$user['subscription_status']}\n";
            echo "Subscription End Date: {$user['subscription_end_date']}\n";
            echo "Last Payment: {$user['last_payment_status']} - {$user['last_payment_amount']} on {$user['last_payment_date']}\n";
            
            // Check if should be active
            if ($user['subscription_status'] === 'active' && 
                $user['subscription_end_date'] && 
                strtotime($user['subscription_end_date']) > time()) {
                echo "✅ ACTIVE SUBSCRIPTION (Expires: {$user['subscription_end_date']})\n";
            } elseif ($user['last_payment_status'] === 'approved') {
                echo "⚠️ Has approved payment but subscription not marked as active\n";
            }
        }
    }
    
    // Show current server time for reference
    echo "\n=== Current Server Time ===\n";
    echo date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "PDO Error: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}
?>
