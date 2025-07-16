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
    // Get all active couriers (Status = 1 is Active, Status = 2 is Pending)
    $couriersStmt = $pdo->query("
        SELECT 
            Courier_id,
            Full_Company_name,
            Contact_person,
            Contact_person_phone as Phone_number
        FROM Couriers 
        WHERE Status IN (1, 2)
        ORDER BY Full_Company_name
    ");
    
    $couriers = $couriersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'couriers' => $couriers
    ]);
    
} catch (Exception $e) {
    error_log("Error getting couriers for assignment: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 