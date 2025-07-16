<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

try {
    $clientId = $_GET['client_id'] ?? null;
    $searchTerm = $_GET['search'] ?? '';
    $type = $_GET['type'] ?? 'companies'; // 'companies' or 'addresses'

    if (!$clientId) {
        throw new Exception('Client ID is required');
    }

    if ($type === 'companies') {
        // Fetch unique company names from previous orders for this client
        $sql = "
            SELECT DISTINCT p.Company_name as name, COUNT(*) as usage_count
            FROM Points p
            INNER JOIN Orders o ON p.Order_id = o.Order_id
            WHERE o.Client_id = ? 
            AND p.Company_name IS NOT NULL 
            AND p.Company_name != ''
        ";
        
        $params = [$clientId];
        
        if (!empty($searchTerm)) {
            $sql .= " AND p.Company_name LIKE ?";
            $params[] = '%' . $searchTerm . '%';
        }
        
        $sql .= " 
            GROUP BY p.Company_name 
            ORDER BY usage_count DESC, p.Company_name ASC 
            LIMIT 10
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'companies' => array_map(function($row) {
                return [
                    'name' => $row['name'],
                    'usage_count' => $row['usage_count']
                ];
            }, $results)
        ]);

    } else if ($type === 'addresses') {
        $companyName = $_GET['company_name'] ?? '';
        
        if (!empty($companyName)) {
            // Fetch addresses for specific company
            $sql = "
                SELECT DISTINCT p.Address_Loading as address, COUNT(*) as usage_count
                FROM Points p
                INNER JOIN Orders o ON p.Order_id = o.Order_id
                WHERE o.Client_id = ? 
                AND p.Company_name = ?
                AND p.Address_Loading IS NOT NULL 
                AND p.Address_Loading != ''
            ";
            
            $params = [$clientId, $companyName];
            
            if (!empty($searchTerm)) {
                $sql .= " AND p.Address_Loading LIKE ?";
                $params[] = '%' . $searchTerm . '%';
            }
            
            $sql .= " 
                GROUP BY p.Address_Loading 
                ORDER BY usage_count DESC, p.Address_Loading ASC 
                LIMIT 10
            ";
        } else {
            // Fetch all unique addresses for this client
            $sql = "
                SELECT DISTINCT p.Address_Loading as address, p.Company_name, COUNT(*) as usage_count
                FROM Points p
                INNER JOIN Orders o ON p.Order_id = o.Order_id
                WHERE o.Client_id = ? 
                AND p.Address_Loading IS NOT NULL 
                AND p.Address_Loading != ''
            ";
            
            $params = [$clientId];
            
            if (!empty($searchTerm)) {
                $sql .= " AND p.Address_Loading LIKE ?";
                $params[] = '%' . $searchTerm . '%';
            }
            
            $sql .= " 
                GROUP BY p.Address_Loading, p.Company_name 
                ORDER BY usage_count DESC, p.Address_Loading ASC 
                LIMIT 10
            ";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'addresses' => array_map(function($row) {
                return [
                    'address' => $row['address'],
                    'company_name' => $row['Company_name'] ?? '',
                    'usage_count' => $row['usage_count']
                ];
            }, $results)
        ]);

    } else {
        throw new Exception('Invalid type parameter');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 