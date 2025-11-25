<?php
// Database configuration
$dbHost = 'localhost';
$dbName = 'waterbill_db';
$dbUser = 'root';
$dbPass = ''; // Default XAMPP password is empty

// Connect to MySQL server with buffered queries
try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", 
        $dbUser, 
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_EMULATE_PREPARES => true
        ]
    );

    // Run database migrations

    // Include database connection
    require_once __DIR__ . '/api/includes/db_connect.php';

    try {
        // Get database connection
        $pdo = getDBConnection();
        
        // Check if migrations table exists, create if not
        $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Get the latest batch number
        $latestBatch = $pdo->query("SELECT MAX(batch) as max_batch FROM migrations")->fetch()['max_batch'] ?? 0;
        $nextBatch = $latestBatch + 1;
        
        // Get all migration files
        $migrationFiles = glob(__DIR__ . '/database/migrations/*.sql');
        
        // Sort migrations by filename
        sort($migrationFiles);
        
        $migrationsRun = 0;
        
        // Run each migration
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file);
            
            // Check if migration has already been run
            $stmt = $pdo->prepare("SELECT id FROM migrations WHERE migration = ?");
            $stmt->execute([$migrationName]);
            
            if (!$stmt->fetch()) {
                // Read and execute the migration file
                $sql = file_get_contents($file);
                
                // Split into individual statements
                $statements = explode(';', $sql);
                
                // Execute each statement
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $pdo->exec($statement);
                        } catch (PDOException $e) {
                            // Log the error but continue with other statements
                            echo "Warning: " . $e->getMessage() . "\n";
                        }
                    }
                }
                
                // Record the migration
                $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                $stmt->execute([$migrationName, $nextBatch]);
                
                echo "Ran migration: $migrationName\n";
                $migrationsRun++;
            }
        }
        
        if ($migrationsRun > 0) {
            echo "\nSuccessfully ran $migrationsRun migration(s).\n";
        } else {
            echo "No new migrations to run.\n";
        }
        
        // Check for any missing columns in the payments table
        echo "\nChecking for missing columns in payments table...\n";
        $requiredColumns = [
            'notes', 'bank_name', 'transaction_date', 'payment_type', 'updated_at'
        ];
        
        $stmt = $pdo->query("SHOW COLUMNS FROM payments");
        $existingColumns = array_column($stmt->fetchAll(), 'Field');
        
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        
        if (!empty($missingColumns)) {
            echo "The following columns are missing from the payments table: " . implode(', ', $missingColumns) . "\n";
            echo "Please run the latest migration manually if needed.\n";
        } else {
            echo "All required columns exist in the payments table.\n";
        }
        
        // Check if notifications table exists
        $tables = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount();
        if ($tables === 0) {
            echo "\nWARNING: The notifications table does not exist.\n";
            echo "Please run the latest migration to create it.\n";
        }
        
        // Check if subscriptions table exists
        $tables = $pdo->query("SHOW TABLES LIKE 'subscriptions'")->rowCount();
        if ($tables === 0) {
            echo "\nWARNING: The subscriptions table does not exist.\n";
            echo "Please run the latest migration to create it.\n";
        }
        
    } catch (Exception $e) {
        die("Error running migrations: " . $e->getMessage() . "\n");
    }
    
} catch (Exception $e) {
    die("\n❌ Migration failed: " . $e->getMessage() . "\n");
}
?>
