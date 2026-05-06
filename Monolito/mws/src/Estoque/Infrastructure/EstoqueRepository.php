<?php

namespace App\Estoque\Infrastructure;

use App\Estoque\Domain\EstoqueRepositoryInterface;
use Exception;

class EstoqueRepository implements EstoqueRepositoryInterface
{

    public function listarTodos()
    {
        $estoqueApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $estoqueApi->get('/api/estoque/listar');
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $estoqueApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $estoqueApi->get("/api/estoque/buscarPorId/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizar($id, $dados)
    {
        $estoqueApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $estoqueApi->put("/api/estoque/atualizarEstoque/{$id}", [
                'json' => $dados,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($_COOKIE['token'] ?? ''),
                    'Accept'        => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API ao atualizar: " . $e->getMessage());
        }
    }

    public function salvar($estoque)
    {
        $estoqueApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 2.0,
        ]);

        try {
            $response = $estoqueApi->post('/api/estoque/salvar', [
                'json' => $estoque,
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

    public function alterarEstoque($itens)
    {
        $estoqueApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 2.0,
        ]);

        try {
            $response = $estoqueApi->post('/api/estoque/alterarEstoque', [
                'json' => $itens,
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

    public function estornarItens(array $itensFormatados)
    {
        $estoqueApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $estoqueApi->post('/api/estoque/estornarItens', [
                'json' => $itensFormatados,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($_COOKIE['token'] ?? ''),
                    'Accept'        => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro ao estornar itens na API de Estoque: " . $e->getMessage());
        }
    }

    public function deletarEstoque($id)
    {
        $estoqueApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout' => 5.0,
        ]);

        try {
            $response = $estoqueApi->post("/api/estoque/deletarEstoque/{$id}");

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}