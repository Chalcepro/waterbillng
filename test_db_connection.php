<?php
// Test database connection
require_once 'api/includes/db_connect.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Test query
    $stmt = $pdo->query('SELECT DATABASE() as db_name, VERSION() as db_version');
    $dbInfo = $stmt->fetch();
    
    // Get table list
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>Database Connection Test</h1>";
    echo "<p>Connected to database: <strong>{$dbInfo['db_name']}</strong> (Version: {$dbInfo['db_version']})</p>";
    
    echo "<h3>Tables in database:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h1>Database Connection Failed</h1>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . print_r($e->getTrace(), true) . "</pre>";
}
