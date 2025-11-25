<?php
function get_setting($name) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE name = ?");
    $stmt->execute([$name]);
    return $stmt->fetchColumn();
}

function send_notification($user_id, $type, $message) {
    global $pdo;
    
    // Save to database (align with schema: status + created_at)
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, status, created_at) 
                          VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$user_id, $type, $message]);
    
    // Get user phone
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $phone = $stmt->fetchColumn();
    
    // Outbound channels are best-effort; ignore failures and skip if not configured
    try { send_sms($phone, $message); } catch (Exception $e) { /* ignore */ }
    try { send_whatsapp($phone, $message); } catch (Exception $e) { /* ignore */ }
}

function send_sms($phone, $message) {
    // Using Twilio API (skip if not configured)
    if (!defined('TWILIO_SID') || !defined('TWILIO_TOKEN') || !defined('TWILIO_PHONE') || !TWILIO_SID || !TWILIO_TOKEN || !TWILIO_PHONE) {
        return;
    }
    $url = "https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Messages.json";
    
    $data = [
        'To' => $phone,
        'From' => TWILIO_PHONE,
        'Body' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    curl_exec($ch);
    curl_close($ch);
}

function send_whatsapp($phone, $message) {
    // Using Twilio WhatsApp API (skip if not configured)
    if (!defined('TWILIO_SID') || !defined('TWILIO_TOKEN') || !defined('TWILIO_WHATSAPP') || !TWILIO_SID || !TWILIO_TOKEN || !TWILIO_WHATSAPP) {
        return;
    }
    $url = "https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Messages.json";
    
    $data = [
        'To' => 'whatsapp:' . $phone,
        'From' => 'whatsapp:' . TWILIO_WHATSAPP,
        'Body' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ":" . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    curl_exec($ch);
    curl_close($ch);
}

function process_receipt_ocr($file_path) {
    // Simulated OCR extraction
    return [
        'name' => 'John Doe',
        'amount' => 6000,
        'date' => date('Y-m-d H:i:s'),
        'transaction_id' => 'TX_' . uniqid(),
        'bank' => 'First Bank',
        'account' => '1234567890'
    ];
}

function add_notification($user_id, $type, $message) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $type, $message]);
    } catch (Exception $e) {
        // Fail silently if notifications table does not exist
    }
}
?>