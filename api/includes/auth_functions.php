<?php
// Check if user is logged in and has admin privileges
function isAdmin() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in and has admin role
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is logged in
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['role'] ?? null;
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Redirect to home if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /');
        exit();
    }
}
