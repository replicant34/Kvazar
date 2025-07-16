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
    
    if (!isset($data['contract_id']) || !isset($data['entity_type']) || !isset($data['status'])) {
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

    // Verify that the status exists
    $statusCheckStmt = $pdo->prepare("SELECT Status_name FROM list_contract_status WHERE Status_name = ?");
    $statusCheckStmt->execute([$data['status']]);
    if (!$statusCheckStmt->fetch()) {
        throw new Exception('Invalid status');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Get current status for logging
    $currentStatusStmt = $pdo->prepare("
        SELECT Contract_number, Contract_status 
        FROM {$table} 
        WHERE Contract_id = ?
    ");
    $currentStatusStmt->execute([$data['contract_id']]);
    $currentData = $currentStatusStmt->fetch();

    // Update the contract status
    $stmt = $pdo->prepare("
        UPDATE {$table} 
        SET Contract_status = ?,
            Updated_at = CURRENT_TIMESTAMP
        WHERE Contract_id = ?
    ");

    $success = $stmt->execute([$data['status'], $data['contract_id']]);

    if ($success) {
        try {
            // Log the status change
            $logDetails = json_encode([
                'action' => 'update_status',
                'contract_number' => $currentData['Contract_number'],
                'contract_id' => $data['contract_id'],
                'old_status' => $currentData['Contract_status'],
                'new_status' => $data['status'],
                'entity_type' => $data['entity_type']
            ]);
            
            error_log("Attempting to log status change");
            $logSuccess = logAction($pdo, $_SESSION['user_id'], 'Изменение статуса контракта', $logDetails);
            
            if (!$logSuccess) {
                throw new Exception('Failed to log action');
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        throw new Exception('Failed to update status');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error updating contract status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 