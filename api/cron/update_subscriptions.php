<?php
/**
 * Cron job to check and update subscription statuses
 * Run this daily to ensure subscriptions are properly managed
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to text/plain for CLI
if (php_sapi_name() === 'cli') {
    header('Content-Type: text/plain');
}

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/subscription_manager.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    echo "Starting subscription update process...\n";
    
    // 1. Check and update expired subscriptions
    $result = checkAndUpdateSubscriptions($pdo);
    
    if ($result) {
        echo "Successfully updated subscription statuses.\n";
    } else {
        echo "Warning: There were issues updating some subscriptions.\n";
    }
    
    // 2. Log the execution
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Subscription update completed.\n";
    file_put_contents(__DIR__ . '/subscription_updates.log', $logMessage, FILE_APPEND);
    
    echo "Subscription update process completed.\n";
    
} catch (Exception $e) {
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/subscription_errors.log', $errorMessage, FILE_APPEND);
    
    if (php_sapi_name() === 'cli') {
        echo $errorMessage;
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error updating subscriptions',
            'error' => $e->getMessage()
        ]);
    }
}

// For testing: Access this file directly via browser or run: php update_subscriptions.php
// For production: Set up a cron job: 0 0 * * * php /path/to/update_subscriptions.php >/dev/null 2>&1
