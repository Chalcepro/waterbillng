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
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid payment ID']);
        exit;
    }

    // Discover columns for payments and users
    $pCols = array_map(function($r){ return strtolower($r['Field']); }, $pdo->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_ASSOC));
    $uCols = array_map(function($r){ return strtolower($r['Field']); }, $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC));
    $hasP = function($c) use ($pCols){ return in_array(strtolower($c), $pCols, true); };
    $hasU = function($c) use ($uCols){ return in_array(strtolower($c), $uCols, true); };

    $sel = ['p.id', 'p.user_id'];
    $sel[] = $hasP('amount') ? 'p.amount' : '0 AS amount';
    $sel[] = $hasP('method') ? 'p.method' : "'Unknown' AS method";
    $sel[] = $hasP('status') ? 'p.status' : "'pending' AS status";
    if ($hasP('created_at')) $sel[] = 'p.created_at';
    elseif ($hasP('date')) $sel[] = 'p.date AS created_at';
    else $sel[] = 'NOW() AS created_at';
    $sel[] = $hasP('reference') ? 'p.reference' : "'' AS reference";
    $sel[] = $hasP('notes') ? 'p.notes' : "'' AS notes";

    if ($hasU('first_name')) $sel[] = 'u.first_name'; else $sel[] = "'' AS first_name";
    if ($hasU('middle_name')) $sel[] = 'u.middle_name'; else $sel[] = "'' AS middle_name";
    if ($hasU('surname')) $sel[] = 'u.surname'; else $sel[] = "'' AS surname";
    if ($hasU('last_name')) $sel[] = 'u.last_name'; else $sel[] = "'' AS last_name";
    if ($hasU('email')) $sel[] = 'u.email'; else $sel[] = "'' AS email";
    if ($hasU('phone')) $sel[] = 'u.phone'; else $sel[] = "'' AS phone";
    if ($hasU('flat_no')) $sel[] = 'u.flat_no'; else $sel[] = "'' AS flat_no";

    $sql = 'SELECT '.implode(',', $sel).' FROM payments p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ? LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
        exit;
    }

    // Build user name
    $lastName = $row['surname'] ?? $row['last_name'] ?? '';
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . $lastName);
    
    // Normalize status to UI terms
    $status = strtolower($row['status'] ?? 'pending');
    if ($status === 'approved') $status = 'completed';
    
    $payment = [
        'id' => (int)$row['id'],
        'user_id' => (int)$row['user_id'],
        'user' => $name ?: 'User#'.$row['user_id'],
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'flat_no' => $row['flat_no'] ?? '',
        'amount' => (float)$row['amount'],
        'method' => ucfirst($row['method'] ?? 'Unknown'),
        'date' => date('Y-m-d', strtotime($row['created_at'] ?? 'now')),
        'time' => date('H:i:s', strtotime($row['created_at'] ?? 'now')),
        'status' => $status,
        'reference' => $row['reference'] ?? '',
        'notes' => $row['notes'] ?? ''
    ];

    echo json_encode(['success' => true, 'payment' => $payment]);
} catch (Throwable $e) {
    error_log("Get payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load payment details']);
}
