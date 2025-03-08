<?php
/**
 * Common utility functions for the Sales System
 */

/**
 * Format currency value to Brazilian Real (BRL)
 * @param float $value The value to format
 * @return string Formatted value
 */
function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Format date to Brazilian format
 * @param string $date Date string
 * @param bool $showTime Whether to include time in the format
 * @return string Formatted date
 */
function formatDate($date, $showTime = false) {
    if ($showTime) {
        return date('d/m/Y H:i', strtotime($date));
    }
    return date('d/m/Y', strtotime($date));
}

/**
 * Sanitize input data
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random password
 * @param int $length Password length
 * @return string Generated password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Check if user has required access level
 * @param string|array $requiredLevel Required access level(s)
 * @return bool Whether user has access
 */
function checkAccess($requiredLevel) {
    if (!isset($_SESSION['user_level'])) {
        return false;
    }
    
    if (is_array($requiredLevel)) {
        return in_array($_SESSION['user_level'], $requiredLevel);
    }
    
    return $_SESSION['user_level'] === $requiredLevel;
}

/**
 * Get system setting value
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value
 */
function getSetting($key, $default = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Error getting setting: " . $e->getMessage());
        return $default;
    }
}

/**
 * Log system activity
 * @param string $action Action performed
 * @param string $description Description of the action
 * @param string $module Module where the action was performed
 * @return bool Whether logging was successful
 */
function logActivity($action, $description, $module) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO activity_log (
                user_id, action, description, module, ip_address
            ) VALUES (
                :user_id, :action, :description, :module, :ip_address
            )
        ");
        
        return $stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => $action,
            'description' => $description,
            'module' => $module,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Calculate installment value
 * @param float $total Total amount
 * @param int $installments Number of installments
 * @return float Installment value
 */
function calculateInstallment($total, $installments) {
    return round($total / $installments, 2);
}

/**
 * Check if a value is a valid date
 * @param string $date Date string to validate
 * @param string $format Expected date format
 * @return bool Whether the date is valid
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Format document number (CPF/CNPJ)
 * @param string $doc Document number
 * @return string Formatted document
 */
function formatDocument($doc) {
    $doc = preg_replace('/[^0-9]/', '', $doc);
    
    if (strlen($doc) === 11) {
        // CPF
        return substr($doc, 0, 3) . '.' . 
               substr($doc, 3, 3) . '.' . 
               substr($doc, 6, 3) . '-' . 
               substr($doc, 9);
    } elseif (strlen($doc) === 14) {
        // CNPJ
        return substr($doc, 0, 2) . '.' . 
               substr($doc, 2, 3) . '.' . 
               substr($doc, 5, 3) . '/' . 
               substr($doc, 8, 4) . '-' . 
               substr($doc, 12);
    }
    
    return $doc;
}

/**
 * Format phone number
 * @param string $phone Phone number
 * @return string Formatted phone
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 11) {
        return '(' . substr($phone, 0, 2) . ') ' . 
               substr($phone, 2, 5) . '-' . 
               substr($phone, 7);
    } elseif (strlen($phone) === 10) {
        return '(' . substr($phone, 0, 2) . ') ' . 
               substr($phone, 2, 4) . '-' . 
               substr($phone, 6);
    }
    
    return $phone;
}

/**
 * Get status label with appropriate color class
 * @param string $status Status value
 * @param string $type Type of status (sale, payment, etc.)
 * @return string HTML span element with status
 */
function getStatusLabel($status, $type = 'general') {
    $labels = [
        'sale' => [
            'pending' => ['text' => 'Pendente', 'class' => 'warning'],
            'completed' => ['text' => 'Concluída', 'class' => 'success'],
            'cancelled' => ['text' => 'Cancelada', 'class' => 'danger']
        ],
        'payment' => [
            'pending' => ['text' => 'Pendente', 'class' => 'warning'],
            'partial' => ['text' => 'Parcial', 'class' => 'info'],
            'paid' => ['text' => 'Pago', 'class' => 'success'],
            'overdue' => ['text' => 'Vencido', 'class' => 'danger']
        ],
        'user' => [
            'active' => ['text' => 'Ativo', 'class' => 'success'],
            'inactive' => ['text' => 'Inativo', 'class' => 'danger']
        ]
    ];
    
    $statusInfo = $labels[$type][$status] ?? ['text' => $status, 'class' => 'secondary'];
    
    return sprintf(
        '<span class="badge bg-%s">%s</span>',
        $statusInfo['class'],
        $statusInfo['text']
    );
}

/**
 * Generate pagination links
 * @param int $total Total number of items
 * @param int $perPage Items per page
 * @param int $currentPage Current page number
 * @param string $url Base URL for pagination
 * @return string HTML pagination links
 */
function generatePagination($total, $perPage, $currentPage, $url) {
    $totalPages = ceil($total / $perPage);
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Navegação"><ul class="pagination">';
    
    // Previous button
    $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . ($currentPage > 1 ? $url . ($currentPage - 1) : '#') . '">&laquo;</a></li>';
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $html .= '<li class="page-item ' . ($currentPage == $i ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="' . $url . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . ($currentPage < $totalPages ? $url . ($currentPage + 1) : '#') . '">&raquo;</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Send email using configured SMTP settings
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body
 * @return bool Whether email was sent successfully
 */
function sendEmail($to, $subject, $body) {
    // Get email settings from database
    $smtpHost = getSetting('smtp_host');
    $smtpPort = getSetting('smtp_port');
    $smtpUser = getSetting('smtp_user');
    $smtpPass = getSetting('smtp_pass');
    $fromEmail = getSetting('company_email');
    $fromName = getSetting('company_name');
    
    if (!$smtpHost || !$smtpPort || !$smtpUser || !$smtpPass || !$fromEmail) {
        error_log("Email settings not configured");
        return false;
    }
    
    // Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    
    // Send email
    try {
        return mail($to, $subject, $body, $headers);
    } catch (Exception $e) {
        error_log("Error sending email: " . $e->getMessage());
        return false;
    }
}
?>
