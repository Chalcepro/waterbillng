<?php
// Include the database configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

// Test payment ID - using the newly created test payment
$test_payment_id = 16; // Using the test payment we just created
$action = 'approve'; // or 'reject' to test rejection
$notes = 'Test approval via direct script - ' . date('Y-m-d H:i:s');

// Suppress PDO warning about redefining constants
error_reporting(E_ALL & ~E_WARNING);

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // 1. First, check if the payment exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND status = 'pending'");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        // Try to find any payment with this ID to see if it exists but not pending
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$test_payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            die("Error: Payment #{$test_payment_id} exists but is not in 'pending' status. Current status: " . ($payment['status'] ?? 'N/A') . "\n");
        } else {
            die("Error: Payment #{$test_payment_id} not found.\n");
        }
    }
    
    // 2. Display payment info before update
    echo "=== Payment Approval Test ===\n";
    echo "Payment ID: {$payment['id']}\n";
    echo "User ID: {$payment['user_id']}\n";
    echo "Amount: {$payment['amount']}\n";
    echo "Current Status: {$payment['status']}\n";
    echo "Method: " . ($payment['method'] ?? 'N/A') . "\n";
    echo "Payment Method: " . ($payment['payment_method'] ?? 'N/A') . "\n";
    echo "Payment Type: " . ($payment['payment_type'] ?? 'N/A') . "\n";
    echo "Transaction ID: " . ($payment['transaction_id'] ?? 'N/A') . "\n";
    echo "Notes: " . (empty($payment['notes']) ? 'N/A' : substr($payment['notes'], 0, 100) . (strlen($payment['notes']) > 100 ? '...' : '')) . "\n";
    echo "Admin Notes: " . (empty($payment['admin_notes']) ? 'N/A' : substr($payment['admin_notes'], 0, 100) . (strlen($payment['admin_notes']) > 100 ? '...' : '')) . "\n";
    echo "Created At: " . ($payment['created_at'] ?? 'N/A') . "\n";
    echo "Updated At: " . ($payment['updated_at'] ?? 'N/A') . "\n\n";
    
    echo "Attempting to {$action} payment...\n\n";
    
    // 3. Update the payment status
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $now = date('Y-m-d H:i:s');
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Prepare the admin notes update
        $admin_notes = "[{$now}] {$action}d by system: {$notes}" . 
                      (empty($payment['admin_notes']) ? '' : "\n" . $payment['admin_notes']);
        
        // Update payment status and admin notes
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
            ':id' => $test_payment_id
        ]);
        
        $rows_updated = $stmt->rowCount();
        
        if ($rows_updated === 0) {
            throw new Exception("No rows were updated. Payment may have been modified by another process.");
        }
        
        // If approving, update user's subscription if this is a subscription payment
        if ($action === 'approve' && ($payment['payment_type'] ?? '') === 'subscription') {
            $end_date = date('Y-m-d H:i:s', strtotime('+1 year'));
            
            // First check if the user_subscriptions table exists
            $table_exists = $pdo->query("SHOW TABLES LIKE 'user_subscriptions'")->rowCount() > 0;
            
            if ($table_exists) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_subscriptions (
                        user_id, 
                        payment_id, 
                        plan_name, 
                        start_date, 
                        end_date, 
                        status, 
                        created_at, 
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                        payment_id = VALUES(payment_id),
                        end_date = GREATEST(COALESCE(end_date, '1970-01-01'), VALUES(end_date)),
                        status = 'active',
                        updated_at = NOW()
                ");
                
                $stmt->execute([
                    $payment['user_id'],
                    $payment['id'],
                    'Annual Subscription',
                    $now,
                    $end_date
                ]);
                
                echo "✅ Updated user subscription record.\n";
            } else {
                echo "ℹ️ user_subscriptions table does not exist. Skipping subscription update.\n";
            }
            
            // Update the users table for subscription status
            $users_columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            $subscription_columns = array_intersect($users_columns, [
                'subscription_status', 'subscription_plan', 
                'subscription_end_date', 'last_payment_date', 'next_payment_date'
            ]);
            
            if (!empty($subscription_columns)) {
                $update_parts = [];
                $params = [];
                
                if (in_array('subscription_status', $subscription_columns)) {
                    $update_parts[] = 'subscription_status = :status';
                    $params[':status'] = 'active';
                }
                
                if (in_array('subscription_plan', $subscription_columns)) {
                    $update_parts[] = 'subscription_plan = :plan';
                    $params[':plan'] = 'Annual';
                }
                
                if (in_array('subscription_end_date', $subscription_columns)) {
                    $update_parts[] = 'subscription_end_date = :end_date';
                    $params[':end_date'] = $end_date;
                }
                
                if (in_array('last_payment_date', $subscription_columns)) {
                    $update_parts[] = 'last_payment_date = :last_payment';
                    $params[':last_payment'] = $now;
                }
                
                if (in_array('next_payment_date', $subscription_columns)) {
                    $next_payment_date = date('Y-m-d H:i:s', strtotime('+1 year'));
                    $update_parts[] = 'next_payment_date = :next_payment';
                    $params[':next_payment'] = $next_payment_date;
                }
                
                if (!empty($update_parts)) {
                    $update_sql = "UPDATE users SET " . implode(', ', $update_parts) . " WHERE id = :user_id";
                    $params[':user_id'] = $payment['user_id'];
                    
                    $stmt = $pdo->prepare($update_sql);
                    $stmt->execute($params);
                    
                    echo "✅ Updated user subscription status.\n";
                }
            } else {
                echo "ℹ️ No subscription-related columns found in users table.\n";
            }
        }
        
        // Create a notification for the user if notifications table exists
        $notifications_table_exists = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
        
        if ($notifications_table_exists) {
            $notification_type = ($action === 'approve') ? 'payment_approved' : 'payment_rejected';
            $title = ($action === 'approve') ? 'Payment Approved' : 'Payment Rejected';
            $message = "Your payment of {$payment['amount']} has been {$new_status}.";
            
            if (!empty($notes) && $notes !== $message) {
                $message .= "\nNote: {$notes}";
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (
                        user_id, 
                        type, 
                        title, 
                        message, 
                        is_read, 
                        created_at, 
                        updated_at
                    ) VALUES (?, ?, ?, ?, 0, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $payment['user_id'],
                    $notification_type,
                    $title,
                    $message
                ]);
                
                echo "✅ Created notification for user.\n";
            } catch (Exception $e) {
                // Log the error but don't fail the whole process
                error_log("Failed to create notification: " . $e->getMessage());
                echo "⚠️ Could not create notification: " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️ Notifications table does not exist. Skipping notification creation.\n";
        }
        
        // Commit the transaction
        $pdo->commit();
        
        echo "\n✅ Payment successfully {$action}d!\n\n";
        
        // Get updated payment info
        $stmt = $pdo->prepare("
            SELECT p.*, u.email, u.first_name, u.last_name 
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$test_payment_id]);
        $updated_payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "=== Updated Payment Info ===\n";
        echo "Status: " . ($updated_payment['status'] ?? 'N/A') . "\n";
        echo "User: " . ($updated_payment['first_name'] ?? 'N/A') . ' ' . ($updated_payment['last_name'] ?? '') . "\n";
        echo "Email: " . ($updated_payment['email'] ?? 'N/A') . "\n";
        echo "Amount: " . ($updated_payment['amount'] ?? 'N/A') . "\n";
        echo "Method: " . ($updated_payment['method'] ?? 'N/A') . "\n";
        echo "Payment Type: " . ($updated_payment['payment_type'] ?? 'N/A') . "\n";
        echo "Transaction ID: " . ($updated_payment['transaction_id'] ?? 'N/A') . "\n";
        echo "Updated At: " . ($updated_payment['updated_at'] ?? 'N/A') . "\n\n";
        
        // Show a preview of the admin notes
        if (!empty($updated_payment['admin_notes'])) {
            echo "=== Admin Notes Preview ===\n";
            $notes = explode("\n", $updated_payment['admin_notes']);
            $preview = array_slice($notes, 0, 5);
            echo implode("\n", $preview);
            if (count($notes) > 5) {
                echo "\n... (" . (count($notes) - 5) . " more lines)";
            }
            echo "\n\n";
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\n=== Test Completed ===\n";
?>
