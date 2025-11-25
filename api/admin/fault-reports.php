<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get filter parameters
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? '';
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            // Build base query
            $query = "
                SELECT 
                    fr.*, 
                    u.username, 
                    u.email,
                    CONCAT(u.first_name, ' ', u.surname) as user_name,
                    u.account_number
                FROM fault_reports fr
                JOIN users u ON fr.user_id = u.id
                WHERE 1=1
            ";
            
            $params = [];
            $where = [];
            
            // Add status filter
            if ($status && in_array($status, ['open', 'in_progress', 'resolved', 'rejected'])) {
                $where[] = "fr.status = :status";
                $params[':status'] = $status;
            }
            
            // Add search filter
            if ($search) {
                $where[] = "(fr.category LIKE :search OR fr.description LIKE :search OR u.account_number = :account_search)";
                $searchTerm = "%$search%";
                $params[':search'] = $searchTerm;
                $params[':account_search'] = $search;
            }
            
            // Add WHERE conditions
            if (!empty($where)) {
                $query .= " AND " . implode(' AND ', $where);
            }
            
            // Get total count for pagination
            $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM ($query) as count_query");
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($total / $perPage);
            
            // Add sorting and pagination
            $query .= " ORDER BY fr.created_at DESC LIMIT :limit OFFSET :offset";
            
            // Execute main query
            $stmt = $pdo->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return response
            echo json_encode([
                'success' => true,
                'data' => $reports,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $totalPages
                ]
            ]);
            break;
            
        case 'PUT':
            // Update report status
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || !isset($data['status'])) {
                throw new Exception('Report ID and status are required');
            }
            
            $validStatuses = ['open', 'in_progress', 'resolved', 'rejected'];
            if (!in_array($data['status'], $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("
                UPDATE fault_reports 
                SET status = :status,
                    admin_notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':status' => $data['status'],
                ':notes' => $data['notes'] ?? null,
                ':id' => $data['id']
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Report not found or no changes made');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Report updated successfully'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
