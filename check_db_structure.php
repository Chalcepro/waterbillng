<?php
// Check database structure for export functionality
require_once __DIR__ . '/api/includes/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type
echo "<pre>";

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Check users table structure
    echo "Checking users table structure...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Users table columns: " . implode(', ', $userColumns) . "\n\n";
    
    // Check payments table structure
    echo "Checking payments table structure...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM payments");
    $paymentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Payments table columns: " . implode(', ', $paymentColumns) . "\n\n";
    
    // Try to find the correct column name for flat number
    $possibleFlatColumns = ['flat_number', 'flat_no', 'apartment_number', 'unit_number', 'house_number'];
    $foundFlatColumn = null;
    
    foreach ($possibleFlatColumns as $col) {
        if (in_array($col, $userColumns)) {
            $foundFlatColumn = $col;
            break;
        }
    }
    
    if ($foundFlatColumn) {
        echo "Found flat number column: $foundFlatColumn\n";
        
        // Update the export-payments.php file with the correct column name
        $exportFile = __DIR__ . '/api/admin/export-payments.php';
        if (file_exists($exportFile)) {
            $content = file_get_contents($exportFile);
            $updatedContent = str_replace(
                ["u.flat_number", "flat_number'", 'flat_number"'],
                ["u.$foundFlatColumn", "$foundFlatColumn'", "$foundFlatColumn\""],
                $content
            );
            
            if (file_put_contents($exportFile, $updatedContent)) {
                echo "✅ Updated export-payments.php with correct column name: $foundFlatColumn\n";
            } else {
                echo "❌ Failed to update export-payments.php\n";
            }
        } else {
            echo "❌ export-payments.php not found\n";
        }
    } else {
        echo "❌ Could not find a flat number column in the users table.\n";
        echo "Please check your database structure and ensure there's a column for flat/apartment numbers.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
