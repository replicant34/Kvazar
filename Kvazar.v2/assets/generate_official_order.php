<?php
// Start output buffering to catch any unwanted output
ob_start();

session_start();
require_once '../config/db_connect.php';
require_once '../vendor/autoload.php';

// Set error reporting to catch all errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1); // Log errors instead

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid request data']);
        exit();
    }
    
    $orderId = intval($input['order_id'] ?? 0);
    $documentType = $input['document_type'] ?? 'official_order';
    $exportFormat = $input['export_format'] ?? 'pdf';
    $includeSignatures = $input['include_signatures'] ?? true;
    $includeStamps = $input['include_stamps'] ?? true;
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        exit();
    }
    
        // Get complete order data
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Full_Company_name as client_name,
            c.Legal_address as client_address,
            c.Contact_person as client_contact,
            c.Contact_person_phone as client_phone,
            
            courier.Full_Company_name as courier_name,
            courier.Legal_address as courier_address,
            courier.Contact_person as courier_contact,
            courier.Contact_person_phone as courier_phone,
            
            contract.Contract_number,
            contract.Contract_date,
            
            lcn.cargo_name,
            
            curr.currency_name
        FROM Orders o
        LEFT JOIN Clients c ON o.Client_id = c.Client_id
        LEFT JOIN Couriers courier ON o.Courier_id = courier.Courier_id
        LEFT JOIN Client_contracts contract ON o.Contract_id = contract.Contract_id
        LEFT JOIN list_cargo_name lcn ON o.Cargo_type = lcn.id
        LEFT JOIN list_currency curr ON o.Currency_id = curr.id
        WHERE o.Order_id = ?
    ");
    
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit();
    }
    
    // Get route points
    $routeStmt = $pdo->prepare("
        SELECT p.* FROM Points p WHERE p.Order_id = ? ORDER BY p.Position
    ");
    $routeStmt->execute([$orderId]);
    $routePoints = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Clean any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Generate document
    if ($exportFormat === 'pdf') {
        $result = generateOfficialOrderPDF($order, $routePoints, $documentType, $includeSignatures, $includeStamps);
    } else {
        $result = generateOfficialOrderWord($order, $routePoints, $documentType, $includeSignatures, $includeStamps);
    }
    
    if ($result['success']) {
        // Save generation record to database
        $stmt = $pdo->prepare("
            INSERT INTO Documents (Order_id, File_path, Original_filename, File_type, File_size, Uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Get file size and determine file type
        $fileSize = file_exists($result['filepath']) ? filesize($result['filepath']) : 0;
        $fileType = $exportFormat === 'pdf' ? 'pdf' : 'docx';
        
        $stmt->execute([
            $orderId,
            $result['relative_path'],
            $result['filename'],
            $fileType,
            $fileSize,
            $_SESSION['user_id']
        ]);
        
        // Clean output and send success response
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Документ успешно создан',
            'filename' => $result['filename'],
            'download_url' => 'assets/download_official_order.php?order_id=' . $orderId . '&format=' . $exportFormat . '&type=' . $documentType
        ]);
    } else {
        // Clean output and send error response
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка при создании документа: ' . $result['error']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error generating official order: " . $e->getMessage());
    
    // Clean output and send error response
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred: ' . $e->getMessage()
    ]);
}

// End output buffering and flush
ob_end_flush();

