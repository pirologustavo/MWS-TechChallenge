<?php
namespace App\Seguranca\Application;

use App\Seguranca\Infrastructure\SegurancaRepository;
use Firebase\JWT\JWT;

class SegurancaService implements SegurancaServiceInterface
{
    private SegurancaRepository $repository;
    public function __construct()
    {
        $this->repository = new SegurancaRepository();
    }
    public function validarLogin($usuario, $senha)
    {
        if (empty($usuario) || empty($senha)) {
            return false;
        }

        $dados = $this->repository->obterPorCriterio($usuario);

        if (!$dados || !password_verify($senha, $dados['password'])) {
            return false;
        }

        $payload = [
            'exp' => time() + 3600,
            'iat' => time(),
            'usuario' => $usuario,
        ];

        return((JWT::encode($payload, $_ENV['JWT_KEY'], 'HS256')));
    }
}