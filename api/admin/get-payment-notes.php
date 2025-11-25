<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if payment_id is provided
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
    exit;
}

$paymentId = $_GET['payment_id'];

try {
    // Get database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT admin_notes FROM manual_payments WHERE id = :id");
    $stmt->bindParam(':id', $paymentId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'notes' => $result['admin_notes']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Payment not found'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching payment notes'
    ]);
}
?>
