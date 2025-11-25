<?php
// Start session
session_start();

// Simulate admin login
$_SESSION['user_id'] = 1; // Assuming 1 is an admin user ID
$_SESSION['role'] = 'admin';

// Test payment ID - replace with an existing pending payment ID
$test_payment_id = 17; // Using the test payment we created earlier
$action = 'approve'; // or 'reject'
$notes = 'Test approval via direct script - ' . date('Y-m-d H:i:s');

// Simulate POST request with JSON content type
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Create the input data
$input = [
    'payment_id' => $test_payment_id,
    'action' => $action,
    'notes' => $notes
];

// For testing, let's also try setting $_POST directly
$_POST = $input;

// Set the raw input for JSON parsing
$json_input = json_encode($input);

// Save the current input stream
$original_stdin = file_get_contents('php://input');

// Create a stream to simulate the input
$stream = fopen('php://memory', 'r+');
fwrite($stream, $json_input);
rewind($stream);

// Override the input stream
$GLOBALS['_POST'] = $input;
$GLOBALS['HTTP_RAW_POST_DATA'] = $json_input;

// Override the php://input stream
function override_php_input($data) {
    return function() use ($data) {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $data);
        rewind($stream);
        return $stream;
    };
}

// Override file_get_contents for php://input
function overridden_file_get_contents($filename) {
    global $json_input;
    if ($filename === 'php://input') {
        return $json_input;
    }
    return file_get_contents($filename);
}

// Override the function for this test
namespace {
    function file_get_contents($filename, ...$args) {
        if ($filename === 'php://input') {
            global $json_input;
            return $json_input;
        }
        return \file_get_contents($filename, ...$args);
    }
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to capture the output
ob_start();

// Include the approve-payment.php file to test it directly
require_once __DIR__ . '/api/admin/approve-payment.php';

// Get the output
$output = ob_get_clean();

// Output the result
echo "=== Test Payment Approval ===\n";
echo "Payment ID: $test_payment_id\n";
echo "Action: $action\n\n";

echo "=== API Response ===\n";
$response = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    // Pretty print JSON
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    // Check if the response indicates success
    if (isset($response['success']) && $response['success']) {
        echo "\n✅ Payment successfully {$action}d!\n";
    } else {
        echo "\n❌ Failed to {$action} payment. " . ($response['message'] ?? 'Unknown error') . "\n";
    }
} else {
    // Not JSON, output as is
    echo $output . "\n";
}

// Verify the payment status in the database
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/api/includes/db_connect.php';
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, status, admin_notes, updated_at FROM payments WHERE id = ?");
    $stmt->execute([$test_payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "\n=== Database Verification ===\n";
        echo "Payment ID: " . $payment['id'] . "\n";
        echo "Status: " . $payment['status'] . "\n";
        echo "Updated At: " . $payment['updated_at'] . "\n";
        
        if (!empty($payment['admin_notes'])) {
            echo "\nAdmin Notes Preview:\n";
            $notes = explode("\n", $payment['admin_notes']);
            $preview = array_slice($notes, 0, 5);
            echo implode("\n", $preview);
            if (count($notes) > 5) {
                echo "\n... (" . (count($notes) - 5) . " more lines)";
            }
            echo "\n";
        }
    } else {
        echo "\n⚠️ Payment not found in database.\n";
    }
} catch (PDOException $e) {
    echo "\n⚠️ Database error: " . $e->getMessage() . "\n";
}
?>
