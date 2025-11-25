<?php
/**
 * Test script for subscription and payment functionality
 * 
 * This script tests:
 * 1. Creating a test user
 * 2. Making a payment
 * 3. Verifying subscription status
 * 4. Checking dashboard data
 */

require_once 'config.php';
require_once 'api/includes/db_connect.php';

// Get database connection
$pdo = getDBConnection();

// Test user data
$testUser = [
    'email' => 'testuser_' . time() . '@example.com',
    'password' => password_hash('test123', PASSWORD_DEFAULT),
    'full_name' => 'Test User',
    'phone' => '080' . rand(10000000, 99999999),
    'role' => 'user',
    'address' => '123 Test Street, Test City'
];

// Function to create a test user
function createTestUser($pdo, $userData) {
    echo "Creating test user...\n";
    
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        
        if ($stmt->rowCount() > 0) {
            $userId = $stmt->fetchColumn();
            echo "⚠️  User already exists with ID: $userId\n";
            return $userId;
        }
        
        // Create new user
        $sql = "INSERT INTO users (email, password, full_name, phone, role, address, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userData['email'],
            $userData['password'],
            $userData['full_name'],
            $userData['phone'],
            $userData['role'],
            $userData['address']
        ]);
        
        $userId = $pdo->lastInsertId();
        echo "✅ Created test user with ID: $userId\n";
        return $userId;
        
    } catch (Exception $e) {
        echo "❌ Error creating test user: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to create a test payment
function createTestPayment($pdo, $userId, $amount, $status = 'approved') {
    echo "Creating test payment...\n";
    
    try {
        $reference = 'TEST' . time() . rand(100, 999);
        $now = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO payments (user_id, amount, reference, status, payment_method, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'test', ?, ?)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $amount,
            $reference,
            $status,
            $now,
            $now
        ]);
        
        $paymentId = $pdo->lastInsertId();
        echo "✅ Created test payment with ID: $paymentId (Reference: $reference)\n";
        return $paymentId;
        
    } catch (Exception $e) {
        echo "❌ Error creating test payment: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to create/update subscription
function updateUserSubscription($pdo, $userId, $paymentId, $amount) {
    echo "Updating user subscription...\n";
    
    try {
        // Get subscription duration from settings
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'subscription_duration_days'");
        $stmt->execute();
        $durationDays = (int)$stmt->fetchColumn() ?: 30; // Default to 30 days if not set
        
        // Get min payment amount
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'min_payment_amount'");
        $stmt->execute();
        $minPayment = (float)$stmt->fetchColumn() ?: 2000; // Default to 2000 if not set
        
        // Calculate months covered (1 month per min payment amount)
        $monthsCovered = floor($amount / $minPayment);
        if ($monthsCovered < 1) $monthsCovered = 1;
        
        // Calculate end date
        $startDate = new DateTime();
        $endDate = clone $startDate;
        $endDate->modify("+{$monthsCovered} months");
        
        // Check if user has an existing subscription
        $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE user_id = ? ORDER BY end_date DESC LIMIT 1");
        $stmt->execute([$userId]);
        
        if ($subscription = $stmt->fetch()) {
            // Update existing subscription
            $sql = "UPDATE subscriptions SET 
                    end_date = ?, 
                    status = 'active',
                    amount_paid = ?,
                    months_covered = ?,
                    payment_id = ?,
                    updated_at = NOW()
                    WHERE id = ?";
                    
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $endDate->format('Y-m-d H:i:s'),
                $amount,
                $monthsCovered,
                $paymentId,
                $subscription['id']
            ]);
            
            $subscriptionId = $subscription['id'];
            $action = 'updated';
        } else {
            // Create new subscription
            $sql = "INSERT INTO subscriptions 
                    (user_id, start_date, end_date, status, amount_paid, months_covered, payment_id, created_at, updated_at)
                    VALUES (?, ?, ?, 'active', ?, ?, ?, NOW(), NOW())";
                    
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $userId,
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s'),
                $amount,
                $monthsCovered,
                $paymentId
            ]);
            
            $subscriptionId = $pdo->lastInsertId();
            $action = 'created';
        }
        
        if ($result) {
            echo "✅ Successfully $action subscription with ID: $subscriptionId\n";
            echo "   - Amount: $amount\n";
            echo "   - Months Covered: $monthsCovered\n";
            echo "   - Start Date: " . $startDate->format('Y-m-d') . "\n";
            echo "   - End Date: " . $endDate->format('Y-m-d') . "\n";
            return $subscriptionId;
        } else {
            throw new Exception("Failed to $action subscription");
        }
        
    } catch (Exception $e) {
        echo "❌ Error updating subscription: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to get dashboard data
function getDashboardData($pdo, $userId) {
    echo "\nFetching dashboard data...\n";
    
    try {
        // Get user info
        $stmt = $pdo->prepare("SELECT id, email, full_name, phone, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Get subscription info
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   p.reference as payment_reference,
                   p.status as payment_status,
                   p.payment_method,
                   p.created_at as payment_date
            FROM subscriptions s
            LEFT JOIN payments p ON s.payment_id = p.id
            WHERE s.user_id = ?
            ORDER BY s.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get payment history
        $stmt = $pdo->prepare("
            SELECT id, amount, reference, status, payment_method, created_at 
            FROM payments 
            WHERE user_id = ? 
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate subscription status
        $subscriptionStatus = [
            'has_subscription' => false,
            'status' => 'inactive',
            'days_remaining' => 0,
            'progress_percent' => 0,
            'is_active' => false
        ];
        
        if ($subscription) {
            $now = new DateTime();
            $startDate = new DateTime($subscription['start_date']);
            $endDate = new DateTime($subscription['end_date']);
            
            $totalDays = $startDate->diff($endDate)->days;
            $daysRemaining = $now->diff($endDate)->days;
            $daysElapsed = $now->diff($startDate)->days;
            $progressPercent = min(100, max(0, ($daysElapsed / $totalDays) * 100));
            
            $subscriptionStatus = [
                'has_subscription' => true,
                'status' => $subscription['status'],
                'start_date' => $subscription['start_date'],
                'end_date' => $subscription['end_date'],
                'days_remaining' => $daysRemaining,
                'progress_percent' => round($progressPercent, 2),
                'is_active' => ($subscription['status'] === 'active' && $endDate > $now),
                'amount_paid' => (float)$subscription['amount_paid'],
                'months_covered' => (int)$subscription['months_covered'],
                'payment_reference' => $subscription['payment_reference'] ?? null,
                'payment_status' => $subscription['payment_status'] ?? null
            ];
        }
        
        return [
            'user' => $user,
            'subscription' => $subscriptionStatus,
            'recent_payments' => $payments
        ];
        
    } catch (Exception $e) {
        echo "❌ Error fetching dashboard data: " . $e->getMessage() . "\n";
        return [];
    }
}

// Main test execution
echo "=== Subscription and Payment Test ===\n\n";

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Create test user
    $userId = createTestUser($pdo, $testUser);
    if (!$userId) {
        throw new Exception("Failed to create test user");
    }
    
    // 2. Create test payment
    $amount = 6000; // This should cover 3 months if min payment is 2000
    $paymentId = createTestPayment($pdo, $userId, $amount);
    if (!$paymentId) {
        throw new Exception("Failed to create test payment");
    }
    
    // 3. Update subscription
    $subscriptionId = updateUserSubscription($pdo, $userId, $paymentId, $amount);
    if (!$subscriptionId) {
        throw new Exception("Failed to update subscription");
    }
    
    // 4. Get and display dashboard data
    $dashboardData = getDashboardData($pdo, $userId);
    
    // Commit transaction
    $pdo->commit();
    
    // Display results
    echo "\n=== Test Results ===\n";
    echo "User ID: " . $dashboardData['user']['id'] . "\n";
    echo "Name: " . $dashboardData['user']['full_name'] . "\n";
    echo "Email: " . $dashboardData['user']['email'] . "\n\n";
    
    if ($dashboardData['subscription']['has_subscription']) {
        $sub = $dashboardData['subscription'];
        echo "Subscription Status: " . ($sub['is_active'] ? '✅ Active' : '❌ Inactive') . "\n";
        echo "Start Date: " . $sub['start_date'] . "\n";
        echo "End Date: " . $sub['end_date'] . "\n";
        echo "Days Remaining: " . $sub['days_remaining'] . "\n";
        echo "Progress: " . $sub['progress_percent'] . "%\n";
        echo "Amount Paid: " . number_format($sub['amount_paid'], 2) . "\n";
        echo "Months Covered: " . $sub['months_covered'] . "\n\n";
        
        if (!empty($dashboardData['recent_payments'])) {
            echo "Recent Payments:\n";
            foreach ($dashboardData['recent_payments'] as $payment) {
                echo "- " . $payment['reference'] . ": " . number_format($payment['amount'], 2) . " " . 
                     "({$payment['status']}) - " . $payment['created_at'] . "\n";
            }
        }
    } else {
        echo "❌ No active subscription found\n";
    }
    
    echo "\n=== Test Completed Successfully ===\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\n❌ Test Failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
?>
