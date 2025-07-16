<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Add at the beginning of try block
    error_log("Received request parameters: " . print_r($_GET, true));
    
    // Get parameters
    $tab = $_GET['tab'] ?? 'client';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['perPage']) ? max(1, intval($_GET['perPage'])) : 10;
    $search = $_GET['search'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $bankFilter = $_GET['bank'] ?? '';
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';
    $sort = json_decode($_GET['sort'] ?? '{}', true);

    // Determine table and joins based on tab
    switch ($tab) {
        case 'client':
            $mainTable = 'Clients';
            $idColumn = 'Client_id';
            $contractTable = 'Client_contracts';
            $extraJoins = 'LEFT JOIN Orders o ON c.Client_id = o.Client_id';
            break;
        case 'courier':
            $mainTable = 'Couriers';
            $idColumn = 'Courier_id';
            $contractTable = 'Courier_contracts';
            $extraJoins = '
                LEFT JOIN Drivers d ON c.Courier_id = d.Courier_id
                LEFT JOIN Vehicles v ON c.Courier_id = v.Courier_id';
            break;
        case 'agent':
            $mainTable = 'Agents';
            $idColumn = 'Agent_id';
            $contractTable = 'Agent_contracts';
            $extraJoins = '';
            break;
        default:
            throw new Exception('Invalid tab type');
    }

    // Build base query
    $query = "
        SELECT 
            c.*,
            ps.Status_name as status_name,
            ps.Status_color as status_color,
            u.Full_name as created_by_name,
            c.Company_type,
            c.Full_Company_name,
            c.Short_Company_name,
            c.INN,
            c.KPP,
            c.OGRN,
            c.Physical_address,
            c.Legal_address,
            c." . $idColumn . " as id,
            GROUP_CONCAT(
                DISTINCT CONCAT_WS('|',
                    con.Contract_id,
                    con.Contract_number,
                    con.Contract_date,
                    ct.Type_name,
                    cs.Status_name,
                    cs.Status_color,
                    COALESCE(con.file_path, '')
                )
            ) as contracts
    ";

    // Add tab-specific columns
    if ($tab === 'courier') {
        $query .= ",
            GROUP_CONCAT(DISTINCT CONCAT_WS('|', 
                COALESCE(d.Driver_id, ''), 
                COALESCE(d.Name, '')
            )) as drivers,
            GROUP_CONCAT(DISTINCT CONCAT_WS('|', 
                COALESCE(v.Vehicle_id, ''), 
                COALESCE(v.Brand, ''),
                COALESCE(v.Plate_number, '')
            )) as vehicles
        ";
    } else if ($tab === 'client') {
        $query .= ",
            GROUP_CONCAT(DISTINCT CONCAT_WS('|', o.Order_id, o.Status)) as orders
        ";
    }

    $query .= "
        FROM {$mainTable} c
        LEFT JOIN list_partners_status ps ON c.Status = ps.Status_id
        LEFT JOIN Users u ON c.Created_by = u.User_id OR c.Created_by IS NULL
        LEFT JOIN {$contractTable} con ON c.{$idColumn} = con.{$idColumn}
        LEFT JOIN list_contract_type ct ON con.Contract_type = ct.Type_id
        LEFT JOIN list_contract_status cs ON con.Contract_status = cs.Status_name
        {$extraJoins}
        WHERE 1=1
    ";

    // Add filters
    $params = [];
    if ($search) {
        $query .= " AND (
            c.Full_Company_name LIKE ? OR 
            c.Short_Company_name LIKE ? OR 
            c.INN LIKE ? OR 
            c.KPP LIKE ?
        )";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    if ($statusFilter) {
        $query .= " AND c.Status = ?";
        $params[] = $statusFilter;
    }

    if ($bankFilter) {
        $query .= " AND c.Bank_name = ?";
        $params[] = $bankFilter;
    }

    if ($dateFrom) {
        $query .= " AND DATE(c.created_at) >= ?";
        $params[] = $dateFrom;
    }

    if ($dateTo) {
        $query .= " AND DATE(c.created_at) <= ?";
        $params[] = $dateTo;
    }

    // Add grouping
    $query .= " GROUP BY c.{$idColumn}";

    // Add sorting
    $sortField = $sort['field'] ?? 'created_at';
    $sortDir = strtoupper($sort['direction'] ?? 'desc') === 'ASC' ? 'ASC' : 'DESC';
    $query .= " ORDER BY c.{$sortField} {$sortDir}";

    // Add pagination
    $offset = ($page - 1) * $perPage;
    $query .= " LIMIT $perPage OFFSET $offset";

    // Add before executing the main query
    error_log("Final query: " . $query);
    error_log("Parameters: " . print_r($params, true));

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add after fetching results
    error_log("Found " . count($partners) . " partners");

    // Process the results
    foreach ($partners as &$partner) {
        // Process contracts
        if ($partner['contracts']) {
            $contractsArray = [];
            foreach (explode(',', $partner['contracts']) as $contract) {
                list($id, $number, $date, $type, $status, $color, $filePath) = explode('|', $contract);
                $contractsArray[] = [
                    'id' => $id,
                    'number' => $number,
                    'date' => $date,
                    'type' => $type,
                    'status' => $status,
                    'status_color' => $color,
                    'file_path' => $filePath
                ];
            }
            $partner['contracts'] = $contractsArray;
        }

        // Process tab-specific data
        if ($tab === 'courier') {
            // Process drivers
            if ($partner['drivers']) {
                $driversArray = [];
                foreach (explode(',', $partner['drivers']) as $driver) {
                    list($id, $name) = array_pad(explode('|', $driver), 2, '');
                    if ($id || $name) {
                        $driversArray[] = [
                            'id' => $id,
                            'name' => $name
                        ];
                    }
                }
                $partner['drivers'] = $driversArray;
            }

            // Process vehicles
            if ($partner['vehicles']) {
                $vehiclesArray = [];
                foreach (explode(',', $partner['vehicles']) as $vehicle) {
                    list($id, $brand, $plate) = array_pad(explode('|', $vehicle), 3, '');
                    if ($id || $brand || $plate) {
                        $vehiclesArray[] = [
                            'id' => $id,
                            'brand' => $brand,
                            'plate_number' => $plate
                        ];
                    }
                }
                $partner['vehicles'] = $vehiclesArray;
            }
        } else if ($tab === 'client') {
            // Process orders
            if ($partner['orders']) {
                $ordersArray = [];
                foreach (explode(',', $partner['orders']) as $order) {
                    list($id, $status) = explode('|', $order);
                    $ordersArray[] = ['id' => $id, 'status' => $status];
                }
                $partner['orders'] = $ordersArray;
            }
        }
    }

    // Get total count for pagination
    $countQuery = "SELECT COUNT(DISTINCT c.{$idColumn}) as count FROM {$mainTable} c WHERE 1=1";
    
    // Add the same filters to count query
    if ($search) {
        $countQuery .= " AND (
            c.Full_Company_name LIKE ? OR 
            c.Short_Company_name LIKE ? OR 
            c.INN LIKE ? OR 
            c.KPP LIKE ?
        )";
    }

    if ($statusFilter) {
        $countQuery .= " AND c.Status = ?";
    }

    if ($bankFilter) {
        $countQuery .= " AND c.Bank_name = ?";
    }

    if ($dateFrom) {
        $countQuery .= " AND DATE(c.created_at) >= ?";
    }

    if ($dateTo) {
        $countQuery .= " AND DATE(c.created_at) <= ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    $totalPages = ceil($totalCount / $perPage);

    // Add before sending response
    $response = [
        'success' => true,
        'partners' => $partners,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalCount' => $totalCount,
        'itemsPerPage' => $perPage,
        'debug' => [
            'query' => $query,
            'params' => $params,
            'tab' => $tab,
            'sort' => $sort
        ]
    ];
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);

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