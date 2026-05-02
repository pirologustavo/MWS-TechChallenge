<?php

namespace App\OS\Application;

use App\Estoque\Infrastructure\EstoqueRepository;
use App\OS\Infrastructure\OsRepository;
use Exception;

class OsService implements OsServiceInterface
{
    public function validarOs($os)
    {
        if(empty($os)) {
            throw new Exception("Campos da OS devem ser preenchidos");
        }

        if(empty($os['clientid'])) {
            throw new Exception("Deve ser escolhido um cliente");
        }

        if (empty($os['carid'])) {
            throw new Exception("Deve ser escolhido um carro para a OS");
        }

        if (empty($os['funcid'])) {
            throw new Exception("Deve ser escolhido um mecânico para a OS");
        }

        if (empty($os['itens'])) {
            throw new Exception("Deve ser escolhido um itens para a OS");
        }

        return $os;
    }

    /**
     * @throws Exception
     */
    public function salvar($os)
    {
        $dadosValidados = self::validarOs($os);
        (new OsRepository())->salvar($dadosValidados);

        $dadosValidados['itens'] = array_map(function($item) {
            return is_string($item) ? json_decode($item, true) : $item;
        }, $dadosValidados['itens']);

        (new EstoqueRepository())->corrigirEstoque($dadosValidados['itens']);
    }

    public function listarTodos()
    {
        return (new OsRepository())->listarTodos();
    }

    public function buscarPorId($id)
    {
        return (new OsRepository())->buscarPorId($id);
    }

    /**
     * @throws Exception
     */
    public function atualizarOs($id, $os)
    {
        $dadosValidados = self::validarOs($os);
        return (new OsRepository())->atualizarOs($id, $dadosValidados);
    }
}