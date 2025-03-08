<?php
/**
 * Global configuration settings for the Sales System
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Ensure logs directory exists
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
session_name('SALESESSID');

// Timezone setting
date_default_timezone_set('America/Sao_Paulo');

// Character encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Default headers
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// System constants
define('SYSTEM_NAME', 'Sistema de Vendas');
define('SYSTEM_VERSION', '1.0.0');
define('CURRENCY_SYMBOL', 'R$');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('ROWS_PER_PAGE', 10);
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_TIMEOUT', 900); // 15 minutes
define('SESSION_LIFETIME', 1800); // 30 minutes
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('LOG_PATH', __DIR__ . '/../logs/');

// User levels
define('USER_LEVEL_ADMIN', 'admin');
define('USER_LEVEL_SUPER', 'super');
define('USER_LEVEL_USER', 'user');

// Payment methods
define('PAYMENT_METHODS', [
    'money' => 'Dinheiro',
    'credit_card' => 'Cartão de Crédito',
    'debit_card' => 'Cartão de Débito',
    'pix' => 'PIX',
    'installments' => 'Parcelado'
]);

// Sale status
define('SALE_STATUS', [
    'pending' => 'Pendente',
    'completed' => 'Concluída',
    'cancelled' => 'Cancelada'
]);

// Payment status
define('PAYMENT_STATUS', [
    'pending' => 'Pendente',
    'partial' => 'Parcial',
    'paid' => 'Pago',
    'overdue' => 'Vencido'
]);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'siit8512_controle_vendas');
define('DB_USER', 'contr_adm');
define('DB_PASS', '@Ctrl322075SI');
define('DB_CHARSET', 'utf8mb4');

// Security configuration
define('HASH_ALGO', PASSWORD_DEFAULT);
define('HASH_COST', 12);
define('TOKEN_BYTES', 32);

// File upload configuration
define('ALLOWED_EXTENSIONS', [
    'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'
]);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Email configuration
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', '');
define('SMTP_FROM_NAME', SYSTEM_NAME);

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($error, 3, LOG_PATH . 'error.log');
    
    if (ini_get('display_errors')) {
        printf("<pre>%s</pre>", htmlspecialchars($error));
    }
    
    return true;
}
set_error_handler('customErrorHandler');

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    $error = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . 
            "\nFile: " . $exception->getFile() . 
            "\nLine: " . $exception->getLine() . 
            "\nTrace: " . $exception->getTraceAsString() . "\n";
    
    error_log($error, 3, LOG_PATH . 'error.log');
    
    if (ini_get('display_errors')) {
        printf("<pre>%s</pre>", htmlspecialchars($error));
    } else {
        echo "An error occurred. Please try again later.";
    }
}
set_exception_handler('customExceptionHandler');

/**
 * Shutdown function
 */
function shutdownHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $errorMsg = date('Y-m-d H:i:s') . " - Fatal Error: " . $error['message'] . 
                   "\nFile: " . $error['file'] . 
                   "\nLine: " . $error['line'] . "\n";
        
        error_log($errorMsg, 3, LOG_PATH . 'error.log');
        
        if (ini_get('display_errors')) {
            printf("<pre>%s</pre>", htmlspecialchars($errorMsg));
        } else {
            echo "An error occurred. Please try again later.";
        }
    }
}
register_shutdown_function('shutdownHandler');

// Load required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Could not connect to the database. Please try again later.");
}
?>
