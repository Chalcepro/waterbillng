<?php
// api/user/dashboard-data.php

// Hide PHP warnings in output but log them (safer for API)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// JSON response header
header('Content-Type: application/json');

// --- CORS: allowlist ---
// IMPORTANT: when using credentials: 'include' you cannot use Access-Control-Allow-Origin: *
$allowed_origins = [
    'https://waterbill.free.nf',
    'https://yourdomain.com', // add other trusted origins
    'http://localhost:8000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // If origin not recognized, you may allow non-credentialed access or deny.
    // We'll set a safe default (no credentials)
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OK']);
    exit;
}

// Start session (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure config / DB connection available
require_once __DIR__ . '/../../config.php'; // ensure this file sets up $pdo (PDO instance)

// Defensive: ensure $pdo exists
if (!isset($pdo) || !$pdo instanceof PDO) {
    error_log(__FILE__ . " - PDO not found in config.php");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit;
}

// Basic response template
$response = [
    'success' => false,
    'authenticated' => false,
];

// Simple auth guard: require session user_id
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(array_merge($response, ['message' => 'Not authenticated']));
    exit;
}

// Optionally, restrict admin access here if needed. For now we allow any logged-in user.
// if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
//     http_response_code(401);
//     echo json_encode(array_merge($response, ['message' => 'Unauthorized for admin']));
//     exit;
// }

try {
    $userId = (int) $_SESSION['user_id'];

    // Prepare default response structure (guarantee success key present)
    $response = array_merge($response, [
        'success' => true,
        'authenticated' => true,
        'user_id' => $userId,
        'subscription_status' => 'inactive',
        'subscription_end' => null,
        'subscription_plan' => 'None',
        'last_payment' => 'Never',
        'next_payment' => 'Not scheduled',
        'has_subscription' => false,
        'is_expired' => true,
        'pending_payments' => 0,
        'total_paid' => 0.0,
        'user' => [
            'name' => '',
            'email' => '',
            'phone' => '',
            'flat' => ''
        ]
    ]);

    // Fetch basic user info
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT_WS(' ', first_name, middle_name, last_name) as name,
            email, phone, address, flat_no as flat, role
        FROM users 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $response['user'] = [
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? '',
            'flat' => $user['flat'] ?? '',
            'role' => $user['role'] ?? ($_SESSION['role'] ?? '')
        ];
    }

    // Try to load subscription info from users table (fields may vary)
    $stmt = $pdo->prepare("
        SELECT 
            subscription_status as status,
            subscription_end_date as end_date,
            subscription_start_date as start_date,
            subscription_plan,
            last_payment_date,
            next_payment_date,
            created_at as user_since,
            (SELECT p.amount FROM payments p WHERE p.user_id = u.id AND p.status = 'approved' ORDER BY p.created_at DESC LIMIT 1) as last_payment_amount,
            (SELECT p.created_at FROM payments p WHERE p.user_id = u.id AND p.status = 'approved' ORDER BY p.created_at DESC LIMIT 1) as last_payment_date
        FROM users u
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback: if no subscription end date, check subscriptions table
    if (!$subscription || empty($subscription['end_date'])) {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                p.amount as last_payment_amount,
                p.created_at as last_payment_date
            FROM subscriptions s
            LEFT JOIN payments p ON p.id = s.payment_id AND p.status = 'approved'
            WHERE s.user_id = ?
            ORDER BY s.end_date DESC, s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Process subscription info if available
    if ($subscription && !empty($subscription['end_date'])) {
        // Parse dates defensively
        try {
            $endDateStr = $subscription['end_date'];
            $endDate = new DateTime($endDateStr);
        } catch (Exception $e) {
            $endDate = (new DateTime())->modify('+30 days');
        }

        try {
            $startDateStr = $subscription['start_date'] ?? $subscription['created_at'] ?? null;
            $startDate = $startDateStr ? new DateTime($startDateStr) : (clone $endDate)->modify('-30 days');
        } catch (Exception $e) {
            $startDate = (clone $endDate)->modify('-30 days');
        }

        $now = new DateTime();

        $daysRemaining = max(0, (int)$now->diff($endDate)->format('%a'));
        $totalDays = max(1, (int)$startDate->diff($endDate)->format('%a'));
        $daysUsed = max(0, (int)$startDate->diff($now)->format('%a'));

        $response['subscription_status'] = strtolower($subscription['status'] ?? 'inactive');
        $response['subscription_plan'] = $subscription['subscription_plan'] ?? ($subscription['plan'] ?? 'Standard');
        $response['subscription_start'] = $startDate->format('M j, Y');
        $response['subscription_end'] = $endDate->format('M j, Y');
        $response['raw_subscription_end'] = $subscription['end_date'];
        $response['days_remaining'] = $daysRemaining;
        $response['has_subscription'] = true;
        $response['is_expired'] = ($now > $endDate);
        $response['subscription_progress'] = $totalDays > 0 ? min(100, round(($daysUsed / $totalDays) * 100, 2)) : 0;

        if (!empty($subscription['last_payment_date'])) {
            $response['last_payment'] = date('M j, Y', strtotime($subscription['last_payment_date']));
            $response['last_payment_amount'] = (float)($subscription['last_payment_amount'] ?? 0.0);
        }

        // Calculate next payment (example logic)
        $nextPaymentDate = (clone $endDate)->modify('+30 days');
        $response['next_payment'] = $nextPaymentDate->format('M j, Y');
    } else {
        // Keep defaults - no active subscription
        $response['subscription_status'] = 'inactive';
        $response['subscription_plan'] = 'None';
        $response['has_subscription'] = false;
        $response['is_expired'] = true;
        $response['subscription_end'] = 'No active subscription';
    }

    // Payment stats
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) as total_paid
        FROM payments 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['pending_payments'] = (int)($stats['pending_count'] ?? 0);
    $response['total_paid'] = (float)($stats['total_paid'] ?? 0.0);

    // Return final response
    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    // Log error server-side only
    error_log('dashboard-data error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'message' => 'Internal server error'
    ]);
    exit;
}
