<?php
require_once 'init.php';

// Test credentials
$username = 'adm';
$password = '328050';

// Create test user with known hash
$pdo = Database::getInstance()->getConnection();

try {
    // Clear existing user
    $pdo->exec("DELETE FROM users WHERE username = 'adm'");
    
    // Insert test user
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (
            username, 
            password_hash, 
            full_name, 
            email, 
            user_level, 
            status
        ) VALUES (
            ?, ?, 'Administrador', 'admin@sistema.com', 'admin', 'active'
        )
    ");
    $stmt->execute([$username, $hash]);
    
    echo "Test user created successfully\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "Hash: $hash\n\n";
    
    // Test authentication
    $auth = Auth::getInstance();
    try {
        $result = $auth->authenticate($username, $password);
        echo "Authentication result: " . ($result ? "Success" : "Failed") . "\n";
    } catch (Exception $e) {
        echo "Authentication error: " . $e->getMessage() . "\n";
    }
    
    // Verify stored hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $storedHash = $stmt->fetchColumn();
    
    echo "\nStored hash verification:\n";
    echo "Stored hash: $storedHash\n";
    echo "Direct verification: " . (password_verify($password, $storedHash) ? "Success" : "Failed") . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
