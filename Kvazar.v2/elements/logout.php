<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log the logout event if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once '../config/db_connect.php';
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    try {
        // Log the logout in auth_logs table
        $stmt = $pdo->prepare("INSERT INTO auth_logs (user_id, status, ip_address, user_agent) VALUES (?, 'Выход', ?, ?)");
        $stmt->execute([$user_id, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        // Just continue with logout even if logging fails
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../index.php");
exit;
?> 