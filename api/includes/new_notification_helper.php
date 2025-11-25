<?php
require_once 'db_connect.php';
require_once 'mailer.php'; // Make sure this file exists for email functionality

/**
 * Sends a notification to a user
 * @param int $userId User ID to send notification to
 * @param string $type Notification type (payment, reading, account, system, alert, maintenance)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param int|null $createdBy Admin user ID who is sending the notification (null for system)
 * @param string|null $subject Email subject (optional, uses title if not provided)
 * @return bool True if notification was sent successfully, false otherwise
 */
function sendNotification($userId, $type, $title, $message, $createdBy = null, $subject = null) {
    global $pdo;
    
    // Default to system user if not specified
    $createdBy = $createdBy ?? 1; // Assuming 1 is the system user ID
    $subject = $subject ?? $title;
    
    // Check for duplicate notification
    if (isDuplicateNotification($userId, $type, $message)) {
        error_log("Duplicate notification detected for user $userId: $title");
        return false;
    }
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (title, subject, message, type, created_by, recipient_count)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$title, $subject, $message, $type, $createdBy]);
        $notificationId = $pdo->lastInsertId();
        
        // Add recipient
        $stmt = $pdo->prepare("
            INSERT INTO notification_recipients (notification_id, user_id, is_read)
            VALUES (?, ?, 0)
        ");
        $stmt->execute([$notificationId, $userId]);
        
        // Get user details for sending notifications
        $user = getUserDetails($userId);
        if (!$user) {
            throw new Exception("User not found: $userId");
        }
        
        // Send email notification
        if (!empty($user['email'])) {
            sendEmailNotification($user['email'], $subject, $message, $title);
        }
        
        // Commit transaction
        $pdo->commit();
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error sending notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a similar notification already exists
 * @param int $userId
 * @param string $type
 * @param string $message
 * @param int $timeWindowSeconds Time window in seconds to check for duplicates (default 24 hours)
 * @return bool True if a similar notification exists
 */
function isDuplicateNotification($userId, $type, $message, $timeWindowSeconds = 86400) {
    global $pdo;
    
    $timeAgo = date('Y-m-d H:i:s', time() - $timeWindowSeconds);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notifications n
        JOIN notification_recipients nr ON n.id = nr.notification_id
        WHERE nr.user_id = ? 
        AND n.type = ? 
        AND n.message = ?
        AND n.created_at > ?
    ");
    
    $stmt->execute([$userId, $type, $message, $timeAgo]);
    $count = $stmt->fetchColumn();
    
    return $count > 0;
}

/**
 * Get user details including contact information
 * @param int $userId
 * @return array|null User data or null if not found
 */
function getUserDetails($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, email, phone, first_name, last_name 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Send email notification
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $title
 * @return bool True if email was sent successfully
 */
function sendEmailNotification($to, $subject, $message, $title) {
    // Use the existing mailer function if available
    if (function_exists('sendEmail')) {
        return sendEmail($to, $subject, $message, $title);
    }
    
    // Fallback to basic PHP mail()
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: WaterBill NG <noreply@waterbill.ng>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $htmlMessage = "
        <html>
        <head>
            <title>$title</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #3498db; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$title</h1>
                </div>
                <div class='content'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <div class='footer'>
                    <p>This is an automated message from WaterBill NG. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    return mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
}

/**
 * Get notifications for a user
 * @param int $userId
 * @param int $limit Maximum number of notifications to return (0 for no limit)
 * @param bool $unreadOnly Whether to return only unread notifications
 * @return array Array of notifications
 */
function getUserNotifications($userId, $limit = 20, $unreadOnly = false) {
    global $pdo;
    
    $params = [$userId];
    $whereClause = 'WHERE nr.user_id = ?';
    
    if ($unreadOnly) {
        $whereClause .= ' AND nr.is_read = 0';
    }
    
    $limitClause = $limit > 0 ? 'LIMIT ?' : '';
    if ($limit > 0) {
        $params[] = $limit;
    }
    
    $query = "
        SELECT 
            n.id, n.title, n.subject, n.message, n.type, 
            n.created_at, n.updated_at, n.created_by,
            nr.is_read, nr.read_at
        FROM notifications n
        JOIN notification_recipients nr ON n.id = nr.notification_id
        $whereClause
        ORDER BY n.created_at DESC
        $limitClause
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mark a notification as read
 * @param int $notificationId
 * @param int $userId
 * @return bool True if successful
 */
function markNotificationAsRead($notificationId, $userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notification_recipients 
        SET is_read = 1, read_at = NOW() 
        WHERE notification_id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$notificationId, $userId]);
}

/**
 * Mark all notifications as read for a user
 * @param int $userId
 * @return bool True if successful
 */
function markAllNotificationsAsRead($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notification_recipients 
        SET is_read = 1, read_at = NOW() 
        WHERE user_id = ? AND is_read = 0
    ");
    
    return $stmt->execute([$userId]);
}

/**
 * Get unread notification count for a user
 * @param int $userId
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notification_recipients 
        WHERE user_id = ? AND is_read = 0
    ");
    
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// Example usage:
// sendNotification(1, 'payment', 'Payment Received', 'Your payment of N5,000 has been received.', 1);
// $notifications = getUserNotifications(1, 10, true); // Get 10 unread notifications for user 1
// markNotificationAsRead(123, 1); // Mark notification 123 as read for user 1
?>
