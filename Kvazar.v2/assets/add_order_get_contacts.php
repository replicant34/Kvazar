<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = $_GET['term'] ?? '';
    $clientId = $_GET['client_id'] ?? '';

    if (empty($clientId)) {
        throw new Exception('Client ID is required');
    }

    $contacts = [];
    
    // Get contacts from Contact_list table with usage counts
    $stmt = $pdo->prepare("
        SELECT 
            cl.Name as name, 
            cl.Phone_number as phone,
            COUNT(*) as usage_count
        FROM Contact_list cl
        JOIN Points p ON cl.Point_id = p.Point_id
        JOIN Orders o ON p.Order_id = o.Order_id
        WHERE o.Client_id = ?
        AND (cl.Name LIKE ? OR cl.Phone_number LIKE ?)
        GROUP BY cl.Name, cl.Phone_number
        ORDER BY usage_count DESC, cl.Name
        LIMIT 10
    ");
    
    $searchPattern = "%$searchTerm%";
    $stmt->execute([$clientId, $searchPattern, $searchPattern]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'contacts' => $contacts
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 