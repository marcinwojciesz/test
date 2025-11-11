<?php
/**
 * Klasa do obsługi bazy danych
 */
class Database {
    private $pdo;
    private $error;

    public function __construct($host, $dbname, $username, $password) {
        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            throw new Exception("Błąd połączenia z bazą danych: " . $e->getMessage());
        }
    }

    /**
     * Wykonuje zapytanie SELECT
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Wykonuje zapytanie i zwraca pojedynczy wiersz
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Wykonuje zapytanie INSERT, UPDATE, DELETE
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Zwraca ostatnio wstawione ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Zwraca informację o błędzie
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Rozpoczyna transakcję
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Zatwierdza transakcję
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Wycofuje transakcję
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
}
?>