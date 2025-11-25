<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Test database connection
    $pdo = getDBConnection();
    
    // Test query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    if ($result && $result['test'] == 1) {
        echo "Database connection successful!<br>";
    } else {
        echo "Database query failed<br>";
    }
    
    // Check if users table exists and has data
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    
    if (count($tables) > 0) {
        echo "Users table exists<br>";
        
        // Count users
        $userCount = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
        echo "Number of users: " . $userCount['count'] . "<br>";
        
        // Show first user
        $user = $pdo->query("SELECT * FROM users LIMIT 1")->fetch();
        echo "First user: " . ($user ? print_r($user, true) : 'No users found') . "<br>";
    } else {
        echo "Users table does not exist<br>";
    }
    
} catch (PDOException $e) {
    echo "<strong>Database Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
    echo "<strong>In file:</strong> " . $e->getFile() . " on line " . $e->getLine() . "<br>";
}

echo "<br>PHP Version: " . phpversion();
?>
