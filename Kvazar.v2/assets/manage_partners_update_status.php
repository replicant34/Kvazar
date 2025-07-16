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
    
    if (!isset($data['type']) || !isset($data['id']) || !isset($data['status'])) {
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

    // Get partner details for logging
    $stmt = $pdo->prepare("SELECT Full_Company_name FROM {$table} WHERE {$idColumn} = ?");
    $stmt->execute([$data['id']]);
    $partnerName = $stmt->fetchColumn();

    // Update status
    $stmt = $pdo->prepare("UPDATE {$table} SET Status = ? WHERE {$idColumn} = ?");
    $success = $stmt->execute([$data['status'], $data['id']]);

    if ($success) {
        // Log the action
        $logDetails = json_encode([
            'action' => 'change_partner_status',
            'partner_type' => $data['type'],
            'partner_id' => $data['id'],
            'partner_name' => $partnerName,
            'new_status' => $data['status']
        ]);
        
        logAction($pdo, $_SESSION['user_id'], 'Изменение статуса партнера', $logDetails);
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update status');
    }

} catch (Exception $e) {
    error_log("Error updating partner status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 