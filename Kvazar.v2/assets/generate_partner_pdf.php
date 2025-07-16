<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$partnerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partnerType = isset($_GET['type']) ? $_GET['type'] : '';

if (!$partnerId || !$partnerType) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing partner ID or type']);
    exit();
}

try {
    $table = '';
    $idColumn = '';
    
    switch($partnerType) {
        case 'client':
            $table = 'Clients';
            $idColumn = 'Client_id';
            break;
        case 'courier':
            $table = 'Couriers';
            $idColumn = 'Courier_id';
            break;
        case 'agent':
            $table = 'Agents';
            $idColumn = 'Agent_id';
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid partner type']);
            exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $idColumn = ?");
    $stmt->execute([$partnerId]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        http_response_code(404);
        echo json_encode(['error' => 'Partner not found']);
        exit();
    }
    
    // Create PDF with UTF-8 support
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Kvazar System');
    $pdf->SetAuthor('Kvazar Admin');
    $pdf->SetTitle('Partner Information - ' . $partner['Short_Company_name']);
    $pdf->SetSubject('Partner Information Card');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'Kvazar System', 'Partner Information Card', array(102, 126, 234), array(118, 75, 162));
    $pdf->setHeaderFont(Array('dejavusans', '', 12));
    $pdf->setFooterFont(Array('dejavusans', '', 8));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont('courier');
    
    // Set margins
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font for UTF-8 support
    $pdf->SetFont('dejavusans', '', 10);
    
    // Partner type badge
    $typeLabels = [
        'client' => 'Клиент',
        'courier' => 'Курьер',
        'agent' => 'Агент'
    ];
    $partnerTypeLabel = $typeLabels[$partnerType] ?? $partnerType;
    
    // Header
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, 'Информация о партнере #' . $partner[$idColumn], 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->Cell(0, 8, 'Тип: ' . $partnerTypeLabel, 0, 1, 'L');
    $pdf->Ln(5);
    
    // Company Information Section
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Информация о компании', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 10);
    
    $companyData = [
        'Тип компании' => $partner['Company_type'],
        'Полное название' => $partner['Full_Company_name'],
        'Краткое название' => $partner['Short_Company_name'],
        'ИНН' => $partner['INN'],
        'КПП' => $partner['KPP'],
        'ОГРН' => $partner['OGRN']
    ];
    
    foreach ($companyData as $label => $value) {
        $pdf->Cell(50, 6, $label . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, $value ?: '—', 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Addresses Section
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Адреса', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 10);
    
    $pdf->Cell(50, 6, 'Физический адрес:', 0, 0, 'L');
    $pdf->MultiCell(0, 6, $partner['Physical_address'] ?: '—', 0, 'L');
    $pdf->Cell(50, 6, 'Юридический адрес:', 0, 0, 'L');
    $pdf->MultiCell(0, 6, $partner['Legal_address'] ?: '—', 0, 'L');
    $pdf->Ln(5);
    
    // Bank Information Section
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Банковская информация', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 10);
    
    $bankData = [
        'Название банка' => $partner['Bank_name'],
        'БИК' => $partner['BIK'],
        'Расчетный счет' => $partner['Settlement_account'],
        'Корр. счет' => $partner['Correspondent_account']
    ];
    
    foreach ($bankData as $label => $value) {
        $pdf->Cell(50, 6, $label . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, $value ?: '—', 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Contact Information Section
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Контактная информация', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 10);
    
    $contactData = [
        'Контактное лицо' => $partner['Contact_person'],
        'Должность' => $partner['Contact_person_position'],
        'Телефон' => $partner['Contact_person_phone'],
        'Email' => $partner['Contact_person_email']
    ];
    
    foreach ($contactData as $label => $value) {
        $pdf->Cell(50, 6, $label . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, $value ?: '—', 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Head Information Section
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Руководитель', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 10);
    
    $headData = [
        'Должность' => $partner['Head_position'],
        'ФИО' => $partner['Head_name']
    ];
    
    foreach ($headData as $label => $value) {
        $pdf->Cell(50, 6, $label . ':', 0, 0, 'L');
        $pdf->Cell(0, 6, $value ?: '—', 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Dates Section
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Даты', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 10);
    
    $createdDate = new DateTime($partner['created_at']);
    $updatedDate = new DateTime($partner['updated_at']);
    
    $pdf->Cell(50, 6, 'Дата создания:', 0, 0, 'L');
    $pdf->Cell(0, 6, $createdDate->format('d.m.Y H:i'), 0, 1, 'L');
    $pdf->Cell(50, 6, 'Последнее обновление:', 0, 0, 'L');
    $pdf->Cell(0, 6, $updatedDate->format('d.m.Y H:i'), 0, 1, 'L');
    
    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('dejavusans', 'I', 8);
    $pdf->Cell(0, 6, 'Сгенерировано: ' . date('d.m.Y H:i:s'), 0, 1, 'C');
    
    // Output PDF
    $filename = 'partner_' . $partnerType . '_' . $partner[$idColumn] . '_' . date('Y-m-d') . '.pdf';
    
    // Log the PDF download action
    try {
        $logStmt = $pdo->prepare("INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) VALUES (?, 'Скачивание PDF', ?, ?, ?)");
        $description = "Скачан PDF файл карточки партнера: " . $partner['Short_Company_name'] . " (ID: " . $partner[$idColumn] . ", Тип: " . $partnerTypeLabel . ")";
        $logStmt->execute([$_SESSION['user_id'], $table, $description, $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        // Continue with PDF generation even if logging fails
        error_log('Failed to log PDF download: ' . $e->getMessage());
    }
    
    $pdf->Output($filename, 'D');
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'PDF generation error']);
}
?> 