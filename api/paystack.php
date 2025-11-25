<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();
require_once __DIR__ . '/../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Please log in first']);
    exit;
}

// Paystack payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['reference']) || !isset($input['amount'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }
    
    $reference = $input['reference'];
    $user_id = $_SESSION['user_id'];
    $amount = $input['amount'] * 100; // Convert to kobo
    
    // Verify transaction with Paystack
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Payment verification failed: ' . $err]);
        exit;
    }
    
    $result = json_decode($response);
    
    if (!$result || !$result->status) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Invalid response from payment provider']);
        exit;
    }
    
    if ($result->status && $result->data && $result->data->status === 'success') {
        try {
            // Save payment to database
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, status, method, transaction_id) 
                                  VALUES (?, ?, 'approved', 'paystack', ?)");
            $stmt->execute([$user_id, $amount / 100, $reference]);
            
            // Update subscription
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, start_date, end_date, status) 
                                  VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active') 
                                  ON DUPLICATE KEY UPDATE end_date = DATE_ADD(end_date, INTERVAL 30 DAY), status = 'active'");
            $stmt->execute([$user_id]);
            
            echo json_encode(['status' => 'success', 'message' => 'Payment successful']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Payment verification failed']);
    }
}
?>