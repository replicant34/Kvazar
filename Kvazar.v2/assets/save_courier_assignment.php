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
    $courierId = intval($input['courier_id'] ?? 0);
    $driverIds = $input['driver_ids'] ?? [];
    $vehicleIds = $input['vehicle_ids'] ?? [];
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
    if (!$courierId) {
        echo json_encode(['success' => false, 'error' => 'Courier ID is required']);
        exit();
    }
    
    // Validate that drivers and vehicles belong to the selected courier
    if (!empty($driverIds)) {
        $driverCheckStmt = $pdo->prepare("
            SELECT COUNT(*) FROM Drivers 
            WHERE Driver_id IN (" . str_repeat('?,', count($driverIds) - 1) . "?) 
            AND Courier_id = ?
        ");
        $driverCheckStmt->execute(array_merge($driverIds, [$courierId]));
        $validDrivers = $driverCheckStmt->fetchColumn();
        
        if ($validDrivers != count($driverIds)) {
            echo json_encode(['success' => false, 'error' => 'Some drivers do not belong to the selected courier']);
            exit();
        }
    }
    
    if (!empty($vehicleIds)) {
        $vehicleCheckStmt = $pdo->prepare("
            SELECT COUNT(*) FROM Vehicles 
            WHERE Vehicle_id IN (" . str_repeat('?,', count($vehicleIds) - 1) . "?) 
            AND Courier_id = ?
        ");
        $vehicleCheckStmt->execute(array_merge($vehicleIds, [$courierId]));
        $validVehicles = $vehicleCheckStmt->fetchColumn();
        
        if ($validVehicles != count($vehicleIds)) {
            echo json_encode(['success' => false, 'error' => 'Some vehicles do not belong to the selected courier']);
            exit();
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update order courier
    $updateOrderStmt = $pdo->prepare("
        UPDATE Orders 
        SET Courier_id = ?, Updated_at = NOW() 
        WHERE Order_id = ?
    ");
    $updateOrderStmt->execute([$courierId, $orderId]);
    
    // Clear existing driver assignments
    $clearDriversStmt = $pdo->prepare("DELETE FROM Drivers_list WHERE Order_id = ?");
    $clearDriversStmt->execute([$orderId]);
    
    // Clear existing vehicle assignments  
    $clearVehiclesStmt = $pdo->prepare("DELETE FROM Vehicle_list WHERE Order_id = ?");
    $clearVehiclesStmt->execute([$orderId]);
    
    // Assign new drivers
    if (!empty($driverIds)) {
        $assignDriverStmt = $pdo->prepare("
            INSERT INTO Drivers_list (Order_id, Driver_id) VALUES (?, ?)
        ");
        
        foreach ($driverIds as $driverId) {
            $assignDriverStmt->execute([$orderId, intval($driverId)]);
        }
    }
    
    // Assign new vehicles
    if (!empty($vehicleIds)) {
        $assignVehicleStmt = $pdo->prepare("
            INSERT INTO Vehicle_list (Order_id, Vehicle_id) VALUES (?, ?)
        ");
        
        foreach ($vehicleIds as $vehicleId) {
            $assignVehicleStmt->execute([$orderId, intval($vehicleId)]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Courier assignment updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error saving courier assignment: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 