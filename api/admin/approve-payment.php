<?php
/**
 * Approve or reject a payment
 * 
 * Required POST parameters:
 * - payment_id: The ID of the payment to approve/reject
 * - action: Either 'approve' or 'reject'
 * - notes: Optional notes about the approval/rejection
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

// Start session with secure settings
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax'
]);

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'debug' => (object)[]
];

try {
    // Check if user is admin
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        throw new Exception('Unauthorized: Please log in as an administrator');
    }

    // Check if request is POST (for HTTP) or if we're running in CLI mode
    $isCli = (php_sapi_name() === 'cli');
    $isPost = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
    
    if (!$isCli && !$isPost) {
        throw new Exception('Method not allowed. Use POST method.');
    }

    // Get and validate input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST; // Fall back to form data if JSON parsing fails
    }
    
    $payment_id = (int)($input['payment_id'] ?? 0);
    $action = strtolower(trim($input['action'] ?? ''));
    $notes = trim($input['notes'] ?? '');
    
    // Validate input
    if (!$payment_id) {
        throw new Exception('Payment ID is required');
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action. Must be either "approve" or "reject"');
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if payment exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception("Payment #{$payment_id} not found");
    }
    
    // Only allow updating pending payments
    if ($payment['status'] !== 'pending') {
        throw new Exception("Payment #{$payment_id} is already {$payment['status']}");
    }
    
    // Calculate new status
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $now = date('Y-m-d H:i:s');
    
    // Prepare the admin notes
    $admin_notes = "[{$now}] {$action}d by admin: {$notes}";
    if (!empty($payment['admin_notes'])) {
        $admin_notes = $payment['admin_notes'] . "\n" . $admin_notes;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // 1. Update payment status
        $update_sql = "
            UPDATE payments 
            SET status = :status,
                admin_notes = :admin_notes,
                updated_at = :updated_at
            WHERE id = :id
        ";
        
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute([
            ':status' => $new_status,
            ':admin_notes' => $admin_notes,
            ':updated_at' => $now,
            ':id' => $payment_id
        ]);
        
        // 2. If approved, update user subscription
        if ($action === 'approve') {
            // Include subscription manager
            require_once __DIR__ . '/../includes/subscription_manager.php';
            
            try {
                // Get payment details including user_id
                $stmt = $pdo->prepare("
                    SELECT p.*, u.subscription_status, u.subscription_end_date 
                    FROM payments p
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE p.id = ?
                
                ");
                $stmt->execute([$payment_id]);
                $paymentDetails = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($paymentDetails) {
                    // Update the user's subscription based on payment
                    updateUserSubscription($pdo, $paymentDetails);
                    
                    // Update payment record with subscription details
                    $updatePayment = "
                        UPDATE payments 
                        SET is_subscription_payment = 1,
                            updated_at = NOW()
                        WHERE id = ?
                    ";
                    $pdo->prepare($updatePayment)->execute([$payment_id]);
                    
                    // Get updated user data
                    $stmt = $pdo->prepare("
                        SELECT subscription_status, subscription_end_date 
                        FROM users 
                        WHERE id = ?
                    
                    ");
                    $stmt->execute([$paymentDetails['user_id']]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $response['subscription'] = [
                        'status' => $userData['subscription_status'],
                        'end_date' => $userData['subscription_end_date']
                    ];
                }
            } catch (Exception $e) {
                // Log the error but don't fail the entire operation
                error_log("Error updating subscription for payment {$payment_id}: " . $e->getMessage());                
                // Add debug info to response
                $response['debug']->subscription_error = $e->getMessage();
            }
        }
        
        // 3. Create a notification for the user
        $notification_type = 'payment'; // Using the enum value from the table
        $title = 'Payment ' . ucfirst($action) . 'd';
        $message = "Your payment of {$payment['amount']} has been {$new_status}.";
        
        if (!empty($notes)) {
            $message .= "\n\nNote: {$notes}";
        }
        
        // Check if notifications table exists
        $notifications_table = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
        
        if ($notifications_table) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (
                    title,
                    message,
                    type,
                    created_by,
                    recipient_count
                ) VALUES (
                    :title,
                    :message,
                    :type,
                    :created_by,
                    1
                )
            ");
            
            $stmt->execute([
                ':title' => $title,
                ':message' => $message,
                ':type' => $notification_type,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            // Get the notification ID for the recipient
            $notification_id = $pdo->lastInsertId();
            
            // Insert into notification_recipients if the table exists
            $recipients_table = $pdo->query("SHOW TABLES LIKE 'notification_recipients'")->rowCount() > 0;
            
            if ($recipients_table) {
                $stmt = $pdo->prepare("
                    INSERT INTO notification_recipients (
                        notification_id,
                        user_id,
                        is_read,
                        read_at
                    ) VALUES (
                        :notification_id,
                        :user_id,
                        0,
                        NULL
                    )
                ");
                
                $stmt->execute([
                    ':notification_id' => $notification_id,
                    ':user_id' => $payment['user_id']
                ]);
            }
        }
        
        // Commit the transaction
        $pdo->commit();
        
        // Set success response
        $response = [
            'success' => true,
            'message' => "Payment successfully {$action}d",
            'payment_id' => $payment_id,
            'new_status' => $new_status
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'error' => [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ];
    
    // Log the error
    error_log('Payment approval error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);

// End of script
exit;