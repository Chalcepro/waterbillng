<?php
// Simple fault report submission
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session and include required files
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../../config.php';

// Simple error logging function
function logError($message) {
    $logFile = __DIR__ . '/../../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to submit a report']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get form data
$userId = (int)$_SESSION['user_id'];
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$photoPath = null;

// Simple validation
if (empty($category) || empty($description)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields']);
    exit;
}

// Handle file upload if present
if (!empty($_FILES['photo']['name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../../uploads/faults/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    
    if (in_array($ext, $allowed)) {
        $filename = 'fault_' . $userId . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photoPath = 'uploads/faults/' . $filename;
        }
    }
}

try {
    // Simple insert query
    $stmt = $pdo->prepare("
        INSERT INTO fault_reports 
        (user_id, category, description, photo_path, status) 
        VALUES (?, ?, ?, ?, 'open')
    ");
    
    $stmt->execute([$userId, $category, $description, $photoPath]);
    $reportId = $pdo->lastInsertId();
    
    // Simple success response
    echo json_encode([
        'success' => true,
        'message' => 'Report submitted successfully',
        'reportId' => $reportId
    ]);
    
} catch (PDOException $e) {
    // Log the error
    logError('Database error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit report. Please try again.'
    ]);
}
