<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated and is an admin
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Query to get all fault reports with user information
    $query = "SELECT 
                fr.*, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone_number 
              FROM fault_reports fr 
              LEFT JOIN users u ON fr.user_id = u.id 
              ORDER BY fr.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'reports' => $reports
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
