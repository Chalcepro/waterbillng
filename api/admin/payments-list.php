<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $method = $_GET['method'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Build query
    $query = "
        SELECT p.*, 
               u.first_name, u.last_name, u.email, u.phone, u.flat_no,
               CONCAT(u.first_name, ' ', u.last_name) as user_name
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply filters
    if (!empty($search)) {
        $query .= " AND (p.reference LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($status)) {
        $query .= " AND p.status = ?";
        $params[] = $status;
    }
    
    if (!empty($method)) {
        $query .= " AND p.method = ?";
        $params[] = $method;
    }
    
    if (!empty($date_from)) {
        $query .= " AND DATE(p.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND DATE(p.created_at) <= ?";
        $params[] = $date_to;
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formattedPayments = array_map(function($payment) {
        return [
            'id' => $payment['id'],
            'user_id' => $payment['user_id'],
            'user_name' => $payment['user_name'],
            'user_email' => $payment['email'],
            'user_phone' => $payment['phone'],
            'flat_no' => $payment['flat_no'],
            'amount' => floatval($payment['amount']),
            'status' => $payment['status'],
            'method' => $payment['method'],
            'reference' => $payment['reference'],
            'transaction_id' => $payment['transaction_id'],
            'receipt_image' => $payment['receipt_image'],
            'bank_name' => $payment['bank_name'],
            'transaction_date' => $payment['transaction_date'],
            'payment_type' => $payment['payment_type'],
            'notes' => $payment['notes'],
            'created_at' => $payment['created_at'],
            'updated_at' => $payment['updated_at']
        ];
    }, $payments);
    
    echo json_encode([
        'success' => true,
        'payments' => $formattedPayments,
        'count' => count($formattedPayments)
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching payments: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch payments'
    ]);
}
?>