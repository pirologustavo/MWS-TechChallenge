<?php

namespace App\Estoque\Application;

interface EstoqueServiceInterface
{
    public function listarTodos();

    public function deletarEstoque($id);
}