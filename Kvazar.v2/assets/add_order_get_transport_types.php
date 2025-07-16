<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT Type_id, Type_name, Max_load_ton, Max_load_kg, Max_capacity_m3, Refrigerator, Min_temp, Max_temp, transport_rate, hours, overwork_rate
        FROM list_transport_type 
        ORDER BY Type_name ASC
    ");
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'types' => $types
    ]);
} catch (PDOException $e) {
    error_log("Error fetching transport types: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?> 