<?php

namespace App\Seguranca\Infrastructure;

use App\Seguranca\Domain\SegurancaRepositoryInterface;
use App\Shared\Infrastructure\BaseRepository;
use Exception;

class SegurancaRepository extends BaseRepository implements SegurancaRepositoryInterface
{
    public function __construct()
    {
        $this->baseUri = self::URI;
    }

    public function autenticarNoMicroservico($usuario, $senha)
    {
        $client = $this->httpClient();

        try {
            $response = $client->post('api/login', [
                'json' => [
                    'usr' => $usuario,
                    'password' => $senha
                ]
            ]);

            $dados = json_decode($response->getBody()->getContents(), true);

            return $dados['token'] ?? null;
        } catch (Exception $e) {
            throw new Exception("Erro de autenticação: " . $e->getMessage());
        }
    }
}