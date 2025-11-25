<?php
/**
 * Script to check subscription data for a user
 */

require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

try {
    // Get all users with their subscription status
    $query = "
        SELECT 
            u.id as user_id,
            u.email,
            u.full_name,
            s.id as subscription_id,
            s.start_date,
            s.end_date,
            s.status,
            s.amount_paid,
            s.months_covered,
            s.auto_renew,
            s.created_at as sub_created,
            s.updated_at as sub_updated,
            (
                SELECT COUNT(*) 
                FROM payments p 
                WHERE p.user_id = u.id 
                AND p.status = 'approved'
            ) as approved_payments
        FROM users u
        LEFT JOIN subscriptions s ON u.id = s.user_id
        ORDER BY u.id, s.end_date DESC
    ";
    
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>User Subscriptions</h2>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>
        <tr>
            <th>User ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Subscription ID</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Amount Paid</th>
            <th>Months</th>
            <th>Auto Renew</th>
            <th>Approved Payments</th>
            <th>Created</th>
            <th>Updated</th>
        </tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>" . ($user['subscription_id'] ?: 'None') . "</td>";
        echo "<td>" . ($user['start_date'] ?: 'N/A') . "</td>";
        echo "<td>" . ($user['end_date'] ?: 'N/A') . "</td>";
        echo "<td>" . ($user['status'] ?: 'Inactive') . "</td>";
        echo "<td>" . ($user['amount_paid'] ? number_format($user['amount_paid'], 2) : '0.00') . "</td>";
        echo "<td>{$user['months_covered']}</td>";
        echo "<td>" . ($user['auto_renew'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$user['approved_payments']}</td>";
        echo "<td>" . ($user['sub_created'] ?: 'N/A') . "</td>";
        echo "<td>" . ($user['sub_updated'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check system settings
    echo "<h2>System Settings</h2>";
    $settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin-top: 20px;'>
        <tr>
            <th>ID</th>
            <th>Key</th>
            <th>Value</th>
            <th>Group</th>
            <th>Updated</th>
        </tr>";
    
    foreach ($settings as $setting) {
        echo "<tr>";
        echo "<td>{$setting['id']}</td>";
        echo "<td>{$setting['setting_key']}</td>";
        echo "<td>{$setting['setting_value']}</td>";
        echo "<td>{$setting['setting_group']}</td>";
        echo "<td>{$setting['updated_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
