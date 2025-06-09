<?php

namespace App\Repositories;

use App\Models\UrlCheck;
use Carbon\Carbon;
use PDO;

class UrlCheckRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }
    public function all(int $urlId): array
    {
        $stmt = $this->conn->prepare("SELECT id, status_code, h1, title, description, created_at FROM url_checks
        WHERE url_id = :urlId ORDER BY id DESC");
        $stmt->execute(['urlId' => $urlId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function save(UrlCheck $urlCheck, int $urlId): void
    {
        $stmt = $this->conn->prepare("INSERT INTO
        url_checks (url_id, status_code, h1, title, description, created_at)
        VALUES (:urlId, :statusCode, :h1, :title, :description, :createdAt)");
        $stmt->execute([
            'urlId' => $urlId,
            'statusCode' => $urlCheck->getStatusCode(),
            'h1' => $urlCheck->getH1(),
            'title' => $urlCheck->getTitle(),
            'description' => $urlCheck->getDescription(),
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
}
