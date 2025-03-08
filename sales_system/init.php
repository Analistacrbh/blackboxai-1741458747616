<?php
/**
 * System Initialization Script
 * Include this file at the beginning of each PHP file
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting based on environment
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Define base path
define('BASE_PATH', __DIR__);

// Load configuration
require_once BASE_PATH . '/includes/config.php';

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert class name to file path
    $file = BASE_PATH . '/includes/' . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Initialize core components
try {
    // Database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Session handler
    $session = Session::getInstance();
    
    // Check session timeout
    if ($session->checkTimeout()) {
        // Redirect to login if session has expired
        if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'logout.php'])) {
            header('Location: ' . getBaseUrl() . 'login.php');
            exit();
        }
    }
    
    // Authentication handler
    $auth = Auth::getInstance();
    
    // Logger
    $logger = Logger::getInstance();
    
} catch (Exception $e) {
    // Log error
    error_log("Initialization error: " . $e->getMessage());
    
    // Show error page
    if (file_exists(BASE_PATH . '/error.php')) {
        include BASE_PATH . '/error.php';
    } else {
        die("System initialization failed. Please check error logs.");
    }
    exit();
}

/**
 * Get base URL of the application
 * @return string Base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    return $protocol . '://' . $host . $path . '/';
}

/**
 * Check if user has required access level
 * @param string|array $requiredLevel Required access level(s)
 * @return bool Whether user has access
 */
function checkAccess($requiredLevel) {
    global $auth;
    return $auth->hasPermission($requiredLevel);
}

/**
 * Redirect to another page
 * @param string $page Page to redirect to
 * @param array $params URL parameters
 */
function redirect($page, $params = []) {
    $url = getBaseUrl() . $page;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Set flash message
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlash($type, $message) {
    global $session;
    $session->setFlash($type, $message);
}

/**
 * Get flash message
 * @return array|null Flash message data
 */
function getFlash() {
    global $session;
    return $session->getFlash();
}

/**
 * Sanitize output
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 * @param string $date Date string
 * @param bool $showTime Whether to include time
 * @return string Formatted date
 */
function formatDate($date, $showTime = false) {
    return date($showTime ? DATETIME_FORMAT : DATE_FORMAT, strtotime($date));
}

/**
 * Format currency
 * @param float $amount Amount to format
 * @return string Formatted amount
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2, ',', '.');
}

// Load common functions
require_once BASE_PATH . '/includes/functions.php';

// Set default timezone
date_default_timezone_set('America/Sao_Paulo');

// Set default character encoding
mb_internal_encoding('UTF-8');
?>
