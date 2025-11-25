<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Set session cookie parameters for consistency
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 3600 * 24 * 30, // 30 days
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Discover columns for users
    $uCols = array_map(function($r){ return strtolower($r['Field']); }, $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC));
    $hasU = function($c) use ($uCols){ return in_array(strtolower($c), $uCols, true); };

    $totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    // Active users: prefer status/is_active; fallback to subscriptions if table exists
    $activeUsers = 0;
    if ($hasU('status')) {
        $activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(status)='active'")->fetchColumn();
    } elseif ($hasU('is_active')) {
        $activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
    } else {
        // try subscriptions
        try {
            $activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE end_date >= CURDATE() AND status='active'")->fetchColumn();
        } catch (Throwable $e) {
            $activeUsers = 0;
        }
    }

    // New registrations by month (last 6)
    $dateCol = $hasU('created_at') ? 'created_at' : ($hasU('registered_at') ? 'registered_at' : null);
    $registrations = [];
    if ($dateCol) {
        $stmt = $pdo->query("SELECT DATE_FORMAT($dateCol,'%Y-%m') AS label, COUNT(*) AS count FROM users GROUP BY DATE_FORMAT($dateCol,'%Y-%m') ORDER BY MIN($dateCol) DESC LIMIT 6");
        $registrations = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    echo json_encode([
        'success' => true,
        'total_users' => $totalUsers,
        'active_users' => $activeUsers,
        'registrations' => $registrations,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to compute analytics']);
}
