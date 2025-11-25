<?php
// Set headers for CORS and JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Required fields
    $required = ['user_id', 'first_name', 'surname', 'email', 'phone', 'current_password'];
    $missing = array_diff($required, array_keys($input));
    
    if (!empty($missing)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing));
    }

    $user_id = (int)$input['user_id'];
    
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Verify current password
    if (!password_verify($input['current_password'], $user['password'])) {
        throw new Exception('Current password is incorrect');
    }

    // Prepare update data
    $updateFields = [
        'first_name' => $input['first_name'],
        'middle_name' => $input['middle_name'] ?? $user['middle_name'],
        'surname' => $input['surname'],
        'email' => $input['email'],
        'phone' => $input['phone'],
        'flat_no' => $input['flat_no'] ?? $user['flat_no']
    ];

    // Update password if new one is provided
    if (!empty($input['new_password'])) {
        if (strlen($input['new_password']) < 6) {
            throw new Exception('New password must be at least 6 characters');
        }
        $updateFields['password'] = password_hash($input['new_password'], PASSWORD_DEFAULT);
    }

    // Build update query
    $setClause = [];
    $params = [];
    foreach ($updateFields as $field => $value) {
        $setClause[] = "$field = ?";
        $params[] = $value;
    }
    $params[] = $user_id; // For WHERE clause

    $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if (!$result) {
        throw new Exception('Failed to update profile');
    }

    // Get updated user data
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, middle_name, surname, flat_no, phone, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $updatedUser
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
