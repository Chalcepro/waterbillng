<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$status = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : '';

if ($userId <= 0 || !in_array($status, ['active', 'inactive'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    // Support either a string status column or a boolean is_active column
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);
        if ($stmt->rowCount() === 0) {
            // Fallback to is_active boolean
            $stmt2 = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt2->execute([$status === 'active' ? 1 : 0, $userId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update user status']);
}
