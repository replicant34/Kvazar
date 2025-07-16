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
    $courierId = intval($_GET['courier_id'] ?? 0);
    
    if (!$courierId) {
        echo json_encode(['success' => false, 'error' => 'Courier ID is required']);
        exit();
    }
    
    // Get available drivers for this courier
    $driversStmt = $pdo->prepare("
        SELECT 
            Driver_id,
            Name as driver_name,
            Phone_number as driver_phone,
            Passport as driver_passport
        FROM Drivers 
        WHERE Courier_id = ?
        ORDER BY Name
    ");
    
    $driversStmt->execute([$courierId]);
    $drivers = $driversStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available vehicles for this courier
    $vehiclesStmt = $pdo->prepare("
        SELECT 
            v.Vehicle_id,
            v.Brand as vehicle_brand,
            v.Plate_number as vehicle_plate,
            CONCAT(v.Brand, ' - ', v.Plate_number) as vehicle_display
        FROM Vehicles v
        WHERE v.Courier_id = ?
        ORDER BY v.Brand, v.Plate_number
    ");
    
    $vehiclesStmt->execute([$courierId]);
    $vehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'drivers' => $drivers,
        'vehicles' => $vehicles
    ]);
    
} catch (Exception $e) {
    error_log("Error getting courier drivers and vehicles: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 