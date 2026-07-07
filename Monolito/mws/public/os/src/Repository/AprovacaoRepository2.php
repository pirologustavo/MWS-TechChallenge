<?php

namespace App\Seguranca\Infrastructure;

use App\Seguranca\Domain\SegurancaRepositoryInterface;
use App\Shared\Infrastructure\DataBase;

class SegurancaRepository implements SegurancaRepositoryInterface
{
    private DataBase $db;
    public function __construct()
    {
        $this->db = new DataBase();
    }

    public function obterPorCriterio($usuario)
    {
        $sql = "SELECT * FROM funcionarios WHERE usr = :usuario";
        $res = $this->db->fetchOne($sql, ["usuario" => $usuario]);

        return $res ?: null;
    }
}