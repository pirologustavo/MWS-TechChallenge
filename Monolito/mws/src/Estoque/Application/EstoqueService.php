<?php

namespace App\Estoque\Application;

use App\Estoque\Infrastructure\EstoqueRepository;
use Exception;

class EstoqueService implements EstoqueServiceInterface
{
    private $estoqueRepository;

    public function __construct(){
        $this->estoqueRepository = new EstoqueRepository();
    }
    public function validarEstoque($dados)
    {
        if (empty($dados['descricao']) || strlen($dados['descricao']) < 3) {
            throw new Exception("A descrição do item deve ter pelo menos 3 caracteres");
        }

        if (!isset($dados['tipo']) || !in_array($dados['tipo'], ['peca', 'servico'])) {
            throw new Exception("O tipo deve ser 'peca' ou 'servico'");
        }

        if ($dados['tipo'] == 'peca') {
            if (!isset($dados['quantidade_atual']) || !is_numeric($dados['quantidade_atual'])) {
                throw new Exception("Para peças, a quantidade atual é obrigatória e deve ser um número.");
            }

            if ($dados['quantidade_atual'] < 0) {
                throw new Exception ("A quantidade em estoque não pode ser negativa.");
            }
        }

        if ($dados['valor_venda'] < 0) {
            throw new Exception("O valor de venda não pode ser negativo.");
        }

        return $dados;
    }

    /**
     * @throws Exception
     */
    public function atualizarEstoque($id, $dados)
    {
        $dadosValidados = $this->validarEstoque($dados);

        return $this->estoqueRepository->atualizar($id, $dadosValidados);
    }

    public function deletarEstoque($id)
    {
        return $this->estoqueRepository->deletarEstoque($id);
    }

    /**
     * @throws Exception
     */
    public function listarTodos()
    {
        $itens = $this->estoqueRepository->listarTodos();

        foreach ($itens as $item) {
            $item->alerta = ($item->tipo === 'peca' && $item->quantidade_atual <= $item->quantidade_minima);
        }

        return $itens;
    }

    /**
     * @throws Exception
     */
    public function buscarPorId($id)
    {
        return $this->estoqueRepository->buscarPorId($id);
    }

    /**
     * @throws Exception
     */
    public function salvar ($estoque)
    {
        $dadosValidados = self::validarEstoque($estoque);
        return $this->estoqueRepository->salvar($dadosValidados);
    }
}