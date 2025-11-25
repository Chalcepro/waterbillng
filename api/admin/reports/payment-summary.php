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
    // Discover columns
    $pCols = array_map(function($r){ return strtolower($r['Field']); }, $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_ASSOC));
    $has = function($c) use ($pCols){ return in_array(strtolower($c), $pCols, true); };
    $statusCol = $has('status') ? 'status' : "'approved'";
    $amountCol = $has('amount') ? 'amount' : '0';
    $methodCol = $has('method') ? 'method' : "'Unknown'";

    // By method
    $byMethod = [];
    $stmt = $pdo->query("SELECT $methodCol AS method, SUM($amountCol) AS total, COUNT(*) AS count FROM payments GROUP BY $methodCol ORDER BY total DESC");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $byMethod[] = [ 'method' => ucfirst($r['method'] ?? 'Unknown'), 'total' => (float)$r['total'], 'count' => (int)$r['count'] ];
    }

    // By status
    $byStatus = [];
    $stmt2 = $pdo->query("SELECT LOWER($statusCol) AS status, SUM($amountCol) AS total, COUNT(*) AS count FROM payments GROUP BY LOWER($statusCol)");
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $st = $r['status'] ?? 'pending';
        if ($st === 'approved') $st = 'completed';
        $byStatus[] = [ 'status' => $st, 'total' => (float)$r['total'], 'count' => (int)$r['count'] ];
    }

    echo json_encode(['success' => true, 'by_method' => $byMethod, 'by_status' => $byStatus]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to compute payment summary']);
}
