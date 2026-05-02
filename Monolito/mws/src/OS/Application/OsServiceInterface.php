<?php

namespace App\OS\Application;

interface OsServiceInterface
{
    public function salvar($os);

    public function listarTodos();

    public function atualizarOs($id, $os);
}