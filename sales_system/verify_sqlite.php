<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Verifying SQLite setup...\n\n";

try {
    // Check if database file exists
    if (!file_exists('sales_system.db')) {
        throw new Exception("Database file not found!");
    }
    echo "Database file exists\n";

    // Connect to database
    $db = new PDO('sqlite:sales_system.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database\n";

    // Check users table
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if (!$result->fetch()) {
        throw new Exception("Users table not found!");
    }
    echo "Users table exists\n";

    // Check admin user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['adm']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Admin user not found!");
    }

    echo "\nAdmin user details:\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "User Level: " . $user['user_level'] . "\n";
    echo "Status: " . $user['status'] . "\n";

    // Test password verification
    $password = '328050';
    $verify = password_verify($password, $user['password_hash']);
    echo "\nPassword verification test:\n";
    echo "Testing password '$password'\n";
    echo "Result: " . ($verify ? "SUCCESS" : "FAILED") . "\n";

    if (!$verify) {
        echo "\nCurrent hash: " . $user['password_hash'] . "\n";
        echo "Expected hash: \$2y\$10\$vSf1Js/Wbg23.tTDpdGtleD3CujyEQa0aGccIUSBIKbZ1YvOXu2Ta\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
