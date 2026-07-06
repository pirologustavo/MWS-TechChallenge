<?php

namespace App\Funcionario\Domain;

interface FuncionarioRepositoryInterface
{
    const URI = "http://funcionario-lv";

    public function buscarMecanico($nome);
}