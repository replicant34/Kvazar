<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['client_id'])) {
    $clientId = $_GET['client_id'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT Contract_number, Contract_date
            FROM Client_contracts
            WHERE Client_id = ?
            AND Contract_status != 'Terminated'
            ORDER BY Contract_date DESC
        ");
        $stmt->execute([$clientId]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'contracts' => $contracts
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'No client ID provided']);
}
?> 