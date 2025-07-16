<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get parameters
    error_log("Received parameters: " . print_r($_GET, true));
    
    $tab = $_GET['tab'] ?? 'client';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 10;
    $search = $_GET['search'] ?? '';
    $typeFilter = $_GET['type'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';
    
    error_log("Parsed sort parameter: " . $_GET['sort']);
    $sort = json_decode($_GET['sort'], true);
    error_log("Decoded sort: " . print_r($sort, true));
    
    // Parse sort parameters
    $sortField = $sort['field'] ?? 'created_at';
    $sortDir = $sort['direction'] ?? 'desc';

    // Validate sort direction
    $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

    // Debug log
    error_log("Processing request - Tab: $tab, Page: $page, Sort: $sortField $sortDir");

    // Determine table and join based on tab
    switch ($tab) {
        case 'client':
            $table = 'Client_contracts';
            $entityTable = 'Clients';
            $entityId = 'Client_id';
            break;
        case 'courier':
            $table = 'Courier_contracts';
            $entityTable = 'Couriers';
            $entityId = 'Courier_id';
            break;
        case 'agent':
            $table = 'Agent_contracts';
            $entityTable = 'Agents';
            $entityId = 'Agent_id';
            break;
        default:
            throw new Exception('Invalid tab type');
    }

    // Build base query
    $query = "
        SELECT 
            c.Contract_id as id,
            COALESCE(c.Contract_number, '-') as contract_number,
            COALESCE(t.Type_name, '-') as contract_type,
            COALESCE(c.Contract_status, 'pending') as status_name,
            DATE_FORMAT(c.Contract_date, '%Y-%m-%d') as contract_date,
            DATE_FORMAT(c.Created_at, '%Y-%m-%d %H:%i:%s') as created_at,
            COALESCE(e.Full_company_name, '-') as company_name,
            COALESCE(s.Status_color, '#ddd') as status_color,
            COALESCE(u.Full_name, 'System') as created_by_name,
            COALESCE(c.file_path, '') as file_path
        FROM {$table} c
        LEFT JOIN {$entityTable} e ON c.{$entityId} = e.{$entityId}
        LEFT JOIN Users u ON c.Created_by = u.User_id
        LEFT JOIN list_contract_status s ON c.Contract_status = s.Status_name
        LEFT JOIN list_contract_type t ON c.Contract_type = t.Type_id
        WHERE 1=1
    ";

    // Add filters
    $params = [];
    
    if ($search) {
        $query .= " AND (c.Contract_number LIKE ? OR e.Full_company_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($typeFilter) {
        $query .= " AND c.Contract_type = ?";
        $params[] = $typeFilter;
    }

    if ($statusFilter) {
        $query .= " AND c.Contract_status = ?";
        $params[] = $statusFilter;
    }

    if ($dateFrom) {
        $query .= " AND DATE(c.Contract_date) >= ?";
        $params[] = $dateFrom;
    }

    if ($dateTo) {
        $query .= " AND DATE(c.Contract_date) <= ?";
        $params[] = $dateTo;
    }

    // Debug log
    error_log("Query before count: " . $query);
    error_log("Params: " . print_r($params, true));

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as count FROM {$table} c 
                   JOIN {$entityTable} e ON c.{$entityId} = e.{$entityId}
                   WHERE 1=1";

    if ($search) {
        $countQuery .= " AND (c.Contract_number LIKE ? OR e.Full_company_name LIKE ?)";
    }
    if ($typeFilter) {
        $countQuery .= " AND c.Contract_type = ?";
    }
    if ($statusFilter) {
        $countQuery .= " AND c.Contract_status = ?";
    }
    if ($dateFrom) {
        $countQuery .= " AND DATE(c.Contract_date) >= ?";
    }
    if ($dateTo) {
        $countQuery .= " AND DATE(c.Contract_date) <= ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    $totalPages = ceil($totalCount / $perPage);

    // Update sort handling
    $sortableFields = [
        'contract_number' => 'Contract_number',
        'company_name' => 'Full_company_name',
        'contract_type' => 't.Type_name',
        'contract_date' => 'Contract_date',
        'created_at' => 'Created_at',
        'status' => 'Contract_status'
    ];

    $sortField = isset($sortableFields[$sort['field']]) 
        ? ($sort['field'] === 'company_name' ? "e.{$sortableFields[$sort['field']]}" 
        : ($sort['field'] === 'contract_type' ? $sortableFields[$sort['field']] 
        : "c.{$sortableFields[$sort['field']]}"))
        : 'c.Created_at';

    // Add sorting
    $query .= " ORDER BY {$sortField} {$sortDir}";
    
    // Add pagination (modified syntax)
    $offset = intval(($page - 1) * $perPage);
    $limit = intval($perPage);
    $query .= " LIMIT $limit OFFSET $offset";  // Direct integer values instead of parameters

    // Remove pagination params since we're using direct values
    // $params[] = intval($perPage);
    // $params[] = intval(($page - 1) * $perPage);

    // Debug log
    error_log("Final query: " . $query);
    error_log("Final params: " . print_r($params, true));

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'contracts' => $contracts,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalCount' => $totalCount,
        'debug' => [
            'query' => $query,
            'params' => $params,
            'totalCount' => $totalCount
        ]
    ]);

} catch (Exception $e) {
    error_log("Full error details: " . $e->getMessage());
    error_log("Error occurred in file: " . $e->getFile() . " on line " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 