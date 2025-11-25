<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$pdo = new PDO(
    'mysql:host=localhost;dbname=waterbill_db;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Check users table columns
$stmt = $pdo->query("DESCRIBE users");
$usersColumns = $stmt->fetchAll();
echo "=== Users Table Columns ===\n";
foreach ($usersColumns as $column) {
    echo "{$column['Field']} ({$column['Type']})\n";
}

// Check subscriptions table if it exists
$tables = $pdo->query("SHOW TABLES LIKE 'subscriptions'")->fetchAll();
if (count($tables) > 0) {
    echo "\n=== Subscriptions Table Columns ===\n";
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $subscriptionColumns = $stmt->fetchAll();
    foreach ($subscriptionColumns as $column) {
        echo "{$column['Field']} ({$column['Type']})\n";
    }
} else {
    echo "\nNo subscriptions table found\n";
}

// Check if there are any subscriptions
$subscriptions = $pdo->query("SELECT * FROM subscriptions LIMIT 5")->fetchAll();
echo "\n=== Sample Subscriptions ===\n";
print_r($subscriptions);

// Check if there are any users with subscription data
$usersWithSubs = $pdo->query("
    SELECT u.id, u.username, u.subscription_status, u.subscription_end_date, u.last_payment_date, u.next_payment_date
    FROM users u
    WHERE u.subscription_status IS NOT NULL
    LIMIT 5
")->fetchAll();

echo "\n=== Users with Subscription Data ===\n";
print_r($usersWithSubs);
?>
