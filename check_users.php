<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    $pdo = getDBConnection();
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        die("Users table does not exist in the database. Please run database migrations.");
    }
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Users Table Structure:</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Count users
    $count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    
    echo "<h2>User Count: " . $count['count'] . "</h2>";
    
    // Show first 5 users if any
    if ($count['count'] > 0) {
        $users = $pdo->query("SELECT * FROM users LIMIT 5")->fetchAll();
        echo "<h2>First 5 Users:</h2>";
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Database Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Check database connection
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        echo "<p>Connected to MySQL server successfully.</p>";
        
        // List databases
        $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<h3>Available Databases:</h3>";
        echo "<pre>";
        print_r($databases);
        echo "</pre>";
        
    } catch (PDOException $dbError) {
        echo "<p>Failed to connect to MySQL: " . $dbError->getMessage() . "</p>";
    }
}

echo "<h3>PHP Version: " . phpversion() . "</h3>";
?>
