<?php
/**
 * Database management class with connection pooling
 */
class Database {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $connectionCount = 0;
    
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
    }
    
    /**
     * Get Database instance (Singleton)
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get a database connection from the pool
     * @return PDO
     * @throws PDOException
     */
    public function getConnection() {
        // Remove closed connections from the pool
        foreach ($this->connections as $key => $connection) {
            try {
                $connection->query('SELECT 1');
            } catch (PDOException $e) {
                unset($this->connections[$key]);
                $this->connectionCount--;
            }
        }
        
        // Return an existing connection if available
        foreach ($this->connections as $connection) {
            if ($connection !== null) {
                return $connection;
            }
        }
        
        // Create new connection if under limit
        if ($this->connectionCount < $this->maxConnections) {
            $connection = $this->createConnection();
            $this->connections[] = $connection;
            $this->connectionCount++;
            return $connection;
        }
        
        // Wait for an available connection
        while (true) {
            foreach ($this->connections as $key => $connection) {
                try {
                    $connection->query('SELECT 1');
                    return $connection;
                } catch (PDOException $e) {
                    unset($this->connections[$key]);
                    $this->connectionCount--;
                    
                    $connection = $this->createConnection();
                    $this->connections[] = $connection;
                    $this->connectionCount++;
                    return $connection;
                }
            }
            usleep(100000); // Wait 100ms before trying again
        }
    }
    
    /**
     * Create a new database connection
     * @return PDO
     * @throws PDOException
     */
    private function createConnection() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci"
        ];
        
        try {
            $pdo = new PDO($dsn, $this->username, $this->password, $options);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            throw new PDOException("Connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Begin a transaction
     * @param PDO $connection
     * @return bool
     */
    public function beginTransaction($connection = null) {
        if ($connection === null) {
            $connection = $this->getConnection();
        }
        return $connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * @param PDO $connection
     * @return bool
     */
    public function commit($connection = null) {
        if ($connection === null) {
            $connection = $this->getConnection();
        }
        return $connection->commit();
    }
    
    /**
     * Rollback a transaction
     * @param PDO $connection
     * @return bool
     */
    public function rollback($connection = null) {
        if ($connection === null) {
            $connection = $this->getConnection();
        }
        return $connection->rollBack();
    }
    
    /**
     * Execute a query and return the result
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param PDO $connection Optional specific connection
     * @return PDOStatement
     */
    public function query($query, $params = [], $connection = null) {
        if ($connection === null) {
            $connection = $this->getConnection();
        }
        
        try {
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw new PDOException("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get a single row
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param PDO $connection Optional specific connection
     * @return array|false
     */
    public function getRow($query, $params = [], $connection = null) {
        $stmt = $this->query($query, $params, $connection);
        return $stmt->fetch();
    }
    
    /**
     * Get multiple rows
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param PDO $connection Optional specific connection
     * @return array
     */
    public function getRows($query, $params = [], $connection = null) {
        $stmt = $this->query($query, $params, $connection);
        return $stmt->fetchAll();
    }
    
    /**
     * Get a single value
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param PDO $connection Optional specific connection
     * @return mixed
     */
    public function getValue($query, $params = [], $connection = null) {
        $stmt = $this->query($query, $params, $connection);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert a row and return the last insert ID
     * @param string $table Table name
     * @param array $data Data to insert
     * @param PDO $connection Optional specific connection
     * @return string Last insert ID
     */
    public function insert($table, $data, $connection = null) {
        if ($connection === null) {
            $connection = $this->getConnection();
        }
        
        $fields = array_keys($data);
        $values = array_fill(0, count($fields), '?');
        
        $query = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                  VALUES (" . implode(', ', $values) . ")";
        
        $this->query($query, array_values($data), $connection);
        return $connection->lastInsertId();
    }
    
    /**
     * Update rows
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where Where clause
     * @param array $params Query parameters
     * @param PDO $connection Optional specific connection
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $params = [], $connection = null) {
        $fields = array_keys($data);
        $set = implode(' = ?, ', $fields) . ' = ?';
        
        $query = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $stmt = $this->query($query, array_merge(array_values($data), $params), $connection);
        return $stmt->rowCount();
    }
    
    /**
     * Delete rows
     * @param string $table Table name
     * @param string $where Where clause
     * @param array $params Query parameters
     * @param PDO $connection Optional specific connection
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = [], $connection = null) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->query($query, $params, $connection);
        return $stmt->rowCount();
    }
    
    /**
     * Close all connections in the pool
     */
    public function closeAll() {
        foreach ($this->connections as $key => $connection) {
            $connection = null;
            unset($this->connections[$key]);
        }
        $this->connectionCount = 0;
    }
    
    /**
     * Prevent cloning of the instance (Singleton)
     */
    private function __clone() {}
    
    /**
     * Prevent unserialize of the instance (Singleton)
     */
    private function __wakeup() {}
    
    /**
     * Destructor - close all connections
     */
    public function __destruct() {
        $this->closeAll();
    }
}
?>
