<?php
// Database configuration
$dbHost = 'localhost';
$dbName = 'waterbill_db';
$dbUser = 'root';
$dbPass = ''; // Default XAMPP password is empty

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check database connection and structure
    require_once 'config.php';
    require_once 'api/includes/db_connect.php';

    $pdo = getDBConnection();

    // Function to execute SQL file
    function executeSqlFile($pdo, $file) {
        if (!file_exists($file)) {
            echo "❌ SQL file not found: $file\n";
            return false;
        }
        
        $sql = file_get_contents($file);
        if ($sql === false) {
            echo "❌ Failed to read SQL file: $file\n";
            return false;
        }
        
        try {
            $pdo->exec($sql);
            echo "✅ Executed SQL file: $file\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Error executing $file: " . $e->getMessage() . "\n";
            return false;
        }
    }

    // Check if system_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($stmt->rowCount() === 0) {
        echo "System settings table not found. Creating...\n";
        executeSqlFile($pdo, __DIR__ . '/database/migrations/20241031_add_system_settings_table.sql');
    }

    // Check required tables
    $tables = [
        'users' => [
            'columns' => ['id', 'email', 'password', 'full_name', 'phone', 'role', 'created_at', 'updated_at']
        ],
        'payments' => [
            'columns' => ['id', 'user_id', 'amount', 'reference', 'status', 'payment_method', 'created_at', 'updated_at']
        ],
        'subscriptions' => [
            'columns' => ['id', 'user_id', 'start_date', 'end_date', 'status', 'amount_paid', 'months_covered', 'payment_id', 'auto_renew']
        ],
        'system_settings' => [
            'columns' => ['id', 'setting_key', 'setting_value', 'setting_group', 'created_at', 'updated_at']
        ]
    ];

    echo "\nChecking database structure...\n";

    foreach ($tables as $table => $info) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
            
            // Check columns
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $missingColumns = array_diff($info['columns'], $columns);
            if (!empty($missingColumns)) {
                echo "⚠️  Table '$table' is missing columns: " . implode(', ', $missingColumns) . "\n";
                
                // Try to add missing columns
                foreach ($missingColumns as $column) {
                    $alterSql = "";
                    switch ($column) {
                        case 'amount_paid':
                            $alterSql = "ALTER TABLE `$table` ADD COLUMN `amount_paid` DECIMAL(10,2) DEFAULT NULL";
                            break;
                        case 'months_covered':
                            $alterSql = "ALTER TABLE `$table` ADD COLUMN `months_covered` INT(11) DEFAULT 1";
                            break;
                        case 'auto_renew':
                            $alterSql = "ALTER TABLE `$table` ADD COLUMN `auto_renew` TINYINT(1) DEFAULT 0";
                            break;
                        // Add more column definitions as needed
                    }
                    
                    if (!empty($alterSql)) {
                        try {
                            $pdo->exec($alterSql);
                            echo "✅ Added column '$column' to table '$table'\n";
                        } catch (Exception $e) {
                            echo "❌ Failed to add column '$column': " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
        } else {
            echo "❌ Table '$table' is missing. Please run the migration script.\n";
        }
    }

    // Check and insert default settings
    echo "\nChecking system settings...\n";

    try {
        $settings = [
            'min_payment_amount' => ['value' => '2000', 'group' => 'payment'],
            'subscription_duration_days' => ['value' => '30', 'group' => 'subscription'],
            'currency' => ['value' => 'NGN', 'group' => 'general'],
            'currency_symbol' => ['value' => '₦', 'group' => 'general'],
            'company_name' => ['value' => 'WaterBill NG', 'group' => 'general'],
            'support_email' => ['value' => 'support@waterbill.ng', 'group' => 'general']
        ];

        foreach ($settings as $key => $data) {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            
            if ($value === false) {
                echo "⚠️  Setting '$key' is missing, inserting default value... ";
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_group) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$key, $data['value'], $data['group']]);
                echo "✅ Added with value: {$data['value']}\n";
            } else {
                echo "✅ Setting '$key' exists with value: $value\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error checking settings: " . $e->getMessage() . "\n";
    }

    // Check and create indexes
    echo "\nChecking database indexes...\n";

    $indexes = [
        'subscriptions' => [
            'idx_subscriptions_user_status' => "CREATE INDEX IF NOT EXISTS idx_subscriptions_user_status ON subscriptions (user_id, status)",
            'idx_subscriptions_end_date' => "CREATE INDEX IF NOT EXISTS idx_subscriptions_end_date ON subscriptions (end_date)"
        ],
        'payments' => [
            'idx_payments_user_status' => "CREATE INDEX IF NOT EXISTS idx_payments_user_status ON payments (user_id, status)"
        ]
    ];

    foreach ($indexes as $table => $tableIndexes) {
        foreach ($tableIndexes as $indexName => $sql) {
            try {
                $pdo->exec($sql);
                echo "✅ Created/verified index '$indexName' on table '$table'\n";
            } catch (Exception $e) {
                echo "❌ Failed to create index '$indexName': " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\nDatabase check complete!\n";

    // Display current settings
    echo "\nCurrent System Settings:\n";
    $settings = $pdo->query("SELECT setting_key, setting_value, setting_group FROM system_settings ORDER BY setting_group, setting_key")->fetchAll(PDO::FETCH_ASSOC);

    $currentGroup = '';
    foreach ($settings as $setting) {
        if ($setting['setting_group'] !== $currentGroup) {
            echo "\n[{$setting['setting_group']}]\n";
            $currentGroup = $setting['setting_group'];
        }
        echo str_pad($setting['setting_key'], 30) . " = " . $setting['setting_value'] . "\n";
    }

    // Check if tables exist
    $tables = ['notifications', 'user_notifications', 'users'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists.\n";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE `$table`");
            echo "Structure of '$table':\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- {$row['Field']} ({$row['Type']})\n";
            }
            echo "\n";
        } else {
            echo "Table '$table' does NOT exist.\n\n";
        }
    }
    
    // Check for foreign key constraints
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
        FROM
n            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = '$dbName'
            AND REFERENCED_TABLE_NAME IS NOT NULL;
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($constraints) > 0) {
        echo "Foreign key constraints found:\n";
        foreach ($constraints as $constraint) {
            echo "- {$constraint['CONSTRAINT_NAME']}: {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']} -> ";
            echo "{$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
        }
    } else {
        echo "No foreign key constraints found.\n";
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
?>
