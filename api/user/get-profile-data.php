<?php
// Set headers for CORS and JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Authentication required',
        'redirect' => '/login.html'
    ]);
    exit();
}

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Fetch user data from database
    $stmt = $pdo->prepare("
        SELECT 
            id, username, email, first_name, middle_name, surname, 
            flat_no, phone, created_at, updated_at
        FROM users 
        WHERE id = ?
    ");
    
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Format dates for better readability
    $user['created_at'] = date('Y-m-d H:i:s', strtotime($user['created_at']));
    $user['updated_at'] = $user['updated_at'] ? date('Y-m-d H:i:s', strtotime($user['updated_at'])) : null;
    
    // Return user data
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch profile data: ' . $e->getMessage()
    ]);
}
?>