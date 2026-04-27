<?php

namespace App\OS\Infrastructure;

use App\OS\Domain\Entity\OS;
use App\OS\Domain\Repository\OrderRepositoryInterface;
use App\Shared\Infrastructure\DataBase;

class OSRespository implements OrderRepositoryInterface
{
    private DataBase $db;
    public function __construct()
    {
        $this->db = new DataBase();
    }

    public function salvar(OS $os): void
    {
        $sql = 'INSERT INTO OS (';

        $this->db->execute($sql, [
            'id' => $os->getId(),
            'status' => $os->getStatus(),
            'valor' => $os->getValor()
        ]);
    }

    public function buscarPorId(string $orderId): ?array
    {
        return $this->db->fetchOne('SELECT * FROM OS WHERE id = :id', ['id' => $orderId]);
    }
}