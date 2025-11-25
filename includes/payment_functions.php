// ...existing code...

function processPayment($userId, $method, $amount) {
    if (empty($userId) || empty($method) || $amount <= 0) {
        return ['success' => false, 'message' => 'Invalid payment details.'];
    }

    // Add logic to handle payment processing
    // Example: Save payment details to the database
    return ['success' => true, 'message' => 'Payment processed successfully.'];
}
