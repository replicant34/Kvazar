<?php
session_start();
require_once '../config/db_connect.php';
require_once 'manage_partners_log_action.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

try {
    $tab = $_GET['tab'] ?? 'client';
    
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

    // Build query with all necessary joins
    $query = "
        SELECT 
            c.Contract_number as 'Contract Number',
            e.Full_company_name as 'Company Name',
            t.Type_name as 'Contract Type',
            DATE_FORMAT(c.Contract_date, '%Y-%m-%d') as 'Contract Date',
            c.Contract_status as 'Status',
            DATE_FORMAT(c.Created_at, '%Y-%m-%d %H:%i:%s') as 'Created At',
            u.Full_name as 'Created By'
        FROM {$table} c
        LEFT JOIN {$entityTable} e ON c.{$entityId} = e.{$entityId}
        LEFT JOIN Users u ON c.Created_by = u.User_id
        LEFT JOIN list_contract_type t ON c.Contract_type = t.Type_id
        ORDER BY c.Created_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the download action
    $logDetails = json_encode([
        'action' => 'download_contracts',
        'entity_type' => $tab,
        'filters' => [
            'search' => $_GET['search'] ?? null,
            'type' => $_GET['type'] ?? null,
            'status' => $_GET['status'] ?? null,
            'dateFrom' => $_GET['dateFrom'] ?? null,
            'dateTo' => $_GET['dateTo'] ?? null
        ],
        'record_count' => count($contracts)
    ]);
    
    logAction($pdo, $_SESSION['user_id'], 'Скачивание контрактов', $logDetails);

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="contracts_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Add headers
    if (!empty($contracts)) {
        fputcsv($output, array_keys($contracts[0]));
    }

    // Add data
    foreach ($contracts as $contract) {
        fputcsv($output, $contract);
    }

    fclose($output);

} catch (Exception $e) {
    error_log("Error downloading contracts: " . $e->getMessage());
    die('Error downloading contracts');
}
?> 