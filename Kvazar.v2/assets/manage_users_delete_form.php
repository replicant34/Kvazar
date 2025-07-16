<?php
session_start();
require_once '../config/db_connect.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['user_id'];

    try {
        // Fetch user details before deletion
        $stmt = $pdo->prepare("SELECT Full_name FROM Users WHERE User_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['error' => 'User not found']);
            exit();
        }
        
        $fullName = $user['Full_name'];

        // Delete user from the database
        $stmt = $pdo->prepare("DELETE FROM Users WHERE User_id = ?");
        $stmt->execute([$userId]);

        // Log the delete action
        $logStmt = $pdo->prepare("INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) VALUES (?, 'Удаление пользователя', 'Users', ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], "Удален пользователь: $fullName", $_SERVER['REMOTE_ADDR']]);

        echo json_encode(['success' => 'Пользователь успешно удален']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?> 