<?php
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/../config/db_connect.php';

class OrderPDFGenerator {
    private $pdo;
    private $pdf;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializePDF();
    }
    
    private function initializePDF() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('Kvazar Logistics');
        $this->pdf->SetTitle('Order Details');
        $this->pdf->SetSubject('Order Information');
        
        // Set default header data
        $this->pdf->SetHeaderData('', 0, 'KVAZAR LOGISTICS', 'Order Details Document');
        
        // Set header and footer fonts
        $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Set font
        $this->pdf->SetFont('dejavusans', '', 12);
    }
    
    public function generateOrderPDF($orderData, $orderId) {
        try {
            // Start output buffering to catch any TCPDF output
            ob_start();
            
            $this->pdf->AddPage();
            
            // Title
            $this->pdf->SetFont('dejavusans', 'B', 16);
            $this->pdf->Cell(0, 10, 'ДЕТАЛИ ЗАКАЗА', 0, 1, 'C');
            $this->pdf->Ln(5);
            
            // Order Information Section
            $this->addSection('ОСНОВНАЯ ИНФОРМАЦИЯ', [
                'Номер заказа' => $orderData['order_number'] ?? '',
                'Дата заказа' => $this->formatDate($orderData['order_date'] ?? ''),
                'Клиент' => $orderData['client_name'] ?? '',
                'Подрядчик' => $orderData['contractor_name'] ?? '',
                'Договор' => $this->formatContract($orderData['contract_number'] ?? '', $orderData['contract_date'] ?? ''),
                'Тип перевозки' => $orderData['shipping_type'] ?? ''
            ]);
            
            // Cargo Information Section
            $this->addSection('ИНФОРМАЦИЯ О ГРУЗЕ', [
                'Наименование груза' => $orderData['cargo_name'] ?? '',
                'Тип транспорта' => $orderData['transport_type'] ?? '',
                'Вес груза' => $this->formatWeight($orderData['cargo_weight'] ?? '', $orderData['weight_unit'] ?? ''),
                'Объем груза' => $this->formatVolume($orderData['cargo_volume'] ?? ''),
                'Габариты (Д×Ш×В)' => $this->formatDimensions($orderData['length'] ?? '', $orderData['width'] ?? '', $orderData['height'] ?? ''),
                'Количество мест' => $orderData['cargo_quantity'] ?? '',
                'Тип погрузки' => $orderData['loading_type'] ?? '',
                'Тип упаковки' => $orderData['packaging_type'] ?? ''
            ]);
            
            // Temperature Section (if applicable)
            if (!empty($orderData['min_temperature']) || !empty($orderData['max_temperature']) || !empty($orderData['temp_print_list'])) {
                $this->addSection('ТЕМПЕРАТУРНЫЙ РЕЖИМ', [
                    'Мин. температура' => !empty($orderData['min_temperature']) ? $orderData['min_temperature'] . '°C' : '—',
                    'Макс. температура' => !empty($orderData['max_temperature']) ? $orderData['max_temperature'] . '°C' : '—',
                    'Температурный лист' => $orderData['temp_print_list'] ?? '—'
                ]);
            }
            
            // Route Points Section
            if (!empty($orderData['route_points'])) {
                $this->addRouteSection($orderData['route_points']);
            }
            
            // Cost Information Section
            $this->addSection('СТОИМОСТЬ', [
                'Стоимость груза' => $this->formatPrice($orderData['cargo_price'] ?? '', $orderData['currency'] ?? ''),
                'Валюта' => $orderData['currency'] ?? '',
                'Коэффициент' => $orderData['rate'] ?? '',
                'Страхование' => $this->formatPrice($orderData['total_insurance'] ?? '', $orderData['currency'] ?? ''),
                'Транспортировка' => $this->formatPrice($orderData['transport_total'] ?? '', $orderData['currency'] ?? '')
            ]);
            
            // Extra Services Section (if applicable)
            if (!empty($orderData['extra_services'])) {
                $this->addExtraServicesSection($orderData['extra_services']);
            }
            
            // Total Cost
            $this->pdf->SetFont('dejavusans', 'B', 14);
            $this->pdf->SetFillColor(40, 167, 69);
            $this->pdf->SetTextColor(255, 255, 255);
            $this->pdf->Cell(0, 12, 'ОБЩАЯ СТОИМОСТЬ УСЛУГ: ' . ($orderData['order_total'] ?? '0') . ' ' . ($orderData['currency'] ?? ''), 0, 1, 'C', true);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->Ln(5);
            
            // Additional Notes (if applicable)
            if (!empty($orderData['order_notes'])) {
                $this->addSection('ДОПОЛНИТЕЛЬНЫЕ ПРИМЕЧАНИЯ', [], $orderData['order_notes']);
            }
            
            // Generate filename and save
            $filename = 'order_' . $orderId . '_' . date('Y-m-d_H-i-s') . '.pdf';
            $uploadDir = __DIR__ . '/../uploads/order_files/';
            $filepath = $uploadDir . $filename;
            
            // Ensure directory exists and is writable
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Unable to create directory: $uploadDir");
                }
            }
            
            if (!is_writable($uploadDir)) {
                throw new Exception("Directory not writable: $uploadDir");
            }
            
            // Save PDF to file (clean any output buffer first)
            if (ob_get_level()) {
                ob_clean();
            }
            
            // Capture any TCPDF output/errors
            ob_start();
            
            try {
                $this->pdf->Output($filepath, 'F');
                
                // Check if file was actually created
                if (!file_exists($filepath)) {
                    $tcpdfOutput = ob_get_contents();
                    ob_end_clean();
                    throw new Exception("PDF file was not created. TCPDF output: " . $tcpdfOutput);
                }
                
                // Clear any TCPDF output
                ob_end_clean();
                
            } catch (Exception $e) {
                $tcpdfOutput = ob_get_contents();
                ob_end_clean();
                throw new Exception("TCPDF error: " . $e->getMessage() . " Output: " . $tcpdfOutput);
            }
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'relative_path' => 'uploads/order_files/' . $filename
            ];
            
        } catch (Exception $e) {
            // Clean any output buffer on error
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            return [
                'success' => false,
                'error' => 'PDF generation failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function addSection($title, $data, $notes = null) {
        // Section title
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->pdf->Ln(3);
        
        // Section data
        $this->pdf->SetFont('dejavusans', '', 11);
        foreach ($data as $label => $value) {
            if (!empty($value) && $value !== '—') {
                $this->pdf->SetFont('dejavusans', 'B', 11);
                $this->pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
                $this->pdf->SetFont('dejavusans', '', 11);
                $this->pdf->Cell(0, 6, $value, 0, 1, 'L');
            }
        }
        
        // Notes if provided
        if ($notes) {
            $this->pdf->SetFont('dejavusans', '', 11);
            $this->pdf->MultiCell(0, 6, $notes, 0, 'L');
        }
        
        $this->pdf->Ln(5);
    }
    
    private function addRouteSection($routePoints) {
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 8, 'МАРШРУТ', 0, 1, 'L', true);
        $this->pdf->Ln(3);
        
        foreach ($routePoints as $index => $point) {
            $pointNum = $index + 1;
            $this->pdf->SetFont('dejavusans', 'B', 12);
            $this->pdf->Cell(0, 6, "Пункт {$pointNum}: " . ($point['action_type'] ?? 'Не указано'), 0, 1, 'L');
            
            $this->pdf->SetFont('dejavusans', '', 11);
            $dateTime = $this->formatDateTime($point['date'] ?? '', $point['time'] ?? '');
            $this->pdf->Cell(60, 5, 'Дата и время:', 0, 0, 'L');
            $this->pdf->Cell(0, 5, $dateTime, 0, 1, 'L');
            
            if (!empty($point['address'])) {
                $this->pdf->Cell(60, 5, 'Адрес:', 0, 0, 'L');
                $this->pdf->Cell(0, 5, $point['address'], 0, 1, 'L');
            }
            
            if (!empty($point['company_name'])) {
                $this->pdf->Cell(60, 5, 'Компания:', 0, 0, 'L');
                $this->pdf->Cell(0, 5, $point['company_name'], 0, 1, 'L');
            }
            
            if (!empty($point['contact_person'])) {
                $this->pdf->Cell(60, 5, 'Контактное лицо:', 0, 0, 'L');
                $this->pdf->Cell(0, 5, $point['contact_person'], 0, 1, 'L');
            }
            
            if (!empty($point['phone_number'])) {
                $this->pdf->Cell(60, 5, 'Телефон:', 0, 0, 'L');
                $this->pdf->Cell(0, 5, $point['phone_number'], 0, 1, 'L');
            }
            
            $this->pdf->Ln(3);
        }
        
        $this->pdf->Ln(2);
    }
    
    private function addExtraServicesSection($extraServices) {
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(0, 8, 'ДОПОЛНИТЕЛЬНЫЕ УСЛУГИ', 0, 1, 'L', true);
        $this->pdf->Ln(3);
        
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->Cell(80, 6, 'Услуга', 1, 0, 'C');
        $this->pdf->Cell(30, 6, 'Количество', 1, 0, 'C');
        $this->pdf->Cell(30, 6, 'Цена', 1, 0, 'C');
        $this->pdf->Cell(30, 6, 'Итого', 1, 1, 'C');
        
        $this->pdf->SetFont('dejavusans', '', 10);
        foreach ($extraServices as $service) {
            $this->pdf->Cell(80, 6, $service['service_name'] ?? '', 1, 0, 'L');
            $this->pdf->Cell(30, 6, $service['quantity'] ?? '', 1, 0, 'C');
            $this->pdf->Cell(30, 6, $service['service_price'] ?? '', 1, 0, 'C');
            $this->pdf->Cell(30, 6, $service['total'] ?? '', 1, 1, 'C');
        }
        
        $this->pdf->Ln(5);
    }
    
    // Helper formatting methods
    private function formatDate($dateString) {
        if (empty($dateString)) return '—';
        
        try {
            // Handle DD.MM.YYYY format
            if (strpos($dateString, '.') !== false) {
                $parts = explode('.', $dateString);
                if (count($parts) === 3) {
                    return $dateString; // Already in Russian format
                }
            }
            
            $date = new DateTime($dateString);
            return $date->format('d.m.Y');
        } catch (Exception $e) {
            return $dateString;
        }
    }
    
    private function formatDateTime($dateString, $timeString) {
        $date = $this->formatDate($dateString);
        return $date . ($timeString ? ' ' . $timeString : '');
    }
    
    private function formatContract($contractNumber, $contractDate) {
        if (empty($contractNumber)) return '—';
        if (empty($contractDate)) return $contractNumber;
        
        $formattedDate = $this->formatDate($contractDate);
        return $contractNumber . ' от ' . $formattedDate;
    }
    
    private function formatWeight($weight, $unit) {
        if (empty($weight)) return '—';
        return $weight . ' ' . ($unit ?: 'тонн');
    }
    
    private function formatVolume($volume) {
        if (empty($volume)) return '—';
        return $volume . ' м³';
    }
    
    private function formatDimensions($length, $width, $height) {
        if (empty($length) && empty($width) && empty($height)) return '—';
        return ($length ?: '—') . ' × ' . ($width ?: '—') . ' × ' . ($height ?: '—') . ' м';
    }
    
    private function formatPrice($price, $currency) {
        if (empty($price)) return '—';
        return $price . ' ' . ($currency ?: '');
    }
}

// Function to be called from submit_order.php
function generateAndStoreOrderPDF($pdo, $orderData, $orderId) {
    try {
        $generator = new OrderPDFGenerator($pdo);
        $result = $generator->generateOrderPDF($orderData, $orderId);
        
        if ($result['success']) {
            // Store file information in database
            $stmt = $pdo->prepare("
                INSERT INTO Order_files (Order_id, Status, File_path, File_name, File_size, File_type, Created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $fileSize = file_exists($result['filepath']) ? filesize($result['filepath']) : 0;
            
            $stmt->execute([
                $orderId,
                1, // Status: 1 when order is first created
                $result['relative_path'],
                $result['filename'],
                $fileSize,
                'application/pdf'
            ]);
            
            return [
                'success' => true,
                'file_id' => $pdo->lastInsertId(),
                'filename' => $result['filename'],
                'filepath' => $result['relative_path']
            ];
        } else {
            return $result;
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Failed to generate and store PDF: ' . $e->getMessage()
        ];
    }
}
?> 