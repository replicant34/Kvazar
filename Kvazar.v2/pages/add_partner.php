<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $partnerType = $_POST['partner_type'];
    $companyType = $_POST['company_type'];
    $fullCompanyName = $_POST['full_company_name'];
    $shortCompanyName = $_POST['short_company_name'];
    $inn = $_POST['inn'];
    $kpp = $_POST['kpp'];
    $ogrn = $_POST['ogrn'];
    $physicalAddress = $_POST['physical_address'];
    $legalAddress = $_POST['legal_address'];
    $bankName = $_POST['bank_name'];
    $bik = $_POST['bik'];
    $settlementAccount = $_POST['settlement_account'];
    $correspondentAccount = $_POST['correspondent_account'];
    $contactPerson = $_POST['contact_person'];
    $contactPersonPosition = $_POST['contact_person_position'];
    $contactPersonPhone = $_POST['contact_person_phone'];
    $contactPersonEmail = $_POST['contact_person_email'];
    $headPosition = $_POST['head_position'];
    $headName = $_POST['head_name'];

    // Validate required fields
    if (empty($fullCompanyName)) {
        $error = 'Полное название компании обязательно';
    } elseif (empty($partnerType)) {
        $error = 'Тип партнера обязателен';
    } else {
        // Determine table and column names based on partner type
        $table = '';
        $idColumn = '';
        $partnerTypeLabel = '';
        
        switch($partnerType) {
            case 'client':
                $table = 'Clients';
                $idColumn = 'Client_id';
                $partnerTypeLabel = 'клиент';
                break;
            case 'courier':
                $table = 'Couriers';
                $idColumn = 'Courier_id';
                $partnerTypeLabel = 'перевозчик';
                break;
            case 'agent':
                $table = 'Agents';
                $idColumn = 'Agent_id';
                $partnerTypeLabel = 'агент';
                break;
            default:
                $error = 'Неверный тип партнера';
                break;
        }
        
        if (empty($error)) {
            // Insert partner into appropriate database table
            try {
                $stmt = $pdo->prepare("INSERT INTO $table (Company_type, Full_Company_name, Short_Company_name, INN, KPP, OGRN, Physical_address, Legal_address, Bank_name, BIK, Settlement_account, Correspondent_account, Contact_person, Contact_person_position, Contact_person_phone, Contact_person_email, Head_position, Head_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$companyType, $fullCompanyName, $shortCompanyName, $inn, $kpp, $ogrn, $physicalAddress, $legalAddress, $bankName, $bik, $settlementAccount, $correspondentAccount, $contactPerson, $contactPersonPosition, $contactPersonPhone, $contactPersonEmail, $headPosition, $headName]);

                // Log the action
                $stmt = $pdo->prepare("INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) VALUES (?, 'Добавление партнера', ?, 'Добавлен новый $partnerTypeLabel: $fullCompanyName', ?)");
                $stmt->execute([$_SESSION['user_id'], $table, $_SERVER['REMOTE_ADDR']]);

                $success = ucfirst($partnerTypeLabel) . ' успешно добавлен.';
            } catch (PDOException $e) {
                $error = 'Ошибка базы данных: ' . $e->getMessage();
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
    <title>Добавить нового партнера - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/add_partner.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>

        <div id="content">
            <?php 
            $page_title = 'Добавить нового партнера';
            include '../elements/admin_navbar.php'; 
            ?>

            <div class="content-wrapper">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Добавить нового партнера</h1>
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

                    <form action="" method="POST">
                        <?php include '../assets/add_partners_add_form.php'; ?>
                        <button type="submit" class="btn-submit">Добавить партнера</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/sidebar.js"></script>
    <script src="../js/add_partners_autocomplete.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const partnerTypeSelect = document.getElementById('partner_type');
            const formTitle = document.querySelector('.form-header h1');
            const submitButton = document.querySelector('.btn-submit');
            
            // Partner type labels
            const partnerTypeLabels = {
                'client': 'Клиент',
                'courier': 'Перевозчик', 
                'agent': 'Агент'
            };
            
            // Update form title and button text based on selected partner type
            partnerTypeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                const label = partnerTypeLabels[selectedType] || 'партнер';
                
                // Update form title
                formTitle.textContent = `Добавить нового ${label.toLowerCase()}`;
                
                // Update submit button text
                submitButton.textContent = `Добавить ${label.toLowerCase()}`;
                
                // Add visual feedback
                this.style.borderColor = '#2ecc71';
                this.style.boxShadow = '0 0 0 3px rgba(46, 204, 113, 0.25)';
                
                // Remove the visual feedback after a moment
                setTimeout(() => {
                    this.style.borderColor = '#3498db';
                    this.style.boxShadow = '0 4px 8px rgba(52, 152, 219, 0.15)';
                }, 1000);
            });
            
            // Add pulse effect to partner type selector
            partnerTypeSelect.addEventListener('click', function() {
                this.classList.add('clicked');
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 300);
            });
        });
    </script>
</body>
</html> 