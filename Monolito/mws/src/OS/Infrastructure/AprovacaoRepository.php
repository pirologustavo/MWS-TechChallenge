<?php

namespace App\OS\Infrastructure;

use App\OS\Domain\AprovacaoRepositoryInterface;
use App\OS\Domain\OsRepositoryInterface;
use App\Shared\Infrastructure\BaseRepository;
use Exception;

class AprovacaoRepository extends BaseRepository implements AprovacaoRepositoryInterface
{
    private ?array $dados = null;
    private ?string $osId = null;

    public function __construct()
    {
        $this->baseUri = OsRepositoryInterface::URI;
    }

    public function buscarPorId($id)
    {
        $this->carregar($id);
        return (object) ($this->dados['os'] ?? []);
    }

    public function buscarItensPorOsId($id)
    {
        $this->carregar($id);
        $os = $this->dados['os'] ?? [];

        return array_map(static fn (array $item) => [
            'modelo' => $os['modelo'] ?? '',
            'sintomas' => $os['sintomas'] ?? '',
            'analise' => $os['analise'] ?? '',
            'descricao' => $item['descricao'] ?? 'Item removido do estoque',
            'quantidade' => $item['qtd'] ?? 0,
            'valor_unitario' => $item['preco'] ?? 0,
        ], $this->dados['itens'] ?? []);
    }

    private function carregar($id): void
    {
        if ($this->osId === (string) $id && $this->dados !== null) return;

        $secret = (string) ($_ENV['OS_APPROVAL_SECRET'] ?? '');
        if ($secret === '') throw new Exception('Chave de aprovação não configurada.');
        $signature = hash_hmac('sha256', (string) $id, $secret);

        try {
            $response = $this->httpClient()->get("/api/os/publico/{$id}", [
                'query' => ['signature' => $signature],
                'headers' => ['Accept' => 'application/json'],
            ]);
            $dados = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            if (empty($dados['os'])) throw new Exception('Ordem de Serviço não encontrada.');
            $this->osId = (string) $id;
            $this->dados = $dados;
        } catch (\Throwable $exception) {
            throw new Exception('Não foi possível consultar a Ordem de Serviço: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
