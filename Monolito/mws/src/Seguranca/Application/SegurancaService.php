<?php
namespace App\Seguranca\Application;

use App\Seguranca\Infrastructure\SegurancaRepository;

class SegurancaService implements SegurancaServiceInterface
{
    private SegurancaRepository $repository;
    public function __construct()
    {
        $this->repository = new SegurancaRepository();
    }

    /**
     * @throws \Exception
     */
    public function validarLogin($usuario, $senha)
    {
        if (empty($usuario) || empty($senha)) {
            return false;
        }

        $tokenSanctum = $this->repository->autenticarNoMicroservico($usuario, $senha);

        return $tokenSanctum ?: false;
    }
}