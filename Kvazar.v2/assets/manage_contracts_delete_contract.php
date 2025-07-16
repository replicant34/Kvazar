<?php
session_start();
require_once '../config/db_connect.php';
require_once 'manage_partners_log_action.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['contract_id']) || !isset($data['entity_type'])) {
        throw new Exception('Missing required fields');
    }

    // Determine table based on entity type
    switch ($data['entity_type']) {
        case 'client':
            $table = 'Client_contracts';
            break;
        case 'courier':
            $table = 'Courier_contracts';
            break;
        case 'agent':
            $table = 'Agent_contracts';
            break;
        default:
            throw new Exception('Invalid entity type');
    }

    // Start transaction
    $pdo->beginTransaction();

    // First, check if the contract exists and can be deleted
    $checkStmt = $pdo->prepare("SELECT Contract_id FROM {$table} WHERE Contract_id = ?");
    $checkStmt->execute([$data['contract_id']]);
    
    if (!$checkStmt->fetch()) {
        throw new Exception('Contract not found');
    }

    // Get contract details for logging
    $contractStmt = $pdo->prepare("SELECT Contract_number FROM {$table} WHERE Contract_id = ?");
    $contractStmt->execute([$data['contract_id']]);
    $contractNumber = $contractStmt->fetchColumn();

    // Delete the contract
    $deleteStmt = $pdo->prepare("DELETE FROM {$table} WHERE Contract_id = ?");
    $success = $deleteStmt->execute([$data['contract_id']]);

    if (!$success) {
        throw new Exception('Failed to delete contract');
    }

    // Log the action
    $logDetails = json_encode([
        'action' => 'delete_contract',
        'contract_number' => $contractNumber,
        'contract_id' => $data['contract_id'],
        'entity_type' => $data['entity_type']
    ]);
    
    logAction($pdo, $_SESSION['user_id'], 'Удаление контракта', $logDetails);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction if error occurs
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error deleting contract: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 