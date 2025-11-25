<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

// Test each query individually
try {
    echo "=== Testing Database Connection ===\n";
    $test = $pdo->query("SELECT 1")->fetch();
    echo "✓ Database connection successful\n\n";
    
    // Test 1: Users count
    echo "=== Testing Users Count ===\n";
    $usersQuery = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
    if ($usersQuery === false) {
        echo "❌ Users query failed: " . print_r($pdo->errorInfo(), true) . "\n";
    } else {
        $totalUsers = $usersQuery->fetch(PDO::FETCH_ASSOC)['total'];
        echo "✓ Total users (non-admin): $totalUsers\n";
    }
    
    // Test 2: Pending payments
    echo "\n=== Testing Pending Payments ===\n";
    $pendingQuery = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
    if ($pendingQuery === false) {
        echo "❌ Pending payments query failed: " . print_r($pdo->errorInfo(), true) . "\n";
    } else {
        $pendingPayments = $pendingQuery->fetch(PDO::FETCH_ASSOC)['total'];
        echo "✓ Pending payments: $pendingPayments\n";
    }
    
    // Test 3: Active subscriptions
    echo "\n=== Testing Active Subscriptions ===\n";
    $subsQuery = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM user_subscriptions WHERE status = 'active'");
    if ($subsQuery === false) {
        echo "❌ Active subscriptions query failed: " . print_r($pdo->errorInfo(), true) . "\n";
    } else {
        $activeSubs = $subsQuery->fetch(PDO::FETCH_ASSOC)['total'];
        echo "✓ Active subscriptions: $activeSubs\n";
    }
    
    // Test 4: Open fault reports
    echo "\n=== Testing Open Fault Reports ===\n";
    $faultsQuery = $pdo->query("SELECT COUNT(*) as total FROM fault_reports WHERE status = 'open'");
    if ($faultsQuery === false) {
        echo "❌ Open faults query failed: " . print_r($pdo->errorInfo(), true) . "\n";
    } else {
        $openFaults = $faultsQuery->fetch(PDO::FETCH_ASSOC)['total'];
        echo "✓ Open fault reports: $openFaults\n";
    }
    
    // Test 5: Recent payments
    echo "\n=== Testing Recent Payments ===\n";
    $recentPaymentsQuery = $pdo->query("
        SELECT 
            p.id,
            CONCAT(u.first_name, ' ', u.surname) as user_name,
            p.amount,
            p.method,
            p.created_at,
            p.status
        FROM payments p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    
    if ($recentPaymentsQuery === false) {
        echo "❌ Recent payments query failed: " . print_r($pdo->errorInfo(), true) . "\n";
    } else {
        $recentPayments = $recentPaymentsQuery->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Found " . count($recentPayments) . " recent payments\n";
        if (count($recentPayments) > 0) {
            echo "Sample payment: " . json_encode($recentPayments[0], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
    }
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
