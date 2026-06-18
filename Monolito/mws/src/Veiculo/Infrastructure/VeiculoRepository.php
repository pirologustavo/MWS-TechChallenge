<?php

namespace App\Veiculo\Infrastructure;
use App\Shared\Infrastructure\BaseRepository;
use App\Veiculo\Domain\VeiculoRepositoryInterface;
use Exception;

class VeiculoRepository extends BaseRepository implements VeiculoRepositoryInterface
{
    public function __construct()
    {
        $this->baseUri = self::URI;
    }
    
    public function salvar($veiculo)
    {
        $veiculoApi = $this->httpClient(2.0);

        try {
            $response = $veiculoApi->post('/api/veiculos/salvar', [
                'json' => $veiculo,
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function listarTodos()
    {
        $veiculoApi = $this->httpClient();

        try {
            $response = $veiculoApi->get('/api/veiculos/listar', [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $veiculoApi = $this->httpClient();

        try {
            $response = $veiculoApi->get("/api/veiculos/buscarPorId/{$id}", [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizar($id, array $dados)
    {
        $veiculoApi = $this->httpClient();

        try {
            $response = $veiculoApi->put("/api/veiculos/atualizarVeiculo/{$id}", [
                'json' => $dados,
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API ao atualizar: " . $e->getMessage());
        }
    }

    public function veiculoEstoque($id)
    {
        $veiculoApi = $this->httpClient();

        try {
            $response = $veiculoApi->post("/api/veiculos/deletarVeiculo/{$id}", [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorClienteId($id)
    {
        $veiculoApi = $this->httpClient();

        try {
            $response = $veiculoApi->get("/api/veiculos/buscarVeiculoCliente/{$id}", [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}