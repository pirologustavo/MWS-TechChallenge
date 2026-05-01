<?php

namespace App\Estoque\Application;

use App\Estoque\Infrastructure\EstoqueRepository;
use Exception;

class EstoqueService implements EstoqueServiceInterface
{
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

    public function atualizarEstoque($id, $dados)
    {
        $dadosValidados = $this->validarEstoque($dados);

        return (new EstoqueRepository())->atualizar($id, $dadosValidados);
    }

    /**
     * @throws Exception
     */
    public function listarTodos()
    {
        $itens = (new EstoqueRepository)->listarTodos();

        foreach ($itens as $item) {
            $item->alerta = ($item->tipo === 'peca' && $item->quantidade_atual <= $item->quantidade_minima);
        }

        return $itens;
    }

    public function buscarPorId($id)
    {
        return (new EstoqueRepository())->buscarPorId($id);
    }

    public function salvar ($estoque)
    {
        $dadosValidados = self::validarEstoque($estoque);
        (new EstoqueRepository())->salvar($dadosValidados);
    }
}