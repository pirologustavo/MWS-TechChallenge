<?php

namespace App\Funcionario\Infrastructure;

use App\Shared\Infrastructure\BaseRepository;
use App\Funcionario\Domain\FuncionarioRepositoryInterface;
use Exception;

class FuncionarioRepository extends BaseRepository implements FuncionarioRepositoryInterface
{
    public function __construct()
    {
        $this->baseUri = self::URI;
    }
    public function buscarMecanico($nome)
    {
        $funcApi = $this->httpClient();

        try {
            $response = $funcApi->get("/api/funcionario/buscarMecanico", [
                'query' => ['q' => $nome],
                'headers' => $this->getHeaders()
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}