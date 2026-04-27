<?php

namespace App\Shared\Infrastructure;

use PDO;

class DataBase
{
    private PDO $pdo;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'];
        $db = $_ENV["DB_NAME"];
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];
        $port = $_ENV['DB_PORT'];

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function fetchAll(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchOne(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}