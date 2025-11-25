<?php
    
// Bypass InfinityFree JavaScript challenge
if (isset($_GET['i'])) {
    // This is a redirect from the challenge, process normally
}

require_once __DIR__ . '/../../api/includes/session_boot.php';
// ... rest of your code
require_once __DIR__ . '/../../api/includes/session_boot.php';
session_boot();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$allowed = ['https://waterbill.free.nf'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

header('Content-Type: application/json');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Handle both JSON and form data
    $input = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Handle JSON input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? [];
    } else {
        // Handle form data
        $input = $_POST;
    }
    
    $identifier = trim($input['identifier'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        exit;
    }

    // Try to find user by username, email, or phone
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Clear any existing session data to prevent conflicts
        session_unset();
        
        // Build full name from available name parts
        $nameParts = array_filter([
            $user['first_name'],
            $user['middle_name'],
            $user['last_name']
        ]);
        $fullName = trim(implode(' ', $nameParts));
        
        // Set session variables with consistent naming
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_role'] = $user['role'];  // For backward compatibility
        $_SESSION['name'] = $fullName;
        
        // Ensure session is written
        session_write_close();
        
        $response = [
            'success' => true,
            'user_id' => $user['id'],
            'role' => $user['role'],
            'name' => $fullName,
            'message' => 'Login successful'
        ];
        
        // Log successful login (without sensitive data)
        error_log("Login successful for user ID: " . $user['id']);
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials. Please check your username/email/phone and password.'
        ]);
    }
} catch (Exception $e) {
    // Log the error
    error_log("Login error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Return a generic error message to the client
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.',
        'error' => 'Internal server error'
    ]);
}
?>