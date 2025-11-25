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
    // Discover available columns to avoid SQL errors on unknown columns
    $colsStmt = $pdo->query("SHOW COLUMNS FROM users");
    $available = array_map(function($r){ return strtolower($r['Field']); }, $colsStmt->fetchAll(PDO::FETCH_ASSOC));

    $has = function($name) use ($available){ return in_array(strtolower($name), $available, true); };

    $select = [];
    $select[] = 'id';
    if ($has('first_name')) $select[] = 'first_name';
    if ($has('middle_name')) $select[] = 'middle_name';
    if ($has('surname')) $select[] = 'surname';
    if ($has('email')) $select[] = 'email'; else $select[] = "'' AS email";
    if ($has('phone')) $select[] = 'phone'; else $select[] = "'' AS phone";
    if ($has('username')) $select[] = 'username'; else $select[] = "'' AS username";
    if ($has('flat_no')) $select[] = 'flat_no'; else $select[] = "'' AS flat_no";

    // status normalization
    if ($has('status')) {
        $select[] = "LOWER(status) AS status";
    } elseif ($has('is_active')) {
        $select[] = "CASE WHEN is_active=1 THEN 'active' ELSE 'inactive' END AS status";
    } else {
        $select[] = "'active' AS status";
    }

    // created/registered date
    if ($has('created_at')) {
        $select[] = 'created_at';
    } elseif ($has('registered_at')) {
        $select[] = 'registered_at AS created_at';
    } else {
        $select[] = 'NOW() AS created_at';
    }

    $sql = 'SELECT ' . implode(',', $select) . ' FROM users ORDER BY id DESC';
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $users = array_map(function($u){
        $name = trim(($u['first_name'] ?? '') . ' ' . ($u['middle_name'] ?? '') . ' ' . ($u['surname'] ?? ''));
        return [
            'id' => (int)$u['id'],
            'name' => $name ?: ($u['username'] ?? 'User'),
            'email' => $u['email'] ?? '',
            'phone' => $u['phone'] ?? '',
            'username' => $u['username'] ?? '',
            'flat_no' => $u['flat_no'] ?? '',
            'status' => strtolower($u['status'] ?? 'active'),
            'joined' => date('Y-m-d', strtotime($u['created_at']))
        ];
    }, $rows);

    echo json_encode(['success' => true, 'users' => $users]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load users']);
}
