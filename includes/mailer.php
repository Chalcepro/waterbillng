<?php
require_once __DIR__ . '/config.php';

class Mailer {
    private $mailer;
    
    public function __construct() {
        $this->initializeMailer();
    }
    
    private function initializeMailer() {
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            if (MAIL_IS_SMTP) {
                $this->mailer->isSMTP();
                $this->mailer->Host = MAIL_HOST;
                $this->mailer->SMTPAuth = MAIL_SMTP_AUTH;
                $this->mailer->Username = MAIL_USERNAME;
                $this->mailer->Password = MAIL_PASSWORD;
                $this->mailer->SMTPSecure = MAIL_SMTP_SECURE;
                $this->mailer->Port = MAIL_PORT;
            }
            
            // Recipients
            $this->mailer->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $this->mailer->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
            // Content
            $this->mailer->isHTML(true);
            
        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            throw new Exception("Failed to initialize mailer: " . $e->getMessage());
        }
    }
    
    public function sendEmail($to, $subject, $body, $altBody = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->wrapInTemplate($body, $subject);
            $this->mailer->AltBody = $altBody ?: strip_tags($body);
            
            $this->mailer->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Mailer Error: {$e->getMessage()}");
            return false;
        }
    }
    
    private function wrapInTemplate($content, $title = '') {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>" . htmlspecialchars($title) . "</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #039ed1; padding: 20px; text-align: center; color: white; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { margin-top: 20px; padding: 10px; text-align: center; font-size: 12px; color: #777; }
                .button { display: inline-block; padding: 10px 20px; background-color: #039ed1; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>WaterBill NG</h1>
            </div>
            <div class='content'>
                " . $content . "
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " WaterBill NG. All rights reserved.</p>
                <p>This is an automated message, please do not reply directly to this email.</p>
            </div>
        </body>
        </html>";
    }
}

// Helper function to send quick emails
function send_email($to, $subject, $body, $altBody = '') {
    $mailer = new Mailer();
    return $mailer->sendEmail($to, $subject, $body, $altBody);
}
?>
