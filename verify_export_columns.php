<?php
// Verify database columns for export functionality
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Check users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check payments table
    $stmt = $pdo->query("SHOW COLUMNS FROM payments");
    $paymentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Required columns for users table
    $requiredUserColumns = ['id', 'first_name', 'last_name', 'email', 'flat_number'];
    $missingUserColumns = array_diff($requiredUserColumns, $userColumns);
    
    // Required columns for payments table
    $requiredPaymentColumns = ['id', 'user_id', 'amount', 'method', 'status', 'created_at', 'reference'];
    $missingPaymentColumns = array_diff($requiredPaymentColumns, $paymentColumns);
    
    // Output results
    echo "<h2>Database Structure Verification</h2>";
    
    echo "<h3>Users Table</h3>";
    if (empty($missingUserColumns)) {
        echo "<p style='color:green;'>✓ All required columns exist in users table</p>";
    } else {
        echo "<p style='color:red;'>✗ Missing columns in users table: " . implode(', ', $missingUserColumns) . "</p>";
    }
    
    echo "<h3>Payments Table</h3>";
    if (empty($missingPaymentColumns)) {
        echo "<p style='color:green;'>✓ All required columns exist in payments table</p>";
    } else {
        echo "<p style='color:red;'>✗ Missing columns in payments table: " . implode(', ', $missingPaymentColumns) . "</p>";
    }
    
    // Test the export query
    echo "<h3>Export Query Test</h3>";
    try {
        $sql = "SELECT p.id, p.user_id, p.amount, p.method, p.status, p.created_at, p.reference,
                       u.first_name, u.last_name, u.email, u.flat_number
                FROM payments p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT 1";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<p style='color:green;'>✓ Export query executed successfully</p>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        } else {
            echo "<p style='color:orange;'>ℹ No payment records found to test export</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ Export query failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Database connection error: " . $e->getMessage() . "</p>";
}
?>
