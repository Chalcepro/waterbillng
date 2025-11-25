<?php
// api/includes/session_config.php

function configureSession() {
    // Session security settings
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // Determine if using HTTPS
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || ($_SERVER['SERVER_PORT'] ?? null) == 443;
    
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}
?>