<?php 
session_start();
$error = '';

// Handle the login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/db_connect.php';
    
    $login = $_POST['login'];
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['Password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['User_id'];
            $_SESSION['full_name'] = $user['Full_name'];
            $_SESSION['role'] = $user['Role'];
            
            // Log successful authentication
            $stmt = $pdo->prepare("INSERT INTO auth_logs (user_id, status, ip_address, user_agent) VALUES (?, 'Успешно', ?, ?)");
            $stmt->execute([$user['User_id'], $ip_address, $user_agent]);

            // Redirect based on role
            switch($user['Role']) {
                case 'admin':
                    header('Location: pages/admin_dashboard.php');
                    break;
                case 'client':
                    header('Location: pages/client_dashboard.php');
                    break;
                case 'courier':
                    header('Location: pages/courier_dashboard.php');
                    break;
                case 'agent':
                    header('Location: pages/agent_dashboard.php');
                    break;
                case 'ceo':
                    header('Location: pages/ceo_dashboard.php');
                    break;
            }
            exit();
        } else {
            // Log failed authentication
            $stmt = $pdo->prepare("INSERT INTO auth_logs (status, ip_address, user_agent) VALUES ('Ошибка', ?, ?)");
            $stmt->execute([$ip_address, $user_agent]);

            $error = 'Неверные учетные данные';
        }
    } catch(PDOException $e) {
        $error = 'Ошибка базы данных: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Вход в систему</h1>
            <p>Пожалуйста, введите свои учетные данные</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required>
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Войти</button>
        </form>

        <div class="login-footer">
            <p>Забыли пароль? <a href="#">Нажмите здесь</a></p>
        </div>
    </div>
</body>
</html> 