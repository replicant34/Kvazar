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
    $driverId = intval($input['driver_id'] ?? 0);
    $vehicleId = intval($input['vehicle_id'] ?? 0);
    $userId = $_SESSION['user_id'];
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
    if (!$courierId) {
        echo json_encode(['success' => false, 'error' => 'Courier ID is required']);
        exit();
    }
    
    // Validate that the order exists and doesn't already have a courier
    $orderStmt = $pdo->prepare("
        SELECT Order_id, display_order_number, Courier_id 
        FROM Orders 
        WHERE Order_id = ?
    ");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    if (!empty($order['Courier_id'])) {
        echo json_encode(['success' => false, 'error' => 'Order already has a courier assigned']);
        exit();
    }
    
    // Validate that driver and vehicle belong to the selected courier if provided
    if ($driverId) {
        $driverCheckStmt = $pdo->prepare("
            SELECT Driver_id FROM Drivers 
            WHERE Driver_id = ? AND Courier_id = ?
        ");
        $driverCheckStmt->execute([$driverId, $courierId]);
        if (!$driverCheckStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Driver does not belong to the selected courier']);
            exit();
        }
    }
    
    if ($vehicleId) {
        $vehicleCheckStmt = $pdo->prepare("
            SELECT Vehicle_id FROM Vehicles 
            WHERE Vehicle_id = ? AND Courier_id = ?
        ");
        $vehicleCheckStmt->execute([$vehicleId, $courierId]);
        if (!$vehicleCheckStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Vehicle does not belong to the selected courier']);
            exit();
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update order with courier
    $updateOrderStmt = $pdo->prepare("
        UPDATE Orders 
        SET Courier_id = ?, Updated_at = NOW() 
        WHERE Order_id = ?
    ");
    $updateOrderStmt->execute([$courierId, $orderId]);
    
    // Clear any existing driver assignments for this order
    $clearDriversStmt = $pdo->prepare("DELETE FROM Drivers_list WHERE Order_id = ?");
    $clearDriversStmt->execute([$orderId]);
    
    // Clear any existing vehicle assignments for this order
    $clearVehiclesStmt = $pdo->prepare("DELETE FROM Vehicle_list WHERE Order_id = ?");
    $clearVehiclesStmt->execute([$orderId]);
    
    // Assign driver if provided
    if ($driverId) {
        $assignDriverStmt = $pdo->prepare("
            INSERT INTO Drivers_list (Order_id, Driver_id) VALUES (?, ?)
        ");
        $assignDriverStmt->execute([$orderId, $driverId]);
    }
    
    // Assign vehicle if provided
    if ($vehicleId) {
        $assignVehicleStmt = $pdo->prepare("
            INSERT INTO Vehicle_list (Order_id, Vehicle_id) VALUES (?, ?)
        ");
        $assignVehicleStmt->execute([$orderId, $vehicleId]);
    }
    
    // Get courier name for response
    $courierStmt = $pdo->prepare("SELECT Full_Company_name FROM Couriers WHERE Courier_id = ?");
    $courierStmt->execute([$courierId]);
    $courierName = $courierStmt->fetchColumn();
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Courier assigned successfully',
        'courier_name' => $courierName,
        'order_number' => $order['display_order_number']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error assigning courier to order: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 