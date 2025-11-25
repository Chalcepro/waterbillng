<?php
// api/auth/login-direct.php - FIXED VERSION

error_reporting(0); // Turn off error display
ini_set('display_errors', 0);

header('Content-Type: application/json');

// CORS headers
$allowed_origins = ['https://waterbill.free.nf', 'http://localhost'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
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

// Database connection - SIMPLIFIED
try {
    require_once __DIR__ . '/../includes/db_connect.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    // Normalize identifier
    $identifier = $input['identifier'] ?? $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Identifier and password are required'
        ]);
        exit;
    }

    // Lookup user
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1');
    $stmt->execute([$identifier, $identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Start session
        session_start();
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['surname'] ?? ''));
        
        // Remove password from response
        unset($user['password']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials'
        ]);
    }

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed'
    ]);
}
?>