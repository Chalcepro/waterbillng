<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    echo "=== Updating User Subscriptions ===\n\n";
    
    // Find users with approved payments but not marked as active
    $query = "
        SELECT DISTINCT p.user_id, MAX(p.created_at) as last_payment_date
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.status = 'approved'
        AND (u.subscription_status != 'active' OR u.subscription_status IS NULL)
        GROUP BY p.user_id
    ";
    
    $stmt = $pdo->query($query);
    $usersToUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($usersToUpdate)) {
        echo "No users need subscription updates.\n";
    } else {
        echo "Found " . count($usersToUpdate) . " users to update.\n\n";
        
        foreach ($usersToUpdate as $user) {
            // Set subscription end date to 30 days from last payment
            $updateQuery = "
                UPDATE users 
                SET 
                    subscription_status = 'active',
                    subscription_end_date = DATE_ADD(?, INTERVAL 30 DAY),
                    updated_at = NOW()
                WHERE id = ?
            ";
            
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$user['last_payment_date'], $user['user_id']]);
            
            if ($updateStmt->rowCount() > 0) {
                echo "✅ Updated user {$user['user_id']}: Set subscription to active for 30 days from {$user['last_payment_date']}\n";
            } else {
                echo "⚠️ No changes made for user {$user['user_id']}\n";
            }
        }
    }
    
    // Commit the transaction
    $pdo->commit();
    
    echo "\n=== Update Complete ===\n";
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "PDO Error: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}
?>
