<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users

// Set headers for JSON response
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if JSON was parsed correctly
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Get token and password from request
$token = $data['token'] ?? '';
$password = $data['password'] ?? '';

// Validate input
if (empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or expired reset token']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters long']);
    exit;
}

try {
    // Get database connection
    global $pdo;
    
    if (!isset($pdo)) {
        throw new Exception('Database connection failed');
    }
    
    // Find valid reset token (not expired and not used)
    $stmt = $pdo->prepare("
        SELECT pr.email, pr.expires_at, u.id as user_id 
        FROM password_resets pr
        JOIN users u ON pr.email = u.email
        WHERE pr.token = ? 
        AND pr.used = 0 
        AND pr.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resetRequest) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired reset token']);
        exit;
    }
    
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update user's password
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->execute([$hashedPassword, $resetRequest['email']]);
        
        // Mark token as used
        $updateStmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $updateStmt->execute([$token]);
        
        // Log the password change
        logActivity($resetRequest['user_id'], 'password_changed', 'Password changed via reset link');
        
        // Commit transaction
        $pdo->commit();
        
        // Invalidate all user sessions (optional but recommended)
        $updateStmt = $pdo->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
        $updateStmt->execute([$resetRequest['user_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Your password has been reset successfully. You can now log in with your new password.'
        ]);
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log('Password reset error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred while processing your request. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log('Password reset error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while resetting your password. Please try again.']);
}
?>
