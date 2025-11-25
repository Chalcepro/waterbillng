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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all subscriptions with user information
    $query = "
        SELECT 
            s.*,
            u.name as user_name,
            u.email as user_email,
            DATEDIFF(s.end_date, s.start_date) as total_days,
            DATEDIFF(CURDATE(), s.start_date) as days_used,
            DATEDIFF(s.end_date, CURDATE()) as days_remaining,
            ROUND((DATEDIFF(CURDATE(), s.start_date) / DATEDIFF(s.end_date, s.start_date)) * 100, 1) as percentage_used
        FROM subscriptions s
        JOIN users u ON s.user_id = u.id
        ORDER BY s.end_date ASC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Failed to fetch subscriptions: ' . $conn->error);
    }
    
    $subscriptions = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate percentage used (capped at 100%)
        $row['percentage_used'] = min(100, max(0, (float)$row['percentage_used']));
        
        // Update status if expired
        if ($row['status'] === 'active' && $row['days_remaining'] < 0) {
            $row['status'] = 'expired';
            // Update in database
            $updateStmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
            $updateStmt->bind_param('i', $row['id']);
            $updateStmt->execute();
        }
        
        $subscriptions[] = $row;
    }
    
    echo json_encode($subscriptions);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch subscriptions',
        'message' => $e->getMessage()
    ]);
}
?>
