<?php
session_start();

require_once '../config/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Pagination setup
$limit = 10; // Number of entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Fetch clients from the database
    $stmt = $pdo->prepare("SELECT *, DATE_FORMAT(Created_at, '%Y-%m-%d') as Formatted_date FROM Users LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of clients for pagination
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM Users");
    $totalUsers = $totalStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/manage_users.css">
    <link rel="stylesheet" href="../css/partner_info_modal.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>
        <div id="content">
            <?php 
            $page_title = 'Управление пользователями';
            include '../elements/admin_navbar.php'; 
            ?>

            <div class="mc-content-wrapper">
                <div class="page-header">
                    <h1>Управление пользователями</h1>
                </div>
                
                <?php include '../assets/manage_users_table.php'; ?>
            </div>
        </div>
    </div>

    <?php 
    include '../assets/manage_users_edit.php';
    include '../assets/manage_users_delete.php';
    include '../assets/manage_users_password.php';
    include '../assets/partner_info_modal.php';
    ?>

    <script src="../js/manage_users.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/partner_info.js"></script>
</body>
</html> 