<?php
session_start();
require_once '../config/db_connect.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    header('Location: ../index.php');
    exit();
}

$userRole = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление партнерами - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/manage_partners.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>
        
        <div id="content">
            <?php 
            $page_title = 'Управление партнерами';
            include '../elements/admin_navbar.php'; 
            ?>
            
            <div class="content-wrapper">
                <div class="mc-content-wrapper">
                    <div class="page-header">
                        <h2>Управление партнерами</h2>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="filters-section">
                        <div class="filters-left">
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Поиск партнеров...">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="filters">
                            <select id="statusFilter">
                                <option value="">Все статусы</option>
                                <?php
                                $stmt = $pdo->query("SELECT Status_id, Status_name FROM list_partners_status ORDER BY Status_name");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='" . htmlspecialchars($row['Status_id']) . "'>" . 
                                         htmlspecialchars($row['Status_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <select id="bankFilter">
                                <option value="">Все банки</option>
                                <?php
                                // Get unique bank names from all partner tables
                                $stmt = $pdo->query("
                                    SELECT DISTINCT Bank_name FROM (
                                        SELECT Bank_name FROM Clients
                                        UNION
                                        SELECT Bank_name FROM Couriers
                                        UNION
                                        SELECT Bank_name FROM Agents
                                    ) as banks WHERE Bank_name IS NOT NULL ORDER BY Bank_name
                                ");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='" . htmlspecialchars($row['Bank_name']) . "'>" . 
                                         htmlspecialchars($row['Bank_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="date-range">
                                <input type="date" id="dateFrom" placeholder="От">
                                <input type="date" id="dateTo" placeholder="До">
                            </div>
                            </div>
                        </div>
                        <div class="filters-right">
                            <button id="columnSettingsBtn" class="btn-secondary" title="Настройки колонок">
                                <i class="fas fa-columns"></i>
                            </button>
                            <button id="downloadPartners" class="btn-secondary" title="Скачать данные">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="tabs">
                        <div class="tabs-left">
                            <button class="tab-btn active" data-tab="client">Клиенты</button>
                            <button class="tab-btn" data-tab="courier">Перевозчики</button>
                            <button class="tab-btn" data-tab="agent">Подрядчики</button>
                        </div>
                        <div class="tabs-right">
                            <button class="tab-scroll-btn" id="tabScrollLeft" title="Прокрутить таблицу влево">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="tab-scroll-btn" id="tabScrollRight" title="Прокрутить таблицу вправо">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Partners Table -->
                    <div class="table-container" id="tableContainer">
                        <div class="scroll-indicator" id="scrollIndicator">Прокрутка...</div>
                        <table id="partnersTable">
                            <thead>
                                <tr id="tableHeaders">
                                    <!-- Headers will be generated by JavaScript -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Column Settings Modal -->
    <div id="columnSettingsModal" class="modal">
        <div class="modal-content">
            <h3>Видимость колонок</h3>
            <div id="columnCheckboxes">
                <!-- Checkboxes will be added via JavaScript -->
            </div>
            <div class="modal-buttons">
                <button id="applyColumnSettings" class="btn-primary">Применить</button>
                <button id="cancelColumnSettings" class="btn-secondary">Отмена</button>
            </div>
        </div>
    </div>

    <!-- Password Confirmation Modal -->
    <div id="passwordModal" class="mc-modal">
        <div class="mc-modal-content">
            <span class="mc-close" id="passwordModalClose">&times;</span>
            <h2>Введите пароль для подтверждения действия</h2>
            <form id="passwordForm">
                <input type="hidden" id="actionType" name="action_type">
                <input type="password" id="actionPassword" name="action_password" placeholder="Пароль" required>
                <button type="submit" class="mc-btn-submit">Подтвердить</button>
            </form>
            <div id="passwordError" style="color: red; display: none; margin-top: 10px;"></div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Редактировать партнера</h3>
            <form id="editForm" class="edit-form">
                <input type="hidden" id="editId">
                <input type="hidden" id="editType">
                
                <div class="form-row">
                    <div class="form-column">
                        <h4>Основная информация</h4>
                        <div class="form-group">
                            <label for="editCompanyType">Тип компании</label>
                            <select id="editCompanyType" name="Company_type" required>
                                <option value="ООО">ООО</option>
                                <option value="ИП">ИП</option>
                                <option value="Самозанятый">Самозанятый</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editFullName">Полное название компании</label>
                            <input type="text" id="editFullName" name="Full_Company_name" required>
                        </div>

                        <div class="form-group">
                            <label for="editShortName">Сокращенное название компании</label>
                            <input type="text" id="editShortName" name="Short_Company_name" required>
                        </div>

                        <div class="form-group">
                            <label for="editStatus">Статус</label>
                            <div class="status-select-wrapper">
                                <select id="editStatus" name="Status" required>
                                    <?php
                                    $stmt = $pdo->query("SELECT Status_id, Status_name, Status_color FROM list_partners_status ORDER BY Status_name");
                                    while ($row = $stmt->fetch()) {
                                        echo "<option value='" . htmlspecialchars($row['Status_id']) . "' 
                                            data-color='" . htmlspecialchars($row['Status_color']) . "'>" . 
                                            htmlspecialchars($row['Status_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="editINN">ИНН</label>
                            <input type="text" id="editINN" name="INN" required>
                        </div>

                        <div class="form-group">
                            <label for="editKPP">КПП</label>
                            <input type="text" id="editKPP" name="KPP" required>
                        </div>

                        <div class="form-group">
                            <label for="editOGRN">ОГРН</label>
                            <input type="text" id="editOGRN" name="OGRN" required>
                        </div>
                    </div>

                    <div class="form-column">
                        <h4>Адрес</h4>
                        <div class="form-group">
                            <label for="editPhysicalAddress">Фактический адрес</label>
                            <input type="text" id="editPhysicalAddress" name="Physical_address" required>
                        </div>

                        <div class="form-group">
                            <label for="editLegalAddress">Юридический адрес</label>
                            <input type="text" id="editLegalAddress" name="Legal_address" required>
                        </div>

                        <h4>Банковские реквизиты</h4>
                        <div class="form-group">
                            <label for="editBankName">Наименование банка</label>
                            <input type="text" id="editBankName" name="Bank_name" required>
                        </div>

                        <div class="form-group">
                            <label for="editBIK">БИК</label>
                            <input type="text" id="editBIK" name="BIK" required>
                        </div>

                        <div class="form-group">
                            <label for="editSettlementAccount">Расчетный счет</label>
                            <input type="text" id="editSettlementAccount" name="Settlement_account" required>
                        </div>

                        <div class="form-group">
                            <label for="editCorrespondentAccount">Корреспондентский счет</label>
                            <input type="text" id="editCorrespondentAccount" name="Correspondent_account" required>
                        </div>
                    </div>

                    <div class="form-column">
                        <h4>Контактная информация</h4>
                        <div class="form-group">
                            <label for="editContactPerson">Контактное лицо</label>
                            <input type="text" id="editContactPerson" name="Contact_person" required>
                        </div>

                        <div class="form-group">
                            <label for="editContactPersonPosition">Должность контактного лица</label>
                            <input type="text" id="editContactPersonPosition" name="Contact_person_position" required>
                        </div>

                        <div class="form-group">
                            <label for="editContactPersonPhone">Телефон контактного лица</label>
                            <input type="text" id="editContactPersonPhone" name="Contact_person_phone" required>
                        </div>

                        <div class="form-group">
                            <label for="editContactPersonEmail">Email контактного лица</label>
                            <input type="email" id="editContactPersonEmail" name="Contact_person_email" required>
                        </div>

                        <div class="form-group">
                            <label for="editHeadPosition">Должность руководителя</label>
                            <input type="text" id="editHeadPosition" name="Head_position" required>
                        </div>

                        <div class="form-group">
                            <label for="editHeadName">Имя руководителя</label>
                            <input type="text" id="editHeadName" name="Head_name" required>
                        </div>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="submit" class="btn-primary" title="Сохранить изменения">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('editModal').style.display='none'">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3>Изменить статус партнера</h3>
            <div class="status-options">
                <?php
                $stmt = $pdo->query("SELECT Status_id, Status_name, Status_color FROM list_partners_status ORDER BY Status_name");
                while ($status = $stmt->fetch()) {
                    echo "<button class='status-option' data-status-id='{$status['Status_id']}' 
                        style='border-color: {$status['Status_color']}'>
                        <span class='status-dot' style='background-color: {$status['Status_color']}'></span>
                        {$status['Status_name']}
                    </button>";
                }
                ?>
            </div>
            <div class="modal-buttons">
                <button id="cancelStatusChange" class="btn-secondary">Отмена</button>
            </div>
        </div>
    </div>

    <script src="../js/manage_partners.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html> 