<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

// Get all subscriptions with user details
$query = "
    SELECT 
        us.*,
        u.username,
        CONCAT(u.first_name, ' ', u.last_name) as user_name,
        u.email,
        u.phone,
        u.flat_no,
        p.amount,
        p.status as payment_status
    FROM user_subscriptions us
    LEFT JOIN users u ON us.user_id = u.id
    LEFT JOIN payments p ON us.payment_id = p.id
    ORDER BY us.end_date DESC
";

try {
    $stmt = $pdo->query($query);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== All Subscriptions ===\n";
    foreach ($subscriptions as $sub) {
        echo "\n=== Subscription ID: {$sub['id']} ===\n";
        echo "User: {$sub['user_name']} ({$sub['username']})\n";
        echo "Plan: {$sub['plan_name']}\n";
        echo "Status: {$sub['status']}\n";
        echo "Start Date: {$sub['start_date']}\n";
        echo "End Date: {$sub['end_date']}\n";
        echo "Payment Status: {$sub['payment_status']}\n";
        echo "Amount: {$sub['amount']}\n";
        echo "Flat No: {$sub['flat_no']}\n";
        
        // Check if subscription is active
        $now = new DateTime();
        $endDate = new DateTime($sub['end_date']);
        $isActive = ($sub['status'] === 'active' && $endDate > $now);
        
        echo "Is Active: " . ($isActive ? 'Yes' : 'No') . "\n";
        if (!$isActive) {
            echo "Reason: ";
            if ($sub['status'] !== 'active') {
                echo "Status is '{$sub['status']}', not 'active'\n";
            } else {
                echo "End date has passed\n";
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
