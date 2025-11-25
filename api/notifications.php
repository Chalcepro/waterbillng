<?php
// API for notifications (fetch/send)
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/notification_helper.php';

// Force JSON-only responses and prevent HTML error output from breaking JSON
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error reporting for development
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');

// Get the current user ID from session or token
function getCurrentUserId() {
    // This is a placeholder - implement your actual user authentication
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? 0;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $currentUserId = getCurrentUserId();

    if ($method === 'GET') {
        // Get query parameters
        $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $currentUserId;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $unreadOnly = isset($_GET['unread']) ? (bool)$_GET['unread'] : false;
        
        // Sanitize inputs
        $limit = max(1, min(100, $limit));
        
        // Check permissions
        if ($userId !== $currentUserId && $currentUserId !== 1) { // Assuming 1 is admin
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        // Get notifications
        $notifications = getUserNotifications($userId, $limit, $unreadOnly);
        $unreadCount = getUnreadNotificationCount($userId);
        
        echo json_encode([
            'success' => true, 
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        exit;
    }

    if ($method === 'POST') {
        // Only allow admins to send notifications
        if ($currentUserId !== 1) { // Assuming 1 is admin
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        // Get and validate input
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
        $type = isset($input['type']) ? $input['type'] : 'system';
        $title = isset($input['title']) ? $input['title'] : 'New Notification';
        $message = isset($input['message']) ? $input['message'] : '';
        $subject = isset($input['subject']) ? $input['subject'] : $title;
        
        if ($userId <= 0 || empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        // Send notification using our new helper
        $result = sendNotification($userId, $type, $title, $message, $currentUserId, $subject);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification sent successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to send notification. It may be a duplicate.'
            ]);
        }
        exit;
    }
    
    // Mark notification as read
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $notificationId = isset($input['notification_id']) ? intval($input['notification_id']) : 0;
        
        if ($notificationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
            exit;
        }
        
        $result = markNotificationAsRead($notificationId, $currentUserId);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Notification marked as read' : 'Failed to update notification'
        ]);
        exit;
    }
    
    // Mark all notifications as read
    if ($method === 'PATCH') {
        $result = markAllNotificationsAsRead($currentUserId);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'All notifications marked as read' : 'Failed to update notifications'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    
} catch (Throwable $e) {
    error_log("Notification API error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'An internal server error occurred',
        'debug' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
    ]);
}
