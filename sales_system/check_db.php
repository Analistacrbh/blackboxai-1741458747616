<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Loaded Extensions:\n";
print_r(get_loaded_extensions());

echo "\nChecking PDO drivers:\n";
if (class_exists('PDO')) {
    echo "PDO is installed\n";
    echo "Available PDO drivers:\n";
    print_r(PDO::getAvailableDrivers());
} else {
    echo "PDO is NOT installed\n";
}

echo "\nTrying MySQL connection...\n";
try {
    // Database settings
    $settings = [
        ['host' => 'localhost', 'db' => 'db_vendas'],
        ['host' => '127.0.0.1', 'db' => 'db_vendas'],
        ['host' => 'localhost', 'db' => 'sales_system'],
        ['host' => '127.0.0.1', 'db' => 'sales_system']
    ];

    foreach ($settings as $config) {
        echo "\nTrying connection to {$config['host']}/{$config['db']}...\n";
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
            $pdo = new PDO($dsn, 'root', '');
            echo "Connection successful!\n";
            echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
            
            // Check if users table exists
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "Available tables:\n";
            print_r($tables);
            
            if (in_array('users', $tables)) {
                echo "\nUsers table found!\n";
                $users = $pdo->query("SELECT username, user_level, status FROM users")->fetchAll();
                echo "Current users:\n";
                print_r($users);
            } else {
                echo "\nUsers table not found in this database.\n";
            }
            
            // Found working connection, break the loop
            break;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
