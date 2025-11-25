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
    
    if (!isset($input['user_id']) || !is_numeric($input['user_id'])) {
        throw new Exception('Invalid user ID');
    }
    
    $userId = (int)$input['user_id'];
    $days = isset($input['days']) ? (int)$input['days'] : 30; // Default to 30 days
    $reason = $input['reason'] ?? '';
    
    if ($days < 1) {
        throw new Exception('Number of days must be at least 1');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current subscription or create new one
        $query = "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY end_date DESC LIMIT 1 FOR UPDATE";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $newEndDate = new DateTime();
        $newEndDate->modify("+{$days} days");
        $newEndDateStr = $newEndDate->format('Y-m-d');
        
        if ($result->num_rows > 0) {
            // Extend existing subscription
            $subscription = $result->fetch_assoc();
            $currentEndDate = new DateTime($subscription['end_date']);
            
            // If subscription is expired, start from today, otherwise extend from end date
            $extendFrom = $currentEndDate > new DateTime() ? $currentEndDate : new DateTime();
            $extendFrom->modify("+{$days} days");
            $newEndDateStr = $extendFrom->format('Y-m-d');
            
            $updateQuery = "UPDATE subscriptions SET end_date = ?, status = 'active' WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('si', $newEndDateStr, $subscription['id']);
            $updateStmt->execute();
            
            if ($updateStmt->affected_rows === 0) {
                throw new Exception('Failed to update subscription');
            }
            
            $subscriptionId = $subscription['id'];
        } else {
            // Create new subscription
            $startDate = new DateTime();
            $insertQuery = "INSERT INTO subscriptions (user_id, start_date, end_date, status) VALUES (?, ?, ?, 'active')";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param('iss', $userId, $startDate->format('Y-m-d'), $newEndDateStr);
            $insertStmt->execute();
            
            if ($insertStmt->affected_rows === 0) {
                throw new Exception('Failed to create subscription');
            }
            
            $subscriptionId = $conn->insert_id;
        }
        
        // Log the extension
        $logQuery = "INSERT INTO subscription_logs (subscription_id, action, details, admin_id) VALUES (?, 'extended', ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $details = json_encode([
            'days_added' => $days,
            'new_end_date' => $newEndDateStr,
            'reason' => $reason
        ]);
        $adminId = $_SESSION['user_id'];
        $logStmt->bind_param('isi', $subscriptionId, $details, $adminId);
        $logStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription extended successfully',
            'new_end_date' => $newEndDateStr
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
