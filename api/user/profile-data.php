<?php
// api/user/profile-data.php - FIXED FOR YOUR DATABASE SCHEMA

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// CORS headers
$allowed_origins = ['https://waterbill.free.nf', 'http://localhost:8000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required. Please log in.',
        'redirect' => '/waterbill/frontend/auth/login.html'
    ]);
    exit();
}

// Database connection
try {
    require_once __DIR__ . '/../../config.php';
    
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception('Database connection not available');
    }
    
    $user_id = $_SESSION['user_id'];

    // Get user data - USING YOUR ACTUAL DATABASE COLUMN NAMES
    $stmt = $pdo->prepare("
        SELECT 
            id, username, email, first_name, middle_name, last_name, flat_no, phone,
            full_name, address, role,
            DATE_FORMAT(created_at, '%Y-%m-%d') as created_at
        FROM users 
        WHERE id = ?
    ");
    
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }
    
    // Return success response - mapping last_name to surname for frontend
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'middle_name' => $user['middle_name'],
            'surname' => $user['last_name'], // Map last_name to surname
            'full_name' => $user['full_name'],
            'flat_no' => $user['flat_no'],
            'phone' => $user['phone'],
            'address' => $user['address'],
            'role' => $user['role'],
            'created_at' => $user['created_at']
        ],
        'message' => 'Profile data loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Profile data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load profile data: ' . $e->getMessage()
    ]);
}
?>