<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

function checkTable($pdo, $tableName, $requiredColumns = []) {
    $result = [
        'exists' => false,
        'columns' => [],
        'missing_columns' => []
    ];
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() === 0) {
            return $result;
        }
        
        $result['exists'] = true;
        
        // Get all columns in the table
        $stmt = $pdo->query("DESCRIBE `$tableName`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $result['columns'] = $columns;
        
        // Check for required columns
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columns)) {
                $result['missing_columns'][] = $col;
            }
        }
        
    } catch (PDOException $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

try {
    require_once __DIR__ . '/includes/db_connect.php';
    $pdo = getDBConnection();
    
    // Test connection
    $pdo->query("SELECT 1 as test")->fetch();
    
    // Check required tables and columns
    $tables = [
        'users' => ['id', 'username', 'email', 'first_name', 'last_name', 'role', 'subscription_status', 'subscription_end_date'],
        'payments' => ['id', 'user_id', 'amount', 'status', 'method', 'created_at'],
        'fault_reports' => ['id', 'user_id', 'status', 'description', 'created_at'],
        'subscriptions' => ['id', 'user_id', 'start_date', 'end_date', 'status']
    ];
    
    $dbCheck = [];
    foreach ($tables as $table => $columns) {
        $dbCheck[$table] = checkTable($pdo, $table, $columns);
    }
    
    // Check for any critical issues
    $hasCriticalIssues = false;
    foreach ($dbCheck as $table => $info) {
        if (!$info['exists'] || !empty($info['missing_columns'])) {
            $hasCriticalIssues = true;
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database check completed',
        'has_critical_issues' => $hasCriticalIssues,
        'tables' => $dbCheck
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>
