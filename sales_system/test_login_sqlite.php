<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing login with SQLite...\n\n";

try {
    // Connect to SQLite database
    $dbPath = __DIR__ . '/sales_system.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test credentials
    $username = 'adm';
    $password = '328050';
    
    echo "Testing login with:\n";
    echo "Username: $username\n";
    echo "Password: $password\n\n";
    
    // Get user from database
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found or inactive');
    }
    
    echo "User found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Level: " . $user['user_level'] . "\n";
    echo "Status: " . $user['status'] . "\n\n";
    
    // Verify password
    $valid = password_verify($password, $user['password_hash']);
    
    echo "Password verification: " . ($valid ? "SUCCESS" : "FAILED") . "\n";
    if (!$valid) {
        echo "\nDebug info:\n";
        echo "Stored hash: " . $user['password_hash'] . "\n";
        echo "Test hash: " . password_hash($password, PASSWORD_DEFAULT) . "\n";
    }
    
    // If successful, simulate session creation
    if ($valid) {
        echo "\nLogin successful! Session would contain:\n";
        echo "user_id: " . $user['id'] . "\n";
        echo "username: " . $user['username'] . "\n";
        echo "user_level: " . $user['user_level'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
