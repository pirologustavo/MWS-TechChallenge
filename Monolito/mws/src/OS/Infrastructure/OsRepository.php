<?php

namespace App\OS\Infrastructure;

use App\OS\Domain\OsRepositoryInterface;
use App\Shared\Infrastructure\BaseRepository;
use Exception;

class OsRepository extends BaseRepository implements OsRepositoryInterface
{
    public function __construct()
    {
        $this->baseUri = self::URI;
    }

    public function salvar($os)
    {
        $osApi = $this->httpClient(2.0);

        try {
            $response = $osApi->post('api/os/salvar', [
                'json' => $os,
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function listarTodos()
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->get('/api/os/listar', [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->get("/api/os/buscarPorId/{$id}", [
                'headers' => $this->getHeaders(),
                'http_errors' => true,
            ]);
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
        $osApi = $this->httpClient();

        try {
            $url = "/api/os/atualizarOs/{$id}";

            $dados['_method'] = 'PUT';

            $response = $osApi->post($url, [
                'json' => $dados,
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (\Exception $e) {
            throw new \Exception("Erro na API ao atualizar: " . $e->getMessage());
        }
    }

    public function deletarOs($id)
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->post("/api/os/deletarOs/{$id}", [
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function listarRecebidos()
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->get('/api/os/listarRecebidos', [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function statusDiagnostico($id)
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->post("/api/os/emDiagnostico/{$id}", [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function analiseOs($id, $os)
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->post("/api/os/analiseOs/{$id}", [
                'json' => $os,
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function listarAguardandoAprovacao()
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->get('/api/os/listarAguardandoAprovacao', [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function aprovarOs($id)
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->post("/api/os/aprovarOs/{$id}", [
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function finalizarOs($id)
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->post("/api/os/finalizarOs/{$id}", [
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function entregarOs($id)
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->post("/api/os/entregar/{$id}", [
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function listarEmExecucao()
    {
        $osApi = $this->httpClient();

        try {
            $response = $osApi->post("/api/os/listarExecucao", [
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function aprovarOsUsuario($id)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 2.0,
        ]);

        try {
            $response = $osApi->post("/api/os/aprovarOsUsuario/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function reprovarOsUsuario($id)
    {
        $osApi = new \GuzzleHttp\Client([
            'base_uri' => self::URI,
            'timeout'  => 2.0,
        ]);

        try {
            $response = $osApi->post("/api/os/reprovarOsUsuario/{$id}");
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}