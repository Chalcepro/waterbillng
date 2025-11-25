<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if this is a login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    // Simulate successful login
    $_SESSION['user_id'] = 1; // Replace with actual user ID
    $_SESSION['username'] = 'testuser';
    $_SESSION['role'] = 'user';
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'session_id' => session_id()
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in',
        'session' => $_SESSION,
        'cookies' => $_COOKIE,
        'session_id' => session_id(),
        'session_name' => session_name()
    ]);
    exit;
}

// Return session data
echo json_encode([
    'success' => true,
    'message' => 'Session is active',
    'session' => $_SESSION,
    'session_id' => session_id(),
    'session_name' => session_name(),
    'cookie_params' => session_get_cookie_params()
]);
?>
