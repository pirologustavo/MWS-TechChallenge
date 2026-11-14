<?php

namespace App\OS\Application;

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
        $ordemCriada = (new OsRepository())->salvar($dadosValidados);
        return $ordemCriada;
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
    public function deletarOs($id)
    {
        return $this->osRepository->deletarOs($id);
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
        return $this->osRepository->analiseOs($id, $os);
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

    public function aprovarOsUsuario($id)
    {
        return $this->osRepository->aprovarOsUsuario($id);
    }

    public function reprovarOsUsuario($id)
    {
        return $this->osRepository->reprovarOsUsuario($id);
    }
}
