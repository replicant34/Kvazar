<?php
session_start();
require_once '../config/db_connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Ensure this path is correct

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $position = $_POST['position'];
    $role = $_POST['role'];
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    // Handle ID for all roles using Client_id column
    $entityId = null;
    if (!isset($_POST['skip_client']) && isset($_POST['client_id']) && !empty($_POST['client_id'])) {
        // Validate that the ID exists in the appropriate table
        try {
            $table = '';
            switch($role) {
                case 'client':
                    $table = 'Clients';
                    $column = 'Client_id';
                    break;
                case 'courier':
                    $table = 'Couriers';
                    $column = 'Courier_id';
                    break;
                case 'agent':
                    $table = 'Agents';
                    $column = 'Agent_id';
                    break;
                default:
                    $table = '';
            }
            
            if ($table && $_POST['client_id']) {
                $checkStmt = $pdo->prepare("SELECT $column FROM $table WHERE $column = ?");
                $checkStmt->execute([$_POST['client_id']]);
                if ($result = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                    $entityId = $_POST['client_id'];
                } else {
                    $error = "Выбранный партнер не найден в базе данных. Пожалуйста, выберите другого партнера.";
                }
            }
        } catch (PDOException $e) {
            $error = 'Ошибка при проверке данных. Пожалуйста, попробуйте еще раз.';
        }
    }
    
    // Continue only if no error
    if (empty($error)) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Пожалуйста, проверьте формат электронной почты';
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            try {
                // Insert user into the database
                $stmt = $pdo->prepare("
                    INSERT INTO Users (Full_name, Email, Phone, Position, Role, Login, Password, Client_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$fullName, $email, $phone, $position, $role, $login, $hashedPassword, $entityId]);

                // Log the action
                $stmt = $pdo->prepare("INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) VALUES (?, 'Регистрация пользователя', 'users', 'Добавлен новый пользователь: $login', ?)");
                $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);

                $success = 'Пользователь успешно добавлен!';
                // Send email notification
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
                    $mail->SMTPAuth = true;
                    $mail->Username = 'kvazarlogistics@gmail.com'; // SMTP username
                    $mail->Password = 'iijybydhuhzyatbn'; // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    //Recipients
                    $mail->setFrom('kvazarlogistics@gmail.com', 'Kvazar');
                    $mail->addAddress($email, $fullName);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Kvazar';
                    $mail->Body    = "Здравствуйте, $fullName,<br><br>Ваш аккаунт был создан. Ваш логин: $login.<br>Ваш пароль: $password<br>С наилучшими пожеланиями,<br>Команда Kvazar";

                    $mail->send();
                    $success .= ' Уведомление по электронной почте отправлено.';
                } catch (Exception $e) {
                    $success .= ' Пользователь добавлен, но уведомление по электронной почте не было отправлено.';
                }
            } catch (PDOException $e) {
                $error = 'Ошибка при добавлении пользователя. Пожалуйста, проверьте данные и попробуйте еще раз.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить нового пользователя - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/add_user.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>

        <div id="content">
            <?php 
            $page_title = 'Добавить нового пользователя';
            include '../elements/admin_navbar.php'; 
            ?>

            <div class="content-wrapper">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Добавить нового пользователя</h1>
                    </div>

                    <?php if ($error): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-message">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name">Имя и фамилия</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Почта</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Телефон</label>
                            <input type="text" id="phone" name="phone">
                        </div>

                        <div class="form-group">
                            <label for="position">Должность</label>
                            <input type="text" id="position" name="position">
                        </div>

                        <div class="form-group">
                            <label for="role">Роль</label>
                            <select id="role" name="role" required>
                                <option value="" disabled selected>Выберите роль</option>
                                <option value="client">Клиент</option>
                                <option value="courier">Перевозчик</option>
                                <option value="agent">Подрядчик</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="login">Логин</label>
                            <input type="text" id="login" name="login" required>
                            <small class="form-text">Нажмите 'Сгенерировать из почты' или введите свой логин</small>
                        </div>

                        <div class="form-group">
                            <label for="password">Пароль</label>
                            <input type="password" id="password" name="password" required>
                            <small class="form-text">Нажмите 'Сгенерировать пароль' или введите свой пароль</small>
                        </div>

                        <div class="form-group">
                            <label for="client_id" id="client_id_label">Привязать к партнеру</label>
                            <select id="client_id" name="client_id">
                                <option value="">Выберите</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                            <div class="skip-button-container">
                                <button type="button" id="skip_button" class="skip-button">Пропустить выбор</button>
                                <input type="checkbox" id="skip_client" name="skip_client" style="display: none;">
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">Добавить пользователя</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script>
        // Form handling functionality
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const clientIdSelect = document.getElementById('client_id');
            const skipClientCheckbox = document.getElementById('skip_client');
            const clientIdContainer = document.querySelector('.form-group:has(#client_id)');
            const emailInput = document.getElementById('email');
            const loginInput = document.getElementById('login');
            const clientIdLabel = document.getElementById('client_id_label');

            // Auto-fill login from email
            emailInput.addEventListener('input', function() {
                // Only update login if it's empty or matches the previous email
                const currentEmail = emailInput.value;
                const currentLogin = loginInput.value;
                
                // If login is empty or was previously set to an email
                if (!currentLogin || currentLogin.includes('@')) {
                    loginInput.value = currentEmail;
                }
            });

            // Update labels based on role
            function updateLabels(role) {
                switch(role) {
                    case 'client':
                        clientIdLabel.textContent = 'Связанный клиент';
                        skipButton.textContent = 'Пропустить выбор клиента';
                        break;
                    case 'courier':
                        clientIdLabel.textContent = 'Связанный перевозчик';
                        skipButton.textContent = 'Пропустить выбор перевозчика';
                        break;
                    case 'agent':
                        clientIdLabel.textContent = 'Связанный подрядчик';
                        skipButton.textContent = 'Пропустить выбор подрядчика';
                        break;
                    default:
                        clientIdLabel.textContent = 'Связанный клиент';
                        skipButton.textContent = 'Пропустить выбор';
                }
            }

            roleSelect.addEventListener('change', function() {
                const role = roleSelect.value;
                clientIdSelect.innerHTML = '<option value="">Выберите</option>';

                if (role) {
                    // Show selection for all roles
                    clientIdContainer.style.display = 'block';
                    skipClientCheckbox.checked = false;
                    clientIdSelect.disabled = false;
                    
                    // Update labels
                    updateLabels(role);

                    // Display loading indicator
                    clientIdSelect.innerHTML = '<option value="">Загрузка...</option>';
                    
                    fetch(`../assets/fetch_partners.php?role=${encodeURIComponent(role)}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Received data:', data);
                            // Reset the select
                            clientIdSelect.innerHTML = '<option value="">Выберите</option>';
                            
                            if (Array.isArray(data) && data.length > 0) {
                                data.forEach(item => {
                                    const option = document.createElement('option');
                                    option.value = item.id;
                                    option.textContent = item.Short_Company_name || `ID: ${item.id}`;
                                    clientIdSelect.appendChild(option);
                                });
                            } else {
                                // No results
                                clientIdSelect.innerHTML = '<option value="">Нет партнеров</option>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching data:', error);
                            clientIdSelect.innerHTML = '<option value="">Ошибка загрузки данных</option>';
                            alert('Не удалось загрузить список партнеров. Пожалуйста, попробуйте еще раз или обратитесь к администратору.');
                        });
                } else {
                    clientIdContainer.style.display = 'none';
                }
                skipButton.classList.remove('active');
            });

            const skipButton = document.getElementById('skip_button');
            const skipCheckbox = document.getElementById('skip_client');

            skipButton.addEventListener('click', function() {
                skipCheckbox.checked = !skipCheckbox.checked;
                skipButton.classList.toggle('active');
                clientIdSelect.disabled = skipCheckbox.checked;
                if (skipCheckbox.checked) {
                    clientIdSelect.value = '';
                    skipButton.textContent = 'Выбор пропущен';
                } else {
                    skipButton.textContent = 'Пропустить выбор';
                }
            });

            const passwordInput = document.getElementById('password');
            const generatePasswordBtn = document.createElement('button');
            generatePasswordBtn.type = 'button'; // Prevent form submission
            generatePasswordBtn.className = 'btn-generate-password';
            generatePasswordBtn.textContent = 'Сгенерировать пароль';

            // Insert generate button after password input
            passwordInput.parentNode.insertBefore(generatePasswordBtn, passwordInput.nextSibling);

            // Function to generate random password
            function generatePassword() {
                const length = 8;
                const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
                let password = '';
                
                // Ensure at least one uppercase, one lowercase, one number, and one special character
                password += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)];
                password += 'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random() * 26)];
                password += '0123456789'[Math.floor(Math.random() * 10)];
                password += '!@#$%^&*'[Math.floor(Math.random() * 8)];

                // Fill the rest with random characters
                for (let i = password.length; i < length; i++) {
                    password += charset[Math.floor(Math.random() * charset.length)];
                }

                // Shuffle the password
                password = password.split('').sort(() => Math.random() - 0.5).join('');
                
                return password;
            }

            generatePasswordBtn.addEventListener('click', function() {
                const newPassword = generatePassword();
                passwordInput.value = newPassword;
                passwordInput.type = 'text'; // Show password temporarily
                setTimeout(() => {
                    passwordInput.type = 'password'; // Hide password after 3 seconds
                }, 5000);
            });

            // Create generate login button
            const generateLoginBtn = document.createElement('button');
            generateLoginBtn.type = 'button';
            generateLoginBtn.className = 'btn-generate-password'; // Using same style as password button
            generateLoginBtn.textContent = 'Сгенерировать из почты';

            // Insert generate login button after login input
            loginInput.parentNode.insertBefore(generateLoginBtn, loginInput.nextSibling);

            // Function to generate login from email
            function generateLoginFromEmail() {
                const email = emailInput.value;
                if (email) {
                    loginInput.value = email;
                } else {
                    alert('Пожалуйста введите адрес электронной почты');
                }
            }

            // Add click event for generate login button
            generateLoginBtn.addEventListener('click', function() {
                generateLoginFromEmail();
            });

            // Remove the automatic login generation on email input
            emailInput.removeEventListener('input', function() {});

            // Add this to your existing JavaScript, after creating the buttons
            function addPulseEffect(button) {
                button.addEventListener('click', function() {
                    button.classList.add('clicked');
                    setTimeout(() => {
                        button.classList.remove('clicked');
                    }, 300);
                });
            }

            // Apply the effect to both buttons
            addPulseEffect(generatePasswordBtn);
            addPulseEffect(generateLoginBtn);
        });
    </script>
</body>
</html> 