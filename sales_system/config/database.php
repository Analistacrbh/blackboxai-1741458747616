<?php
class Database {
    private $host = 'localhost';
    private $db = 'siit8512_controle_vendas';
    private $user = 'contr_adm';
    private $pass = '@Ctrl322075SI';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db,
                $this->user,
                $this->pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
