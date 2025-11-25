<?php
// Start session
session_start();

// Set test data
$test_payment_id = 17; // Replace with a valid payment ID
$action = 'approve'; // or 'reject'
$notes = 'Test approval with proper session - ' . date('Y-m-d H:i:s');

// Simulate admin login
$_SESSION['user_id'] = 1; // Assuming 1 is an admin user ID
$_SESSION['role'] = 'admin';

// Include the database connection
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output headers for debugging
echo "=== Debug Information ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data: " . json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n\n";

// 1. First, check if the payment exists
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        die("Error: Payment #{$test_payment_id} not found.\n");
    }
    
    echo "=== Current Payment Status ===\n";
    echo "Payment ID: " . $payment['id'] . "\n";
    echo "Status: " . $payment['status'] . "\n";
    echo "User ID: " . $payment['user_id'] . "\n";
    echo "Amount: " . $payment['amount'] . "\n";
    echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}

// 2. Now try to approve the payment using the API endpoint
echo "=== Attempting to approve payment via API ===\n";

// Create a stream context for the HTTP request
$data = [
    'payment_id' => $test_payment_id,
    'action' => $action,
    'notes' => $notes
];

$options = [
    'http' => [
        'header'  => [
            'Content-type: application/json',
            'Cookie: PHPSESSID=' . session_id()
        ],
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/waterbill/api/admin/approve-payment.php', false, $context);

// Check for errors
if ($result === FALSE) {
    $error = error_get_last();
    die("Error: " . ($error['message'] ?? 'Unknown error') . "\n");
}

// Decode the response
$response = json_decode($result, true);

// Output the response
echo "=== API Response ===\n";
if (json_last_error() === JSON_ERROR_NONE) {
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo $result . "\n";
}

// 3. Verify the payment status after the API call
try {
    $stmt = $pdo->prepare("SELECT id, status, admin_notes, updated_at FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $updated_payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n=== Updated Payment Status ===\n";
    if ($updated_payment) {
        echo "Status: " . $updated_payment['status'] . "\n";
        echo "Updated At: " . $updated_payment['updated_at'] . "\n";
        
        if (!empty($updated_payment['admin_notes'])) {
            echo "\nAdmin Notes Preview:\n";
            $notes = explode("\n", $updated_payment['admin_notes']);
            $preview = array_slice($notes, 0, 5);
            echo implode("\n", $preview);
            if (count($notes) > 5) {
                echo "\n... (" . (count($notes) - 5) . " more lines)";
            }
            echo "\n";
        }
    } else {
        echo "Payment not found after update.\n";
    }
} catch (PDOException $e) {
    echo "\n⚠️ Error verifying payment status: " . $e->getMessage() . "\n";
}
?>
