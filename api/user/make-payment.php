<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $amount = (int)($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? '';
    
    // Get min payment amount
    $minPayment = $pdo->query("SELECT value FROM system_settings WHERE name = 'min_payment'")->fetchColumn();
    $minPayment = $minPayment ? (int)$minPayment : 2000;
    
    // Validate amount
    if ($amount < $minPayment || $amount % $minPayment !== 0) {
        echo json_encode([
            'success' => false,
            'message' => "Amount must be a multiple of ₦" . number_format($minPayment)
        ]);
        exit;
    }
    
    if (empty($method)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please select a payment method'
        ]);
        exit;
    }
    
    // Save payment
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, method, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$userId, $amount, $method]);
    
    // Add notification for admin if function exists
    if (function_exists('add_notification')) {
        add_notification(1, 'payment', "User made a payment: ₦" . number_format($amount) . ", Method: $method");
    }
    
    // For Paystack payments, redirect to payment processor
    if ($method === 'paystack') {
        echo json_encode([
            'success' => true,
            'message' => 'Redirecting to payment gateway...',
            'redirect_url' => "../../api/paystack.php?amount=$amount"
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Payment submitted successfully! It will be reviewed by admin.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Payment failed. Please try again.'
    ]);
}
?>
