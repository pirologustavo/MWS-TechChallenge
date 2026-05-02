<?php

namespace App\OS\Infrastructure;

use App\OS\Domain\OsRepositoryInterface;
use Exception;

class OsRepository implements OsRepositoryInterface
{
    public function salvar($os)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 2.0,
        ]);

        try {
            $response = $osApi->post('api/os/salvar', [
                'json' => $os,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($_COOKIE['token'] ?? ''),
                    'Accept' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function listarTodos()
    {
        $veiculoApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $veiculoApi->get('/api/os/listar');
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $osApi->get("/api/os/buscarPorId/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizarOs($id, $dados)
    {
        $osApi = new \GuzzleHttp\Client([
            // Como o Monolito está no Docker (mws-monolito), ele usa o nome do serviço
            'base_uri' => 'http://os-lv-api',
            'timeout'  => 5.0,
        ]);

        try {
            // Caminho relativo para o Guzzle concatenar com a base_uri
            $url = "/api/os/atualizarOs/{$id}";

            $dados['_method'] = 'PUT';

            $response = $osApi->post($url, [
                'json' => $dados,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (\Exception $e) {
            throw new \Exception("Erro na API ao atualizar: " . $e->getMessage());
        }
    }
}