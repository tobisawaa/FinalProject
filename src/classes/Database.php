<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = require __DIR__ . '/../../config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->connection = new PDO($dsn, $config['username'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
 public function query($sql, $params = []) {
    $stmt = $this->connection->prepare($sql);

    if (!empty($params)) {
        // deteksi associative vs positional
        $isAssoc = array_keys($params) !== range(0, count($params) - 1);

        foreach ($params as $k => $v) {
            // Tentukan tipe: integer jika integer atau string berisi hanya angka (contoh: "100")
            $isIntegerLike = is_int($v) || (is_string($v) && preg_match('/^-?\d+$/', $v));
            $type = $isIntegerLike ? PDO::PARAM_INT : PDO::PARAM_STR;

            if ($isAssoc) {
                $name = (strpos($k, ':') === 0) ? $k : ':' . $k;
                $stmt->bindValue($name, $v, $type);
            } else {
                // positional: bindValue expects 1-based index
                $index = $k + 1;
                $stmt->bindValue($index, $v, $type);
            }
        }

        $stmt->execute();
    } else {
        $stmt->execute();
    }

    return $stmt;
}

    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $fieldsStr = implode(', ', $fields);
        
        $sql = "UPDATE $table SET $fieldsStr WHERE $where";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params)->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params)->rowCount();
    }
}