function generateOfficialOrderPDF($order, $routePoints, $documentType, $includeSignatures, $includeStamps) {
    require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    try {
        // Clear any output that might have been sent
        if (ob_get_level()) {
            ob_clean();
        }
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('KVAZAR LOGISTICS');
        $pdf->SetAuthor('KVAZAR LOGISTICS');
        $pdf->SetTitle('Официальный заказ #' . $order['display_order_number']);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('dejavusans', '', 12);
        
        // Document header
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, getDocumentTitle($documentType), 0, 1, 'C');
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 8, '№ ' . ($order['display_order_number'] ?? $order['Order_id']) . ' от ' . formatDate($order['Order_date']), 0, 1, 'C');
        
        if ($order['Contract_number']) {
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 6, 'по Договору № ' . $order['Contract_number'] . ' от ' . formatDate($order['Contract_date']), 0, 1, 'C');
        }
        
        $pdf->Ln(10);
        
        // Services table
        $html = '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #f0f0f0;">
                <th style="width: 40%; font-weight: bold;">Услуги</th>
                <th style="width: 60%; font-weight: bold;">Автоперевозки по РФ</th>
            </tr>
            <tr>
                <td>Даты загрузки-разгрузки</td>
                <td>' . formatDate($order['Order_date']) . '</td>
            </tr>
            <tr>
                <td>Маршрут перевозки</td>
                <td>' . generateRouteString($routePoints) . '</td>
            </tr>
            <tr>
                <td>Стоимость перевозки</td>
                <td>' . number_format($order['Order_total'], 2) . ' ' . ($order['currency_name'] ?? '') . '</td>
            </tr>
        </table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(10);
        
        // Cargo table  
        $cargoHtml = '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">
            <tr style="background-color: #f0f0f0;">
                <th style="width: 40%; font-weight: bold;">Груз (характер груза, наименование)</th>
                <th style="width: 60%; font-weight: bold;">Продукция КОЛДОСТ</th>
            </tr>
            <tr>
                <td>Вес груза, габариты, вид упаковки, количество мест</td>
                <td>' . ($order['Weight'] ? $order['Weight'] . ' ' . ($order['Weight_unit'] ?? 'кг') : 'Не указано') . 
                      ($order['Quantity'] ? ', ' . $order['Quantity'] . ' мест' : '') . '</td>
            </tr>
            <tr>
                <td>Стоимость груза</td>
                <td>' . number_format($order['Cargo_price'] ?? 0, 2) . ' ' . ($order['currency_name'] ?? '') . '</td>
            </tr>
        </table>';
        
        $pdf->writeHTML($cargoHtml, true, false, true, false, '');
        $pdf->Ln(10);
        
        // Company sections
        generateCompanySection($pdf, 'Отправитель 1 (наименование)', $order, 'client');
        generateCompanySection($pdf, 'Получатель 1 (наименование)', $order, 'courier');
        
        // Signatures if requested
        if ($includeSignatures) {
            $pdf->Ln(20);
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 8, 'Подписи Сторон:', 0, 1, 'L');
            $pdf->Ln(10);
            
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(90, 6, 'Заказчик:', 0, 0, 'L');
            $pdf->Cell(90, 6, 'Исполнитель:', 0, 1, 'L');
            $pdf->Cell(90, 6, 'ООО «БиоФАРМАХОЛДИНГ»', 0, 0, 'L');
            $pdf->Cell(90, 6, 'ООО «ТК КВАЗАР»', 0, 1, 'L');
            $pdf->Ln(15);
            
            $pdf->Cell(90, 6, '____________/Павлюченков С.В./', 0, 0, 'L');
            $pdf->Cell(90, 6, '____________/Сафонов В.В./', 0, 1, 'L');
            
            if ($includeStamps) {
                $pdf->Cell(90, 6, 'МП', 0, 0, 'L');
                $pdf->Cell(90, 6, 'МП', 0, 1, 'L');
            }
        }
        
        // Generate filename and save
        $filename = 'official_order_' . $order['Order_id'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $uploadDir = __DIR__ . '/../uploads/order_files/';
        $filepath = $uploadDir . $filename;
        
        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Save PDF with proper output handling
        ob_start();
        try {
            $pdf->Output($filepath, 'F');
            
            // Check if file was created and has content
            if (!file_exists($filepath)) {
                throw new Exception("PDF file was not created");
            }
            
            $fileSize = filesize($filepath);
            if ($fileSize === 0) {
                throw new Exception("PDF file is empty");
            }
            
            // Check if it's a valid PDF by reading first few bytes
            $handle = fopen($filepath, 'r');
            $header = fread($handle, 4);
            fclose($handle);
            
            if ($header !== '%PDF') {
                // File doesn't start with PDF header, likely corrupted
                $content = file_get_contents($filepath);
                error_log("Invalid PDF content starts with: " . substr($content, 0, 100));
                throw new Exception("Generated file is not a valid PDF");
            }
            
        } catch (Exception $e) {
            $output = ob_get_contents();
            if ($output) {
                error_log("TCPDF output captured: " . $output);
            }
            throw $e;
        } finally {
            ob_end_clean();
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'relative_path' => 'uploads/order_files/' . $filename
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'PDF generation failed: ' . $e->getMessage()
        ];
    }
}

