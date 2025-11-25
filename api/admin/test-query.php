<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

function testQuery($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

try {
    // Include database connection
    require_once __DIR__ . '/../includes/db_connect.php';
    
    // Test database connection
    echo "=== Testing Database Connection ===\n";
    $test = $pdo->query("SELECT 1")->fetch();
    echo "✓ Database connection successful\n\n";
    
    // Test 1: Check if user_subscriptions table exists
    echo "=== Checking user_subscriptions table ===\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'user_subscriptions'")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "❌ user_subscriptions table not found. Available tables:\n";
        $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        print_r($allTables);
    } else {
        echo "✓ user_subscriptions table exists\n";
        
        // Test 2: Check table structure
        echo "\n=== user_subscriptions table structure ===\n";
        $structure = $pdo->query("DESCRIBE user_subscriptions")->fetchAll(PDO::FETCH_ASSOC);
        echo "Columns:\n";
        foreach ($structure as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
        
        // Test 3: Try the actual query
        echo "\n=== Testing active subscriptions query ===\n";
        $query = "SELECT COUNT(DISTINCT user_id) as total 
                 FROM user_subscriptions 
                 WHERE status = 'active' 
                 AND end_date > NOW()";
        
        $result = testQuery($pdo, $query);
        if (isset($result['error'])) {
            echo "❌ Query failed: {$result['error']}\n";
        } else {
            echo "✓ Query successful. Active subscriptions: " . ($result[0]['total'] ?? 0) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}
?>
