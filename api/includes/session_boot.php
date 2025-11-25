<?php
// api/includes/session_boot.php

require_once __DIR__ . '/session_config.php';

function session_boot() {
    configureSession();
    
    // Initialize session arrays if not set
    if (!isset($_SESSION['user'])) {
        $_SESSION['user'] = [];
    }
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
}

if (!function_exists('session_boot')) {
    function session_boot(): void {
        // Determine host and whether we're on HTTPS
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        // Choose a cookie domain:
        // - On localhost or hosts without a dot, omit 'domain'
        // - Otherwise use a leading-dot domain so subdomains are covered
        $cookieDomain = '';
        if ($host && strpos($host, '.') !== false) {
            // If you know the exact public hostname (example: waterbill.free.nf)
            // uncomment and set explicit domain to be safe:
            // $cookieDomain = '.waterbill.free.nf';

            // Otherwise fall back to inferred host with leading dot
            $cookieDomain = '.' . preg_replace('/:\d+$/', '', $host); // remove port if present
        }

        // Set cookie path. If your app lives at /waterbill/ use that path, otherwise use '/'
        $cookiePath = '/waterbill/'; // <<-- adjust to your app path or use '/'

        // Lifetime: 0 = session cookie (deleted when browser closes). Use >0 for persistent.
        $lifetime = 0; // set >0 if you want persistent cookies (in seconds)

        // Set cookie params - require PHP 7.3+ for array form
        $cookieParams = [
            'lifetime' => $lifetime,
            'path'     => $cookiePath,
            'domain'   => $cookieDomain ?: '',
            'secure'   => $isHttps,        // must be true when SameSite=None
            'httponly' => true,
            'samesite' => 'None'          // Important for cross-site fetch + credentials
        ];

        // Apply cookie params before session_start
        session_set_cookie_params($cookieParams);

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Mark first-time initialization (avoid regenerating id on every request)
        if (empty($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = time();
        }

        // For debugging (server logs) - comment out in production
        error_log('session_boot: started; host=' . $host . '; secure=' . ($isHttps ? '1' : '0') . '; domain=' . ($cookieDomain ?: '(none)') . '; path=' . $cookiePath . '; samesite=None');
    }
}
