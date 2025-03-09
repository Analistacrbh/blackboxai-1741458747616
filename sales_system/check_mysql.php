<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking MySQL connection...\n\n";

// First check if mysqli extension is available
if (!extension_loaded('mysqli')) {
    echo "mysqli extension is NOT loaded!\n";
    echo "Available extensions:\n";
    print_r(get_loaded_extensions());
    exit(1);
}

echo "mysqli extension is loaded\n";

// Try to connect using mysqli
try {
    $mysqli = new mysqli('localhost', 'root', '', 'db_vendas');
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "Connected successfully to MySQL!\n";
    echo "Server version: " . $mysqli->server_info . "\n";
    
    // Check tables
    $result = $mysqli->query("SHOW TABLES");
    if ($result) {
        echo "\nAvailable tables:\n";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "\n";
        }
    }
    
    // Check users table specifically
    $result = $mysqli->query("SELECT username, user_level, status FROM users");
    if ($result) {
        echo "\nUsers in database:\n";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "\nNo users table or no users found.\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
