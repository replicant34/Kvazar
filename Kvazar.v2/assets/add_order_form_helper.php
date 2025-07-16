<?php
function canEditField($fieldName, $userRole) {
    $access = require __DIR__ . '/../config/form_access.php';
    
    // Admin has full access
    if ($userRole === 'admin' || ($access[$userRole]['all'] ?? false)) {
        return true;
    }
    
    // Check if user role has edit permission for this field
    return in_array($fieldName, $access[$userRole]['edit'] ?? []);
}

function canViewField($fieldName, $userRole) {
    $access = require __DIR__ . '/../config/form_access.php';
    
    // Admin can view all
    if ($userRole === 'admin' || ($access[$userRole]['all'] ?? false)) {
        return true;
    }
    
    // Check if user role has view permission
    return in_array($fieldName, $access[$userRole]['view'] ?? []) || 
           in_array('*', $access[$userRole]['view'] ?? []);
}

function generateOrderNumber() {
    global $pdo;
    
    try {
        // Get current year
        $year = date('Y');
        
        // Get the last order number for this year
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(Order_number, '/', 1) AS UNSIGNED)) as last_number
            FROM Orders 
            WHERE Order_number LIKE ?
        ");
        $stmt->execute(["%" . $year]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $lastNumber = $result['last_number'] ?? 0;
        $nextNumber = $lastNumber + 1;
        
        // Format: NUMBER/YEAR (e.g., 1234/2024)
        return sprintf('%04d/%s', $nextNumber, $year);
    } catch (PDOException $e) {
        error_log("Error generating order number: " . $e->getMessage());
        return date('YmdHis'); // Fallback to timestamp if error
    }
} 