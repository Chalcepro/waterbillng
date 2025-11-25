<?php
// Database connection test script
require 'config.php';

try {
    // Test database connection
    echo "<h2>Testing Database Connection</h2>";
    
    // List all tables in the database
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables in database:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='margin: 10px 0 20px 20px; border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show row count
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<div>Total rows: $count</div>";
        
        // Show sample data for subscriptions and payments tables
        if (in_array($table, ['subscriptions', 'payments'])) {
            $data = $pdo->query("SELECT * FROM `$table` LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo "<div>Sample data:</div>";
            echo "<pre>";
            print_r($data);
            echo "</pre>";
        }
        
        echo "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div style='color:red;'>Database Error: " . $e->getMessage() . "</div>";
}
