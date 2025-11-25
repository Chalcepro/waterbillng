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
    // Try to read from fault_reports; if it doesn't exist, return empty list
    try {
        $sql = "SELECT fr.id, fr.user_id, fr.category, fr.description, fr.photo_path, fr.status, fr.created_at,
                       u.first_name, u.middle_name, u.surname, u.flat_no
                FROM fault_reports fr
                LEFT JOIN users u ON fr.user_id = u.id
                ORDER BY fr.created_at DESC
                LIMIT 200";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows = [];
    }

    $items = array_map(function($r){
        $name = trim(($r['first_name'] ?? '').' '.($r['middle_name'] ?? '').' '.($r['surname'] ?? ''));
        return [
            'id' => (int)($r['id'] ?? 0),
            'user' => $name ?: ('User#'.($r['user_id'] ?? '')),
            'flat' => $r['flat_no'] ?? '',
            'category' => $r['category'] ?? 'other',
            'description' => $r['description'] ?? '',
            'photo' => $r['photo_path'] ?? null,
            'status' => $r['status'] ?? 'open',
            'date' => isset($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'])) : ''
        ];
    }, $rows);

    echo json_encode(['success' => true, 'complaints' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load complaints']);
}