function generateOfficialOrderWord($order, $routePoints, $documentType, $includeSignatures, $includeStamps) {
    
    try {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        
        // Document title
        $section->addText(getDocumentTitle($documentType), ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $section->addText('№ ' . ($order['display_order_number'] ?? $order['Order_id']) . ' от ' . formatDate($order['Order_date']), ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        
        if ($order['Contract_number']) {
            $section->addText('по Договору № ' . $order['Contract_number'] . ' от ' . formatDate($order['Contract_date']), ['size' => 12], ['alignment' => 'center']);
        }
        
        $section->addTextBreak(2);
        
        // Add table content
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(4000)->addText('Услуги', ['bold' => true]);
        $table->addCell(6000)->addText('Автоперевозки по РФ', ['bold' => true]);
        
        $table->addRow();
        $table->addCell(4000)->addText('Даты загрузки-разгрузки');
        $table->addCell(6000)->addText(formatDate($order['Order_date']));
        
        $table->addRow();
        $table->addCell(4000)->addText('Маршрут перевозки');
        $table->addCell(6000)->addText(generateRouteString($routePoints));
        
                 $table->addRow();
         $table->addCell(4000)->addText('Стоимость перевозки');
         $table->addCell(6000)->addText(number_format($order['Order_total'], 2) . ' ' . ($order['currency_name'] ?? ''));
         
         $section->addTextBreak(2);
         
         // Cargo information table
         $cargoTable = $section->addTable();
         $cargoTable->addRow();
         $cargoTable->addCell(4000)->addText('Груз (характер груза, наименование)', ['bold' => true]);
         $cargoTable->addCell(6000)->addText('Продукция КОЛДОСТ', ['bold' => true]);
         
         $cargoTable->addRow();
         $cargoTable->addCell(4000)->addText('Вес груза, габариты, вид упаковки, количество мест');
         $cargoTable->addCell(6000)->addText(($order['Weight'] ? $order['Weight'] . ' ' . ($order['Weight_unit'] ?? 'кг') : 'Не указано') . 
                                           ($order['Quantity'] ? ', ' . $order['Quantity'] . ' мест' : ''));
         
         $cargoTable->addRow();
         $cargoTable->addCell(4000)->addText('Стоимость груза');
         $cargoTable->addCell(6000)->addText(number_format($order['Cargo_price'] ?? 0, 2) . ' ' . ($order['currency_name'] ?? ''));
        
        $section->addTextBreak(2);
        
        // Signatures
        if ($includeSignatures) {
            $section->addText('Подписи Сторон:', ['bold' => true]);
            $section->addTextBreak(1);
            
            $sigTable = $section->addTable();
            $sigTable->addRow();
            $sigTable->addCell(5000)->addText('Заказчик: ООО «БиоФАРМАХОЛДИНГ»');
            $sigTable->addCell(5000)->addText('Исполнитель: ООО «ТК КВАЗАР»');
            
            $sigTable->addRow();
            $sigTable->addCell(5000)->addText('____________/Павлюченков С.В./');
            $sigTable->addCell(5000)->addText('____________/Сафонов В.В./');
            
            if ($includeStamps) {
                $sigTable->addRow();
                $sigTable->addCell(5000)->addText('МП');
                $sigTable->addCell(5000)->addText('МП');
            }
        }
        
        // Generate filename and save
        $filename = 'official_order_' . $order['Order_id'] . '_' . date('Y-m-d_H-i-s') . '.docx';
        $uploadDir = __DIR__ . '/../uploads/order_files/';
        $filepath = $uploadDir . $filename;
        
        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Save Word document
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filepath);
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'relative_path' => 'uploads/order_files/' . $filename
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Word generation failed: ' . $e->getMessage()
        ];
    }
}

function generateCompanySection($pdf, $title, $order, $type) {
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->Cell(0, 8, $title, 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 12);
    
    $prefix = $type === 'client' ? 'client' : 'courier';
    
    $html = '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%;">
        <tr>
            <td style="width: 30%; font-weight: bold;">Адрес отправителя</td>
            <td style="width: 70%;">' . htmlspecialchars($order[$prefix . '_address'] ?? 'Не указано') . '</td>
        </tr>
        <tr>
            <td style="width: 30%; font-weight: bold;">Время и дата разгрузки</td>
            <td style="width: 70%;">' . formatDate($order['Order_date']) . ', к 09:00</td>
        </tr>
        <tr>
            <td style="width: 30%; font-weight: bold;">Контактное лицо отправителя</td>
            <td style="width: 70%;">' . htmlspecialchars($order[$prefix . '_contact'] ?? 'Не указано') . '</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);
}

function generateRouteString($routePoints) {
    if (empty($routePoints)) {
        return 'Маршрут не указан';
    }
    
    $route = [];
    foreach ($routePoints as $point) {
        $route[] = $point['Address_Loading'] ?? 'Не указано';
    }
    
    return implode(' - ', $route);
}

function getDocumentTitle($documentType) {
    switch ($documentType) {
        case 'transport_order':
            return 'ЗАЯВКА НА ТРАНСПОРТИРОВКУ';
        case 'service_agreement':
            return 'СОГЛАШЕНИЕ ОБ ОКАЗАНИИ УСЛУГ';
        case 'official_order':
        default:
            return 'ЗАЯВКА № 920 от «29» ноября 2024 г.';
    }
}

function formatDate($dateString) {
    if (!$dateString) return '';
    try {
        $date = new DateTime($dateString);
        return $date->format('d.m.Y');
    } catch (Exception $e) {
        return $dateString;
    }
} 