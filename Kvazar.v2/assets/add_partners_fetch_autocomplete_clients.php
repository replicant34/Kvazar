<?php
require_once '../config/db_connect.php';

$term = $_GET['term'];
$column = $_GET['column'];

$validColumns = ['bank_name', 'bik', 'contact_person_position', 'head_position'];

if (in_array($column, $validColumns)) {
    $stmt = $pdo->prepare("SELECT DISTINCT $column FROM Clients WHERE $column LIKE ? LIMIT 10");
    $stmt->execute(["%$term%"]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($results);
}
?> 