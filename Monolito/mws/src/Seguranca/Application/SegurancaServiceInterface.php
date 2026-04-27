<?php

namespace App\Seguranca\Application;

interface SegurancaServiceInterface
{
    public function validarLogin($usuario, $senha);
}