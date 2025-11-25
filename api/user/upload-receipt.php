<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config.php';

// Set error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Validate required fields
$requiredFields = ['amount', 'transactionDate', 'bankName', 'transactionRef'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Validate receipt file
if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please upload a valid receipt file']);
    exit;
}

$receiptFile = $_FILES['receipt'];

// Validate file type
$allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$fileMimeType = finfo_file($fileInfo, $receiptFile['tmp_name']);
finfo_close($fileInfo);

if (!in_array($fileMimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a PDF, JPEG, or PNG file']);
    exit;
}

// Validate file size (max 5MB)
$maxFileSize = 5 * 1024 * 1024; // 5MB
if ($receiptFile['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB']);
    exit;
}

// Generate unique filename
$fileExtension = pathinfo($receiptFile['name'], PATHINFO_EXTENSION);
$fileName = 'receipt_' . time() . '_' . uniqid() . '.' . $fileExtension;
$uploadPath = __DIR__ . '/../../uploads/' . $fileName;

// Move uploaded file
if (!move_uploaded_file($receiptFile['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    exit;
}

try {
    // Get database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL statement
    $stmt = $pdo->prepare("
        INSERT INTO manual_payments (
            user_id, 
            amount, 
            payment_date, 
            payment_method, 
            transaction_reference, 
            bank_name,
            payment_proof,
            status,
            created_at
        ) VALUES (
            :user_id, 
            :amount, 
            :payment_date, 
            'bank_transfer', 
            :transaction_reference, 
            :bank_name,
            :payment_proof,
            'pending',
            NOW()
        )
    ");

    // Bind parameters
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':amount', $_POST['amount'], PDO::PARAM_STR);
    $stmt->bindParam(':payment_date', $_POST['transactionDate'], PDO::PARAM_STR);
    $stmt->bindParam(':transaction_reference', $_POST['transactionRef'], PDO::PARAM_STR);
    $stmt->bindParam(':bank_name', $_POST['bankName'], PDO::PARAM_STR);
    $stmt->bindParam(':payment_proof', $fileName, PDO::PARAM_STR);

    // Execute the statement
    $stmt->execute();

    // Get the inserted payment ID
    $paymentId = $pdo->lastInsertId();

    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Receipt uploaded successfully', 
        'payment_id' => $paymentId
    ]);

} catch (PDOException $e) {
    // Log the error
    error_log("Database error: " . $e->getMessage());
    
    // Delete the uploaded file if database operation failed
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your payment. Please try again.'
    ]);
}
?>
