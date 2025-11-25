<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/session_boot.php';
session_boot();
require_once __DIR__ . '/../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get filter parameters
            $status = $_GET['status'] ?? null;
            $method = $_GET['method'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            
            // Build base query
            $query = "
                SELECT 
                    mp.*, 
                    u.username, 
                    u.email, 
                    CONCAT(u.first_name, ' ', u.surname) as user_name,
                    u.account_number
                FROM manual_payments mp
                JOIN users u ON mp.user_id = u.id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add filters
            if ($status) {
                $query .= " AND mp.status = :status";
                $params[':status'] = $status;
            }
            
            if ($method) {
                $query .= " AND mp.payment_method = :method";
                $params[':method'] = $method;
            }
            
            if ($dateFrom) {
                $query .= " AND DATE(mp.payment_date) >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $query .= " AND DATE(mp.payment_date) <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            // Add sorting
            $query .= " ORDER BY mp.created_at DESC";
            
            // Prepare and execute query
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the response
            $response = [
                'success' => true, 
                'data' => $payments,
                'meta' => [
                    'total' => count($payments),
                    'filters' => [
                        'status' => $status,
                        'method' => $method,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo
                    ]
                ]
            ];
            
            echo json_encode($response);
            break;
            
        case 'POST':
            // Create a new manual payment record
            $data = json_decode(file_get_contents('php://input'), true);
            
            $required = ['user_id', 'amount', 'payment_date', 'payment_method'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Check if payment already exists with same reference
            if (!empty($data['transaction_reference'])) {
                $checkStmt = $pdo->prepare("
                    SELECT id FROM manual_payments 
                    WHERE transaction_reference = ? 
                    AND payment_method = ?
                    LIMIT 1
                ");
                $checkStmt->execute([$data['transaction_reference'], $data['payment_method']]);
                
                if ($checkStmt->rowCount() > 0) {
                    throw new Exception('A payment with this reference already exists');
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO manual_payments 
                (user_id, amount, payment_date, payment_method, transaction_reference, 
                 status, admin_notes, payment_proof, bank_name, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['user_id'],
                $data['amount'],
                $data['payment_date'] ?? date('Y-m-d H:i:s'),
                $data['payment_method'],
                $data['transaction_reference'] ?? null,
                $data['status'] ?? 'pending',
                $data['admin_notes'] ?? null,
                $data['payment_proof'] ?? null,
                $data['bank_name'] ?? null
            ]);
            
            $paymentId = $pdo->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Manual payment recorded',
                'payment_id' => $paymentId
            ]);
            break;
            
        case 'PUT':
            // Update payment status
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || !isset($data['status'])) {
                throw new Exception('Payment ID and status are required');
            }
            
            $stmt = $pdo->prepare("
                UPDATE manual_payments 
                SET status = ?, admin_notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['status'],
                $data['admin_notes'] ?? null,
                $data['id']
            ]);
            
            if ($stmt->rowCount() > 0) {
                // If approved, update user's subscription
                if ($data['status'] === 'approved') {
                    $payment = $pdo->query("SELECT * FROM manual_payments WHERE id = " . (int)$data['id'])->fetch();
                    
                    // Add to payments table
                    $stmt = $pdo->prepare("
                        INSERT INTO payments (user_id, amount, payment_date, payment_method, status, reference)
                        VALUES (?, ?, NOW(), 'manual', 'completed', ?)
                    ");
                    $stmt->execute([
                        $payment['user_id'],
                        $payment['amount'],
                        'MANUAL-' . time()
                    ]);
                    
                    // Update user's subscription (you might need to adjust this based on your subscription logic)
                    $stmt = $pdo->prepare("
                        INSERT INTO subscriptions (user_id, start_date, end_date, status, payment_id)
                        VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 'active', ?)
                        ON DUPLICATE KEY UPDATE 
                            end_date = DATE_ADD(end_date, INTERVAL 1 MONTH),
                            status = 'active',
                            payment_id = ?
                    ");
                    $stmt->execute([
                        $payment['user_id'],
                        $pdo->lastInsertId(),
                        $pdo->lastInsertId()
                    ]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Payment updated']);
            } else {
                throw new Exception('Payment not found');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
