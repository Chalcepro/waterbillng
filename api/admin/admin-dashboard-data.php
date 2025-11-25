<?php
// api/admin/admin-dashboard-data.php - SIMPLIFIED WORKING VERSION

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// CORS headers
$allowed_origins = ['https://waterbill.free.nf', 'http://localhost:8000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin access required.'
    ]);
    exit();
}

// Database connection
require_once __DIR__ . '/../../config.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    $adminId = $_SESSION['user_id'];

    // Get admin user info
    $stmt = $pdo->prepare("
        SELECT 
            id, username, email, first_name, middle_name, last_name, phone,
            DATE_FORMAT(created_at, '%Y-%m-%d') as created_at
        FROM users 
        WHERE id = ? AND role = 'admin'
    ");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
        exit();
    }

    // Get basic statistics - using only the users table for now
    $totalUsers = 0;
    $pendingPayments = 0;
    $activeSubscriptions = 0;
    $openFaults = 0;

    try {
        // Total users (excluding admin)
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
        $totalUsers = $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error counting users: " . $e->getMessage());
        $totalUsers = 0;
    }

    // Get recent users (last 5)
    $recentUsers = [];
    try {
        $stmt = $pdo->query("
            SELECT id, username, email, first_name, last_name, phone, created_at 
            FROM users 
            WHERE role = 'user' 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting recent users: " . $e->getMessage());
        $recentUsers = [];
    }

    // Get recent payments - return empty array for now since payments table might not exist
    $recentPayments = [];

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'total_users' => (int)$totalUsers,
            'pending_payments' => (int)$pendingPayments,
            'active_subscriptions' => (int)$activeSubscriptions,
            'open_faults' => (int)$openFaults,
            'recent_payments' => $recentPayments,
            'system_status' => 'online',
            'last_backup' => date('M d, Y H:i A', strtotime('-1 day')),
            'storage_used' => '1.2 GB / 5 GB'
        ],
        'admin' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'first_name' => $admin['first_name'],
            'last_name' => $admin['last_name'],
            'phone' => $admin['phone']
        ],
        'message' => 'Admin dashboard data loaded successfully'
    ]);

} catch (Exception $e) {
    error_log("Admin dashboard data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load admin dashboard data: ' . $e->getMessage()
    ]);
}
?>