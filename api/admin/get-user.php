<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Enhanced session configuration
session_set_cookie_params([
    'lifetime' => 86400 * 30, // 30 days
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once __DIR__ . '/../includes/db_connect.php';

// Enhanced authentication check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login again']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid User ID is required']);
    exit;
}

try {
    $user_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT id, username, first_name, middle_name, last_name, surname, email, phone, flat_no, role, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Ensure consistent field names
    if (isset($user['surname']) && !isset($user['last_name'])) {
        $user['last_name'] = $user['surname'];
    }
    
    echo json_encode(['success' => true, 'user' => $user]);
    
} catch (PDOException $e) {
    error_log("Database error in get-user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Error in get-user.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}