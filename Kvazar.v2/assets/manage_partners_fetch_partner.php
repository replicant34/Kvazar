<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    if (!isset($_GET['id']) || !isset($_GET['type'])) {
        throw new Exception('Missing required parameters');
    }

    switch ($_GET['type']) {
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
            throw new Exception('Invalid partner type');
    }

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ?");
    $stmt->execute([$_GET['id']]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($partner) {
        echo json_encode(['success' => true] + $partner);
    } else {
        throw new Exception('Partner not found');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 