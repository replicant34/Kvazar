<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $fullName = $_POST['full_name'];
    $clientId = !empty($_POST['client_id']) ? $_POST['client_id'] : null;  // Handle empty value
    $position = $_POST['position'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $login = $_POST['login'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("UPDATE Users SET 
            Full_name = ?, 
            Client_id = ?, 
            Position = ?, 
            Phone = ?, 
            Email = ?, 
            Login = ?, 
            Role = ? 
            WHERE User_id = ?");
            
        $stmt->execute([
            $fullName, 
            $clientId, 
            $position, 
            $phone, 
            $email, 
            $login, 
            $role, 
            $userId
        ]);

        // Log the action
        $stmt = $pdo->prepare("INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) VALUES (?, 'Изменение пользователя', 'Users', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], "Пользователь обновлен: $fullName", $_SERVER['REMOTE_ADDR']]);

        echo json_encode(['success' => 'Пользователь успешно обновлен']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?> 