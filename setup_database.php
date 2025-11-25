<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';  // Default XAMPP password is empty
$db_name = 'waterbill_db';

// Create connection without selecting database first
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected to MySQL server successfully\n";

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS `$db_name`";
if ($conn->query($sql) === TRUE) {
    echo "Database '$db_name' is ready\n";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($db_name);

// Create fault_reports table if not exists
$sql = "CREATE TABLE IF NOT EXISTS `fault_reports` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `category` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `photo_path` varchar(255) DEFAULT NULL,
    `status` enum('open','in_progress','resolved','rejected') NOT NULL DEFAULT 'open',
    `admin_notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table 'fault_reports' is ready\n";
    
    // Check if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM fault_reports");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Insert test data
        $testData = [
            [1, 'Leak', 'Water leak in the kitchen', 'uploads/faults/leak1.jpg', 'open', 'Not yet reviewed'],
            [1, 'Low Pressure', 'Low water pressure in bathroom', null, 'in_progress', 'Technician assigned'],
            [2, 'Discoloration', 'Brown water coming from tap', 'uploads/faults/water1.jpg', 'open', 'Needs investigation']
        ];
        
        $stmt = $conn->prepare("INSERT INTO fault_reports (user_id, category, description, photo_path, status, admin_notes) VALUES (?, ?, ?, ?, ?, ?)");
        $inserted = 0;
        
        foreach ($testData as $data) {
            $stmt->bind_param("isssss", ...$data);
            if ($stmt->execute()) {
                $inserted++;
            }
        }
        
        echo "Inserted $inserted test records into fault_reports\n";
    } else {
        echo "fault_reports table already contains data\n";
    }
    
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Create users table if it doesn't exist (for foreign key)
$sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `first_name` varchar(50) DEFAULT NULL,
    `surname` varchar(50) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `account_number` varchar(20) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' is ready\n";
    
    // Insert test user if not exists
    $result = $conn->query("SELECT id FROM users WHERE id IN (1,2)");
    if ($result->num_rows < 2) {
        $conn->query("INSERT IGNORE INTO users (id, username, first_name, surname, email, account_number) VALUES 
            (1, 'testuser1', 'John', 'Doe', 'john@example.com', 'ACCT1001'),
            (2, 'testuser2', 'Jane', 'Smith', 'jane@example.com', 'ACCT1002')");
        echo "Added test users\n";
    }
}

// Add foreign key if not exists
$result = $conn->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = '$db_name' 
    AND TABLE_NAME = 'fault_reports' 
    AND CONSTRAINT_NAME = 'fk_user_id'");

if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE fault_reports 
        ADD CONSTRAINT fk_user_id 
        FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE");
    echo "Added foreign key constraint\n";
}

// Update config.php with database credentials
$config_file = __DIR__ . '/config.php';
$config_content = file_get_contents($config_file);

// Update database configuration
$new_config = preg_replace(
    "/define\('DB_HOST', '.*?'\);/", 
    "define('DB_HOST', '" . addslashes($db_host) . "');", 
    $config_content
);
$new_config = preg_replace(
    "/define\('DB_NAME', '.*?'\);/", 
    "define('DB_NAME', '" . addslashes($db_name) . "');", 
    $new_config
);
$new_config = preg_replace(
    "/define\('DB_USER', '.*?'\);/", 
    "define('DB_USER', '" . addslashes($db_user) . "');", 
    $new_config
);
$new_config = preg_replace(
    "/define\('DB_PASS', '.*?'\);/", 
    "define('DB_PASS', '" . addslashes($db_pass) . "');", 
    $new_config
);

file_put_contents($config_file, $new_config);
echo "Updated config.php with database settings\n";

$conn->close();
echo "Setup completed successfully!\n";

echo "\nNext steps:\n";
echo "1. Open http://localhost/waterbill/frontend/admin/fault-reports.html in your browser\n";
echo "2. You should see the fault reports dashboard with test data\n";
?>
