<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'guest';

// Check if user has admin access
$hasAdminAccess = $isLoggedIn && $userRole === 'admin';

// Get current session data
$sessionData = [
    'session_id' => session_id(),
    'is_logged_in' => $isLoggedIn,
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null,
    'role' => $userRole,
    'has_admin_access' => $hasAdminAccess,
    'session_data' => $_SESSION
];

// Check if we should simulate login
if (isset($_GET['login']) && $_GET['login'] === 'admin') {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $sessionData = [
        'status' => 'logged_in',
        'message' => 'Successfully logged in as admin',
        'session_data' => $_SESSION
    ];
    session_write_close();
    header('Location: test-session.php');
    exit;
}

// Output the session information
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'session' => $sessionData,
    'server' => [
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'http_referer' => $_SERVER['HTTP_REFERER'] ?? null,
        'http_cookie' => $_SERVER['HTTP_COOKIE'] ?? null
    ]
], JSON_PRETTY_PRINT);
?>
