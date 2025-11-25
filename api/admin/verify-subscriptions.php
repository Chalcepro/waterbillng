<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    echo "=== Active Subscriptions Verification ===\n\n";
    
    // Get all active subscriptions with payment info
    $query = "
        SELECT 
            u.id as user_id,
            u.username,
            CONCAT(u.first_name, ' ', u.last_name) as full_name,
            u.flat_no,
            p.amount,
            p.status as payment_status,
            p.created_at as payment_date,
            us.status as subscription_status,
            us.end_date as subscription_end,
            DATEDIFF(us.end_date, NOW()) as days_remaining
        FROM users u
        LEFT JOIN user_subscriptions us ON u.id = us.user_id
        LEFT JOIN payments p ON u.id = p.user_id
        WHERE us.status = 'active' 
        AND p.status = 'approved'
        ORDER BY days_remaining DESC
    ";
    
    $stmt = $pdo->query($query);
    $activeSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activeSubscriptions)) {
        echo "No active subscriptions found.\n";
        
        // Check for any potential mismatches
        echo "\n=== Checking for Potential Issues ===\n";
        
        // Check users with active status but no valid subscription
        $mismatchQuery = "
            SELECT u.id, u.username, u.subscription_status, 
                   MAX(p.created_at) as last_payment,
                   MAX(CASE WHEN p.status = 'approved' THEN 1 ELSE 0 END) as has_approved_payment
            FROM users u
            LEFT JOIN payments p ON u.id = p.user_id
            WHERE u.subscription_status = 'active'
            GROUP BY u.id, u.username, u.subscription_status
        ";
        
        $mismatches = $pdo->query($mismatchQuery)->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($mismatches)) {
            echo "\nUsers marked as active but missing valid subscription:\n";
            foreach ($mismatches as $user) {
                echo "- ID: {$user['id']}, Username: {$user['username']}, ";
                echo "Last Payment: {$user['last_payment']}, ";
                echo "Has Approved Payment: " . ($user['has_approved_payment'] ? 'Yes' : 'No') . "\n";
            }
        } else {
            echo "No user subscription mismatches found.\n";
        }
    } else {
        echo "Found " . count($activeSubscriptions) . " active subscriptions:\n\n";
        
        foreach ($activeSubscriptions as $sub) {
            echo "User: {$sub['full_name']} ({$sub['username']})\n";
            echo "Flat No: {$sub['flat_no']}\n";
            echo "Payment: {$sub['amount']} ({$sub['payment_status']} on {$sub['payment_date']})\n";
            echo "Subscription: {$sub['subscription_status']} until {$sub['subscription_end']} ";
            echo "({$sub['days_remaining']} days remaining)\n\n";
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
