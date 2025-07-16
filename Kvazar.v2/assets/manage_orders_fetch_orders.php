<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $page = intval($input['page'] ?? 1);
    $itemsPerPage = intval($input['itemsPerPage'] ?? 15);
    $sort = $input['sort'] ?? ['column' => 'Order_date', 'direction' => 'desc'];
    $filters = $input['filters'] ?? [];
    
    // Calculate offset
    $offset = ($page - 1) * $itemsPerPage;
    
    // Build base query
    $baseQuery = "
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers cr ON o.Courier_id = cr.Courier_id
        LEFT JOIN list_order_status los ON o.Status = los.Status_id
        LEFT JOIN list_currency curr ON o.Currency_id = curr.id
    ";
    
    // Build WHERE conditions
    $whereConditions = [];
    $params = [];
    
    // Search filter
    if (!empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $whereConditions[] = "(
            o.display_order_number LIKE ? OR
            c.Full_Company_name LIKE ? OR
            cr.Full_Company_name LIKE ?
        )";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Status filter
    if (!empty($filters['status'])) {
        $whereConditions[] = "o.Status = ?";
        $params[] = $filters['status'];
    }
    
    // Client filter
    if (!empty($filters['client'])) {
        $whereConditions[] = "o.Client_id = ?";
        $params[] = $filters['client'];
    }
    
    // Courier filter
    if (!empty($filters['courier'])) {
        $whereConditions[] = "o.Courier_id = ?";
        $params[] = $filters['courier'];
    }
    
    // Date range filter
    if (!empty($filters['dateFrom'])) {
        $whereConditions[] = "o.Order_date >= ?";
        $params[] = $filters['dateFrom'];
    }
    
    if (!empty($filters['dateTo'])) {
        $whereConditions[] = "o.Order_date <= ?";
        $params[] = $filters['dateTo'];
    }
    
    // Combine WHERE conditions
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Map sort columns
    $sortMapping = [
        'status' => 'los.Status_name',
        'order_number' => 'o.display_order_number',
        'client_name' => 'c.Full_Company_name',
        'order_date' => 'o.Order_date',
        'courier_name' => 'cr.Full_Company_name',
        'order_total' => 'o.Order_total'
    ];
    
    $sortColumn = $sortMapping[$sort['column']] ?? 'o.Order_date';
    $sortDirection = strtoupper($sort['direction']) === 'ASC' ? 'ASC' : 'DESC';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) " . $baseQuery . " " . $whereClause;
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    // Get orders data
    $dataQuery = "
        SELECT 
            o.Order_id,
            o.display_order_number,
            o.Order_date,
            o.Order_total,
            o.Status,
            o.Courier_id,
            c.Full_Company_name as client_name,
            cr.Full_Company_name as courier_name,
            los.Status_name as status_name,
            los.Status_color as status_color,
            curr.currency_name as currency_name,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM Attached_order_files aof 
                    WHERE aof.Order_id = o.Order_id
                ) THEN 1 
                ELSE 0 
            END as has_attached_files
        " . $baseQuery . " 
        " . $whereClause . "
        ORDER BY " . $sortColumn . " " . $sortDirection . "
        LIMIT " . $itemsPerPage . " OFFSET " . $offset . "
    ";
    
    $dataStmt = $pdo->prepare($dataQuery);
    $dataStmt->execute($params);
    $orders = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    foreach ($orders as &$order) {
        // Format dates
        if ($order['Order_date']) {
            $order['Order_date'] = date('Y-m-d', strtotime($order['Order_date']));
        }
        
        // Format currency
        if ($order['Order_total']) {
            $order['Order_total'] = number_format($order['Order_total'], 2, '.', '');
        }
        
        // Ensure boolean values
        $order['has_attached_files'] = (bool) $order['has_attached_files'];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'totalCount' => intval($totalCount),
        'currentPage' => $page,
        'itemsPerPage' => $itemsPerPage
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 