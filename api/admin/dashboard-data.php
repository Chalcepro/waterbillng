<?php
// Disable error output to prevent corrupting JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Include the database connection file
    $dbConnectPath = __DIR__ . '/../../includes/db_connect.php';
    if (!file_exists($dbConnectPath)) {
        throw new Exception("Database connection file not found at: " . $dbConnectPath);
    }
    require_once $dbConnectPath;

    // Get database connection
    try {
        if (!function_exists('getDBConnection')) {
            throw new Exception('getDBConnection function not found. Check your database connection file.');
        }
        $pdo = getDBConnection();
        
        // Test the connection
        $test = $pdo->query('SELECT 1');
        if ($test === false) {
            throw new Exception('Database test query failed: ' . implode(', ', $pdo->errorInfo()));
        }
    } catch (Exception $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }

    session_start();
    
    // Set JSON content type
    header('Content-Type: application/json');
    
    // Check if user is admin
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    // Get total users count
    $users_stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE role != 'admin'");
    $users_stmt->execute();
    $total_users = $users_stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // Get pending payments count
    $pending_stmt = $pdo->prepare("SELECT COUNT(*) as pending_payments FROM payments WHERE status = 'pending'");
    $pending_stmt->execute();
    $pending_payments = $pending_stmt->fetch(PDO::FETCH_ASSOC)['pending_payments'];

    // Get active subscriptions count
    $active_subs_stmt = $pdo->prepare("
        SELECT COUNT(*) as active_subscriptions 
        FROM users 
        WHERE subscription_status = 'active' 
        AND subscription_end_date > NOW()
    ");
    $active_subs_stmt->execute();
    $active_subscriptions = $active_subs_stmt->fetch(PDO::FETCH_ASSOC)['active_subscriptions'];

    // Get open faults count
    $faults_stmt = $pdo->prepare("SELECT COUNT(*) as open_faults FROM fault_reports WHERE status = 'open'");
    $faults_stmt->execute();
    $open_faults = $faults_stmt->fetch(PDO::FETCH_ASSOC)['open_faults'];

    // Get recent payments
    $recent_payments_stmt = $pdo->prepare("
        SELECT p.*, u.name as user_name 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $recent_payments_stmt->execute();
    $recent_payments = $recent_payments_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_users' => (int)$total_users,
        'pending_payments' => (int)$pending_payments,
        'active_subscriptions' => (int)$active_subscriptions,
        'open_faults' => (int)$open_faults,
        'recent_payments' => $recent_payments,
        'last_updated' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    http_response_code(500);
    // Ensure no output before this
    if (ob_get_level()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log("Unexpected error in admin dashboard: " . $e->getMessage());
    http_response_code(500);
    if (ob_get_level()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
    exit;
}
?>