<?php
require 'db_connect.php';

function sendNotification($userId, $type, $message) {
    global $pdo;
    
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) 
                          VALUES (?, ?, ?)");
    $stmt->execute([$userId, $type, $message]);
    
    // Get user phone
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $phone = $stmt->fetchColumn();
    
    // Send SMS
    sendSMS($phone, $message);
    
    // Send WhatsApp
    sendWhatsApp($phone, $message);
}

function sendSMS($phone, $message) {
    // Twilio implementation (simplified)
    $url = "https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Messages.json";
    
    $data = [
        'To' => $phone,
        'From' => TWILIO_PHONE,
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    curl_exec($ch);
    curl_close($ch);
}

function sendWhatsApp($phone, $message) {
    // Twilio WhatsApp implementation (simplified)
    $url = "https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Messages.json";
    
    $data = [
        'To' => 'whatsapp:' . $phone,
        'From' => 'whatsapp:' . TWILIO_WHATSAPP,
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    curl_exec($ch);
    curl_close($ch);
}

// Scheduled reminder function (to be called via cron)
function sendSubscriptionReminders() {
    global $pdo;
    
    // Get users with expiring subscriptions (3 days)
    $stmt = $pdo->prepare("SELECT * FROM users 
                          WHERE subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $message = "Hi {$user['first_name']}, your water subscription expires on " . 
                   date('M d, Y', strtotime($user['subscription_end'])) . ". " .
                   "Please renew to avoid service interruption.";
        
        sendNotification($user['id'], 'subscription', $message);
    }
    
    // Get users with expired subscriptions
    $stmt = $pdo->prepare("SELECT * FROM users 
                          WHERE subscription_end < CURDATE()");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $message = "Hi {$user['first_name']}, your water subscription has expired. " .
                   "Service will be suspended soon. Please renew immediately.";
        
        sendNotification($user['id'], 'subscription', $message);
    }
}
?>