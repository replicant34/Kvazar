<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_connect.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

class OrderWordGenerator {
    private $pdo;
    private $phpWord;
    private $section;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeWord();
    }
    
    private function initializeWord() {
        $this->phpWord = new PhpWord();
        
        // Set document properties
        $properties = $this->phpWord->getDocInfo();
        $properties->setCreator('Kvazar Logistics');
        $properties->setCompany('Kvazar Logistics');
        $properties->setTitle('Order Details');
        $properties->setDescription('Order Information Document');
        $properties->setCategory('Business');
        $properties->setLastModifiedBy('Kvazar System');
        $properties->setCreated(time());
        $properties->setModified(time());
        
        // Add section
        $this->section = $this->phpWord->addSection([
            'marginLeft' => 1134,
            'marginRight' => 1134,
            'marginTop' => 1134,
            'marginBottom' => 1134
        ]);
    }
    
    public function generateOrderWord($orderData, $orderId) {
        try {
            // Title
            $titleStyle = [
                'name' => 'Arial',
                'size' => 16,
                'bold' => true,
                'color' => '2c3e50'
            ];
            $titleParagraph = $this->section->addTextRun(['alignment' => 'center']);
            $titleParagraph->addText('ДЕТАЛИ ЗАКАЗА', $titleStyle);
            $this->section->addTextBreak(2);
            
            // Company Header
            $headerStyle = [
                'name' => 'Arial',
                'size' => 14,
                'bold' => true,
                'color' => '1f4e79'
            ];
            $headerParagraph = $this->section->addTextRun(['alignment' => 'center']);
            $headerParagraph->addText('KVAZAR LOGISTICS', $headerStyle);
            $this->section->addTextBreak(2);
            
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
            $this->section->addTextBreak(1);
            $totalStyle = [
                'name' => 'Arial',
                'size' => 14,
                'bold' => true,
                'color' => 'ffffff'
            ];
            $totalParagraph = $this->section->addTextRun([
                'alignment' => 'center',
                'bgColor' => '28a745'
            ]);
            $totalParagraph->addText(
                'ОБЩАЯ СТОИМОСТЬ УСЛУГ: ' . ($orderData['order_total'] ?? '0') . ' ' . ($orderData['currency'] ?? ''),
                $totalStyle
            );
            $this->section->addTextBreak(2);
            
            // Additional Notes (if applicable)
            if (!empty($orderData['order_notes'])) {
                $this->addSection('ДОПОЛНИТЕЛЬНЫЕ ПРИМЕЧАНИЯ', [], $orderData['order_notes']);
            }
            
            // Generate filename and save
            $filename = 'order_' . $orderId . '_' . date('Y-m-d_H-i-s') . '.docx';
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
            
            // Save Word document to file
            $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
            $objWriter->save($filepath);
            
            // Check if file was actually created
            if (!file_exists($filepath)) {
                throw new Exception("Word document was not created");
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
                'error' => 'Word generation failed: ' . $e->getMessage()
            ];
        }
    }
    
    private function addSection($title, $data, $notes = null) {
        // Section title
        $titleStyle = [
            'name' => 'Arial',
            'size' => 12,
            'bold' => true,
            'color' => '2c3e50'
        ];
        $this->section->addText($title, $titleStyle, ['bgColor' => 'f0f0f0']);
        $this->section->addTextBreak(1);
        
        // Section data
        $labelStyle = [
            'name' => 'Arial',
            'size' => 11,
            'bold' => true
        ];
        $valueStyle = [
            'name' => 'Arial',
            'size' => 11
        ];
        
        foreach ($data as $label => $value) {
            if (!empty($value) && $value !== '—') {
                $paragraph = $this->section->addTextRun();
                $paragraph->addText($label . ': ', $labelStyle);
                $paragraph->addText($value, $valueStyle);
                $this->section->addTextBreak(0.5);
            }
        }
        
        // Notes if provided
        if ($notes) {
            $this->section->addText($notes, $valueStyle);
        }
        
        $this->section->addTextBreak(1);
    }
    
    private function addRouteSection($routePoints) {
        $titleStyle = [
            'name' => 'Arial',
            'size' => 12,
            'bold' => true,
            'color' => '2c3e50'
        ];
        $this->section->addText('МАРШРУТ', $titleStyle, ['bgColor' => 'f0f0f0']);
        $this->section->addTextBreak(1);
        
        foreach ($routePoints as $index => $point) {
            $pointNum = $index + 1;
            
            $pointStyle = [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
                'color' => '1f4e79'
            ];
            $this->section->addText("Пункт {$pointNum}: " . ($point['action_type'] ?? 'Не указано'), $pointStyle);
            $this->section->addTextBreak(0.5);
            
            $detailStyle = [
                'name' => 'Arial',
                'size' => 10
            ];
            
            $dateTime = $this->formatDateTime($point['date'] ?? '', $point['time'] ?? '');
            if ($dateTime !== '—') {
                $paragraph = $this->section->addTextRun();
                $paragraph->addText('Дата и время: ', ['bold' => true] + $detailStyle);
                $paragraph->addText($dateTime, $detailStyle);
                $this->section->addTextBreak(0.3);
            }
            
            if (!empty($point['address'])) {
                $paragraph = $this->section->addTextRun();
                $paragraph->addText('Адрес: ', ['bold' => true] + $detailStyle);
                $paragraph->addText($point['address'], $detailStyle);
                $this->section->addTextBreak(0.3);
            }
            
            if (!empty($point['company_name'])) {
                $paragraph = $this->section->addTextRun();
                $paragraph->addText('Компания: ', ['bold' => true] + $detailStyle);
                $paragraph->addText($point['company_name'], $detailStyle);
                $this->section->addTextBreak(0.3);
            }
            
            if (!empty($point['contact_person'])) {
                $paragraph = $this->section->addTextRun();
                $paragraph->addText('Контактное лицо: ', ['bold' => true] + $detailStyle);
                $paragraph->addText($point['contact_person'], $detailStyle);
                $this->section->addTextBreak(0.3);
            }
            
            if (!empty($point['phone_number'])) {
                $paragraph = $this->section->addTextRun();
                $paragraph->addText('Телефон: ', ['bold' => true] + $detailStyle);
                $paragraph->addText($point['phone_number'], $detailStyle);
                $this->section->addTextBreak(0.3);
            }
            
            $this->section->addTextBreak(0.7);
        }
        
        $this->section->addTextBreak(1);
    }
    
    private function addExtraServicesSection($extraServices) {
        $titleStyle = [
            'name' => 'Arial',
            'size' => 12,
            'bold' => true,
            'color' => '2c3e50'
        ];
        $this->section->addText('ДОПОЛНИТЕЛЬНЫЕ УСЛУГИ', $titleStyle, ['bgColor' => 'f0f0f0']);
        $this->section->addTextBreak(1);
        
        // Create table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ];
        $table = $this->section->addTable($tableStyle);
        
        // Header row
        $table->addRow();
        $headerStyle = [
            'name' => 'Arial',
            'size' => 10,
            'bold' => true
        ];
        $table->addCell(4000)->addText('Услуга', $headerStyle);
        $table->addCell(2000)->addText('Количество', $headerStyle);
        $table->addCell(2000)->addText('Цена', $headerStyle);
        $table->addCell(2000)->addText('Итого', $headerStyle);
        
        // Data rows
        $cellStyle = [
            'name' => 'Arial',
            'size' => 9
        ];
        foreach ($extraServices as $service) {
            $table->addRow();
            $table->addCell(4000)->addText($service['service_name'] ?? '', $cellStyle);
            $table->addCell(2000)->addText($service['quantity'] ?? '', $cellStyle);
            $table->addCell(2000)->addText($service['service_price'] ?? '', $cellStyle);
            $table->addCell(2000)->addText($service['total'] ?? '', $cellStyle);
        }
        
        $this->section->addTextBreak(1);
    }
    
    // Helper formatting methods (same as PDF generator)
    private function formatDate($dateString) {
        if (empty($dateString)) return '—';
        
        try {
            if (strpos($dateString, '.') !== false) {
                $parts = explode('.', $dateString);
                if (count($parts) === 3) {
                    return $dateString;
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

// Function to be called for Word generation
function generateAndStoreOrderWord($pdo, $orderData, $orderId) {
    try {
        $generator = new OrderWordGenerator($pdo);
        $result = $generator->generateOrderWord($orderData, $orderId);
        
        if ($result['success']) {
            // Store file information in database
            $stmt = $pdo->prepare("
                INSERT INTO Order_files (Order_id, Status, File_path, File_name, File_size, File_type, Created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $fileSize = file_exists($result['filepath']) ? filesize($result['filepath']) : 0;
            
            $stmt->execute([
                $orderId,
                1,
                $result['relative_path'],
                $result['filename'],
                $fileSize,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
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
            'error' => 'Failed to generate and store Word document: ' . $e->getMessage()
        ];
    }
}
?> 