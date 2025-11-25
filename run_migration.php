<?php
// Database connection
require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Read and execute the migration file
    $migrationFile = __DIR__ . '/database/migrations/20241031_add_system_settings_table.sql';
    $sql = file_get_contents($migrationFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read migration file");
    }
    
    // Split the SQL file into individual queries
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    // Commit the transaction
    $pdo->commit();
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback the transaction if something went wrong
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Verify the migration
$tables = $pdo->query("SHOW TABLES LIKE 'system_settings'")->fetchAll(PDO::FETCH_COLUMN);

if (count($tables) > 0) {
    echo "System settings table exists. Verifying settings...\n";
    
    $settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "Current system settings:\n";
    foreach ($settings as $key => $value) {
        echo "- $key: $value\n";
    }
    
    // Check subscriptions table structure
    $subscriptionColumns = $pdo->query("SHOW COLUMNS FROM subscriptions")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nSubscription table columns: " . implode(', ', $subscriptionColumns) . "\n";
    
} else {
    echo "Warning: System settings table was not created.\n";
}

echo "\nMigration verification complete.\n";
?>
