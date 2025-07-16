<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, packaging_name FROM list_packaging_type ORDER BY id");
    $packagingTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'packaging_types' => $packagingTypes]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} 