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
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $osApi->get('/api/os/listar');
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
            'http_errors' => true,
        ]);

        try {
            $response = $osApi->get("/api/os/buscarPorId/{$id}");
            $content = $response->getBody()->getContents();
            $dados = json_decode($content);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Resposta da API de OS inválida (JSON Error).");
            }

            return $dados;
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizarOs($id, $dados)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
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

    public function listarRecebidos()
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $osApi->get('/api/os/listarRecebidos');
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function statusDiagnostico($id)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $osApi->post("/api/os/emDiagnostico/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function analiseOs($id, $os)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $osApi->post("/api/os/analiseOs/{$id}", [
                'json' => $os,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($_COOKIE['token'] ?? ''),
                    'Accept' => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function listarAguardandoAprovacao()
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 5.0,
        ]);

        try {
            $response = $osApi->get('/api/os/listarAguardandoAprovacao');
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function aprovarOs($id)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout' => 5.0,
        ]);

        try {
            $response = $osApi->post("/api/os/aprovarOs/{$id}");

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function finalizarOs($id)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout' => 5.0,
        ]);

        try {
            $response = $osApi->post("/api/os/finalizarOs/{$id}");

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function entregarOs($id)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout' => 5.0,
        ]);

        try {
            $response = $osApi->post("/api/os/entregar/{$id}");

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function listarEmExecucao()
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout' => 5.0,
        ]);

        try {
            $response = $osApi->post("/api/os/listarExecucao");

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}