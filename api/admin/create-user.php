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

$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$username = trim($_POST['username'] ?? '');
$flat_no = trim($_POST['flat_no'] ?? '');
$password = (string)($_POST['password'] ?? '');
$role = trim($_POST['role'] ?? 'user');

if ($first_name === '' || $surname === '' || $email === '' || $username === '' || $password === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Basic uniqueness checks
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR username = ?');
    $stmt->execute([$email, $username]);
    if ((int)$stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Email or username already exists']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Try flexible insert based on available columns
    $pdo->beginTransaction();
    try {
        $sql = "INSERT INTO users (first_name, middle_name, surname, email, phone, username, flat_no, role, password, status, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$first_name, $middle_name, $surname, $email, $phone, $username, $flat_no, $role, $hash]);
    } catch (Throwable $e) {
        // Fallback without status/is_active/role columns
        $sql2 = "INSERT INTO users (first_name, middle_name, surname, email, phone, username, flat_no, password, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$first_name, $middle_name, $surname, $email, $phone, $username, $flat_no, $hash]);
    }
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create user']);
}
