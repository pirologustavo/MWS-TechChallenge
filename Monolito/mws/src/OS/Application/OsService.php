<?php

namespace App\OS\Application;

use App\Estoque\Infrastructure\EstoqueRepository;
use App\OS\Infrastructure\OsRepository;
use Exception;

class OsService implements OsServiceInterface
{
    private $osRepository;

    public function __construct(){
        $this->osRepository = new OsRepository();
    }

    public function validarOs($os)
    {
        if(empty($os)) {
            throw new Exception("Campos da OS devem ser preenchidos");
        }

        if(empty($os['clientid'])) {
            throw new Exception("Deve ser escolhido um cliente");
        }

        if (empty($os['carid'])) {
            throw new Exception("Deve ser escolhido um carro para a OS");
        }

        if (empty($os['funcid'])) {
            throw new Exception("Deve ser escolhido um mecânico para a OS");
        }

        if (empty($os['itens'])) {
            throw new Exception("Deve ser escolhido um itens para a OS");
        }

        return $os;
    }

    /**
     * @throws Exception
     */
    public function salvar($os)
    {
        $dadosValidados = self::validarOs($os);
        (new OsRepository())->salvar($dadosValidados);

        $dadosValidados['itens'] = array_map(function($item) {
            return is_string($item) ? json_decode($item, true) : $item;
        }, $dadosValidados['itens']);

        (new EstoqueRepository())->alterarEstoque($dadosValidados['itens']);
    }

    public function listarTodos()
    {
        return $this->osRepository->listarTodos();
    }

    public function buscarPorId($id)
    {
        return $this->osRepository->buscarPorId($id);
    }

    /**
     * @throws Exception
     */
    public function atualizarOs($id, $os)
    {
        $dadosValidados = self::validarOs($os);
        return $this->osRepository->atualizarOs($id, $dadosValidados);
    }

    /**
     * @throws Exception
     */
    public function listarRecebidos()
    {
        return $this->osRepository->listarRecebidos();
    }

    /**
     * @throws Exception
     */
    public function statusDiagnostico($id)
    {
        return $this->osRepository->statusDiagnostico($id);
    }

    /**
     * @throws Exception
     */
    public function analiseOs($id, $os)
    {
        $osParaComparar = $this->osRepository->buscarPorId($id);
        $itensAntigos = [];

        if (isset($osParaComparar->itens) && is_array($osParaComparar->itens)) {
            foreach ($osParaComparar->itens as $item) {
                $itensAntigos[$item->id] = (float)$item->qtd;
            }
        }

        $this->osRepository->analiseOs($id, $os);

        $itensNovos = array_map(function($i) {
            return is_string($i) ? json_decode($i, true) : $i;
        }, $os['itens'] ?? []);

        $diffEstoque = [];

        foreach ($itensNovos as $novo) {
            $idItem = $novo['id'];
            $qtdNova = (float)$novo['qtd'];
            $qtdAntiga = $itensAntigos[$idItem] ?? 0;

            $diferenca = $qtdNova - $qtdAntiga;

            if ($diferenca != 0) {
                $diffEstoque[] = [
                    'id' => $idItem,
                    'qtd' => $diferenca
                ];
            }
            unset($itensAntigos[$idItem]);
        }

        foreach ($itensAntigos as $idRemovido => $qtdRemovida) {
            $diffEstoque[] = [
                'id' => $idRemovido,
                'qtd' => -$qtdRemovida
            ];
        }

        if (!empty($diffEstoque)) {
            (new \App\Estoque\Infrastructure\EstoqueRepository())->alterarEstoque($diffEstoque);
        }
    }

    /**
     * @throws Exception
     */
    public function listarAguardandoAprovacao()
    {
        return $this->osRepository->listarAguardandoAprovacao();
    }

    /**
     * @throws Exception
     */
    public function aprovarOs($id)
    {
        return $this->osRepository->aprovarOs($id);
    }

    public function finalizarOs($id)
    {
        return $this->osRepository->finalizarOs($id);
    }

    public function entregarOs($id)
    {
        return $this->osRepository->entregarOs($id);
    }

    public function listarEmExecucao()
    {
        return $this->osRepository->listarEmExecucao();
    }
}