<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For testing - comment this out in production
$_SESSION['is_admin'] = true;
$_SESSION['user_id'] = 1;

// Include required files
require_once __DIR__ . '/../../config.php';

// Check if user is admin (temporarily disabled for testing)
/*
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}
*/

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Log request for debugging
file_put_contents('api_debug.log', date('Y-m-d H:i:s') . ' - Request: ' . print_r($_REQUEST, true) . "\n", FILE_APPEND);

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get filter parameters
        $status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : null;
        $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        // Build base query
        $query = "
            SELECT 
                fr.*, 
                u.username, 
                u.email,
                CONCAT(u.first_name, ' ', u.surname) as user_name,
                u.account_number
            FROM fault_reports fr
            JOIN users u ON fr.user_id = u.id
            WHERE 1=1
        ";
        
        $where = [];
        $params = [];
        $types = '';
        
        // Add status filter
        if ($status && in_array($status, ['open', 'in_progress', 'resolved', 'rejected'])) {
            $where[] = "fr.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        // Add search filter
        if ($search) {
            $where[] = "(fr.category LIKE ? OR fr.description LIKE ? OR u.account_number = ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $search;
            $types .= 'sss';
        }
        
        // Add WHERE conditions
        if (!empty($where)) {
            $query .= " AND " . implode(' AND ', $where);
        }
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM ($query) as count_query";
        $stmt = $conn->prepare($countQuery);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $totalPages = ceil($total / $perPage);
        
        // Add sorting and pagination
        $query .= " ORDER BY fr.created_at DESC LIMIT ? OFFSET ?";
        
        // Execute main query
        $stmt = $conn->prepare($query);
        
        // Add pagination parameters
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $reports = [];
        
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        
        // Return response
        $response = [
            'success' => true,
            'data' => $reports,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $totalPages
            ]
        ];
        
        // Log successful response
        file_put_contents('api_debug.log', date('Y-m-d H:i:s') . ' - Response: ' . json_encode($response) . "\n", FILE_APPEND);
        
        echo json_encode($response);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

$conn->close();

// Error handling function
function handleError($message) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'error' => error_get_last()
    ]);
    exit;
}
?>
