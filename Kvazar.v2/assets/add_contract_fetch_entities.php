<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    
    try {
        switch ($type) {
            case 'client':
                $sql = "SELECT Client_id as id, Full_company_name as name FROM Clients ORDER BY Full_company_name";
                break;
                
            case 'courier':
                $sql = "SELECT Courier_id as id, Full_company_name as name FROM Couriers ORDER BY Full_company_name";
                break;
                
            case 'agent':
                $sql = "SELECT Agent_id as id, Full_company_name as name FROM Agents ORDER BY Full_company_name";
                break;
                
            default:
                throw new Exception('Invalid entity type');
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'entities' => $entities
        ]);
        
    } catch (Exception $e) {
        error_log("Error fetching entities: " . $e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'No type provided']);
}
?> 