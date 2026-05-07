<?php

namespace App\Veiculo\Infrastructure;
use App\Veiculo\Domain\VeiculoRepositoryInterface;
use Exception;

class VeiculoRepository implements VeiculoRepositoryInterface
{
    public function salvar($veiculo)
    {
        $veiculoApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 2.0,
        ]);

        try {
            $response = $veiculoApi->post('/api/veiculos/salvar', [
                'json' => $veiculo,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($_COOKIE['token'] ?? ''),
                    'Accept'        => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function listarTodos()
    {
        $veiculoApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $veiculoApi->get('/api/veiculos/listar');
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $veiculoApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $veiculoApi->get("/api/veiculos/buscarPorId/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizar($id, array $dados)
    {
        $veiculoApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $veiculoApi->put("/api/veiculos/atualizarVeiculo/{$id}", [
                'json' => $dados,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($_COOKIE['token'] ?? ''),
                    'Accept'        => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API ao atualizar: " . $e->getMessage());
        }
    }

    public function veiculoEstoque($id)
    {
        $veiculoApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $veiculoApi->post("/api/veiculos/deletarVeiculo/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}