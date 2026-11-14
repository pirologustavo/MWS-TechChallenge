<?php

namespace App\Estoque\Infrastructure;

use App\Estoque\Domain\EstoqueRepositoryInterface;
use App\Shared\Infrastructure\BaseRepository;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

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

    public function alterarEstoque($itens, ?string $idempotencyKey = null)
    {
        $estoqueApi = $this->httpClient(2.0);
        $idempotencyKey = $idempotencyKey ?? bin2hex(random_bytes(24));
        $payload = ['items' => array_map(static function ($item) {
            if (is_object($item)) $item = (array) $item;
            return ['id' => (int) $item['id'], 'qtd' => (int) $item['qtd']];
        }, $itens)];

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $response = $estoqueApi->post('/api/v1/stock-adjustments', [
                    'json' => $payload,
                    'headers' => $this->getHeaders() + ['Idempotency-Key' => $idempotencyKey]
                ]);
                return json_decode($response->getBody()->getContents());
            } catch (ConnectException|ServerException $e) {
                if ($attempt === 1) throw new Exception("Erro na API de estoque: " . $e->getMessage(), 0, $e);
            } catch (Exception $e) {
                throw new Exception("Erro na API: " . $e->getMessage(), 0, $e);
            }
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

    public function buscarEstoque($termo)
    {
        $estoqueApi = $this->httpClient();

        try {
            $response = $estoqueApi->get('/api/estoque/buscar', [
                'query' => ['q' => $termo],
                'headers' => $this->getHeaders(),
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            throw new Exception("Erro na API: " . $e->getMessage());
        }
    }
}
