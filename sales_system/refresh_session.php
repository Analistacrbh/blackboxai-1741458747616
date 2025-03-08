<?php
/**
 * Session Refresh Script
 * Extends session lifetime for active users
 */

require_once 'init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Não autenticado'
    ]);
    exit();
}

try {
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration']) || 
        time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sessão atualizada',
        'expires_in' => SESSION_LIFETIME
    ]);
    
} catch (Exception $e) {
    error_log("Session refresh error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar sessão'
    ]);
}
?>
