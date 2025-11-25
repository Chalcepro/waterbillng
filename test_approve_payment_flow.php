<?php
// Test the complete payment approval flow
require_once __DIR__ . '/api/includes/db_connect.php';

// Start session for admin authentication
session_start();

// Simulate admin login for testing
$_SESSION['user_id'] = 1; // Assuming 1 is the admin user ID
$_SESSION['role'] = 'admin';

try {
    $pdo = getDBConnection();
    
    // 1. Create a test user if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `email` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `first_name` varchar(100) DEFAULT NULL,
        `last_name` varchar(100) DEFAULT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `flat_no` varchar(20) DEFAULT NULL,
        `role` enum('user','admin') NOT NULL DEFAULT 'user',
        `subscription_status` enum('active','inactive','expired') DEFAULT 'inactive',
        `subscription_end_date` datetime DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Insert test user if not exists
    try {
        // First try to get existing user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['test@example.com']);
        $user_id = $stmt->fetchColumn();
        
        if (!$user_id) {
            // User doesn't exist, create one
            $stmt = $pdo->prepare("INSERT INTO users 
                (email, password, first_name, last_name, phone, flat_no, role) 
                VALUES (?, ?, 'Test', 'User', '1234567890', 'T-001', 'user')");
            $password_hash = password_hash('test123', PASSWORD_DEFAULT);
            $stmt->execute(['test@example.com', $password_hash]);
            $user_id = $pdo->lastInsertId();
            echo "Created new test user with ID: $user_id\n";
        } else {
            echo "Using existing test user with ID: $user_id\n";
        }
    } catch (PDOException $e) {
        die("Error with user: " . $e->getMessage() . "\n");
    }
    
    echo "Test User ID: $user_id\n";
    
    // 2. Create a test payment
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `status` enum('pending','approved','rejected','failed') NOT NULL DEFAULT 'pending',
        `method` varchar(50) DEFAULT NULL,
        `transaction_id` varchar(100) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `admin_notes` text DEFAULT NULL,
        `is_subscription_payment` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Insert test payment
    $stmt = $pdo->prepare("INSERT INTO payments 
        (user_id, amount, status, method, notes) 
        VALUES (?, 5000.00, 'pending', 'test', 'Test payment for approval')");
    $stmt->execute([$user_id]);
    $payment_id = $pdo->lastInsertId();
    
    echo "Created Test Payment ID: $payment_id\n";
    
    // 3. Test approving the payment
    echo "\nTesting payment approval...\n";
    
    // Prepare the request data
    $data = [
        'payment_id' => $payment_id,
        'action' => 'approve',
        'notes' => 'Test approval from script'
    ];
    
    // Call the approve-payment.php script
    $_POST = $data;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Start output buffering to capture the output
    ob_start();
    require __DIR__ . '/api/admin/approve-payment.php';
    $output = ob_get_clean();
    
    // Parse the JSON response
    $response = json_decode($output, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error in JSON response: " . json_last_error_msg() . "\n";
        echo "Raw output: $output\n";
        exit(1);
    }
    
    // Display the response
    echo "Response from approve-payment.php:\n";
    print_r($response);
    
    // 4. Verify the payment was updated
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $updated_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUpdated Payment Status: " . $updated_payment['status'] . "\n";
    
    // 5. Check the user's subscription status
    $stmt = $pdo->prepare("SELECT subscription_status, subscription_end_date FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUser Subscription Status: " . $user['subscription_status'] . "\n";
    echo "Subscription End Date: " . $user['subscription_end_date'] . "\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nTest completed.\n";
?>
