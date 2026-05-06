<?php

namespace App\Veiculo\Application;

use App\Veiculo\Infrastructure\VeiculoRepository;
use Exception;

class VeiculoService implements VeiculoServiceInterface
{
    private $veiculoRepository;

    public function __construct(){
        $this->veiculoRepository = new VeiculoRepository();
    }

    public function validarVeiculo(array $veiculo) {
        if (empty($veiculo)) {
            throw new Exception("Campos de veículo devem ser preenchidos");
        }

        if (empty($veiculo['modelo'])) {
            throw new Exception("O campo modelo deve ser preenchido");
        }

        if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $veiculo['placa'])) {
            throw new Exception("A placa informada possui um formato inválido.");
        }

        if (empty($veiculo['marca'])) {
            throw new Exception("O campo marca deve ser preenchido");
        }

        $anoAtual = (int)date('Y');
        if (!is_numeric($veiculo['ano']) || $veiculo['ano'] < 1900 || $veiculo['ano'] > ($anoAtual + 1)) {
            throw new Exception("O ano do veículo é inválido.");
        }

        if (!is_numeric($veiculo['clientid'])) {
            throw new Exception("O proprietário selecionado é inválido.");
        }

        return $veiculo;
    }

    /**
     * @throws Exception
     */
    public function salvar ($veiculo)
    {
        $dadosValidados = self::validarVeiculo($veiculo);
        $this->veiculoRepository->salvar($dadosValidados);
    }

    public function atualizarVeiculo($id, $veiculo)
    {
        $dadosValidados = self::validarVeiculo($veiculo);
        $this->veiculoRepository->atualizar($id, $dadosValidados);
    }

    public function buscarPorId($id)
    {
        return $this->veiculoRepository->buscarPorId($id);
    }

    public function listarTodos()
    {
        return $this->veiculoRepository->listarTodos();
    }

    public function veiculoEstoque($id)
    {
        return $this->veiculoRepository->veiculoEstoque($id);
    }
}