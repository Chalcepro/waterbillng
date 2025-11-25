<?php
/**
 * Handles subscription-related operations
 */

/**
 * Get a system setting value
 * @param PDO $pdo Database connection
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSystemSetting($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        error_log("Error getting system setting '{$key}': " . $e->getMessage());
        return $default;
    }
}

/**
 * Updates user subscription when a payment is approved
 * @param PDO $pdo Database connection
 * @param array $payment Payment data
 * @return bool True on success, false on failure
 */
function updateUserSubscription($pdo, $payment) {
    try {
        // Get system settings for subscription amounts
        $minAmount = (float)getSystemSetting($pdo, 'min_payment_amount', 2000);
        
        // Ensure payment meets minimum amount
        if ($payment['amount'] < $minAmount) {
            throw new Exception("Payment amount must be at least ₦" . number_format($minAmount, 2));
        }
        
        // Calculate number of months paid for (1 month per minimum amount)
        $monthsPaid = floor($payment['amount'] / $minAmount);
        
        // Get current date
        $currentDate = new DateTime();
        $paymentDate = new DateTime($payment['created_at'] ?? 'now');
        
        // Check if user has an active subscription
        $hasActiveSubscription = !empty($payment['subscription_end_date']) && 
                               strtotime($payment['subscription_end_date']) > time();
        
        if ($hasActiveSubscription) {
            // Extend from the end date of current subscription
            $endDate = new DateTime($payment['subscription_end_date']);
        } else {
            // Start new subscription from payment date
            $endDate = clone $paymentDate;
        }
        
        // Add the paid period to the subscription (minimum 1 month)
        $monthsToAdd = max(1, $monthsPaid);
        $endDate->add(new DateInterval("P{$monthsToAdd}M"));
        
        // Log the subscription update for debugging
        error_log("Updating subscription for user {$payment['user_id']}: " .
                 "Amount: ₦{$payment['amount']}, " .
                 "Months: {$monthsToAdd}, " .
                 "New End Date: " . $endDate->format('Y-m-d H:i:s'));
        
        // Update user subscription
        $updateSql = "
            UPDATE users 
            SET 
                subscription_status = 'active',
                subscription_end_date = :end_date,
                last_payment_date = NOW(),
                next_payment_date = :next_payment_date,
                updated_at = NOW()
            WHERE id = :user_id
        ";
        
        // Calculate next payment date (1 day after subscription ends)
        $nextPaymentDate = clone $endDate;
        $nextPaymentDate->add(new DateInterval('P1D'));
        
        $stmt = $pdo->prepare($updateSql);
        $result = $stmt->execute([
            ':end_date' => $endDate->format('Y-m-d H:i:s'),
            ':next_payment_date' => $nextPaymentDate->format('Y-m-d H:i:s'),
            ':user_id' => $payment['user_id']
        ]);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error updating subscription: " . $e->getMessage());
        return false;
    }
}

/**
 * Checks and updates subscription status for all users
 * @param PDO $pdo Database connection
 */
function checkAndUpdateSubscriptions($pdo) {
    try {
        // Update expired subscriptions
        $updateSql = "
            UPDATE users 
            SET 
                subscription_status = 'expired',
                updated_at = NOW()
            WHERE subscription_end_date < NOW()
            AND subscription_status = 'active'
        ";
        $pdo->exec($updateSql);
        
        // Reactivate subscriptions with new payments
        $reactivateSql = "
            UPDATE users u
            JOIN (
                SELECT user_id, MAX(updated_at) as last_payment
                FROM payments 
                WHERE status = 'approved'
                GROUP BY user_id
            ) p ON u.id = p.user_id
            SET 
                u.subscription_status = 'active',
                u.updated_at = NOW()
            WHERE u.subscription_status = 'expired'
            AND p.last_payment > u.subscription_end_date
        ";
        $pdo->exec($reactivateSql);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error checking subscriptions: " . $e->getMessage());
        return false;
    }
}

// Include this in your payment approval flow to update subscriptions when payments are approved
