<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$partnerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partnerType = isset($_GET['type']) ? $_GET['type'] : '';

if (!$partnerId || !$partnerType) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing partner ID or type']);
    exit();
}

try {
    $table = '';
    $idColumn = '';
    
    switch($partnerType) {
        case 'client':
            $table = 'Clients';
            $idColumn = 'Client_id';
            break;
        case 'courier':
            $table = 'Couriers';
            $idColumn = 'Courier_id';
            break;
        case 'agent':
            $table = 'Agents';
            $idColumn = 'Agent_id';
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid partner type']);
            exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $idColumn = ?");
    $stmt->execute([$partnerId]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        http_response_code(404);
        echo json_encode(['error' => 'Partner not found']);
        exit();
    }
    
    // Format the data for display
    $formattedPartner = [
        'id' => $partner[$idColumn],
        'type' => $partnerType,
        'company_info' => [
            'company_type' => $partner['Company_type'],
            'full_name' => $partner['Full_Company_name'],
            'short_name' => $partner['Short_Company_name'],
            'inn' => $partner['INN'],
            'kpp' => $partner['KPP'],
            'ogrn' => $partner['OGRN']
        ],
        'addresses' => [
            'physical' => $partner['Physical_address'],
            'legal' => $partner['Legal_address']
        ],
        'bank_info' => [
            'bank_name' => $partner['Bank_name'],
            'bik' => $partner['BIK'],
            'settlement_account' => $partner['Settlement_account'],
            'correspondent_account' => $partner['Correspondent_account']
        ],
        'contact_info' => [
            'contact_person' => $partner['Contact_person'],
            'contact_position' => $partner['Contact_person_position'],
            'contact_phone' => $partner['Contact_person_phone'],
            'contact_email' => $partner['Contact_person_email']
        ],
        'head_info' => [
            'head_position' => $partner['Head_position'],
            'head_name' => $partner['Head_name']
        ],
        'dates' => [
            'created' => $partner['created_at'],
            'updated' => $partner['updated_at']
        ]
    ];
    
    echo json_encode(['success' => true, 'data' => $formattedPartner]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}
?> 