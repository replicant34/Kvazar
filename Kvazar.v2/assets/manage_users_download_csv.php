<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Log the CSV download action
try {
    $logStmt = $pdo->prepare("INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) VALUES (?, 'Скачивание CSV', 'Users', 'Скачан CSV файл со списком пользователей', ?)");
    $logStmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
} catch (PDOException $e) {
    // Continue with download even if logging fails
    error_log('Failed to log CSV download: ' . $e->getMessage());
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users.csv"');

$output = fopen('php://output', 'w');

// Update the CSV headers to include all columns
fputcsv($output, [
    'ID', 'Имя', 'ID партнера', 'Должность', 'Телефон', 'Почта', 'Логин', 'Роль', 'Дата создания'
]);

// Fetch all columns from the Users table
$stmt = $pdo->query("SELECT User_id, Full_name, Client_id, Position, Phone, Email, Login, Role, Created_at FROM Users");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
?> 