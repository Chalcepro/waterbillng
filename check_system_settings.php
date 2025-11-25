<?php
// Database connection
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Check if system_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        // Create system_settings table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Insert default settings
        $defaultSettings = [
            ['min_payment_amount', '2000'],
            ['subscription_duration_days', '30']
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (name, value) VALUES (?, ?)");
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
        }

        echo "System settings table created successfully with default values.\n";
    } else {
        echo "System settings table already exists.\n";
    }

    // Show current settings
    $settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nCurrent System Settings:\n";
    print_r($settings);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
