<?php
// Script to check the structure of the notifications table

// Include database connection
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/includes/db_connect.php';

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if notifications table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
    
    if (!$tableExists) {
        die("The 'notifications' table does not exist in the database.\n");
    }
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Notifications Table Structure ===\n";
    echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . "Default\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 20) . 
             str_pad($column['Null'], 10) . 
             str_pad($column['Key'], 10) . 
             ($column['Default'] ?? 'NULL') . "\n";
    }
    
    // Check if we have any notifications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "\nTotal notifications: " . $count . "\n";
    
    // If there are notifications, show a sample
    if ($count > 0) {
        $stmt = $pdo->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 1");
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n=== Sample Notification ===\n";
        print_r($sample);
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
