<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['number'])) {
    $number = $_GET['number'];
    
    try {
        // Check all contract tables for the number
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM Client_contracts WHERE Contract_number = ?) +
                (SELECT COUNT(*) FROM Courier_contracts WHERE Contract_number = ?) +
                (SELECT COUNT(*) FROM Agent_contracts WHERE Contract_number = ?) as count
        ");
        $stmt->execute([$number, $number, $number]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'isUnique' => $result['count'] == 0
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'No number provided']);
}
?> 