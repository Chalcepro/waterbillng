<?php
// Set session configuration before starting the session
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
ini_set('session.cookie_samesite', 'Lax');

// Set session cookie parameters
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 3600 * 24 * 30, // 30 days
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

// Function to check if user is regular user
function is_user() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'user';
}

// Function to require login
function require_login() {
    if (!is_logged_in()) {
        http_response_code(401); // Unauthorized
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required', 'redirect' => '/login.html']);
        exit();
    }
}

// Function to require admin access
function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403); // Forbidden
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Admin access required', 'redirect' => '/']);
        exit();
    }
}
?>