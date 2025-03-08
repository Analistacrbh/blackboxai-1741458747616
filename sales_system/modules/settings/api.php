<?php
session_start();
require_once '../../config/database.php';

// Check authentication and admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get the action
$action = $_GET['action'] ?? '';

// Handle different API endpoints
switch ($action) {
    case 'save_settings':
        saveSettings();
        break;
    case 'get_settings':
        getSettings();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

function saveSettings() {
    global $conn;
    
    try {
        $type = $_GET['type'] ?? '';
        if (!in_array($type, ['company', 'system', 'messages'])) {
            throw new Exception('Invalid settings type');
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_description) 
            VALUES (:key, :value, :description)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        
        switch ($type) {
            case 'company':
                $companySettings = [
                    'company_name' => 'Nome da empresa',
                    'company_document' => 'CNPJ da empresa',
                    'company_address' => 'Endereço da empresa',
                    'company_phone' => 'Telefone da empresa',
                    'company_email' => 'Email da empresa'
                ];
                
                foreach ($companySettings as $key => $description) {
                    if (isset($_POST[$key])) {
                        $stmt->execute([
                            'key' => $key,
                            'value' => $_POST[$key],
                            'description' => $description
                        ]);
                    }
                }
                break;
                
            case 'system':
                $systemSettings = [
                    'printer_type' => 'Tipo de impressora',
                    'printer_name' => 'Nome da impressora',
                    'receipt_header' => 'Cabeçalho do recibo',
                    'receipt_footer' => 'Rodapé do recibo'
                ];
                
                foreach ($systemSettings as $key => $description) {
                    if (isset($_POST[$key])) {
                        $stmt->execute([
                            'key' => $key,
                            'value' => $_POST[$key],
                            'description' => $description
                        ]);
                    }
                }
                break;
                
            case 'messages':
                $messageSettings = [
                    'sale_success_message' => 'Mensagem de venda concluída',
                    'payment_success_message' => 'Mensagem de pagamento recebido',
                    'low_stock_message' => 'Mensagem de estoque baixo'
                ];
                
                foreach ($messageSettings as $key => $description) {
                    if (isset($_POST[$key])) {
                        $stmt->execute([
                            'key' => $key,
                            'value' => $_POST[$key],
                            'description' => $description
                        ]);
                    }
                }
                break;
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Settings saved successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getSettings() {
    global $conn;
    
    try {
        $type = $_GET['type'] ?? '';
        
        $query = "SELECT setting_key, setting_value, setting_description FROM settings";
        
        if ($type) {
            switch ($type) {
                case 'company':
                    $query .= " WHERE setting_key LIKE 'company_%'";
                    break;
                case 'system':
                    $query .= " WHERE setting_key IN ('printer_type', 'printer_name', 'receipt_header', 'receipt_footer')";
                    break;
                case 'messages':
                    $query .= " WHERE setting_key LIKE '%_message'";
                    break;
                default:
                    throw new Exception('Invalid settings type');
            }
        }
        
        $stmt = $conn->query($query);
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to key-value format
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['setting_key']] = [
                'value' => $setting['setting_value'],
                'description' => $setting['setting_description']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $formattedSettings
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// Helper function to validate settings
function validateSettings($type, $data) {
    $errors = [];
    
    switch ($type) {
        case 'company':
            if (empty($data['company_name'])) {
                $errors[] = 'Company name is required';
            }
            if (!empty($data['company_email']) && !filter_var($data['company_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            break;
            
        case 'system':
            if (!empty($data['printer_type']) && 
                !in_array($data['printer_type'], ['thermal', 'matrix', 'laser'])) {
                $errors[] = 'Invalid printer type';
            }
            break;
    }
    
    return $errors;
}

// Helper function to sanitize settings
function sanitizeSettings($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        // Remove any HTML tags
        $sanitized[$key] = strip_tags($value);
        
        // Trim whitespace
        $sanitized[$key] = trim($sanitized[$key]);
        
        // Convert special characters to HTML entities
        $sanitized[$key] = htmlspecialchars($sanitized[$key], ENT_QUOTES, 'UTF-8');
    }
    
    return $sanitized;
}
?>
