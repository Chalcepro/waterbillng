<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use correct relative path for includes
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Ensure the user is authenticated and has admin privileges
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get the user ID from the request (both GET and POST)
$userId = $_REQUEST['id'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

try {
    // Debugging log
    error_log("Attempting to delete user with ID: " . $userId);
    
    // Verify user ID is valid
    if (!is_numeric($userId)) {
        throw new Exception('Invalid user ID');
    }

    // First, check if the user exists
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id');
    $checkStmt->execute(['id' => $userId]);
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    // Delete the user from the database
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    
    // Check for foreign key constraint violation
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false, 
            'error' => 'Cannot delete user. This user has related records in the system.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}
