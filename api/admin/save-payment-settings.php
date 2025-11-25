<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$minPaymentAmount = $input['min_payment_amount'] ?? null;

if ($minPaymentAmount === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Minimum payment amount is required']);
    exit;
}

try {
    // Save to system_settings table
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description, updated_at) 
        VALUES ('min_payment_amount', ?, 'Minimum payment amount required for subscriptions', NOW())
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ");
    $stmt->execute([$minPaymentAmount]);
    
    echo json_encode(['success' => true, 'message' => 'Payment settings saved successfully']);
    
} catch (PDOException $e) {
    error_log("Save payment settings error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>