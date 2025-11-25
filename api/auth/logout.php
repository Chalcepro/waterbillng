<?php
// api/auth/logout.php

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();

header('Content-Type: application/json');

// CORS headers
$allowedOrigins = ['https://waterbill.free.nf', 'http://localhost:8000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}

// Clear session
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?>