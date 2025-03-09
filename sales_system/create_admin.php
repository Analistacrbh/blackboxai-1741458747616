<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Database connection parameters
    $host = 'localhost';
    $db   = 'db_vendas';  // Your database name
    $user = 'root';       // Your database username
    $pass = '';           // Your database password
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    echo "Connecting to database...\n";
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully!\n\n";

    // Delete existing admin user
    echo "Removing existing admin user...\n";
    $pdo->exec("DELETE FROM users WHERE username = 'adm'");

    // Insert new admin user
    echo "Creating new admin user...\n";
    $sql = "INSERT INTO users (username, password_hash, full_name, email, user_level, status) 
            VALUES (:username, :password_hash, :full_name, :email, :user_level, :status)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'username' => 'adm',
        'password_hash' => '$2y$10$vSf1Js/Wbg23.tTDpdGtleD3CujyEQa0aGccIUSBIKbZ1YvOXu2Ta', // hash for '328050'
        'full_name' => 'Administrador',
        'email' => 'admin@sistema.com',
        'user_level' => 'admin',
        'status' => 'active'
    ]);

    if ($result) {
        echo "Admin user created successfully!\n";
        
        // Verify the user was created
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['adm']);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "\nUser verification:\n";
            echo "ID: " . $user['id'] . "\n";
            echo "Username: " . $user['username'] . "\n";
            echo "Email: " . $user['email'] . "\n";
            echo "User Level: " . $user['user_level'] . "\n";
            echo "Status: " . $user['status'] . "\n";
            
            // Verify password
            $verify = password_verify('328050', $user['password_hash']);
            echo "Password verification: " . ($verify ? "Success" : "Failed") . "\n";
        }
    } else {
        echo "Failed to create admin user!\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
