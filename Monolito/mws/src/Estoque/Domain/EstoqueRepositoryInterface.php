<?php

namespace App\Estoque\Domain;

interface EstoqueRepositoryInterface
{
    const URI = "http://estoque-lv";

    public function listarTodos();
}