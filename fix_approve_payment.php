<?php
// Include database connection
require_once __DIR__ . '/api/includes/db_connect.php';

// Get all pending payments
$stmt = $pdo->query("SELECT * FROM payments WHERE status = 'pending'");
$pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($pendingPayments)) {
    die("No pending payments found.\n");
}

echo "Found " . count($pendingPayments) . " pending payments.\n";

// Include the subscription manager
require_once __DIR__ . '/api/includes/subscription_manager.php';

foreach ($pendingPayments as $payment) {
    echo "\nProcessing payment ID: " . $payment['id'] . " for user ID: " . $payment['user_id'] . "\n";
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Update payment status
        $updateSql = "
            UPDATE payments 
            SET status = 'approved',
                updated_at = NOW()
            WHERE id = :id";
            
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([':id' => $payment['id']]);
        
        // 2. Update user subscription
        $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->execute([$payment['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Prepare payment details for subscription update
            $paymentDetails = array_merge($payment, [
                'subscription_status' => $user['subscription_status'] ?? null,
                'subscription_end_date' => $user['subscription_end_date'] ?? null
            ]);
            
            // Update subscription
            updateUserSubscription($pdo, $paymentDetails);
            
            // Mark as subscription payment
            $pdo->prepare("UPDATE payments SET is_subscription_payment = 1 WHERE id = ?")
                ->execute([$payment['id']]);
                
            echo "  - Payment approved and subscription updated.\n";
        } else {
            echo "  - User not found.\n";
        }
        
        // 3. Create notification (outside transaction to avoid issues)
        try {
            // First, ensure the notifications table exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `type` enum('payment','system','announcement') NOT NULL DEFAULT 'system',
                    `title` varchar(255) NOT NULL,
                    `message` text NOT NULL,
                    `is_read` tinyint(1) NOT NULL DEFAULT '0',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `is_read` (`is_read`),
                    KEY `created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // Then insert the notification
            $pdo->prepare("
                INSERT INTO notifications 
                (user_id, type, title, message, is_read) 
                VALUES (?, ?, ?, ?, 0)
            ")->execute([
                $payment['user_id'],
                'payment',
                'Payment Approved',
                'Your payment of ' . $payment['amount'] . ' has been approved.'
            ]);
            
            echo "  - Notification created.\n";
            
        } catch (Exception $e) {
            echo "  - Could not create notification: " . $e->getMessage() . "\n";
        }
        
        // Commit transaction
        $pdo->commit();
        echo "  - Transaction committed successfully.\n";
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "  - Error: " . $e->getMessage() . "\n";
    }
}

echo "\nDone processing payments.\n";
