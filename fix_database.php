<?php
// Include the database configuration
require_once __DIR__ . '/config.php';

// Use database credentials from config.php
$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

$charset = 'utf8mb4';

try {
    // Create PDO instance
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Start transaction
    $pdo->beginTransaction();

    // 1. Add missing columns to payments table if they don't exist
    $pdo->exec("ALTER TABLE payments 
        ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER `method`,
        ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) NULL AFTER `receipt_image`,
        ADD COLUMN IF NOT EXISTS transaction_date DATETIME NULL AFTER `bank_name`,
        ADD COLUMN IF NOT EXISTS payment_type ENUM('water_bill', 'subscription', 'other') DEFAULT 'water_bill' AFTER `transaction_date`,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");

    // 2. Create notifications table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `type` varchar(50) NOT NULL,
        `status` enum('unread','read') NOT NULL DEFAULT 'unread',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `read_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `status` (`status`),
        KEY `type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Create subscriptions table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `subscriptions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
        `payment_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `payment_id` (`payment_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Commit the transaction
    $pdo->commit();

    echo "Database structure updated successfully!\n";

} catch (PDOException $e) {
    // Rollback the transaction if something failed
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    die("Error: " . $e->getMessage() . "\n");
}
?>
