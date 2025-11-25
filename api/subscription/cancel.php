<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Only allow admin users
$auth = new Auth();
if (!$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Get and validate input
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['subscription_id']) || !is_numeric($input['subscription_id'])) {
        throw new Exception('Invalid subscription ID');
    }
    
    $subscriptionId = (int)$input['subscription_id'];
    $reason = $input['reason'] ?? '';
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get subscription details
        $query = "SELECT * FROM subscriptions WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $subscriptionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Subscription not found');
        }
        
        $subscription = $result->fetch_assoc();
        
        // Cancel the subscription
        $updateQuery = "UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('i', $subscriptionId);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows === 0) {
            throw new Exception('Failed to cancel subscription');
        }
        
        // Log the cancellation
        $logQuery = "INSERT INTO subscription_logs (subscription_id, action, details, admin_id) VALUES (?, 'cancelled', ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $details = json_encode([
            'reason' => $reason,
            'original_end_date' => $subscription['end_date']
        ]);
        $adminId = $_SESSION['user_id'];
        $logStmt->bind_param('isi', $subscriptionId, $details, $adminId);
        $logStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
