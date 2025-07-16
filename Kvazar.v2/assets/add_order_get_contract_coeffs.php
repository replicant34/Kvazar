<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['contract_number'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Contract number is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT coef_transport_rate, coef_overwork_rate 
        FROM list_rates
        WHERE contract_number = ?
    ");
    
    $stmt->execute([$_GET['contract_number']]);
    $coefficients = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($coefficients) {
        echo json_encode(['success' => true, 'coefficients' => $coefficients]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Coefficients not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} 