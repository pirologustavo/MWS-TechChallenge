<?php

namespace App\Cliente\Domain;

interface ClienteRepositoryInterface
{
    const URI = "http://cliente-lv";

    public function salvar($cliente);
    public function listarTodos();
    public function atualizar($id, $dados);
    public function buscarPorNome(string $termo);
}