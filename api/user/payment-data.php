<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Get min payment amount
    $minPayment = $pdo->query("SELECT value FROM system_settings WHERE name = 'min_payment'")->fetchColumn();
    $minPayment = $minPayment ? (int)$minPayment : 2000;
    
    // Get user subscription end date
    $stmt = $pdo->prepare("SELECT end_date FROM subscriptions WHERE user_id = ? ORDER BY end_date DESC LIMIT 1");
    $stmt->execute([$userId]);
    $subscriptionEnd = $stmt->fetchColumn();
    
    // Calculate subscription status
    $subscriptionStatus = 'Inactive';
    $subscriptionEndFormatted = 'N/A';
    
    if ($subscriptionEnd) {
        $endDate = new DateTime($subscriptionEnd);
        $now = new DateTime();
        
        if ($endDate > $now) {
            $subscriptionStatus = 'Active';
        } else {
            $subscriptionStatus = 'Expired';
        }
        $subscriptionEndFormatted = $endDate->format('M d, Y');
    }
    
    // Calculate payment options
    $paymentOptions = [
        '1 Month' => $minPayment,
        '3 Months' => $minPayment * 3,
        '6 Months' => $minPayment * 6,
        '1 Year' => $minPayment * 12
    ];
    
    echo json_encode([
        'success' => true,
        'min_payment' => $minPayment,
        'subscription_status' => $subscriptionStatus,
        'subscription_end' => $subscriptionEndFormatted,
        'payment_options' => $paymentOptions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load payment data'
    ]);
}
?>
