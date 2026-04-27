<?php

namespace App\Seguranca\Domain;

interface SegurancaRepositoryInterface
{
    public function obterPorCriterio($usuario);
}