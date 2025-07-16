<?php
require_once '../config/db_connect.php';

// Set proper content type for JSON responses
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    if (isset($_GET['role'])) {
        $role = $_GET['role'];
        
        // Whitelist table names to prevent SQL injection
        $validTables = [
            'client' => 'Clients',
            'courier' => 'Couriers',
            'agent' => 'Agents'
        ];

        $validColumn = [
            'client' => 'Client_id',
            'courier' => 'Courier_id',
            'agent' => 'Agent_id'
        ];

        if (array_key_exists($role, $validTables) && array_key_exists($role, $validColumn)) {
            $table = $validTables[$role];
            $column = $validColumn[$role];
            
            try {
                $stmt = $pdo->prepare("SELECT $column as id, Short_Company_name FROM {$table} ORDER BY Short_Company_name ASC");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($results) > 0) {
                    echo json_encode($results);
                } else {
                    echo json_encode([]);
                }
            } catch (PDOException $e) {
                error_log('Database error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Database error occurred: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role specified']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No role specified']);
    }
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A server error occurred']);
}
?> 