<?php

namespace App\Repositories;

use PDO;

class UrlRepository
{
    private PDO $conn;
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }
    public function getNameById(int $id): string|false
    {
        $stmt = $this->conn->prepare("SELECT name FROM urls WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['name'] === false ? false : $result['name'];
    }
}
