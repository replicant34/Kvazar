<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo'])) {
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
    <title>Новый контракт - Kvazar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/add_contract.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <!-- Add Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../elements/admin_sidebar.php'; ?>
        
        <div id="content">
            <?php 
            $page_title = 'Новый контракт';
            include '../elements/admin_navbar.php'; 
            ?>
            
            <div class="form-container">
                <div class="form-header">
                    <h1>Новый контракт</h1>
                </div>

                <form id="contractForm" method="POST" enctype="multipart/form-data" class="standard-form">
                    <!-- Entity Type Selection -->
                    <div class="input-group">
                        <label for="entityType">Тип организации</label>
                        <select id="entityType" name="entity_type" required>
                            <option value="">Выберите тип организации</option>
                            <option value="client">Клиент</option>
                            <option value="courier">Перевозчик</option>
                            <option value="agent">Подрядчик</option>
                        </select>
                    </div>

                    <!-- Entity Selection -->
                    <div class="input-group">
                        <label for="entityId">Выберите компанию</label>
                        <select id="entityId" name="entity_id" required disabled>
                            <option value="">Сначала выберите тип организации</option>
                        </select>
                    </div>

                    <!-- Contract Type -->
                    <div class="input-group">
                        <label for="contractType">Тип контракта</label>
                        <select id="contractType" name="contract_type" required disabled>
                            <option value="">Выберите тип контракта</option>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT Type_id, Type_name FROM list_contract_type ORDER BY Type_name");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='" . htmlspecialchars($row['Type_id']) . "'>" . 
                                         htmlspecialchars($row['Type_name']) . "</option>";
                                }
                            } catch (PDOException $e) {
                                error_log("Error fetching contract types: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Contract Number -->
                    <div class="input-group">
                        <label for="contractNumber">Номер контракта</label>
                        <div class="input-with-button">
                            <input type="text" id="contractNumber" name="contract_number" required disabled>
                            <button type="button" id="generateNumber" class="btn-secondary" disabled>
                                <i class="fas fa-sync-alt"></i> Сгенерировать
                            </button>
                        </div>
                        <div id="contractNumberMessage" class="message"></div>
                    </div>

                    <!-- Contract Date -->
                    <div class="input-group">
                        <label for="contractDate">Дата контракта</label>
                        <input type="text" id="contractDate" name="contract_date" required disabled>
                    </div>

                    <!-- Contract Status -->
                    <div class="input-group">
                        <label for="contractStatus">Статус контракта</label>
                        <select id="contractStatus" name="contract_status" required>
                            <option value="">Выберите статус</option>
                            <?php
                            $stmt = $pdo->query("SELECT Status_name, Status_color FROM list_contract_status ORDER BY Status_name");
                            while ($row = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($row['Status_name']) . "' " .
                                     "data-color='" . htmlspecialchars($row['Status_color']) . "'>" . 
                                     htmlspecialchars($row['Status_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Contract File Upload -->
                    <div class="input-group">
                        <label for="contractFile">Файл контракта</label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="contractFile" name="contract_file" accept=".pdf,.doc,.docx" disabled>
                            <button type="button" class="file-upload-btn" id="fileUploadBtn" disabled>
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Выберите файл контракта</span>
                            </button>
                        </div>
                        <div class="file-name-display" id="fileNameDisplay">
                            <div class="file-info">
                                <div>
                                    <span class="file-name" id="selectedFileName"></span>
                                    <span class="file-size" id="selectedFileSize"></span>
                                </div>
                                <button type="button" class="remove-file" id="removeFileBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <small class="help-text">Поддерживаемые форматы: PDF, DOC, DOCX (максимальный размер: 10MB)</small>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-primary">Создать контракт</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Message Container for styled notifications -->
    <div id="message-container" class="message-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/add_contract.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html> 