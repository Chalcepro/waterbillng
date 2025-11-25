<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Test database connection
    $pdo->query('SELECT 1');
    echo "✅ Database connection successful\n\n";
    
    // Check if fault_reports table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'fault_reports'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "❌ Table 'fault_reports' does not exist in the database.\n";
        exit;
    }
    
    echo "✅ Table 'fault_reports' exists. Checking for data...\n\n";
    
    // Show table structure
    echo "📋 Table Structure:\n";
    $stmt = $pdo->query("DESCRIBE fault_reports");
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    // Count records
    echo "\n🔢 Record Counts:\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM fault_reports GROUP BY status");
    $hasData = false;
    while ($row = $stmt->fetch()) {
        echo "- Status '{$row['status']}': {$row['count']} records\n";
        $hasData = true;
    }
    
    if (!$hasData) {
        echo "- No records found in fault_reports table\n";
        echo "\n💡 Try submitting a test report from the user dashboard to create sample data.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

// Check if users can submit reports
echo "\n🔍 Checking user report submission...\n";
$reportFormPath = __DIR__ . '/../../../frontend/user/report-fault.html';
if (file_exists($reportFormPath)) {
    $reportUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/waterbill/frontend/user/report-fault.html';
    echo "✅ Report form exists at: $reportUrl\n";
    echo "   - Visit this URL to submit a test report\n";
} else {
    echo "❌ Report form not found at: $reportFormPath\n";
}
