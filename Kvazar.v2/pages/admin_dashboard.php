<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>

        <div id="content">
            <?php 
            $page_title = 'Admin Dashboard';
            include '../elements/admin_navbar.php'; 
            ?>

            <div class="content-wrapper">
                <h2>Welcome to Admin Dashboard</h2>
                <p>Select an option from the sidebar to get started.</p>
            </div>
        </div>
    </div>
    <script src="../js/sidebar.js"></script>
</body>
</html> 