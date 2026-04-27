<?php

namespace App\Veiculo\Domain;

interface VeiculoRepositoryInterface
{
    const URI = "http://veiculo-lv";
    public function salvar($veiculo);
}