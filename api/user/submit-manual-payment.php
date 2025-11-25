<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();
require_once __DIR__ . '/../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Handle file upload if present
    $paymentProofPath = null;
    if (!empty($_FILES['payment_proof'])) {
        $uploadDir = __DIR__ . '/../../uploads/payment_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['payment_proof'];
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $paymentProofPath = '/waterbill/uploads/payment_proofs/' . $filename;
        } else {
            throw new Exception('Failed to upload payment proof');
        }
    }
    
    // Get form data
    $data = [
        'user_id' => $_SESSION['user_id'],
        'amount' => $_POST['amount'] ?? null,
        'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
        'payment_method' => $_POST['payment_method'] ?? 'bank_transfer',
        'transaction_reference' => $_POST['transaction_reference'] ?? null,
        'admin_notes' => $_POST['notes'] ?? null,
        'payment_proof' => $paymentProofPath
    ];
    
    // Validate required fields
    $required = ['amount', 'payment_method'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Insert into manual_payments table
    $stmt = $pdo->prepare("
        INSERT INTO manual_payments 
        (user_id, amount, payment_date, payment_method, transaction_reference, status, admin_notes, payment_proof)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    
    $stmt->execute([
        $data['user_id'],
        $data['amount'],
        $data['payment_date'],
        $data['payment_method'],
        $data['transaction_reference'],
        $data['admin_notes'],
        $data['payment_proof']
    ]);
    
    $paymentId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Manual payment submitted for review',
        'payment_id' => $paymentId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
