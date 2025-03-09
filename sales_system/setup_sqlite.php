<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Setting up SQLite database...\n";

try {
    // Create SQLite database
    $db = new PDO('sqlite:sales_system.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to SQLite database\n";
    
    // Create users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL,
        user_level TEXT NOT NULL DEFAULT 'user',
        status TEXT NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    )");
    
    echo "Users table created\n";
    
    // Delete existing admin user if exists
    $db->exec("DELETE FROM users WHERE username = 'adm'");
    
    // Create admin user
    $stmt = $db->prepare("
        INSERT INTO users (username, password_hash, full_name, email, user_level, status)
        VALUES (:username, :password_hash, :full_name, :email, :user_level, :status)
    ");
    
    $result = $stmt->execute([
        'username' => 'adm',
        'password_hash' => '$2y$10$vSf1Js/Wbg23.tTDpdGtleD3CujyEQa0aGccIUSBIKbZ1YvOXu2Ta', // hash for '328050'
        'full_name' => 'Administrador',
        'email' => 'admin@sistema.com',
        'user_level' => 'admin',
        'status' => 'active'
    ]);
    
    echo "Admin user created\n";
    
    // Verify user was created
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['adm']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "\nUser verification:\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "User Level: " . $user['user_level'] . "\n";
        echo "Status: " . $user['status'] . "\n";
        
        // Verify password
        $verify = password_verify('328050', $user['password_hash']);
        echo "Password verification: " . ($verify ? "Success" : "Failed") . "\n";
    }
    
    // Update Database class to use SQLite
    $config_content = <<<PHP
<?php
class Database {
    private static \$instance = null;
    private \$conn = null;
    
    private function __construct() {
        try {
            \$this->conn = new PDO('sqlite:' . __DIR__ . '/../sales_system.db');
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException \$e) {
            throw new Exception("Database connection failed: " . \$e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public function getConnection() {
        return \$this->conn;
    }
    
    private function __clone() {}
    private function __wakeup() {}
}
PHP;
    
    // Save the new Database class configuration
    file_put_contents('includes/Database.php', $config_content);
    echo "\nDatabase configuration updated to use SQLite\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
