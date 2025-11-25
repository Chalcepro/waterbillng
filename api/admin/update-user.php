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
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$required = ['id', 'username', 'email', 'first_name', 'surname', 'role'];
$errors = [];

foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (!in_array($_POST['role'], ['user', 'admin'])) {
    $errors[] = 'Invalid role';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$_POST['email'], $_POST['id']]);
    if ($stmt->fetch()) {
        throw new Exception('Email already in use by another account');
    }

    // Check if username is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$_POST['username'], $_POST['id']]);
    if ($stmt->fetch()) {
        throw new Exception('Username already taken');
    }

    // Update user
    $updateFields = [
        'username' => $_POST['username'],
        'first_name' => $_POST['first_name'],
        'middle_name' => $_POST['middle_name'] ?? '',
        'surname' => $_POST['surname'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'] ?? '',
        'flat_no' => $_POST['flat_no'] ?? '',
        'role' => $_POST['role'],
        'status' => $_POST['status'] ?? 'active'
    ];

    // Only update password if provided
    if (!empty($_POST['password'])) {
        if (strlen($_POST['password']) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        $updateFields['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    $setClause = [];
    $params = [];
    foreach ($updateFields as $field => $value) {
        $setClause[] = "$field = ?";
        $params[] = $value;
    }
    $params[] = $_POST['id'];

    $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No changes made or user not found');
    }

    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
