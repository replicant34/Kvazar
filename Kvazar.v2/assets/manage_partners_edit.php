<?php
session_start();
require_once '../config/db_connect.php';
require_once 'manage_partners_log_action.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['type']) || !isset($data['id'])) {
        throw new Exception('Missing required fields');
    }

    // Determine table based on partner type
    switch ($data['type']) {
        case 'client':
            $table = 'Clients';
            $idColumn = 'Client_id';
            break;
        case 'courier':
            $table = 'Couriers';
            $idColumn = 'Courier_id';
            break;
        case 'agent':
            $table = 'Agents';
            $idColumn = 'Agent_id';
            break;
        default:
            throw new Exception('Invalid partner type');
    }

    // Build update query
    $updateFields = [
        'Company_type',
        'Full_Company_name',
        'Short_Company_name',
        'Status',
        'INN',
        'KPP',
        'OGRN',
        'Physical_address',
        'Legal_address',
        'Bank_name',
        'BIK',
        'Settlement_account',
        'Correspondent_account',
        'Contact_person',
        'Contact_person_position',
        'Contact_person_phone',
        'Contact_person_email',
        'Head_position',
        'Head_name',
        'updated_at'
    ];

    $setClause = implode(', ', array_map(fn($field) => 
        $field . ' = ?', $updateFields));

    $query = "UPDATE {$table} SET {$setClause} WHERE {$idColumn} = ?";

    // Prepare values
    $values = array_map(fn($field) => 
        $data[$field] ?? null, $updateFields);
    
    // Add current timestamp for updated_at
    $values[array_search('updated_at', $updateFields)] = date('Y-m-d H:i:s');
    
    // Add ID for WHERE clause
    $values[] = $data['id'];

    // Execute update
    $stmt = $pdo->prepare($query);
    $success = $stmt->execute($values);

    if ($success) {
        // Log the action
        $logDetails = json_encode([
            'action' => 'edit_partner',
            'partner_type' => $data['type'],
            'partner_id' => $data['id'],
            'partner_name' => $data['Full_Company_name']
        ]);
        
        logAction($pdo, $_SESSION['user_id'], 'Изменение данных партнера', $logDetails);
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update partner');
    }

} catch (Exception $e) {
    error_log("Error editing partner: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 