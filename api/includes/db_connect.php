<?php
// Database configuration
define('DB_HOST', 'sql103.infinityfree.com');
define('DB_NAME', 'if0_40185927_waterbill_db');
define('DB_USER', 'if0_40185927');
define('DB_PASS', 'waterbillEst1'); // XAMPP default is empty password

/**
 * Get a database connection
 * 
 * @return PDO A PDO database connection
 * @throws PDOException If the connection fails
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

// For backward compatibility
$pdo = getDBConnection();
?>