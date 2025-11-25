<?php
/**
 * Script to fix subscription data for a user
 */

require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

try {
    // Get user ID from query parameter or default to 2
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 2;
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get user's total approved payments
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_paid 
        FROM payments 
        WHERE user_id = ? 
        AND status = 'approved'
    ");
    $stmt->execute([$userId]);
    $totalPaid = (float)$stmt->fetchColumn() ?: 0;
    
    // Get min payment amount from settings
    $minPayment = (float)($pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_payment_amount'")->fetchColumn() ?: 2000);
    
    // Calculate months covered (1 month per min payment amount)
    $monthsCovered = floor($totalPaid / $minPayment);
    $monthsCovered = max(1, $monthsCovered); // At least 1 month
    
    // Calculate end date (start from today + months covered)
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+{$monthsCovered} months"));
    
    // Get the most recent subscription
    $stmt = $pdo->prepare("
        SELECT id FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY end_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscriptionId = $stmt->fetchColumn();
    
    if ($subscriptionId) {
        // Update existing subscription
        $stmt = $pdo->prepare("
            UPDATE subscriptions 
            SET amount_paid = ?, 
                months_covered = ?,
                start_date = ?,
                end_date = ?,
                status = 'active',
                updated_at = NOW()
            WHERE id = ?
        ");
        $success = $stmt->execute([
            $totalPaid,
            $monthsCovered,
            $startDate,
            $endDate,
            $subscriptionId
        ]);
        
        if ($success) {
            echo "✅ Updated subscription #$subscriptionId for user #$userId<br>";
            echo "- Amount Paid: $totalPaid<br>";
            echo "- Months Covered: $monthsCovered<br>";
            echo "- Start Date: $startDate<br>";
            echo "- End Date: $endDate<br>";
        } else {
            throw new Exception("Failed to update subscription");
        }
    } else {
        // Create new subscription if none exists
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions 
            (user_id, start_date, end_date, status, amount_paid, months_covered, created_at, updated_at)
            VALUES (?, ?, ?, 'active', ?, ?, NOW(), NOW())
        ");
        $success = $stmt->execute([
            $userId,
            $startDate,
            $endDate,
            $totalPaid,
            $monthsCovered
        ]);
        
        if ($success) {
            $subscriptionId = $pdo->lastInsertId();
            echo "✅ Created new subscription #$subscriptionId for user #$userId<br>";
            echo "- Amount Paid: $totalPaid<br>";
            echo "- Months Covered: $monthsCovered<br>";
            echo "- Start Date: $startDate<br>";
            echo "- End Date: $endDate<br>";
        } else {
            throw new Exception("Failed to create subscription");
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "<br>✅ Subscription updated successfully!<br>";
    echo "<a href='check_subscription.php'>Check Subscription Status</a>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
