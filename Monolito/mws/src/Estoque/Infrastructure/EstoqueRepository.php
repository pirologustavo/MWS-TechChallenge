<?php

namespace App\Estoque\Infrastructure;

use App\Estoque\Domain\EstoqueRepositoryInterface;
use App\Shared\Infrastructure\BaseRepository;
use Exception;

class EstoqueRepository extends BaseRepository implements EstoqueRepositoryInterface
{
    public function __construct()
    {
        $this->baseUri = self::URI;
    }

    public function listarTodos()
    {
        $estoqueApi = $this->httpClient();

        try {
            $response = $estoqueApi->get('/api/estoque/listar', [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function buscarPorId($id)
    {
        $estoqueApi = $this->httpClient();

        try {
            $response = $estoqueApi->get("/api/estoque/buscarPorId/{$id}", [
                'headers' => $this->getHeaders(),
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API: " . $e->getMessage());
        }
    }

    public function atualizar($id, $dados)
    {
        $estoqueApi = $this->httpClient();

        try {
            $response = $estoqueApi->put("/api/estoque/atualizarEstoque/{$id}", [
                'json' => $dados,
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception ("Erro na API ao atualizar: " . $e->getMessage());
        }
    }

    public function salvar($estoque)
    {
        $estoqueApi = $this->httpClient(2.0);

        try {
            $response = $estoqueApi->post('/api/estoque/salvar', [
                'json' => $estoque,
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function alterarEstoque($itens)
    {
        $estoqueApi = $this->httpClient(2.0);

        try {
            $response = $estoqueApi->post('/api/estoque/alterarEstoque', [
                'json' => $itens,
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }

    public function estornarItens(array $itensFormatados)
    {
        $estoqueApi = $this->httpClient();

        try {
            $response = $estoqueApi->post('/api/estoque/estornarItens', [
                'json' => $itensFormatados,
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro ao estornar itens na API de Estoque: " . $e->getMessage());
        }
    }

    public function deletarEstoque($id)
    {
        $estoqueApi = $this->httpClient();

        try {
            $response = $estoqueApi->post("/api/estoque/deletarEstoque/{$id}", [
                'headers' => $this->getHeaders()
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}