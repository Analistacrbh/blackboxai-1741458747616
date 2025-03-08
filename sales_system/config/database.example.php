<?php
/**
 * Database Configuration Example
 * 
 * Copy this file to database.php and update with your settings
 */
class Database {
    // Database credentials
    private $host = 'localhost';
    private $db = 'your_database_name';
    private $user = 'your_username';
    private $pass = 'your_password';
    private $charset = 'utf8mb4';
    
    // Connection instance
    private $conn;
    
    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . 
                   ";dbname=" . $this->db . 
                   ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->charset . 
                                               " COLLATE " . $this->charset . "_unicode_ci"
            ];
            
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
            
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("Connection failed: Database configuration error");
        }
        
        return $this->conn;
    }
}

/**
 * Usage Example:
 * 
 * try {
 *     $database = new Database();
 *     $db = $database->getConnection();
 *     // Use $db for database operations
 * } catch (Exception $e) {
 *     // Handle connection error
 * }
 */
?>
