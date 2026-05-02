<?php

namespace App\OS\Domain;

interface OsRepositoryInterface
{
    const URI = "http://os-lv";
    public function salvar($os);
}