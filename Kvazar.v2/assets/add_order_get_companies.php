<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = $_GET['term'] ?? '';
    $clientId = $_GET['client_id'] ?? '';

    if (empty($searchTerm) || empty($clientId)) {
        throw new Exception('Missing required parameters');
    }

    $companies = [];
    
    // 1. Get company name from Clients table
    $stmt = $pdo->prepare("
        SELECT Full_company_name as name, Phisical_address as address 
        FROM Clients 
        WHERE Client_id = ? 
        AND Full_company_name LIKE ?
    ");
    $stmt->execute([$clientId, "%$searchTerm%"]);
    $clientCompany = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($clientCompany) {
        $companies[] = $clientCompany;
    }

    // 2. Get unique company names from Points table for this client
    $stmt = $pdo->prepare("
        SELECT DISTINCT Company_name as name, Address_Loading as address
        FROM Points p
        JOIN Orders o ON p.Order_id = o.Order_id
        WHERE o.Client_id = ?
        AND Company_name LIKE ?
    ");
    $stmt->execute([$clientId, "%$searchTerm%"]);
    $pointCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge results, removing duplicates
    foreach ($pointCompanies as $company) {
        if (!in_array($company['name'], array_column($companies, 'name'))) {
            $companies[] = $company;
        }
    }

    echo json_encode([
        'success' => true,
        'companies' => $companies
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 