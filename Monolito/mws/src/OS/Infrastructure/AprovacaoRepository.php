<?php

namespace App\OS\Infrastructure;

use App\OS\Domain\AprovacaoRepositoryInterface;
use App\Shared\Infrastructure\DataBase;

class AprovacaoRepository implements AprovacaoRepositoryInterface
{
    private DataBase $db;

    public function __construct()
    {
        $this->db = new DataBase();
    }

    public function buscarPorId($id)
    {
        $sql = "SELECT * FROM os WHERE osid = :id";

        return $this->db->fetchOne($sql, ["id" => $id]);
    }

    public function buscarItensPorOsId($id)
    {
        $sql = "SELECT 
                    veiculos.modelo, 
                    os.sintomas, 
                    os.analise, 
                    estoque.descricao, 
                    estoque.valor_custo, 
                    os_itens.quantidade,
                    os_itens.valor_unitario
                FROM os_itens
                JOIN estoque ON os_itens.estoqid = estoque.estoqid
                INNER JOIN os ON os_itens.osid = os.osid
                JOIN veiculos ON os.carid = veiculos.carid
                WHERE os_itens.osid = :id";

        return $this->db->fetchAll($sql, ["id" => $id]) ?? [];
    }
}