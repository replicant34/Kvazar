<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT id, service_name, price 
        FROM list_extra_service 
        ORDER BY service_name
    ");
    
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($services) {
        echo json_encode(['success' => true, 'services' => $services]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No services found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} 