<?php

namespace App\OS\Domain\Repository;

use App\OS\Domain\Entity\OS;

interface OrderRepositoryInterface
{
    public function salvar(OS $os): void;
    public function buscarPorId(string $orderId): ?array;
}