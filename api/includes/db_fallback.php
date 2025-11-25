<?php
// MySQLi fallback connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Create a PDO-like interface
class DB_Fallback {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function prepare($sql) {
        return new DB_Statement($this->mysqli->prepare($sql));
    }
    
    public function query($sql) {
        return $this->mysqli->query($sql);
    }
    
    // Add other necessary methods...
}

class DB_Statement {
    private $stmt;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function execute($params = []) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $this->stmt->bind_param($types, ...$params);
        }
        return $this->stmt->execute();
    }
    
    public function fetchAll() {
        $result = $this->stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Add other necessary methods...
}

$pdo = new DB_Fallback($mysqli);
?>