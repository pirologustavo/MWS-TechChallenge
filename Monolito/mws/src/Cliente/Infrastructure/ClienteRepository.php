<?php

namespace App\Cliente\Infrastructure;

use App\Cliente\Application\ClienteServiceInterface;
use App\Cliente\Domain\ClienteRepositoryInterface;
use App\Shared\Infrastructure\DataBase;
use Exception;

class ClienteRepository implements ClienteRepositoryInterface
{
    public function salvar($cliente)
    {
        $clienteApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 2.0,
        ]);

        try {
            $response = $clienteApi->post('/api/clientes', [
                'json' => $cliente,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($_COOKIE['token'] ?? ''),
                    'Accept'        => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function listarTodos()
    {
        $clienteApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $clienteApi->get('/api/clientes');
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $clienteApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $clienteApi->get("/api/clientes/bucarPorId/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizar($id, $dados)
    {
        $clienteApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $clienteApi->put("/api/clientes/atualizarCliente/{$id}", [
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

    public function deletarCliente($id)
    {
        $clienteApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $clienteApi->post("/api/clientes/deletarCliente/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }
}