<?php
// Admin bulk notifications sender
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

// Force JSON-only responses
header('Content-Type: application/json');
header('Cache-Control: no-store');
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');

// Initialize response array
$response = ['success' => false, 'message' => '', 'data' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Accept JSON or form-encoded
    $rawBody = file_get_contents('php://input');
    $input = json_decode($rawBody, true);
    if (!is_array($input) || empty($input)) {
        $input = $_POST; // Fallback to POST form fields
    }
    
    if (!is_array($input) || empty($input)) {
        throw new Exception('Empty request body');
    }

    // Basic validation
    $requiredFields = ['type', 'recipients', 'notification_type'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Extract and validate input
    $type = filter_var($input['type'], FILTER_SANITIZE_STRING);
    $recipients = filter_var($input['recipients'], FILTER_SANITIZE_STRING);
    $notificationType = filter_var($input['notification_type'], FILTER_SANITIZE_STRING);
    $userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
    
    // Notification content based on type
    $subject = '';
    $message = '';
    $emailContent = '';
    
    // Set email subject and content based on notification type
    if ($type === 'email' || $type === 'both') {
        $subject = !empty($input['subject']) ? filter_var($input['subject'], FILTER_SANITIZE_STRING) : 'Notification from WaterBill NG';
        $emailContent = !empty($input['email_content']) ? $input['email_content'] : 
                      (!empty($input['message']) ? $input['message'] : '');
    }
    
    // Set in-app message if needed
    if ($type === 'in-app' || $type === 'both') {
        $message = !empty($input['message']) ? $input['message'] : '';
    }
    
    // Handle different notification types
    switch ($notificationType) {
        case 'email':
            $subject = filter_var($input['subject'] ?? '', FILTER_SANITIZE_STRING);
            $emailContent = $input['message'] ?? '';
            $message = strip_tags($emailContent); // Plain text fallback
            if (empty($subject) || empty($emailContent)) {
                throw new Exception('Email subject and content are required');
            }
            break;
            
        case 'both':
            $subject = filter_var($input['email_subject'] ?? '', FILTER_SANITIZE_STRING);
            $emailContent = $input['email_content'] ?? '';
            $message = filter_var($input['in_app_message'] ?? '', FILTER_SANITIZE_STRING);
            if (empty($subject) || empty($emailContent) || empty($message)) {
                throw new Exception('All fields are required for combined notification');
            }
            break;
            
        default: // in_app
            $message = filter_var($input['message'] ?? '', FILTER_SANITIZE_STRING);
            if (empty($message)) {
                throw new Exception('Message is required');
            }
    }

    // Get recipients based on selection
    $recipientEmails = [];
    $recipientIds = [];
    $emailSent = 0;
    $emailFailed = 0;
    $pdo = getPDO();
    $targetUsers = [];
    
    try {
        if ($userId > 0) {
        // Single user
        $stmt = $pdo->prepare("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Multiple users based on recipient type
        if ($recipients === 'active') {
            $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users WHERE status = 'active'");
            $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($recipients === 'inactive') {
            $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users WHERE status != 'active'");
            $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($recipients === 'pending') {
            $stmt = $pdo->query("SELECT DISTINCT u.id, u.email, CONCAT(u.first_name, ' ', u.surname) as name 
                               FROM users u 
                               JOIN payments p ON u.id = p.user_id 
                               WHERE p.status = 'pending'");
            $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($targetUsers)) {
                $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users");
                $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else { // all users
            $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users");
            $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
            $params = [];
            
            switch ($recipients) {
                case 'active':
                    $query .= " AND status = 'active'";
                    break;
                case 'inactive':
                    $query .= " AND status = 'inactive'";
                    break;
                case 'custom':
                    if (!empty($input['user_ids']) && is_array($input['user_ids'])) {
                        $placeholders = str_repeat('?,', count($input['user_ids']) - 1) . '?';
                        $query .= " AND id IN ($placeholders)";
                        $params = $input['user_ids'];
                    }
                    break;
                // 'all' - no additional conditions
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (empty($targetUsers)) {
            throw new Exception('No recipients found matching the criteria');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        $notificationId = null;
        $successCount = 0;
        
        // Create notification record
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (type, subject, message, created_at, created_by, recipient_count) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $type,
            $subject,
            $message,
            date('Y-m-d H:i:s'),
            $_SESSION['user_id'] ?? 0, // Assuming user is logged in
            count($targetUsers)
        ]);
        
        $notificationId = $pdo->lastInsertId();
        
        // Process each recipient
        foreach ($targetUsers as $user) {
            // Save user notification
            $stmt = $pdo->prepare("
                INSERT INTO user_notifications 
                (notification_id, user_id, status, created_at) 
                VALUES (?, ?, 'sent', ?)
            ");
            $stmt->execute([$notificationId, $user['id'], date('Y-m-d H:i:s')]);
            $successCount++;
            
            // Send email if needed
            if (($type === 'email' || $type === 'both') && !empty($user['email'])) {
                $emailSent = sendEmail(
                    $user['email'],
                    $subject,
                    $emailContent,
                    $subject // Using subject as title
                );
                
                if ($emailSent) {
                    $emailSent++;
                } else {
                    $emailFailed++;
                    error_log("Failed to send email to: " . $user['email']);
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare response
        $response['success'] = true;
        $response['message'] = 'Notifications processed successfully';
        $response['data'] = [
            'total_recipients' => count($targetUsers),
            'emails_sent' => $emailSent,
            'emails_failed' => $emailFailed,
            'notifications_sent' => $successCount
        ];
        
        if ($emailFailed > 0) {
            $response['message'] .= " ($emailFailed emails failed to send)";
        }
        
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e; // Re-throw to be caught by the outer try-catch
    }
        // Build recipients query with resilient fallbacks
        try {
            if ($recipients === 'active') {
                try {
                    $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users WHERE status = 'active'");
                } catch (Exception $e) {
                    // Fallback if column doesn't exist
                    $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users");
                }
                $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($recipients === 'inactive') {
                try {
                    $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users WHERE status != 'active'");
                } catch (Exception $e) {
                    $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users");
                }
                $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($recipients === 'pending') {
                try {
                    $stmt = $pdo->query("SELECT DISTINCT u.id, u.email, CONCAT(u.first_name, ' ', u.surname) as name 
                                       FROM users u 
                                       JOIN payments p ON u.id = p.user_id 
                                       WHERE p.status = 'pending'");
                    $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!$targetUsers) {
                        $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users");
                        $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users");
                    $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else { // all
                $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users");
                $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            // Final fallback
            try {
                $stmt = $pdo->query("SELECT id, email, CONCAT(first_name, ' ', surname) as name FROM users LIMIT 100");
                $targetUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to fetch recipients: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    if (empty($targetUsers)) {
        echo json_encode(['success' => false, 'error' => 'No recipients found']);
        exit;
    }

    // Process notifications for each recipient
    $successCount = 0;
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Create notification record
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (type, subject, message, created_at, created_by, recipient_count) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $type,
            $subject,
            $message,
            date('Y-m-d H:i:s'),
            $_SESSION['user_id'] ?? 0,
            count($targetUsers)
        ]);
        
        $notificationId = $pdo->lastInsertId();
        
        // Process each recipient
        foreach ($targetUsers as $user) {
            try {
                // Save user notification
                $stmt = $pdo->prepare("
                    INSERT INTO user_notifications 
                    (notification_id, user_id, status, created_at) 
                    VALUES (?, ?, 'sent', ?)
                ");
                $stmt->execute([$notificationId, $user['id'], date('Y-m-d H:i:s')]);
                
                // Send email if needed
                if (($type === 'email' || $type === 'both') && !empty($user['email'])) {
                    $emailResult = sendEmail(
                        $user['email'],
                        $subject,
                        $emailContent,
                        $subject
                    );
                    
                    if ($emailResult) {
                        $emailSent++;
                    } else {
                        $emailFailed++;
                        error_log("Failed to send email to: " . $user['email']);
                    }
                }
                
                $successCount++;
                
            } catch (Exception $e) {
                // Log error but continue with next user
                error_log("Error processing user {$user['id']}: " . $e->getMessage());
                continue;
            }
        }
        
        // Commit transaction if all went well
        $pdo->commit();
        
        // Prepare response
        $response = [
            'success' => true,
            'message' => "Successfully sent notifications to $successCount users",
            'data' => [
                'total_recipients' => count($targetUsers),
                'emails_sent' => $emailSent,
                'emails_failed' => $emailFailed,
                'notifications_sent' => $successCount
            ]
        ];
        
        if ($emailFailed > 0) {
            $response['message'] .= " ($emailFailed emails failed to send)";
        }
        
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to process notifications: ' . $e->getMessage()
        ]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
