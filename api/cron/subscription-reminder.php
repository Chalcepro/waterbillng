<?php
require __DIR__.'/../../config.php';
require __DIR__.'/../includes/db_connect.php';
require __DIR__.'/../includes/functions.php';

// Get users with expiring subscriptions (3 days)
$stmt = $pdo->prepare("SELECT * FROM users 
                      WHERE subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
$stmt->execute();
$users = $stmt->fetchAll();

foreach ($users as $user) {
    $message = "Hi {$user['first_name']}, your water subscription expires on " . 
               date('M d, Y', strtotime($user['subscription_end'])) . ". " .
               "Please renew to avoid service interruption.";
    
    send_notification($user['id'], 'subscription', $message);
}

// Get users with expired subscriptions
$stmt = $pdo->prepare("SELECT * FROM users 
                      WHERE subscription_end < CURDATE()");
$stmt->execute();
$users = $stmt->fetchAll();

foreach ($users as $user) {
    $message = "Hi {$user['first_name']}, your water subscription has expired. " .
               "Service will be suspended soon. Please renew immediately.";
    
    send_notification($user['id'], 'subscription', $message);
}
