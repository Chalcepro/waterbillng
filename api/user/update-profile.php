<?php
// api/user/update-profile.php - FIXED FOR YOUR DATABASE SCHEMA

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

header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Authentication required', 
        'redirect' => '/waterbill/frontend/auth/login.html'
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    $required_fields = ['first_name', 'surname', 'flat_no', 'email', 'phone'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty(trim($input[$field] ?? ''))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }

    // Sanitize input
    $user_id = $_SESSION['user_id'];
    $first_name = trim($input['first_name']);
    $middle_name = trim($input['middle_name'] ?? '');
    $surname = trim($input['surname']); // This will be stored as last_name
    $flat_no = trim($input['flat_no']);
    $email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $input['phone']);
    
    // Optional password fields
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate phone (11 digits for Nigeria)
    if (strlen($phone) !== 11) {
        throw new Exception('Phone number must be 11 digits');
    }

    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // If password change is requested, verify current password
    $password_updated = false;
    if (!empty($new_password)) {
        if (empty($current_password)) {
            throw new Exception('Current password is required to set a new password');
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }

        if (strlen($new_password) < 6) {
            throw new Exception('New password must be at least 6 characters long');
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match');
        }
        
        $password_updated = true;
    }

    // Check if email is already taken by another user
    $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $user_id]);
    if ($emailCheck->fetch()) {
        throw new Exception('Email is already taken by another user');
    }

    // Check if phone is already taken by another user
    $phoneCheck = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $phoneCheck->execute([$phone, $user_id]);
    if ($phoneCheck->fetch()) {
        throw new Exception('Phone number is already taken by another user');
    }

    // Prepare update data - mapping surname to last_name for database
    $updateData = [
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $surname, // Store as last_name in database
        'flat_no' => $flat_no,
        'email' => $email,
        'phone' => $phone,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Update full_name field as well
    $full_name = trim("$first_name " . ($middle_name ? "$middle_name " : '') . $surname);
    $updateData['full_name'] = $full_name;

    // Add password to update if changed
    if ($password_updated) {
        $updateData['password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // Build and execute update query
    $setClause = [];
    $params = [];
    foreach ($updateData as $field => $value) {
        $setClause[] = "$field = ?";
        $params[] = $value;
    }
    $params[] = $user_id;

    $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if (!$result) {
        throw new Exception('Failed to update profile');
    }

    // Update session data
    $_SESSION['name'] = $full_name;
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $email;

    // Get updated user data for response
    $stmt = $pdo->prepare("
        SELECT id, username, email, first_name, middle_name, last_name, flat_no, phone, full_name
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Map last_name back to surname for frontend response
    $responseUser = $updatedUser;
    $responseUser['surname'] = $updatedUser['last_name'];

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully' . ($password_updated ? ' and password changed' : ''),
        'user' => $responseUser
    ]);

} catch (Exception $e) {
    error_log("Update profile error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>