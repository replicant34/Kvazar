<?php
session_start();
require_once '../config/db_connect.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'operator'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $linkToken = $input['link_token'] ?? '';
    $courierEmail = $input['courier_email'] ?? '';
    
    if (!$linkToken || !$courierEmail) {
        echo json_encode(['success' => false, 'error' => 'Link token and email are required']);
        exit();
    }
    
    // Validate link exists and is active
    $linkStmt = $pdo->prepare("
        SELECT 
            cal.Link_token,
            cal.Order_id,
            cal.Courier_id,
            cal.Expires_at,
            o.display_order_number,
            c.Full_Company_name as courier_name,
            cl.Full_Company_name as client_name
        FROM courier_assignment_links cal
        JOIN Orders o ON cal.Order_id = o.Order_id
        JOIN Couriers c ON cal.Courier_id = c.Courier_id
        LEFT JOIN Clients cl ON o.Client_id = cl.Client_id
        WHERE cal.Link_token = ? AND cal.Expires_at > NOW() AND cal.Is_used = FALSE
    ");
    
    $linkStmt->execute([$linkToken]);
    $linkData = $linkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$linkData) {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired link']);
        exit();
    }
    
    // Generate full URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $linkUrl = $protocol . '://' . $host . '/Kvazar.v2/courier_form.php?token=' . $linkToken;
    
    // Format expiry date
    $expiryDate = date('d.m.Y H:i', strtotime($linkData['Expires_at']));
    
    // Prepare email
    $mail = new PHPMailer(true);
    
    // Server settings (you may need to configure these)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // Configure your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-email@domain.com'; // Configure your email
    $mail->Password   = 'your-password'; // Configure your password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    
    // Recipients
    $mail->setFrom('noreply@kvazar-logistics.com', 'Kvazar Logistics');
    $mail->addAddress($courierEmail);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Форма назначения водителя и транспорта - Заказ #' . $linkData['display_order_number'];
    
    $mail->Body = generateEmailTemplate(
        $linkData['courier_name'],
        $linkData['display_order_number'],
        $linkData['client_name'],
        $linkUrl,
        $expiryDate
    );
    
    $mail->AltBody = generatePlainTextEmail(
        $linkData['courier_name'],
        $linkData['display_order_number'],
        $linkData['client_name'],
        $linkUrl,
        $expiryDate
    );
    
    // Send email
    $mail->send();
    
    // Log the action
    $logStmt = $pdo->prepare("
        INSERT INTO action_logs (user_id, action_type, table_name, description, ip_address) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['user_id'], 
        'Отправка ссылки перевозчику по email',
        'courier_assignment_links',
        "Отправлена ссылка по email {$courierEmail} для перевозчика {$linkData['courier_name']} на заказ {$linkData['display_order_number']}",
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully to ' . $courierEmail
    ]);
    
} catch (Exception $e) {
    error_log("Error sending courier link email: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send email: ' . $e->getMessage()
    ]);
}

function generateEmailTemplate($courierName, $orderNumber, $clientName, $linkUrl, $expiryDate) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚚 Kvazar Logistics</h1>
                <p>Форма назначения водителя и транспорта</p>
            </div>
            <div class='content'>
                <h2>Здравствуйте, " . htmlspecialchars($courierName) . "!</h2>
                
                <p>Вам необходимо назначить водителя и транспортное средство для следующего заказа:</p>
                
                <div style='background: white; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #007bff;'>
                    <h3>📋 Информация о заказе</h3>
                    <p><strong>Номер заказа:</strong> #" . htmlspecialchars($orderNumber) . "</p>
                    <p><strong>Клиент:</strong> " . htmlspecialchars($clientName) . "</p>
                </div>
                
                <p>Для назначения водителя и транспорта, пожалуйста, перейдите по ссылке ниже:</p>
                
                <div style='text-align: center;'>
                    <a href='" . htmlspecialchars($linkUrl) . "' class='button'>
                        📝 Заполнить форму
                    </a>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Важно:</strong>
                    <ul>
                        <li>Ссылка действительна до: <strong>" . htmlspecialchars($expiryDate) . "</strong></li>
                        <li>После заполнения формы ссылка станет недоступной</li>
                        <li>Форма должна быть заполнена только один раз</li>
                    </ul>
                </div>
                
                <p>Если у вас есть вопросы или проблемы с доступом к форме, пожалуйста, свяжитесь с нашим оператором.</p>
                
                <div class='footer'>
                    <p>С уважением,<br>Команда Kvazar Logistics</p>
                    <p><small>Это автоматическое сообщение, пожалуйста, не отвечайте на него.</small></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

function generatePlainTextEmail($courierName, $orderNumber, $clientName, $linkUrl, $expiryDate) {
    return "
Здравствуйте, $courierName!

Вам необходимо назначить водителя и транспортное средство для заказа:

Номер заказа: #$orderNumber
Клиент: $clientName

Для назначения водителя и транспорта перейдите по ссылке:
$linkUrl

ВАЖНО:
- Ссылка действительна до: $expiryDate
- После заполнения формы ссылка станет недоступной
- Форма должна быть заполнена только один раз

Если у вас есть вопросы, свяжитесь с нашим оператором.

С уважением,
Команда Kvazar Logistics

Это автоматическое сообщение, пожалуйста, не отвечайте на него.
    ";
}
?> 