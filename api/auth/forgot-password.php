<?php
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a valid email address']);
    exit;
}

try {
    $db = getDatabaseConnection();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // For security, don't reveal if the email exists or not
    if ($user) {
        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store the token in the database
        $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);
        
        // Send email with reset link
        $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/frontend/auth/reset-password.html?token=" . $token;
        $subject = "Password Reset Request";
        $message = "
            <html>
            <head>
                <title>Password Reset Request</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .button {
                        display: inline-block; 
                        padding: 10px 20px; 
                        background-color: #2e6fff; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 5px;
                        margin: 15px 0;
                    }
                    .footer { margin-top: 30px; font-size: 0.9em; color: #666; }
                </style>
            </head>
            <body>
                <h2>Hello {$user['first_name']},</h2>
                <p>You recently requested to reset your password for your WaterBill NG account. Click the button below to reset it.</p>
                <p><a href='{$resetLink}' class='button'>Reset Password</a></p>
                <p>Or copy and paste this link into your browser:</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>This password reset link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email or contact support if you have any questions.</p>
                <div class='footer'>
                    <p>Thanks,<br>The WaterBill NG Team</p>
                </div>
            </body>
            </html>
        ";
        
        // Send email using the mail() function (you might want to use a proper email library in production)
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: WaterBill NG <noreply@waterbillng.com>' . "\r\n";
        
        if (mail($email, $subject, $message, $headers)) {
            // Log the password reset request
            logActivity($user['id'], 'password_reset_requested', "Password reset requested for email: {$email}");
            
            echo json_encode([
                'success' => true,
                'message' => 'If an account exists with this email, you will receive a password reset link shortly.'
            ]);
        } else {
            throw new Exception('Failed to send password reset email');
        }
    } else {
        // For security, don't reveal if the email exists or not
        echo json_encode([
            'success' => true,
            'message' => 'If an account exists with this email, you will receive a password reset link shortly.'
        ]);
    }
} catch (Exception $e) {
    error_log('Password reset error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request. Please try again later.']);
}
?>
