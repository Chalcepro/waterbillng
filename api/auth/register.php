<?php
// api/auth/register.php

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../register_errors.log');

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start output buffering to prevent any accidental output
ob_start();

try {
    // Include required files
    require_once __DIR__ . '/../includes/db_connect.php';
    
    // Check if required files exist
    if (!file_exists(__DIR__ . '/../includes/db_connect.php')) {
        throw new Exception('Database configuration file not found');
    }

    // Set session
    session_start();

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Get JSON input instead of form data
    $json_input = file_get_contents('php://input');
    
    if (empty($json_input)) {
        throw new Exception('No input data received');
    }

    $input = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Validate and sanitize input data
    $username = trim($input['username'] ?? '');
    $firstName = trim($input['first_name'] ?? '');
    $middleName = trim($input['middle_name'] ?? '');
    $lastName = trim($input['last_name'] ?? ''); // Changed from 'surname' to 'last_name'
    $flat_no = trim($input['flat_no'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';
    $confirm = $input['confirm_password'] ?? '';
    $role = trim($input['role'] ?? 'user');

    $errors = [];

    // Validation
    if (empty($username)) $errors[] = "Username is required";
    if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters";
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($flat_no)) $errors[] = "Flat number is required";
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($phone) || !preg_match('/^[0-9]{11}$/', $phone)) {
        $errors[] = "Valid 11-digit phone number is required";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm) {
        $errors[] = "Passwords don't match";
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        $role = 'user';
    }

    // Database checks
    if (empty($errors)) {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) $errors[] = "Email already registered";

            // Check if phone exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetchColumn() > 0) $errors[] = "Phone number already registered";

            // Check if username exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) $errors[] = "Username already taken";
        } catch (PDOException $e) {
            error_log("Database error during registration checks: " . $e->getMessage());
            $errors[] = "Error checking user information. Please try again.";
        }
    }

    // Return validation errors if any
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Please fix the following errors',
            'errors' => $errors
        ]);
        exit;
    }

    // Proceed with registration
    $pdo->beginTransaction();
    
    try {
        // Create full_name for database
        $full_name = $firstName;
        if (!empty($middleName)) {
            $full_name .= ' ' . $middleName;
        }
        $full_name .= ' ' . $lastName;

        // Insert user - matching your database structure
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, first_name, middle_name, last_name, full_name, flat_no, password, role, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $success = $stmt->execute([
            $username,
            $email,
            $phone,
            $firstName,
            $middleName,
            $lastName,
            $full_name,
            $flat_no,
            $hashed,
            $role
        ]);
        
        if ($success) {
            $userId = $pdo->lastInsertId();
            
            // Try to include and send email if available
            $emailSent = false;
            $mailerFile = __DIR__ . '/../includes/mailer.php';
            if (file_exists($mailerFile)) {
                require_once $mailerFile;
                if (function_exists('sendEmail')) {
                    // Send welcome email
                    $emailSubject = 'Welcome to WaterBill NG';
                    $emailMessage = "Hello {$firstName},\n\nThank you for registering with WaterBill NG. Your account has been created successfully.\n\nUsername: {$username}\n\nYou can now log in to your account and start managing your water bills.\n\nBest regards,\nWaterBill NG Team";
                    
                    $emailSent = sendEmail($email, $emailSubject, $emailMessage, 'Welcome to WaterBill NG');
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Clear any output buffer
            ob_clean();
            
            // Success response - NO AUTO LOGIN, just redirect to login page
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Please login with your credentials.',
                'user_id' => $userId,
                'email_sent' => $emailSent,
                'redirect' => '/waterbill/frontend/auth/login.html' // Redirect to login page
            ]);
            
        } else {
            throw new Exception('Failed to create user account');
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    // Database connection errors
    error_log("Database error: " . $e->getMessage());
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please try again later.',
        'error' => 'Database error'
    ]);
    
} catch (Exception $e) {
    // General errors
    error_log("Registration error: " . $e->getMessage());
    
    ob_clean();
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => 'Registration failed'
    ]);
}

// End output buffering
ob_end_flush();
?>