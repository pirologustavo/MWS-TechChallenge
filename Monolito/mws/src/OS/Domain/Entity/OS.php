<?php

namespace App\OS\Domain\Entity;

class OS
{
    public function __construct(
        private string $id,
        private string $status,
        private float $valorTotal = 0.0
    ){}

    public function approve(): void
    {
        if ($this->valorTotal <= 0){
           throw new \DomainException('Não é possível aprovar uma OS sem itens.');
        }

        $this->status = 'Aprovado';
    }
}