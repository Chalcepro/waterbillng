<?php
/**
 * Test script to check dashboard API response with direct DB access
 */

// Disable error display for production
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
$db_host = 'localhost';
$db_name = 'waterbill_db';
$db_user = 'root';
$db_pass = '';

// Create database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Simulate user ID 2 (for testing)
    $userId = 2;
    
    // Get user data
    $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("User not found");
    }
    
    // Get subscription data
    $stmt = $pdo->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total approved payments
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_paid 
        FROM payments 
        WHERE user_id = ? 
        AND status = 'approved'
    ");
    $stmt->execute([$userId]);
    $totalPaid = (float)$stmt->fetchColumn() ?: 0;
    
    // Get system settings
    $settingsStmt = $pdo->query("SELECT * FROM system_settings");
    $settings = [];
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'user_id' => $user['id'],
        'user_name' => $user['full_name'],
        'user_email' => $user['email'],
        'total_paid' => $totalPaid,
        'pending_payments' => 0, // You can add this if needed
        'settings' => $settings,
        'subscription' => null,
        'has_subscription' => false,
        'is_expired' => true,
        'subscription_status' => 'Inactive',
        'subscription_start' => null,
        'subscription_end' => null,
        'days_remaining' => 0,
        'subscription_progress' => 0
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
        
        $response['subscription'] = $subscription;
        $response['has_subscription'] = true;
        $response['is_expired'] = $isExpired;
        $response['subscription_status'] = $isExpired ? 'Expired' : 'Active';
        $response['subscription_start'] = $subscription['start_date'];
        $response['subscription_end'] = $subscription['end_date'];
        $response['raw_subscription_start'] = $subscription['start_date'];
        $response['raw_subscription_end'] = $subscription['end_date'];
        $response['days_remaining'] = $daysRemaining;
        $response['subscription_progress'] = round($progress, 2);
        $response['amount_paid'] = (float)$subscription['amount_paid'];
        $response['months_covered'] = (int)$subscription['months_covered'];
    }
    
    // Output the response
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Handle database connection error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
