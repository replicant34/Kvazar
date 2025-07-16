<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['contract_number'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Contract number is required']);
    exit;
}

try {
    // Add error logging
    error_log("Fetching rate for contract: " . $_GET['contract_number']);

    $stmt = $pdo->prepare("
        SELECT rate 
        FROM list_rates
        WHERE contract_number = ?
    ");
    
    $stmt->execute([$_GET['contract_number']]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log the result
    error_log("Rate query result: " . print_r($rate, true));
    
    if ($rate) {
        echo json_encode(['success' => true, 'rate' => $rate]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Rate not found']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} 