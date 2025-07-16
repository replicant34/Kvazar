<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT Contractors_id, Full_Company_name 
        FROM Contractors 
        WHERE Contractors_id = 1
        LIMIT 1
    ");
    $stmt->execute();
    $contractor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contractor) {
        echo json_encode([
            'success' => true,
            'contractor' => $contractor
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not found'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error fetching contractor: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?> 