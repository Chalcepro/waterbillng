<?php
// Test database connection and structure
header('Content-Type: text/plain');

try {
    // Include database connection
    require_once 'api/includes/db_connect.php';
    
    // Test connection
    $pdo = getDBConnection();
    echo "✅ Successfully connected to database\n\n";
    
    // Get database info
    $stmt = $pdo->query('SELECT DATABASE() as db_name, VERSION() as db_version');
    $dbInfo = $stmt->fetch();
    echo "Database: {$dbInfo['db_name']} (Version: {$dbInfo['db_version']})\n\n";
    
    // Check required tables
    $requiredTables = ['users', 'payments', 'subscriptions', 'fault_reports'];
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Checking required tables...\n";
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ Table '$table' exists\n";
            
            // Show table structure
            $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            echo "   Columns: " . implode(', ', $columns) . "\n";
            
            // Show row count
            $count = $pdo->query("SELECT COUNT(*) as count FROM $table")->fetch()['count'];
            echo "   Rows: $count\n";
        } else {
            echo "❌ Table '$table' is missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Show connection details (without password)
    echo "\nConnection details:\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n";
    
    // Show PDO error info if available
    if (isset($pdo)) {
        $errorInfo = $pdo->errorInfo();
        if (!empty($errorInfo[2])) {
            echo "\nPDO Error: " . $errorInfo[2] . "\n";
        }
    }
    
    // Show PHP version and extensions
    echo "\nPHP Version: " . phpversion() . "\n";
    echo "PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "\n";
    echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "\n";
}
