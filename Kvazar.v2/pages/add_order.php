<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../assets/add_order_form_helper.php';
require_once __DIR__ . '/../assets/order_logging.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Log page access
logOrderPageAccess($pdo, 'add_order');

$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Get available clients based on user role
function getAvailableClients($pdo, $userRole, $userId) {
    switch ($userRole) {
        case 'admin':
        case 'ceo':
            $stmt = $pdo->query("SELECT Client_id, Full_company_name FROM Clients ORDER BY Full_company_name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        case 'operator':
            // Get clients assigned to operator
            $stmt = $pdo->prepare("
                SELECT DISTINCT c.Client_id, c.Full_company_name 
                FROM Clients c
                JOIN Operator_clients oc ON c.Client_id = oc.Client_id
                WHERE oc.User_id = ?
                ORDER BY c.Full_company_name
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        case 'client':
            // Get only client's own company
            $stmt = $pdo->prepare("
                SELECT c.Client_id, c.Full_company_name 
                FROM Clients c
                JOIN Users u ON c.Client_id = u.Client_id
                WHERE u.User_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        default:
            return [];
    }
}

$availableClients = getAvailableClients($pdo, $userRole, $userId);

// Fetch contractors for dropdown
$contractors = [];
try {
    $stmt = $pdo->query("SELECT Contractors_id, Full_Company_name FROM Contractors ORDER BY Full_Company_name");
    $contractors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching contractors: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="locale" content="ru_RU">
    <title>Новый заказ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/add_order.css">
    <link rel="stylesheet" href="../css/russian_time_picker.css">
    <link rel="stylesheet" href="../css/partner_info_modal.css">
    <link rel="stylesheet" href="../css/order_preview_modal.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>
        
        <div id="content">
            <?php include '../elements/admin_navbar.php'; ?>
            
            <div class="form-container">
                <div class="form-header">
                    <h1>Новый заказ</h1>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="orderTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="cargo-tab" data-toggle="tab" href="#cargo" role="tab">Груз</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cost-tab" data-toggle="tab" href="#cost" role="tab">Стоимость</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="route-tab" data-toggle="tab" href="#route" role="tab">Маршрут</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="additional-tab" data-toggle="tab" href="#additional" role="tab">Дополнительно</a>
                    </li>
                </ul>

                <form id="orderForm" method="POST">
                    <div class="tab-content" id="orderTabContent">
                        <!-- Cargo Tab -->
                        <div class="tab-pane fade show active" id="cargo" role="tabpanel">
                            <!-- Client Info Section -->
                            <div class="cost-section">
                                <h3 class="cost-section-title">Информация о клиенте</h3>
                                <div class="form-row">
                                    <!-- Client field -->
                                    <div class="form-group">
                                        <label for="clientSearch">Клиент</label>
                                        <?php if (canEditField('client', $userRole)): ?>
                                            <div class="search-select-container">
                                                <div class="search-input-wrapper">
                                                    <input type="text" 
                                                           id="clientSearch" 
                                                           class="search-input" 
                                                           placeholder="Введите название компании"
                                                           autocomplete="off">
                                                    <input type="hidden" id="client_id" name="client_id">
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="readonly-field">
                                                <?php 
                                                if (!empty($availableClients)) {
                                                    echo htmlspecialchars($availableClients[0]['Full_company_name']);
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Contract Number field -->
                                    <div class="form-group">
                                        <label for="contract">Номер договора</label>
                                        <div class="contract-container">
                                            <input type="text" 
                                                   id="contract_static" 
                                                   name="contract_number" 
                                                   class="form-input" 
                                                   readonly 
                                                   style="display: none"
                                                   disabled>
                                            <select id="contract_select" 
                                                    name="contract_number" 
                                                    class="form-input" 
                                                    disabled
                                                    required>
                                                <option value="">Выберите договор</option>
                                            </select>
                                        </div>
                                        <div class="contract-info">
                                            <span class="contract-message"></span>
                                            <span class="contract-hint" style="display: none">
                                                <i class="fas fa-info-circle"></i>
                                                <span class="hint-text">Please add a contract for this client before proceeding</span>
                                            </span>
                                        </div>
                                        <div class="contract-message-bar" style="display:none"></div>
                                    </div>

                                    <!-- Contract Date field -->
                                    <div class="form-group">
                                        <label for="contract_date">Дата договора</label>
                                        <div class="contract-date-container">
                                            <input type="text" 
                                                   id="contract_date" 
                                                   name="contract_date" 
                                                   class="form-input" 
                                                   readonly 
                                                   disabled>
                                        </div>
                                    </div>

                                    <!-- Contractor field -->
                                    <div class="form-group">
                                        <label for="contractor">Подрядчик</label>
                                        <div class="contractor-container">
                                            <select id="contractor" name="contractor_id" class="form-input" required>
                                                <option value="">Выберите подрядчика</option>
                                                <?php foreach ($contractors as $contractor): ?>
                                                    <option value="<?= htmlspecialchars($contractor['Contractors_id']) ?>">
                                                        <?= htmlspecialchars($contractor['Full_Company_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Info Section -->
                            <div class="cost-section">
                                <h3 class="cost-section-title">Информация о заказе</h3>
                                <div class="form-row">
                                    <!-- Order Number field -->
                                    <div class="form-group">
                                        <label for="order_number">Номер заказа</label>
                                        <div class="order-number-container">
                                            <div class="input-with-button">
                                                <input type="text" 
                                                       id="order_number" 
                                                       name="display_order_number" 
                                                       class="form-input" 
                                                       <?php if (!in_array($userRole, ['admin', 'ceo'])): ?>readonly<?php endif; ?>
                                                       required>
                                                <button type="button" 
                                                        id="generate_order_number" 
                                                        class="btn-generate"
                                                        <?php if (!in_array($userRole, ['admin', 'ceo'])): ?>disabled<?php endif; ?>>
                                                    <i class="fas fa-sync-alt"></i>
                                                    Сгенерировать
                                                </button>
                                            </div>
                                            <div class="order-number-message"></div>
                                        </div>
                                    </div>

                                    <!-- Order Date field -->
                                    <div class="form-group">
                                        <label for="order_date">Дата заказа</label>
                                        <div class="order-date-container">
                                            <input type="date" 
                                                   id="order_date" 
                                                   name="order_date" 
                                                   class="form-input" 
                                                   value="<?php echo date('Y-m-d'); ?>"
                                                   required>
                                        </div>
                                    </div>

                                    <!-- Shipping Type field -->
                                    <div class="form-group">
                                        <label for="shipping_type">Тип перевозки</label>
                                        <div class="shipping-type-container">
                                            <select id="shipping_type" 
                                                    name="shipping_type" 
                                                    class="form-input" 
                                                    required>
                                                <option value="">Выберите тип перевозки</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Transport Type field -->
                                    <div class="form-group">
                                        <label for="transport_type">Тип транспорта</label>
                                        <div class="transport-type-container">
                                            <select id="transport_type" 
                                                    name="transport_type" 
                                                    class="form-input" 
                                                    required>
                                                <option value="">Выберите тип транспорта</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cargo Info Section -->
                            <div class="cost-section">
                                <h3 class="cost-section-title">Информация о грузе</h3>
                                <!-- First row: Basic cargo properties -->
                                <div class="form-row">
                                    <!-- Cargo Name field -->
                                    <div class="form-group">
                                        <label for="cargo_name">Наименование груза</label>
                                        <div class="cargo-name-container">
                                            <select id="cargo_name" 
                                                    name="cargo_name" 
                                                    class="form-input" 
                                                    required>
                                                <option value="">Выберите наименование</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Cargo Weight field -->
                                    <div class="form-group">
                                        <label for="cargo_weight">Вес груза</label>
                                        <div class="weight-container">
                                            <div class="weight-row">
                                                <input type="number" 
                                                       id="cargo_weight" 
                                                       name="cargo_weight" 
                                                       class="form-input weight-input" 
                                                       step="0.01"
                                                       min="0"
                                                       required
                                                       disabled>
                                                <div class="weight-unit-selector">
                                                    <button type="button" class="unit-btn active" data-unit="ton">ton</button>
                                                    <button type="button" class="unit-btn" data-unit="kg">kg</button>
                                                </div>
                                            </div>
                                            <div class="weight-message"></div>
                                        </div>
                                    </div>

                                    <!-- Cargo Volume field -->
                                    <div class="form-group">
                                        <label for="cargo_volume">Объем груза <span class="volume-unit">(м³)</span> </label>
                                        <div class="volume-container">
                                            <div class="volume-input-group">
                                                <div class="volume-m3">
                                                    <input type="number" 
                                                           id="cargo_volume" 
                                                           name="cargo_volume" 
                                                           class="form-input volume-input" 
                                                           step="0.01"
                                                           min="0"
                                                           required
                                                           disabled>
                                                </div>
                                                <div class="volume-dimensions">
                                                    <div class="dimensions-inputs">
                                                        <input type="number" 
                                                               id="length" 
                                                               name="length" 
                                                               class="form-input dimension" 
                                                               placeholder="Д"
                                                               step="0.01"
                                                               min="0">
                                                        <span>×</span>
                                                        <input type="number" 
                                                               id="width" 
                                                               name="width" 
                                                               class="form-input dimension" 
                                                               placeholder="Ш"
                                                               step="0.01"
                                                               min="0">
                                                        <span>×</span>
                                                        <input type="number" 
                                                               id="height" 
                                                               name="height" 
                                                               class="form-input dimension" 
                                                               placeholder="В"
                                                               step="0.01"
                                                               min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="volume-message"></div>
                                        </div>
                                    </div>

                                    <!-- Quantity field -->
                                    <div class="form-group width-small">
                                        <label for="cargo_quantity">Количество</label>
                                        <div class="quantity-container">
                                            <input type="number" 
                                                   id="cargo_quantity" 
                                                   name="cargo_quantity" 
                                                   class="form-input spinner-input" 
                                                   min="1"
                                                   step="1"
                                                   required
                                                   value="1">
                                            <div class="spinner-buttons">
                                                <button type="button" class="spinner-up">
                                                    <i class="fas fa-chevron-up"></i>
                                                </button>
                                                <button type="button" class="spinner-down">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Second row: Loading, packaging, and temperature -->
                                <div class="form-row">
                                    <!-- Loading Type field -->
                                    <div class="form-group">
                                        <label for="loading_type">Тип погрузки</label>
                                        <div class="loading-type-container">
                                            <select id="loading_type" 
                                                    name="loading_type" 
                                                    class="form-input" 
                                                    required>
                                                <option value="">Выберите тип погрузки</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Packaging Type field -->
                                    <div class="form-group">
                                        <label for="packaging_type">Тип упаковки</label>
                                        <div class="packaging-type-container">
                                            <select id="packaging_type" 
                                                    name="packaging_type" 
                                                    class="form-input" 
                                                    required>
                                                <option value="">Выберите тип упаковки</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Temperature Range field -->
                                    <div class="form-group width-small">
                                        <label for="min_temperature">Температурный режим</label>
                                        <div class="temperature-container">
                                            <div class="temperature-inputs">
                                                <div class="temp-input-wrapper">
                                                    <input type="number" 
                                                           id="min_temperature" 
                                                           name="min_temperature" 
                                                           class="form-input" 
                                                           step="0.1"
                                                           disabled
                                                           placeholder="Мин">
                                                </div>
                                                <span class="temp-separator">-</span>
                                                <div class="temp-input-wrapper">
                                                    <input type="number" 
                                                           id="max_temperature" 
                                                           name="max_temperature" 
                                                           class="form-input" 
                                                           step="0.1"
                                                           disabled
                                                           placeholder="Макс">
                                                </div>
                                            </div>
                                            <div class="temperature-message"></div>
                                        </div>
                                    </div>

                                    <!-- Temperature List field -->
                                    <div class="form-group width-medium">
                                        <label for="temp_print_list">Печать температурного режима</label>
                                        <div class="temp-print-container">
                                            <select id="temp_print_list" 
                                                    name="temp_print_list" 
                                                    class="form-input" 
                                                    disabled>
                                                <option value="">Выбрать</option>
                                                <option value="Да">Да</option>
                                                <option value="Нет">Нет</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Navigation -->
                            <div class="tab-navigation">
                                <button type="button" id="cargo-next" class="btn-next">
                                    Далее <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Cost Tab -->
                        <div class="tab-pane fade" id="cost" role="tabpanel">
                            <!-- Insurance Section -->
                            <div class="cost-section">
                                <h3 class="cost-section-title">Страхование</h3>
                                <div class="form-row">
                                    <!-- Move Cargo Price field here -->
                                    <div class="form-group">
                                        <label for="cargo_price">Стоимость груза</label>
                                        <div class="price-container">
                                            <div class="price-input-wrapper">
                                                <input type="number" 
                                                       id="cargo_price" 
                                                       name="cargo_price" 
                                                       class="form-input price-input" 
                                                       step="0.01"
                                                       min="0"
                                                       required
                                                       placeholder="Enter price">
                                                <select id="currency" 
                                                        name="currency_id" 
                                                        class="form-select currency-select" 
                                                        required>
                                                    <option value="">Валюта</option>
                                                </select>
                                            </div>
                                            <div class="price-message"></div>
                                        </div>
                                    </div>
                                    <!-- Move Rate field here -->
                                    <div class="form-group">
                                        <label for="rate">Ставка</label>
                                        <div class="rate-container">
                                            <input type="number" 
                                                   id="rate" 
                                                   name="rate" 
                                                   class="form-input rate-input" 
                                                   readonly
                                                   disabled>
                                            <div class="rate-message"></div>
                                        </div>
                                    </div>
                                    <!-- Move Insurance field here -->
                                    <div class="form-group">
                                        <label for="insurance_status">Страхование</label>
                                        <div class="insurance-container">
                                            <select id="insurance_status" 
                                                    name="insurance_status" 
                                                    class="form-input" 
                                                    required>
                                                <option value="1">Не требуется</option>
                                                <option value="2">Требуется</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Move Total Insurance field here -->
                                    <div class="form-group">
                                        <label for="total_insurance">Сумма страхования</label>
                                        <div class="total-insurance-container">
                                            <input type="number" 
                                                   id="total_insurance" 
                                                   name="total_insurance" 
                                                   class="form-input total-insurance-input" 
                                                   readonly
                                                   disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transport & Overwork Section -->
                            <div class="cost-section">
                                <h3 class="cost-section-title">Стоимость перевозки и переработка</h3>
                                <div class="form-row">
                                    <!-- Move Transport Rate field here -->
                                    <div class="form-group">
                                        <label for="transport_rate">Ставка перевозки</label>
                                        <div class="transport-rate-container">
                                            <input type="number" 
                                                   id="transport_rate" 
                                                   name="transport_rate" 
                                                   class="form-input transport-rate-input" 
                                                   readonly
                                                   disabled>
                                            <div class="transport-rate-message"></div>
                                        </div>
                                    </div>
                                    <!-- Move Hours field here -->
                                    <div class="form-group">
                                        <label for="transport_hours">Часы</label>
                                        <div class="hours-container">
                                            <input type="number" 
                                                   id="transport_hours" 
                                                   name="transport_hours" 
                                                   class="form-input hours-input spinner-input" 
                                                   min="1"
                                                   step="1"
                                                   required>
                                            <div class="spinner-buttons">
                                                <button type="button" class="spinner-up">
                                                    <i class="fas fa-chevron-up"></i>
                                                </button>
                                                <button type="button" class="spinner-down">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Move Overwork Rate field here -->
                                    <div class="form-group">
                                        <label for="overwork_rate">Ставка переработки</label>
                                        <div class="overwork-rate-container">
                                            <input type="number" 
                                                   id="overwork_rate" 
                                                   name="overwork_rate" 
                                                   class="form-input overwork-rate-input" 
                                                   readonly
                                                   disabled>
                                            <div class="overwork-rate-message"></div>
                                        </div>
                                    </div>
                                    <!-- Move Overwork Hours field here -->
                                    <div class="form-group">
                                        <label for="overwork_hours">Часы переработки</label>
                                        <div class="hours-container">
                                            <input type="number" 
                                                   id="overwork_hours" 
                                                   name="overwork_hours" 
                                                   class="form-input hours-input spinner-input" 
                                                   min="0"
                                                   step="1"
                                                   value="0"
                                                   required>
                                            <div class="spinner-buttons">
                                                <button type="button" class="spinner-up">
                                                    <i class="fas fa-chevron-up"></i>
                                                </button>
                                                <button type="button" class="spinner-down">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Move Transport Total field here -->
                                    <div class="form-group">
                                        <label for="transport_total">Итого за перевозку</label>
                                        <div class="transport-total-container">
                                            <input type="number" 
                                                   id="transport_total" 
                                                   name="transport_total" 
                                                   class="form-input transport-total-input" 
                                                   readonly
                                                   disabled>
                                            <div class="transport-total-message"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Extra Services Section -->
                            <div class="cost-section">
                                <h3 class="cost-section-title">Дополнительные услуги</h3>
                                <div id="extra-services-container">
                                    <!-- Initial extra service row will be created by JavaScript -->
                                </div>
                                <!-- Button group for extra services -->
                                <div class="extra-service-buttons">
                                    <button type="button" id="add-extra-service" class="btn-add-service">
                                        <i class="fas fa-plus"></i> Добавить услугу
                                    </button>
                                    <button type="button" id="remove-extra-service" class="btn-remove-service" disabled>
                                        <i class="fas fa-trash"></i> Удалить услугу
                                    </button>
                                </div>
                            </div>

                            <!-- Total Summary Section -->
                            <div class="cost-section total-summary">
                                <h3 class="cost-section-title">
                                    <i class="fas fa-calculator"></i> Итоговая стоимость
                                </h3>
                                <div class="totals-grid">
                                    <div class="З total-final">
                                        <span class="total-value final-amount" id="summary-grand-total">0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Navigation -->
                            <div class="tab-navigation">
                                <button type="button" id="cost-prev" class="btn-prev">
                                    <i class="fas fa-arrow-left"></i> Назад
                                </button>
                                <button type="button" id="cost-next" class="btn-next">
                                    Далее <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Route Tab -->
                        <div class="tab-pane fade" id="route" role="tabpanel">
                            <div id="route-points-container">
                                <!-- First Point (fixed loading) -->
                                <div class="cost-section route-point" data-position="1">
                                    <h3 class="cost-section-title">Пункт 1</h3>
                                    <!-- First Row: Action Type, Company Name, Address -->
                                    <div class="form-row">
                                        <!-- Action Type -->
                                        <div class="form-group">
                                            <label for="action_type_1">Тип действия</label>
                                            <div class="action-type-container">
                                                <select id="action_type_1" 
                                                        name="action_type_1" 
                                                        class="form-input" 
                                                        required 
                                                        disabled>
                                                                                                    <option value="Погрузка" selected>Погрузка</option>
                                                <option value="Выгрузка">Выгрузка</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Company Name -->
                                        <div class="form-group">
                                            <label for="company_name_1">Название компании</label>
                                            <div class="company-name-container">
                                                <div class="combo-select">
                                                    <input type="text" 
                                                           id="company_name_1" 
                                                           name="company_name_1" 
                                                           class="form-input company-input combo-input" 
                                                           required 
                                                           autocomplete="off"
                                                           placeholder="Выберите или введите компанию">
                                                    <div class="combo-arrow">
                                                        <i class="fas fa-chevron-down"></i>
                                                    </div>
                                                    <div class="combo-dropdown company-dropdown">
                                                        <!-- Options will be populated here -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div class="form-group">
                                            <label for="address_loading_1">Адрес</label>
                                            <div class="address-container">
                                                <div class="combo-select">
                                                    <input type="text" 
                                                           id="address_loading_1" 
                                                           name="address_loading_1" 
                                                           class="form-input address-input combo-input" 
                                                           required 
                                                           autocomplete="off"
                                                           placeholder="Выберите или введите адрес">
                                                    <div class="combo-arrow">
                                                        <i class="fas fa-chevron-down"></i>
                                                    </div>
                                                    <div class="combo-dropdown address-dropdown">
                                                        <!-- Options will be populated here -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Second Row: Date, Time, Contact Person, Phone Number -->
                                    <div class="form-row">
                                        <!-- Date -->
                                        <div class="form-group">
                                            <label for="point_date_1">Дата</label>
                                            <div class="point-date-container">
                                                <input type="date" 
                                                       id="point_date_1" 
                                                       name="point_date_1" 
                                                       class="form-input" 
                                                       required>
                                                <div class="date-message"></div>
                                            </div>
                                        </div>

                                        <!-- Time -->
                                        <div class="form-group">
                                            <label for="point_time_1">Время прибытия</label>
                                            <div class="point-time-container">
                                                <input type="text" 
                                                       id="point_time_1" 
                                                       name="point_time_1" 
                                                       class="form-input time-input" 
                                                       required 
                                                       placeholder="ЧЧ:ММ">
                                                <div class="time-message"></div>
                                            </div>
                                        </div>

                                        <!-- Contact Person -->
                                        <div class="form-group">
                                            <label for="contact_person_1">Контактное лицо</label>
                                            <div class="contact-person-container">
                                                <div class="combo-select">
                                                    <input type="text" 
                                                           id="contact_person_1" 
                                                           name="contact_person_1" 
                                                           class="form-input contact-input combo-input" 
                                                           required 
                                                           autocomplete="off"
                                                           placeholder="Выберите или введите контакт">
                                                    <div class="combo-arrow">
                                                        <i class="fas fa-chevron-down"></i>
                                                    </div>
                                                    <div class="combo-dropdown contact-dropdown">
                                                        <!-- Options will be populated here -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Phone Number -->
                                        <div class="form-group">
                                            <label for="phone_number_1">Телефон</label>
                                            <div class="phone-number-container">
                                                <input type="text" 
                                                       id="phone_number_1" 
                                                       name="phone_number_1" 
                                                       class="form-input" 
                                                       required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Contacts Section -->
                                    <div class="additional-contacts-section" id="additional_contacts_1">
                                        <div class="additional-contacts-header">
                                            <button type="button" class="btn-add-contact" data-point="1">
                                                <i class="fas fa-plus"></i> Добавить контакт
                                            </button>
                                        </div>
                                        <div class="additional-contacts-container" id="additional_contacts_container_1">
                                            <!-- Additional contacts will be added here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Route Controls -->
                            <div class="route-controls">
                                <div class="route-buttons">
                                    <button type="button" id="add-route-point" class="btn-add-service">
                                        <i class="fas fa-plus"></i> Добавить пункт
                                    </button>
                                    <button type="button" id="remove-route-point" class="btn-remove-service" disabled>
                                        <i class="fas fa-trash"></i> Удалить пункт
                                    </button>
                                </div>
                            </div>

                            <!-- Tab Navigation -->
                            <div class="tab-navigation">
                                <button type="button" id="route-prev" class="btn-prev">
                                    <i class="fas fa-arrow-left"></i> Назад
                                </button>
                                <button type="button" id="route-next" class="btn-next">
                                    Далее <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Additional Info Tab -->
                        <div class="tab-pane fade" id="additional" role="tabpanel">
                            <div class="cost-section">
                                <h3 class="cost-section-title">Дополнительная информация</h3>
                                <div class="form-row">
                                    <!-- Notes -->
                                    <div class="form-group">
                                        <label for="order_notes">Примечания</label>
                                        <div class="notes-container">
                                            <textarea id="order_notes" 
                                                    name="order_notes" 
                                                    class="form-input" 
                                                    rows="4" 
                                                    placeholder="Введите дополнительные примечания..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Supporting Documents Section -->
                            <div class="cost-section">
                                <h3 class="cost-section-title">
                                    <i class="fas fa-file-upload"></i> Сопроводительные документы
                                </h3>
                                <div class="documents-upload-container">
                                    <!-- Upload Area -->
                                    <div class="upload-area" id="upload-area">
                                        <div class="upload-content">
                                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                            <p class="upload-text">Перетащите файлы сюда или нажмите для выбора</p>
                                            <p class="upload-info">Максимум 3 файла, до 10 МБ каждый</p>
                                            <input type="file" id="file-input" name="supporting_documents[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt" style="display: none;">
                                        </div>
                                    </div>

                                    <!-- File List -->
                                    <div class="uploaded-files" id="uploaded-files">
                                        <!-- Uploaded files will appear here -->
                                    </div>

                                    <!-- Upload Button -->
                                    <div class="upload-actions">
                                        <button type="button" id="browse-files" class="btn-add-service">
                                            <i class="fas fa-folder-open"></i> Выбрать файлы
                                        </button>
                                        <span class="file-counter">
                                            <span id="files-count">0</span> / 3 файла
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Navigation -->
                            <div class="tab-navigation">
                                <button type="button" id="additional-prev" class="btn-prev">
                                    <i class="fas fa-arrow-left"></i> Назад
                                </button>
                                <button type="button" id="preview-order" class="btn-submit" onclick="showOrderPreview()">
                                    <i class="fas fa-eye"></i> Предварительный просмотр
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Message Container for styled notifications -->
    <div id="message-container" class="message-container"></div>

    <?php include '../assets/order_preview_modal.php'; ?>

    <!-- Tab Navigation JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation functionality
            const tabs = ['cargo', 'cost', 'route', 'additional'];
            let currentTabIndex = 0;

            // Function to show specific tab
            function showTab(tabName) {
                // Hide all tab panes
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Remove active class from all nav links
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Show target tab
                const targetTab = document.getElementById(tabName);
                if (targetTab) {
                    targetTab.classList.add('show', 'active');
                }
                
                // Activate corresponding nav link
                const navLink = document.querySelector(`[href="#${tabName}"]`);
                if (navLink) {
                    navLink.classList.add('active');
                }
                
                // Update current tab index
                currentTabIndex = tabs.indexOf(tabName);
            }

            // Next button functionality
            function goToNextTab() {
                if (currentTabIndex < tabs.length - 1) {
                    showTab(tabs[currentTabIndex + 1]);
                }
            }

            // Previous button functionality
            function goToPreviousTab() {
                if (currentTabIndex > 0) {
                    showTab(tabs[currentTabIndex - 1]);
                }
            }

            // Route validation and navigation
            function validateAndGoToNextTab() {
                // Get all route points
                const routePoints = document.querySelectorAll('.route-point');
                
                if (routePoints.length === 0) {
                    showError('Необходимо добавить хотя бы один пункт маршрута', 'Маршрут не задан');
                    return false;
                }

                // Check if last point is unloading
                const lastPoint = routePoints[routePoints.length - 1];
                const lastActionType = lastPoint.querySelector('select[id^="action_type_"]').value;
                
                if (lastActionType !== 'Выгрузка') {
                    showWarning('Последняя точка должна быть разгрузкой', 'Проверьте маршрут');
                    return false;
                }

                // Check chronological order (if the function exists)
                if (window.validateChronologicalOrder && !window.validateChronologicalOrder()) {
                    showWarning('Пожалуйста, убедитесь, что все точки расположены в хронологическом порядке', 'Неверный порядок дат');
                    return false;
                }

                // If all validations pass, go to next tab
                goToNextTab();
                return true;
            }

            // Add event listeners for navigation buttons
            document.getElementById('cargo-next')?.addEventListener('click', goToNextTab);
            document.getElementById('cost-prev')?.addEventListener('click', goToPreviousTab);
            document.getElementById('cost-next')?.addEventListener('click', goToNextTab);
            document.getElementById('route-prev')?.addEventListener('click', goToPreviousTab);
            document.getElementById('route-next')?.addEventListener('click', validateAndGoToNextTab);
            document.getElementById('additional-prev')?.addEventListener('click', goToPreviousTab);
            
            // Preview button
            document.getElementById('preview-order')?.addEventListener('click', function() {
                if (window.showOrderPreview) {
                    window.showOrderPreview();
                } else {
                    alert('Order preview function not loaded. Please refresh the page.');
                }
            });

            console.log('Tab navigation initialized');
        });
    </script>

    <script>
    window.availableClients = <?php echo json_encode(array_map(function($client) {
        return [
            'id' => $client['Client_id'],
            'name' => $client['Full_company_name']
        ];
    }, $availableClients)); ?>;
    console.log('Available clients:', window.availableClients);
    </script>

    <script src="../js/add_order.js"></script>
    <script src="../js/order_preview.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html> 