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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = intval($input['order_id'] ?? 0);
    $newStatusId = intval($input['new_status_id'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    $userId = $_SESSION['user_id'];
    
    if (!$orderId || !$newStatusId) {
        echo json_encode(['success' => false, 'error' => 'Order ID and Status ID are required']);
        exit();
    }
    
    // Get current order status
    $currentStatusStmt = $pdo->prepare("
        SELECT 
            o.Status as current_status_id,
            los.Status_name as current_status_name,
            o.display_order_number,
            o.Client_id,
            o.Courier_id
        FROM Orders o
        LEFT JOIN list_order_status los ON o.Status = los.Status_id
        WHERE o.Order_id = ?
    ");
    
    $currentStatusStmt->execute([$orderId]);
    $orderInfo = $currentStatusStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderInfo) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Check if status is actually changing
    if ($orderInfo['current_status_id'] == $newStatusId) {
        echo json_encode(['success' => false, 'error' => 'Order already has this status']);
        exit();
    }
    
    // Get new status info
    $newStatusStmt = $pdo->prepare("SELECT Status_name FROM list_order_status WHERE Status_id = ?");
    $newStatusStmt->execute([$newStatusId]);
    $newStatusInfo = $newStatusStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$newStatusInfo) {
        echo json_encode(['success' => false, 'error' => 'Invalid status ID']);
        exit();
    }
    
    // Validate status transition (business rules)
    $validationResult = validateStatusTransition($orderInfo['current_status_id'], $newStatusId, $orderInfo);
    if (!$validationResult['valid']) {
        echo json_encode(['success' => false, 'error' => $validationResult['error']]);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update order status
    $updateStmt = $pdo->prepare("
        UPDATE Orders 
        SET Status = ?, Updated_at = NOW() 
        WHERE Order_id = ?
    ");
    $updateStmt->execute([$newStatusId, $orderId]);
    
    // Create status history record
    $historyStmt = $pdo->prepare("
        INSERT INTO Order_status_history 
        (Order_id, Previous_status, New_status, Changed_by, Change_reason, Change_date) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $historyStmt->execute([
        $orderId,
        $orderInfo['current_status_id'],
        $newStatusId,
        $userId,
        $reason
    ]);
    
    // Execute status-specific actions
    executeStatusActions($orderId, $newStatusId, $orderInfo, $pdo);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'previous_status' => $orderInfo['current_status_name'],
        'new_status' => $newStatusInfo['Status_name']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error updating order status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

function validateStatusTransition($currentStatusId, $newStatusId, $orderInfo) {
    // Allow moving to any future status, but not backwards
    
    // Check if moving to a future status
    if ($newStatusId <= $currentStatusId) {
        return [
            'valid' => false,
            'error' => 'Cannot move backwards to a previous status or the same status.'
        ];
    }
    
    // Additional business rules for specific statuses
    if ($newStatusId == 3 && !$orderInfo['Courier_id']) { // Moving to "In Progress"
        return [
            'valid' => false,
            'error' => 'Cannot move to "In Progress" without assigned courier'
        ];
    }
    
    // Prevent progression beyond final statuses
    if ($currentStatusId >= 4) { // "Completed" or higher are final statuses
        return [
            'valid' => false,
            'error' => 'Cannot change status from a final state'
        ];
    }
    
    return ['valid' => true];
}

function executeStatusActions($orderId, $newStatusId, $orderInfo, $pdo) {
    switch ($newStatusId) {
        case 2: // Confirmed
            // Could trigger confirmation notifications
            break;
            
        case 3: // In Progress
            // Could trigger notifications to courier
            break;
            
        case 4: // Completed
            // Could trigger invoice generation, notifications
            break;
            
        case 5: // Cancelled
            // Could trigger cleanup, notifications
            break;
            
        case 6: // On Hold
            // Could trigger hold notifications
            break;
    }
}
?> 