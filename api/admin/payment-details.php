<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

// Enable error reporting for debugging (commented out for production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate payment ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid payment ID is required']);
    exit;
}

$payment_id = (int)$_GET['id'];

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Prepare and execute query
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.first_name, u.last_name, u.email, u.phone, u.flat_no,
               CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }
    
    // Format the response
    $response = [
        'success' => true,
        'payment' => [
            'id' => (int)$payment['id'],
            'user_id' => (int)$payment['user_id'],
            'user_name' => $payment['user_name'] ?? 'N/A',
            'user_email' => $payment['email'] ?? 'N/A',
            'user_phone' => $payment['phone'] ?? 'N/A',
            'flat_no' => $payment['flat_no'] ?? 'N/A',
            'amount' => (float)$payment['amount'],
            'status' => $payment['status'] ?? 'pending',
            'method' => $payment['method'] ?? 'unknown',
            'reference' => $payment['reference'] ?? 'N/A',
            'transaction_id' => $payment['transaction_id'] ?? null,
            'receipt_image' => $payment['receipt_image'] ?? null,
            'bank_name' => $payment['bank_name'] ?? null,
            'transaction_date' => $payment['transaction_date'] ?? null,
            'payment_type' => $payment['payment_type'] ?? 'water_bill',
            'notes' => $payment['notes'] ?? '',
            'created_at' => $payment['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $payment['updated_at'] ?? null
        ]
    ];
    
    // Output the JSON response
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Log the error
    error_log('Database error in payment-details.php: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Error in payment-details.php: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => $e->getMessage()
    ]);
}
?>