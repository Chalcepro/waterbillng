<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

try {
    // Test database connection
    echo "=== Testing Database Connection ===\n";
    require_once __DIR__ . '/includes/db_connect.php';
    
    // Test connection
    $test = $pdo->query("SELECT 1")->fetchColumn();
    echo "✓ Database connection successful\n\n";
    
    // List all tables
    echo "=== Database Tables ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found in the database.\n";
    } else {
        echo "Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
        echo "\n";
        
        // Check required tables
        $requiredTables = ['users', 'payments', 'subscriptions', 'fault_reports'];
        $missingTables = array_diff($requiredTables, $tables);
        
        if (!empty($missingTables)) {
            echo "⚠️ Missing required tables: " . implode(', ', $missingTables) . "\n\n";
        } else {
            echo "✓ All required tables exist\n\n";
        }
        
        // Check table structures
        foreach ($requiredTables as $table) {
            if (in_array($table, $tables)) {
                echo "=== $table Table Structure ===\n";
                $columns = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
                echo "Columns: " . implode(', ', $columns) . "\n\n";
                
                // Show sample data (first 2 rows)
                $sample = $pdo->query("SELECT * FROM `$table` LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
                echo "Sample data (max 2 rows):\n";
                print_r($sample);
                echo "\n\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Check if PDO is available
    if (isset($pdo)) {
        echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
    }
    
    // Check database credentials
    echo "\n=== Database Configuration ===\n";
    $config = [
        'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'Not defined',
        'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'Not defined',
        'DB_USER' => defined('DB_USER') ? DB_USER : 'Not defined',
        'DB_PASS' => defined('DB_PASS') ? (DB_PASS ? '*****' : '[empty]') : 'Not defined'
    ];
    print_r($config);
}
