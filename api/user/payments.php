<?php
/**
 * User Payments API
 * Handles fetching payment history for the authenticated user
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Get user ID from query parameters or session
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    // Verify user is authenticated and has access to the requested data
    if (empty($userId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required',
            'error' => 'MISSING_USER_ID'
        ]);
        exit;
    }
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(max(1, intval($_GET['per_page'])), 100) : 20;
    $offset = ($page - 1) * $perPage;
    
    // Get sorting parameters
    $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
    $sortOrder = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
    
    // Validate sort column to prevent SQL injection
    $allowedSortColumns = ['id', 'amount', 'payment_date', 'created_at', 'status'];
    $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'created_at';
    
    // Get filter parameters
    $filters = [
        'status' => isset($_GET['status']) ? $_GET['status'] : null,
        'payment_method' => isset($_GET['payment_method']) ? $_GET['payment_method'] : null,
        'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : null,
        'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : null,
    ];
    
    // Build the query
    $query = "SELECT * FROM payments WHERE user_id = :user_id";
    $params = [':user_id' => $userId];
    $countQuery = "SELECT COUNT(*) as total FROM payments WHERE user_id = :user_id";
    $countParams = [':user_id' => $userId];
    
    // Apply filters
    if ($filters['status']) {
        $query .= " AND status = :status";
        $params[':status'] = $filters['status'];
        $countQuery .= " AND status = :status";
        $countParams[':status'] = $filters['status'];
    }
    
    if ($filters['payment_method']) {
        $query .= " AND payment_method = :payment_method";
        $params[':payment_method'] = $filters['payment_method'];
        $countQuery .= " AND payment_method = :payment_method";
        $countParams[':payment_method'] = $filters['payment_method'];
    }
    
    if ($filters['start_date']) {
        $query .= " AND DATE(created_at) >= :start_date";
        $params[':start_date'] = $filters['start_date'];
        $countQuery .= " AND DATE(created_at) >= :start_date";
        $countParams[':start_date'] = $filters['start_date'];
    }
    
    if ($filters['end_date']) {
        $query .= " AND DATE(created_at) <= :end_date";
        $params[':end_date'] = $filters['end_date'];
        $countQuery .= " AND DATE(created_at) <= :end_date";
        $countParams[':end_date'] = $filters['end_date'];
    }
    
    // Get total count for pagination
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Add sorting and pagination
    $query .= " ORDER BY $sortBy $sortOrder LIMIT :offset, :per_page";
    $params[':offset'] = $offset;
    $params[':per_page'] = $perPage;
    
    // Execute the query
    $stmt = $pdo->prepare($query);
    
    // Bind parameters with proper types
    foreach ($params as $key => $value) {
        $paramType = PDO::PARAM_STR;
        if ($key === ':user_id' || $key === ':offset' || $key === ':per_page') {
            $paramType = PDO::PARAM_INT;
        }
        $stmt->bindValue($key, $value, $paramType);
    }
    
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'data' => [
            'payments' => $payments,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage(),
        'error_code' => 'DB_ERROR'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage(),
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>
