<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('Location: ../index.php');
    exit();
}

// Check if password authentication is required and handle it
$passwordRequired = false;
$passwordError = '';

try {
    // Check if Action_id = 8 requires password
    $passwordCheckStmt = $pdo->prepare("
        SELECT request_password, Action_password 
        FROM list_actions_passwords 
        WHERE Action_id = 8
    ");
    $passwordCheckStmt->execute();
    $passwordData = $passwordCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($passwordData && $passwordData['request_password'] == 1) {
        $passwordRequired = true;
        
        // Check if password was submitted
        if ($_POST['action_password'] ?? false) {
            if ($_POST['action_password'] === $passwordData['Action_password']) {
                $_SESSION['lists_access_granted'] = true;
                $passwordRequired = false;
                
                // Log successful password authentication
                try {
                    $logStmt = $pdo->prepare("
                        INSERT INTO action_logs (user_id, action_type, table_name, record_id, description, ip_address, timestamp) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                    if ($ipAddress === '::1') $ipAddress = '127.0.0.1';
                    $logStmt->execute([$_SESSION['user_id'], 'доступ_разрешен', 'manage_lists', 8, 'Успешная аутентификация для доступа к управлению списками', $ipAddress]);
                } catch (Exception $e) {
                    error_log("Failed to log successful authentication: " . $e->getMessage());
                }
            } else {
                $passwordError = 'Неверный пароль доступа';
                
                // Log failed password attempt
                try {
                    $logStmt = $pdo->prepare("
                        INSERT INTO action_logs (user_id, action_type, table_name, record_id, description, ip_address, timestamp) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                    if ($ipAddress === '::1') $ipAddress = '127.0.0.1';
                    $logStmt->execute([$_SESSION['user_id'], 'доступ_запрещен', 'manage_lists', 8, 'Неудачная попытка аутентификации для доступа к управлению списками: неверный пароль', $ipAddress]);
                } catch (Exception $e) {
                    error_log("Failed to log failed authentication: " . $e->getMessage());
                }
            }
        }
        
        // Check if access was already granted in this session
        if ($_SESSION['lists_access_granted'] ?? false) {
            $passwordRequired = false;
        }
    }
} catch (Exception $e) {
    error_log("Error checking password requirement: " . $e->getMessage());
}

// Log access to manage lists page (only if authenticated and access granted)
if (!$passwordRequired && ($_SESSION['lists_access_granted'] ?? false)) {
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO action_logs (user_id, action_type, table_name, record_id, description, ip_address, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ipAddress === '::1') $ipAddress = '127.0.0.1';
        $logStmt->execute([$_SESSION['user_id'], 'доступ_к_странице', 'manage_lists', null, 'Доступ к странице управления списками', $ipAddress]);
    } catch (Exception $e) {
        error_log("Failed to log page access: " . $e->getMessage());
    }
}

$userRole = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление списками - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/manage_lists.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>
        
        <div id="content">
            <?php 
            $page_title = 'Управление списками';
            include '../elements/admin_navbar.php'; 
            ?>
            
            <?php if ($passwordRequired): ?>
            <!-- Password Authentication Modal -->
            <div class="password-overlay">
                <div class="password-modal">
                    <div class="password-header">
                        <i class="fas fa-lock"></i>
                        <h3>Требуется пароль доступа</h3>
                        <p>Для доступа к управлению списками введите пароль</p>
                    </div>
                    
                    <form method="POST" class="password-form">
                        <div class="form-group">
                            <label for="action_password">Пароль доступа:</label>
                            <input type="password" 
                                   id="action_password" 
                                   name="action_password" 
                                   class="form-control" 
                                   required 
                                   autofocus>
                        </div>
                        
                        <?php if ($passwordError): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($passwordError) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-unlock"></i> Получить доступ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <!-- Main Lists Management Interface -->
            <div class="lists-container">
                <div class="lists-header">
                    <h1><i class="fas fa-list"></i> Управление списками</h1>
                    <p>Управление справочниками и списками системы</p>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="tabs-container">
                    <div class="tabs-nav">
                        <button class="tab-btn active" data-tab="contractors">Подрядчики</button>
                        <button class="tab-btn" data-tab="extra-services">Доп. услуги</button>
                        <button class="tab-btn" data-tab="action-passwords">Пароли действий</button>
                        <button class="tab-btn" data-tab="cargo-names">Названия грузов</button>
                        <button class="tab-btn" data-tab="contract-status">Статусы договоров</button>
                        <button class="tab-btn" data-tab="contract-types">Типы договоров</button>
                        <button class="tab-btn" data-tab="currencies">Валюты</button>
                        <button class="tab-btn" data-tab="loading-types">Типы загрузки</button>
                        <button class="tab-btn" data-tab="order-status">Статусы заказов</button>
                        <button class="tab-btn" data-tab="packaging-types">Типы упаковки</button>
                        <button class="tab-btn" data-tab="partner-status">Статусы партнеров</button>
                        <button class="tab-btn" data-tab="rates">Тарифы</button>
                        <button class="tab-btn" data-tab="shipping-types">Типы доставки</button>
                        <button class="tab-btn" data-tab="transport-types">Типы транспорта</button>
                        <button class="tab-btn" data-tab="vehicles">Транспорт</button>
                        <button class="tab-btn" data-tab="drivers">Водители</button>
                    </div>
                    
                    <!-- Tab Contents -->
                    <div class="tab-content active" id="contractors-tab">
                        <div class="tab-header">
                            <h3><i class="fas fa-building"></i> Подрядчики</h3>
                            <div class="tab-actions">
                                <button class="btn btn-success" onclick="addContractor()">
                                    <i class="fas fa-plus"></i> Добавить
                                </button>
                                <button class="btn btn-secondary" onclick="downloadCSV('contractors')">
                                    <i class="fas fa-download"></i> CSV
                                </button>
                            </div>
                        </div>
                        <div class="table-container">
                            <table id="contractors-table" class="lists-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Тип</th>
                                        <th>Полное название</th>
                                        <th>Короткое название</th>
                                        <th>ИНН</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Other tab contents will be added here -->
                    <div class="tab-content" id="extra-services-tab">
                        <div class="tab-header">
                            <h3><i class="fas fa-concierge-bell"></i> Дополнительные услуги</h3>
                            <div class="tab-actions">
                                <button class="btn btn-success" onclick="addExtraService()">
                                    <i class="fas fa-plus"></i> Добавить
                                </button>
                                <button class="btn btn-secondary" onclick="downloadCSV('extra-services')">
                                    <i class="fas fa-download"></i> CSV
                                </button>
                            </div>
                        </div>
                        <div class="table-container">
                            <table id="extra-services-table" class="lists-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Название услуги</th>
                                        <th>Цена</th>
                                        <th>Создано</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Placeholder for other tabs -->
                    <?php
                    $tabs = [
                        'action-passwords' => ['icon' => 'fa-key', 'title' => 'Пароли действий'],
                        'cargo-names' => ['icon' => 'fa-boxes', 'title' => 'Названия грузов'],
                        'contract-status' => ['icon' => 'fa-flag', 'title' => 'Статусы договоров'],
                        'contract-types' => ['icon' => 'fa-file-contract', 'title' => 'Типы договоров'],
                        'currencies' => ['icon' => 'fa-coins', 'title' => 'Валюты'],
                        'loading-types' => ['icon' => 'fa-truck-loading', 'title' => 'Типы загрузки'],
                        'order-status' => ['icon' => 'fa-clipboard-list', 'title' => 'Статусы заказов'],
                        'packaging-types' => ['icon' => 'fa-box', 'title' => 'Типы упаковки'],
                        'partner-status' => ['icon' => 'fa-handshake', 'title' => 'Статусы партнеров'],
                        'rates' => ['icon' => 'fa-dollar-sign', 'title' => 'Тарифы'],
                        'shipping-types' => ['icon' => 'fa-shipping-fast', 'title' => 'Типы доставки'],
                        'transport-types' => ['icon' => 'fa-truck', 'title' => 'Типы транспорта'],
                        'vehicles' => ['icon' => 'fa-car', 'title' => 'Транспорт'],
                        'drivers' => ['icon' => 'fa-user-tie', 'title' => 'Водители']
                    ];
                    
                    foreach ($tabs as $tabId => $tabInfo): ?>
                    <div class="tab-content" id="<?= $tabId ?>-tab">
                        <div class="tab-header">
                            <h3><i class="fas <?= $tabInfo['icon'] ?>"></i> <?= $tabInfo['title'] ?></h3>
                            <div class="tab-actions">
                                <button class="btn btn-success" onclick="addItem('<?= $tabId ?>')">
                                    <i class="fas fa-plus"></i> Добавить
                                </button>
                                <button class="btn btn-secondary" onclick="downloadCSV('<?= $tabId ?>')">
                                    <i class="fas fa-download"></i> CSV
                                </button>
                            </div>
                        </div>
                        <div class="table-container">
                            <table id="<?= $tabId ?>-table" class="lists-table">
                                <thead>
                                    <tr>
                                        <!-- Headers will be dynamically loaded -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Добавить элемент</h3>
            <form id="itemForm">
                <div id="modalFormContent">
                    <!-- Form fields will be dynamically generated -->
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/manage_lists.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html> 