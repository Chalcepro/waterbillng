<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();

require_once __DIR__ . '/../includes/db_connect.php';

// Check if user is logged in and has user role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // First, check if the payments table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
    
    if (!$tableExists) {
        throw new Exception('Payments table does not exist');
    }
    
    // Get all payments for the user with proper error handling
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC");
    if (!$stmt->execute([$userId])) {
        throw new Exception('Failed to fetch payments');
    }
    
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format payments for frontend with proper error handling
    $formattedPayments = [];
    $totalAmount = 0;
    $pendingCount = 0;
    $approvedCount = 0;
    
    foreach ($payments as $payment) {
        // Ensure all required fields exist
        $amount = isset($payment['amount']) ? floatval($payment['amount']) : 0;
        $status = isset($payment['status']) ? strtolower($payment['status']) : 'pending';
        
        // Update counters
        $totalAmount += $amount;
        if ($status === 'pending') $pendingCount++;
        if ($status === 'approved') $approvedCount++;
        
        // Format the payment
        $formattedPayments[] = [
            'id' => $payment['id'] ?? null,
            'amount' => $amount,
            'method' => isset($payment['method']) ? 
                        ucfirst(str_replace('_', ' ', $payment['method'])) : 'Unknown',
            'status' => $status,
            'transaction_id' => $payment['transaction_id'] ?? 'N/A',
            'receipt_image' => $payment['receipt_image'] ?? null,
            'date' => isset($payment['created_at']) ? 
                      date('M d, Y', strtotime($payment['created_at'])) : 'N/A',
            'raw_date' => isset($payment['created_at']) ? 
                         date('Y-m-d', strtotime($payment['created_at'])) : date('Y-m-d')
        ];
    }
    
    // Prepare summary
    $summary = [
        'total_payments' => count($formattedPayments),
        'total_amount' => $totalAmount,
        'pending_count' => $pendingCount,
        'approved_count' => $approvedCount
    ];
    
    echo json_encode([
        'success' => true,
        'payments' => $formattedPayments,
        'summary' => $summary
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in payment-history.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error in payment-history.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>
