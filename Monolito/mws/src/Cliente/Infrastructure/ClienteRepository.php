<?php

namespace App\Cliente\Infrastructure;

use App\Cliente\Application\ClienteServiceInterface;
use App\Cliente\Domain\ClienteRepositoryInterface;
use App\Shared\Infrastructure\BaseRepository;
use App\Shared\Infrastructure\DataBase;
use Exception;

class ClienteRepository extends BaseRepository implements ClienteRepositoryInterface
{
    public function __construct()
    {
        $this->baseUri = self::URI;
    }

    public function salvar($cliente)
    {
        $clienteApi = $this->httpClient(2.0);

        try {
            $response = $clienteApi->post('/api/clientes', [
                'json' => $cliente,
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function listarTodos()
    {
        $clienteApi = $this->httpClient();

        try {
            $response = $clienteApi->get('/api/clientes', [
                'headers' => $this->getHeaders()
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $clienteApi = $this->httpClient();

        try {
            $response = $clienteApi->get("/api/clientes/buscarPorId/{$id}", [
                'headers' => $this->getHeaders()
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizar($id, $dados)
    {
        $clienteApi = $this->httpClient();

        try {
            $response = $clienteApi->put("/api/clientes/atualizarCliente/{$id}", [
                'json' => $dados,
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API ao atualizar: " . $e->getMessage());
        }
    }

    public function deletarCliente($id)
    {
        $clienteApi = $this->httpClient();

        try {
            $response = $clienteApi->post("/api/clientes/deletarCliente/{$id}", [
                'headers' => $this->getHeaders()
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorNome($termo)
    {
        $clienteApi = $this->httpClient();

        try {
            $response = $clienteApi->get("api/clientes/buscar/", [
                'query' => ['q' => $termo],
                'headers' => $this->getHeaders()
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }
}