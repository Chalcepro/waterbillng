<?php
/**
 * Send an email using PHPMailer or fallback to mail()
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML supported)
 * @param string $title Optional title for the email
 * @return bool True if the email was sent successfully, false otherwise
 */
function sendEmail($to, $subject, $message, $title = '') {
    // Default to using the system mailer
    return sendEmailWithPHPMailer($to, $subject, $message, $title);
}

/**
 * Send email using PHPMailer
 */
function sendEmailWithPHPMailer($to, $subject, $message, $title = '') {
    // Check if PHPMailer is available in the includes directory
    $phpmailerPath = __DIR__ . '/PHPMailer/src/';
    
    if (file_exists($phpmailerPath . 'PHPMailer.php') && 
        file_exists($phpmailerPath . 'SMTP.php') && 
        file_exists($phpmailerPath . 'Exception.php')) {
        
        // Include PHPMailer classes
        require $phpmailerPath . 'PHPMailer.php';
        require $phpmailerPath . 'SMTP.php';
        require $phpmailerPath . 'Exception.php';
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com'; // SMTP username
            $mail->Password = 'your-app-specific-password'; // SMTP password (use app password for Gmail)
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('noreply@waterbill.ng', 'WaterBill NG');
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            
            // Create HTML email with a nice template
            $htmlMessage = createEmailTemplate($title ?: $subject, $message);
            $mail->Body = $htmlMessage;
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            error_log("Email sent to $to: $subject");
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            // Fall back to mail() if PHPMailer fails
            return sendEmailWithMailFunction($to, $subject, $message, $title);
        }
    } else {
        // PHPMailer not available, use mail()
        return sendEmailWithMailFunction($to, $subject, $message, $title);
    }
}

/**
 * Fallback email sending using PHP's mail() function
 */
function sendEmailWithMailFunction($to, $subject, $message, $title = '') {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: WaterBill NG <noreply@waterbill.ng>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $htmlMessage = createEmailTemplate($title ?: $subject, $message);
    
    $result = mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
    
    if ($result) {
        error_log("Email sent via mail() to $to: $subject");
    } else {
        error_log("Failed to send email to $to: $subject");
    }
    
    return $result;
}

/**
 * Create a nicely formatted HTML email template
 */
function createEmailTemplate($title, $message) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$title</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { 
                background-color: #3498db; 
                color: white; 
                padding: 20px; 
                text-align: center; 
                border-radius: 5px 5px 0 0;
            }
            .content { 
                padding: 30px; 
                background-color: #ffffff; 
                border-left: 1px solid #e0e0e0;
                border-right: 1px solid #e0e0e0;
            }
            .footer { 
                text-align: center; 
                font-size: 12px; 
                color: #777; 
                padding: 15px;
                background-color: #f9f9f9;
                border-radius: 0 0 5px 5px;
                border: 1px solid #e0e0e0;
                border-top: none;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                margin: 20px 0;
                background-color: #3498db;
                color: white !important;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
            }
            .button:hover {
                background-color: #2980b9;
            }
            p {
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>$title</h1>
            </div>
            <div class='content'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            <div class='footer'>
                <p>This is an automated message from WaterBill NG. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " WaterBill NG. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// For backward compatibility
function send_email($to, $subject, $message, $title = '') {
    return sendEmail($to, $subject, $message, $title);
}
?>
