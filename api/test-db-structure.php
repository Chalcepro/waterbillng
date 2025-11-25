<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once __DIR__ . '/includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Check required tables
    $tables = ['users', 'payments', 'subscriptions', 'fault_reports'];
    $structure = [];
    
    foreach ($tables as $table) {
        $structure[$table] = [
            'exists' => false,
            'columns' => []
        ];
        
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $structure[$table]['exists'] = true;
            
            // Get table structure
            $columns = $pdo->query("SHOW COLUMNS FROM $table");
            while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                $structure[$table]['columns'][$column['Field']] = $column['Type'];
            }
        }
    }
    
    // Check if we can execute a simple query
    $testQuery = $pdo->query("SELECT 1 as test");
    $connectionTest = $testQuery !== false;
    
    // Return results
    echo json_encode([
        'success' => true,
        'connection_test' => $connectionTest,
        'database_structure' => $structure,
        'php_version' => phpversion(),
        'pdo_drivers' => PDO::getAvailableDrivers(),
        'current_driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
