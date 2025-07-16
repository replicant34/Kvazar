<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get all existing contract numbers from all contract tables
    $sql = "
        SELECT Contract_number FROM Client_contracts
        UNION
        SELECT Contract_number FROM Courier_contracts
        UNION
        SELECT Contract_number FROM Agent_contracts
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $existingNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Generate a new 6-digit number
    do {
        $newNumber = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    } while (in_array($newNumber, $existingNumbers));
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'number' => $newNumber
    ]);
} catch (PDOException $e) {
    error_log("Error generating contract number: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?> 