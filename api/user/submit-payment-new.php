<?php
// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * Send JSON response with consistent format
 */
function sendJsonResponse($success, $message, $data = [], $httpCode = null) {
    $httpCode = $httpCode ?: ($success ? 200 : 400);
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    // Include error details in development
    if (!$success && defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $response['debug'] = [
            'file' => debug_backtrace()[0]['file'] ?? null,
            'line' => debug_backtrace()[0]['line'] ?? null
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Start session and check authentication
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'Authentication required. Please log in to continue.', [], 401);
}

// Get authenticated user ID
$user_id = $_SESSION['user_id'];

// Process payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database connection
        $pdo = getDBConnection();
        
        // Initialize input array
        $input = [];
        
        // Parse request body based on content type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // Handle JSON input
            $jsonInput = file_get_contents('php://input');
            $input = json_decode($jsonInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(false, 'Invalid JSON input: ' . json_last_error_msg(), [], 400);
            }
        } elseif (strpos($contentType, 'multipart/form-data') !== false || 
                 strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            // Handle form data
            $input = $_POST;
            
            // Handle file uploads if present
            if (!empty($_FILES)) {
                foreach ($_FILES as $key => $file) {
                    $input[$key] = $file;
                }
            }
        } else {
            // Unsupported content type
            sendJsonResponse(false, 'Unsupported Content-Type. Please use application/json or multipart/form-data', [], 415);
        }
        
        // Validate required fields
        $requiredFields = ['amount', 'payment_type', 'method'];
        $missingFields = [];
        $validationErrors = [];
        
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            sendJsonResponse(false, 'Missing required fields: ' . implode(', ', $missingFields), [
                'missing_fields' => $missingFields
            ], 400);
        }
        
        // Sanitize and validate input
        $amount = filter_var($input['amount'], FILTER_VALIDATE_FLOAT);
        $payment_type = trim(htmlspecialchars($input['payment_type']));
        $method = strtolower(trim($input['method']));
        $reference = !empty($input['reference']) ? trim($input['reference']) : generateReferenceNumber();
        $transaction_id = !empty($input['transaction_id']) ? trim($input['transaction_id']) : $reference;
        $bank_name = !empty($input['bank_name']) ? trim(htmlspecialchars($input['bank_name'])) : '';
        $transaction_date = !empty($input['transaction_date']) ? trim($input['transaction_date']) : date('Y-m-d');
        
        // Validate amount
        if ($amount === false || $amount <= 0) {
            $validationErrors['amount'] = 'Please enter a valid amount greater than zero';
        }
        
        // Validate payment type
        $valid_payment_types = ['recharge', 'maintenance', 'bill', 'subscription'];
        if (!in_array(strtolower($payment_type), $valid_payment_types)) {
            $validationErrors['payment_type'] = 'Invalid payment type. Must be one of: ' . implode(', ', $valid_payment_types);
        }
        
        // Validate payment method
        $valid_methods = ['paystack', 'bank-transfer', 'opay', 'receipt', 'cash'];
        if (!in_array($method, $valid_methods)) {
            $validationErrors['method'] = 'Invalid payment method. Must be one of: ' . implode(', ', $valid_methods);
        }
        
        // Validate transaction date
        if (!strtotime($transaction_date)) {
            $validationErrors['transaction_date'] = 'Invalid transaction date';
        } elseif (strtotime($transaction_date) > strtotime('today')) {
            $validationErrors['transaction_date'] = 'Transaction date cannot be in the future';
        }
        
        // If validation errors exist, return them
        if (!empty($validationErrors)) {
            sendJsonResponse(false, 'Validation failed', ['errors' => $validationErrors], 422);
        }
        
        // Handle file upload if method is receipt
        $receipt_path = null;
        if ($method === 'receipt') {
            if (empty($input['receipt']) || !is_array($input['receipt']) || $input['receipt']['error'] !== UPLOAD_ERR_OK) {
                sendJsonResponse(false, 'Valid receipt file is required', ['error' => 'No file uploaded or upload error'], 400);
            }
            
            $file = $input['receipt'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            $allowedTypes = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/jpg' => 'jpg'
            ];
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                ];
                
                $errorMessage = $uploadErrors[$file['error']] ?? 'Unknown upload error';
                sendJsonResponse(false, 'File upload failed: ' . $errorMessage, [], 400);
            }
            
            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!array_key_exists($mimeType, $allowedTypes)) {
                sendJsonResponse(
                    false, 
                    'Invalid file type. Please upload a PDF, JPG, or PNG file.', 
                    ['allowed_types' => array_keys($allowedTypes)], 
                    400
                );
            }
            
            // Validate file size
            if ($file['size'] > $maxFileSize) {
                sendJsonResponse(
                    false, 
                    'File is too large. Maximum allowed size is 5MB.', 
                    ['max_size' => $maxFileSize], 
                    400
                );
            }
            
            // Generate unique filename
            $fileExt = $allowedTypes[$mimeType];
            $filename = 'receipt_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $uploadDir = __DIR__ . '/../../uploads/receipts/';
            
            // Create upload directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    error_log("Failed to create directory: $uploadDir");
                    sendJsonResponse(false, 'Failed to create upload directory', [], 500);
                }
            }
            
            // Ensure directory is writable
            if (!is_writable($uploadDir)) {
                error_log("Upload directory is not writable: $uploadDir");
                sendJsonResponse(false, 'Upload directory is not writable', [], 500);
            }
            
            $destination = $uploadDir . $filename;
            
            // Move uploaded file to destination
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                error_log("Failed to move uploaded file to: $destination");
                sendJsonResponse(false, 'Failed to save uploaded file', [], 500);
            }
            
            // Set the relative path for database storage
            $receipt_path = 'uploads/receipts/' . $filename;
        }
        
        // Begin database transaction
        $pdo->beginTransaction();
        
        try {
            // Check if reference already exists
            $stmt = $pdo->prepare("SELECT id FROM payments WHERE reference = ? LIMIT 1");
            $stmt->execute([$reference]);
            
            if ($stmt->fetch()) {
                throw new Exception('A payment with this reference already exists');
            }
            
            // Insert payment record
            $stmt = $pdo->prepare("
                INSERT INTO payments 
                (user_id, amount, status, method, reference, transaction_id, receipt_path, 
                 bank_name, transaction_date, payment_type, created_at, updated_at) 
                VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $amount,
                $method,
                $reference,
                $transaction_id,
                $receipt_path,
                $bank_name,
                $transaction_date,
                $payment_type
            ]);
            
            $payment_id = $pdo->lastInsertId();
            
            // Log the payment
            $logStmt = $pdo->prepare("
                INSERT INTO payment_logs 
                (payment_id, user_id, status, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([$payment_id, $user_id, 'pending', 'Payment submitted for verification']);
            
            // Create notification for admin
            $notificationStmt = $pdo->prepare("
                INSERT INTO notifications 
                (user_id, title, message, type, status, created_at)
                VALUES (?, ?, ?, ?, 'unread', NOW())
            ");
            
            $notificationTitle = 'New ' . ucfirst($method) . ' Payment';
            $notificationMessage = sprintf(
                'User #%d submitted a %s payment of ₦%s for %s (Ref: %s)',
                $user_id,
                $method,
                number_format($amount, 2),
                $payment_type,
                $reference
            );
            
            // Get admin users to notify
            $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            $adminStmt->execute();
            
            // Notify all admin users
            $notified = false;
            while ($admin = $adminStmt->fetch(PDO::FETCH_ASSOC)) {
                $notificationStmt->execute([
                    $admin['id'],
                    $notificationTitle,
                    $notificationMessage,
                    'payment_' . $method
                ]);
                $notified = true;
            }
            
            // If no admins, log it
            if (!$notified) {
                error_log("No active admin users found to notify about payment #$payment_id");
            }
            
            // Commit the transaction
            $pdo->commit();
            
            // Prepare success response
            $responseData = [
                'payment_id' => $payment_id,
                'reference' => $reference,
                'amount' => $amount,
                'method' => $method,
                'status' => 'pending',
                'payment_type' => $payment_type,
                'transaction_date' => $transaction_date
            ];
            
            // Add receipt path if available
            if ($receipt_path) {
                $responseData['receipt_url'] = '/' . ltrim($receipt_path, '/');
            }
            
            // Return success response
            sendJsonResponse(true, 'Payment submitted successfully', $responseData);
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Clean up uploaded file if transaction failed
            if (!empty($receipt_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $receipt_path)) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $receipt_path);
            }
            
            error_log("Database error in payment processing: " . $e->getMessage());
            throw new Exception('Failed to process payment. Please try again.');
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Clean up uploaded file if transaction failed
            if (!empty($receipt_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $receipt_path)) {
                @unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $receipt_path);
            }
            
            throw $e; // Re-throw the exception for the outer catch block
        }
        
    } catch (PDOException $e) {
        // Log database errors with more details
        error_log(sprintf(
            'Database error in %s on line %d: %s',
            $e->getFile(),
            $e->getLine(),
            $e->getMessage()
        ));
        
        // Return a generic error message to the client
        sendJsonResponse(
            false, 
            'A database error occurred while processing your request. Please try again.',
            ['code' => $e->getCode()],
            500
        );
        
    } catch (Exception $e) {
        // Log general errors
        error_log('Payment submission error: ' . $e->getMessage());
        
        // Return the error message to the client
        sendJsonResponse(
            false, 
            $e->getMessage(),
            [],
            $e->getCode() >= 400 ? $e->getCode() : 400
        );
    }
} else {
    sendJsonResponse(false, 'Invalid request method', [], 405);
}

/**
 * Generate a unique reference number for payments
 */
function generateReferenceNumber($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $reference = '';
    
    for ($i = 0; $i < $length; $i++) {
        $reference .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return 'WD-' . $reference;
}

// Add CORS headers for development
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: POST, OPTIONS");
    }
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    
    exit(0);
}
?>
