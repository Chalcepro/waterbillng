<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/debug_dashboard.log');

header('Content-Type: application/json');

function logDebug($message, $data = []) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    if (!empty($data)) {
        $log .= 'Data: ' . print_r($data, true) . "\n";
    }
    error_log($log);
}

try {
    // Log request
    logDebug('Debug endpoint accessed', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'get' => $_GET,
        'post' => $_POST,
        'session' => session_id()
    ]);

    // Test database connection
    require_once __DIR__ . '/../../includes/db_connect.php';
    $pdo = getDBConnection();
    
    // Test a simple query
    $testQuery = $pdo->query("SELECT 1 as test");
    if (!$testQuery) {
        throw new Exception('Test query failed: ' . implode(', ', $pdo->errorInfo()));
    }
    
    // Test users table
    $usersQuery = $pdo->query("SELECT COUNT(*) as count FROM users");
    $usersCount = $usersQuery->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Test payments table
    $paymentsQuery = $pdo->query("SELECT COUNT(*) as count FROM payments");
    $paymentsCount = $paymentsQuery->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recent payments with user names
    $recentPaymentsQuery = $pdo->query("
        SELECT 
            p.id,
            CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user_name,
            p.amount,
            COALESCE(p.method, p.payment_method, 'N/A') as method,
            p.created_at,
            p.status
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ") or die(json_encode(['error' => 'Query failed: ' . implode(', ', $pdo->errorInfo())], JSON_PRETTY_PRINT));
    
    $recentPayments = $recentPaymentsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'database' => [
            'users_count' => $usersCount,
            'payments_count' => $paymentsCount
        ],
        'recent_payments' => $recentPayments,
        'server' => [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'
        ]
    ], JSON_PRETTY_INT | JSON_UNESCAPED_SLASHES);
    
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
