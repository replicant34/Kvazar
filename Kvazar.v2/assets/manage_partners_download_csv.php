<?php
session_start();
require_once '../config/db_connect.php';
require_once 'manage_partners_log_action.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

try {
    $tab = $_GET['tab'] ?? 'client';
    
    // Determine table and joins based on tab
    switch ($tab) {
        case 'client':
            $mainTable = 'Clients';
            $idColumn = 'Client_id';
            $contractTable = 'Client_contracts';
            $extraJoins = 'LEFT JOIN Orders o ON c.Client_id = o.Client_id';
            $extraColumns = ", GROUP_CONCAT(DISTINCT o.Order_id) as order_ids";
            break;
        case 'courier':
            $mainTable = 'Couriers';
            $idColumn = 'Courier_id';
            $contractTable = 'Courier_contracts';
            $extraJoins = '
                LEFT JOIN Drivers d ON c.Courier_id = d.Courier_id
                LEFT JOIN Vehicles v ON c.Courier_id = v.Courier_id';
            $extraColumns = ", 
                GROUP_CONCAT(DISTINCT CONCAT(d.Name, ' (', d.Phone, ')')) as drivers,
                GROUP_CONCAT(DISTINCT CONCAT(v.Brand, ' (', v.Plate_number, ')')) as vehicles";
            break;
        case 'agent':
            $mainTable = 'Agents';
            $idColumn = 'Agent_id';
            $contractTable = 'Agent_contracts';
            $extraJoins = '';
            $extraColumns = "";
            break;
        default:
            throw new Exception('Invalid tab type');
    }

    // Build query
    $query = "
        SELECT 
            c.Company_type as 'Company Type',
            c.Full_Company_name as 'Full Name',
            c.Short_Company_name as 'Short Name',
            ps.Status_name as 'Status',
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    con.Contract_number, ' (', 
                    con.Contract_date, ', ',
                    ct.Type_name, ', ',
                    con.Contract_status, ')'
                )
            ) as 'Contracts',
            c.INN as 'INN',
            c.KPP as 'KPP',
            c.OGRN as 'OGRN',
            c.Physical_address as 'Physical Address',
            c.Legal_address as 'Legal Address',
            c.Bank_name as 'Bank Name',
            c.BIK as 'BIK',
            c.Settlement_account as 'Settlement Account',
            c.Correspondent_account as 'Correspondent Account',
            c.Contact_person as 'Contact Person',
            c.Contact_person_position as 'Contact Position',
            c.Contact_person_phone as 'Contact Phone',
            c.Contact_person_email as 'Contact Email',
            c.Head_position as 'Head Position',
            c.Head_name as 'Head Name',
            u.Full_name as 'Created By',
            c.created_at as 'Created At',
            c.updated_at as 'Updated At'
            {$extraColumns}
        FROM {$mainTable} c
        LEFT JOIN list_partners_status ps ON c.Status = ps.Status_id
        LEFT JOIN Users u ON c.Created_by = u.User_id
        LEFT JOIN {$contractTable} con ON c.{$idColumn} = con.{$idColumn}
        LEFT JOIN list_contract_type ct ON con.Contract_type = ct.Type_id
        {$extraJoins}
        WHERE 1=1
    ";

    // Add filters
    $params = [];
    if (!empty($_GET['search'])) {
        $query .= " AND (
            c.Full_Company_name LIKE ? OR 
            c.Short_Company_name LIKE ? OR 
            c.INN LIKE ? OR 
            c.KPP LIKE ?
        )";
        $searchParam = "%" . $_GET['search'] . "%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    if (!empty($_GET['status'])) {
        $query .= " AND c.Status = ?";
        $params[] = $_GET['status'];
    }

    if (!empty($_GET['bank'])) {
        $query .= " AND c.Bank_name = ?";
        $params[] = $_GET['bank'];
    }

    if (!empty($_GET['dateFrom'])) {
        $query .= " AND DATE(c.created_at) >= ?";
        $params[] = $_GET['dateFrom'];
    }

    if (!empty($_GET['dateTo'])) {
        $query .= " AND DATE(c.created_at) <= ?";
        $params[] = $_GET['dateTo'];
    }

    $query .= " GROUP BY c.{$idColumn}";

    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the download action
    $logDetails = json_encode([
        'action' => 'download_partners',
        'entity_type' => $tab,
        'filters' => [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
            'bank' => $_GET['bank'] ?? null,
            'dateFrom' => $_GET['dateFrom'] ?? null,
            'dateTo' => $_GET['dateTo'] ?? null
        ],
        'record_count' => count($partners)
    ]);
    
    logAction($pdo, $_SESSION['user_id'], 'Скачивание данных партнеров', $logDetails);

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $tab . '_partners_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Add headers
    if (!empty($partners)) {
        fputcsv($output, array_keys($partners[0]));
    }

    // Add data
    foreach ($partners as $partner) {
        fputcsv($output, $partner);
    }

    fclose($output);

} catch (Exception $e) {
    error_log("Error downloading partners: " . $e->getMessage());
    die('Error downloading partners');
}
?> 