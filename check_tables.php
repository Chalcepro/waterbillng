<?php
// Database configuration
$host = 'localhost';
$db   = 'waterbill_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check payments table
    echo "Payments table structure:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM payments");
    $columns = $stmt->fetchAll();
    print_r($columns);

    // Check if notifications table exists
    echo "\nChecking notifications table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "Notifications table exists.\n";
        $stmt = $pdo->query("SHOW COLUMNS FROM notifications");
        $columns = $stmt->fetchAll();
        print_r($columns);
    } else {
        echo "Notifications table does not exist.\n";
    }

    // Check subscriptions table
    echo "\nChecking subscriptions table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'subscriptions'");
    if ($stmt->rowCount() > 0) {
        echo "Subscriptions table exists.\n";
        $stmt = $pdo->query("SHOW COLUMNS FROM subscriptions");
        $columns = $stmt->fetchAll();
        print_r($columns);
    } else {
        echo "Subscriptions table does not exist.\n";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
