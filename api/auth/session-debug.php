<?php
// Temporary endpoint for debugging sessions - remove in production
error_reporting(0);
header('Content-Type: application/json');

// Require session boot (ensure it is the same one used by your other endpoints)
require_once __DIR__ . '/../includes/session_boot.php';
session_boot();

// Output what the server currently sees
echo json_encode([
    'now' => date('c'),
    'session_id' => session_id(),
    'cookie_header_sent' => $_SERVER['HTTP_COOKIE'] ?? null,
    '_COOKIE' => $_COOKIE,
    '_SESSION' => $_SESSION
], JSON_PRETTY_PRINT);
