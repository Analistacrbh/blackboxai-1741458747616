<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Database connection parameters
    $host = 'localhost';
    $db   = 'sales_system';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    echo "Attempting to connect to database...\n";
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully!\n\n";

    // Test user creation
    $username = 'adm';
    $password = '328050';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    echo "Creating test user...\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "Generated hash: $hash\n\n";

    // Delete existing user if exists
    $pdo->exec("DELETE FROM users WHERE username = 'adm'");

    // Create new user
    $sql = "INSERT INTO users (username, password_hash, full_name, email, user_level, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'adm',
        $hash,
        'Administrador',
        'admin@sistema.com',
        'admin',
        'active'
    ]);
    echo "User created successfully!\n\n";

    // Verify user exists
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    echo "User verification:\n";
    if ($user) {
        echo "User found in database\n";
        echo "Stored hash: " . $user['password_hash'] . "\n";
        echo "Password verification: " . 
             (password_verify($password, $user['password_hash']) ? "Success" : "Failed") . "\n";
    } else {
        echo "User not found in database!\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
