<?php

namespace App\OS\Domain;

interface AprovacaoRepositoryInterface
{
    public function buscarPorId($id);

    public function buscarItensPorOsId($id);
}