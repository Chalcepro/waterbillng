<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Get auto_approval setting
$auto_approval = $pdo->query("SELECT value FROM system_settings WHERE name = 'auto_approval'")->fetchColumn();
if ($auto_approval !== '1') {
    exit; // Feature is disabled
}

// Approve all pending payments
$stmt = $pdo->prepare("SELECT id FROM payments WHERE status = 'pending'");
$stmt->execute();
$pending = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($pending) {
    foreach ($pending as $payment_id) {
        // Approve payment
        $pdo->prepare("UPDATE payments SET status = 'approved' WHERE id = ?")->execute([$payment_id]);
        // You can add notification logic here if needed
    }
}
// Done
