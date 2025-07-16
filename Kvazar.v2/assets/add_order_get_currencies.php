<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, currency_code, currency_name, currency_symbol FROM list_currency ORDER BY id");
    $currencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'currencies' => $currencies]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} 