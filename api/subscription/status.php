<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Only allow authenticated users
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;

// If admin is requesting another user's data
if ($isAdmin && isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
}

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get the latest active subscription
    $query = "SELECT * FROM subscriptions WHERE user_id = ? AND (status = 'active' OR end_date >= CURDATE()) ORDER BY end_date DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'has_subscription' => false,
            'message' => 'No active subscription found'
        ]);
        exit;
    }
    
    $subscription = $result->fetch_assoc();
    $startDate = new DateTime($subscription['start_date']);
    $endDate = new DateTime($subscription['end_date']);
    $today = new DateTime();
    
    // Calculate days remaining
    $interval = $today->diff($endDate);
    $daysRemaining = $interval->invert ? 0 : $interval->days;
    
    // Calculate percentage used
    $totalDays = $startDate->diff($endDate)->days;
    $daysUsed = $startDate->diff($today)->days;
    $percentageUsed = min(100, round(($daysUsed / $totalDays) * 100));
    
    // Determine status
    $status = $subscription['status'];
    if ($status === 'active' && $daysRemaining <= 0) {
        $status = 'expired';
        // Update status in database
        $updateStmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
        $updateStmt->bind_param('i', $subscription['id']);
        $updateStmt->execute();
    }
    
    echo json_encode([
        'has_subscription' => true,
        'start_date' => $subscription['start_date'],
        'end_date' => $subscription['end_date'],
        'status' => $status,
        'days_remaining' => $daysRemaining,
        'percentage_used' => $percentageUsed,
        'total_days' => $totalDays,
        'days_used' => min($daysUsed, $totalDays)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch subscription status',
        'message' => $e->getMessage()
    ]);
}
?>
