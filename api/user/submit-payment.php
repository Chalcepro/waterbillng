<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers FIRST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session after headers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration - CORRECTED PATH
require_once __DIR__ . '/../../config.php';

// Simple response function
function sendResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    http_response_code($success ? 200 : 400);
    echo json_encode($response);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Please log in to make payments');
}

$user_id = $_SESSION['user_id'];

try {
    // Get database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Determine content type
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';
    $input = [];
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON input
        $json = file_get_contents('php://input');
        $input = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendResponse(false, 'Invalid JSON data: ' . json_last_error_msg());
        }
    } else {
        // Form data input
        $input = $_POST;
        
        // Handle file upload
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $input['receipt'] = $_FILES['receipt'];
        }
    }
    
    // Log the received data for debugging
    $logInput = $input;
    if (isset($logInput['receipt']) && is_array($logInput['receipt'])) {
        $logInput['receipt'] = '[FILE] ' . $logInput['receipt']['name'];
    }
    error_log("Payment submission received: " . print_r($logInput, true));
    
    // Validate required fields
    $requiredFields = ['amount', 'payment_type', 'method'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        sendResponse(false, 'Missing required fields: ' . implode(', ', $missingFields));
    }
    
    // Validate amount
    $amount = floatval($input['amount']);
    if ($amount <= 0) {
        sendResponse(false, 'Invalid amount');
    }
    
    // Get minimum payment amount from system settings
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'min_payment_amount'");
    $stmt->execute();
    $minPaymentAmount = (float)($stmt->fetchColumn() ?: 500); // Default to 500 if not set
    
    // Validate minimum payment amount
    if ($amount < $minPaymentAmount) {
        sendResponse(false, "Minimum payment amount is ₦" . number_format($minPaymentAmount, 2) . ". Please enter a higher amount.");
    }
    
    $payment_type = trim($input['payment_type']);
    $method = strtolower(trim($input['method']));
    $reference = !empty($input['reference']) ? trim($input['reference']) : 'WD-R' . rand(1000000, 9999999);
    $transaction_id = !empty($input['transaction_id']) ? trim($input['transaction_id']) : $reference;
    
    // Additional fields for receipt uploads
    $bank_name = !empty($input['bankName']) ? trim($input['bankName']) : null;
    $transaction_date = !empty($input['transactionDate']) ? trim($input['transactionDate']) : null;
    
    // Handle file upload if method is receipt
    $receipt_path = null;
    if ($method === 'receipt') {
        if (!empty($input['receipt']) && is_array($input['receipt'])) {
            $file = $input['receipt'];
            $uploadDir = __DIR__ . '/../../uploads/receipts/';
            
            // Create upload directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception('Failed to create upload directory');
                }
            }
            
            // Validate file type (PDF only)
            $allowedTypes = ['application/pdf'];
            if (!in_array($file['type'], $allowedTypes)) {
                sendResponse(false, 'Only PDF files are allowed for receipts');
            }
            
            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                sendResponse(false, 'File size must be less than 5MB');
            }
            
            // Generate a unique filename
            $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'receipt_' . $user_id . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $filename;
            
            // Move the uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to save uploaded file');
            }
            
            $receipt_path = 'uploads/receipts/' . $filename;
        } else {
            sendResponse(false, 'Receipt file is required for receipt payment method');
        }
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Insert payment into database
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (user_id, amount, payment_type, status, method, transaction_id, reference, bank_name, transaction_date, receipt_image, created_at)
            VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $amount,
            $payment_type,
            $method,
            $transaction_id,
            $reference,
            $bank_name,
            $transaction_date,
            $receipt_path
        ]);
        
        $payment_id = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare response data
        $payment_data = [
            'payment_id' => $payment_id,
            'reference' => $reference,
            'amount' => $amount,
            'method' => $method,
            'payment_type' => $payment_type,
            'status' => 'pending',
            'user_id' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
            'receipt_path' => $receipt_path,
            'transaction_id' => $transaction_id,
            'bank_name' => $bank_name,
            'transaction_date' => $transaction_date
        ];
        
        sendResponse(true, 'Payment submitted successfully', $payment_data);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        // Clean up uploaded file if transaction failed
        if (!empty($receipt_path) && file_exists(__DIR__ . '/../../' . $receipt_path)) {
            @unlink(__DIR__ . '/../../' . $receipt_path);
        }
        
        throw $e; // Re-throw the exception
    }
    
} catch (Exception $e) {
    error_log("Payment error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
?>