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
    $period = isset($_GET['period']) ? strtolower(trim($_GET['period'])) : 'monthly';
    $limit = isset($_GET['limit']) ? max(1, min(24, (int)$_GET['limit'])) : 12; // up to 24 periods

    // Discover columns
    $pCols = array_map(function($r){ return strtolower($r['Field']); }, $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_ASSOC));
    $has = function($c) use ($pCols){ return in_array(strtolower($c), $pCols, true); };
    $dateCol = $has('created_at') ? 'created_at' : ($has('date') ? 'date' : 'NOW()');
    $statusCol = $has('status') ? 'status' : "'approved'";
    $amountCol = $has('amount') ? 'amount' : '0';

    if ($period === 'yearly') {
        $sql = "SELECT DATE_FORMAT($dateCol, '%Y') AS label, SUM($amountCol) AS total
                FROM payments
                WHERE ($statusCol IN ('approved','completed') OR $statusCol='approved')
                GROUP BY DATE_FORMAT($dateCol,'%Y')
                ORDER BY MIN($dateCol) DESC
                LIMIT :limit";
    } else { // monthly default
        $sql = "SELECT DATE_FORMAT($dateCol, '%Y-%m') AS label, SUM($amountCol) AS total
                FROM payments
                WHERE ($statusCol IN ('approved','completed') OR $statusCol='approved')
                GROUP BY DATE_FORMAT($dateCol,'%Y-%m')
                ORDER BY MIN($dateCol) DESC
                LIMIT :limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reverse to chronological order
    $rows = array_reverse($rows);
    echo json_encode(['success' => true, 'period' => $period, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to compute revenue']);
}
