<?php

namespace App\Cliente\Application;

use Exception;
use App\Cliente\Infrastructure\ClienteRepository;

class ClienteService implements ClienteServiceInterface
{
    /**
     * @throws Exception
     */
    public function validarDados($cliente)
    {
        if (empty($cliente)) {
            throw new Exception("Campos de cliente devem ser preenchidos");
        }

        if (!self::validarNome($cliente['nome'])) {
            throw new Exception("O nome informado é inválido");
        }

        if (!self::validarEmail($cliente['email'])) {
            throw new Exception("O e-mail informado é inválido!");
        }

        if (!self::validarCpf($cliente['cpf'])) {
            throw new Exception("O CPF informado é inválido");
        }

        if (!$cliente['cep'] || strlen(preg_replace("/[^0-9]/", "", $cliente['cep'])) < 8) {
            throw new Exception("O CEP informado é inválido");
        }

        if (isset($cliente['cep'])) {
            $cliente['cep'] = preg_replace('/[^0-9]/', '', $cliente['cep']);
        }

        if (!in_array($cliente['estado'], self::ESTADOS)) {
            throw new Exception("O estado informado é inválido ou não foi selecionado");
        }

        if (!$cliente['endereco']) {
            throw new Exception("O endereço informado é inválido");
        }

        if (!$cliente['cidade']) {
            throw new Exception("A cidade informada é inválida");
        }

        if (!self::validarTelefone($cliente['telefone'])) {
            throw new Exception("O telefone informado é inválido");
        }

        return $cliente;
    }

    /**
     * @throws Exception
     */
    public function salvar($cliente): void
    {
        $dadosValidados = self::validarDados($cliente);
        (new ClienteRepository)->salvar($dadosValidados);
    }

    public function atualizarCliente($id, $dados)
    {
        $dadosValidados = self::validarDados($dados);
        return (new ClienteRepository())->atualizar($id, $dadosValidados);
    }

    public static function validarNome($nome)
    {
        if (empty($nome) || strlen($nome) < 3) {
            return false;
        }

        return true;
    }

    public static function validarEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    public static function validarCpf($cpf)
    {
        // 1. Limpeza: Remove tudo que não é número e garante que é uma string
        $cpf = (string) $cpf;
        $cpf = preg_replace("/\D/", "", $cpf); // \D remove qualquer caractere que NÃO seja dígito

        // 2. Verifica se sobraram exatamente 11 números
        if (strlen($cpf) !== 11) {
            return false;
        }

        // 3. Bloqueia sequências repetidas conhecidas (111.111.111-11, etc)
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // 4. Cálculo dos dígitos verificadores
        for ($posicaoDoDigito = 9; $posicaoDoDigito < 11; $posicaoDoDigito++) {
            $somaDosProdutos = 0;
            $pesoMultiplicador = $posicaoDoDigito + 1;

            for ($index = 0; $index < $posicaoDoDigito; $index++) {
                // Convertendo explicitamente para int para evitar problemas de tipos
                $somaDosProdutos += (int)$cpf[$index] * $pesoMultiplicador;
                $pesoMultiplicador--;
            }

            $digitoCalculado = ((10 * $somaDosProdutos) % 11) % 10;

            if ((int)$cpf[$posicaoDoDigito] !== $digitoCalculado) {
                return false;
            }
        }

        return true;
    }

    public static function validarTelefone($telefone): bool
    {
        $apenasNumeros = preg_replace("/[^0-9]/", "", $telefone);

        return strlen($apenasNumeros) >= 10 && strlen($apenasNumeros) <= 11;
    }

    /**
     * @throws Exception
     */
    public function listarTodos()
    {
        return (new ClienteRepository)->listarTodos();
    }

    /**
     * @throws Exception
     */
    public function buscarPorId($id)
    {
        return (new ClienteRepository)->buscarPorId($id);
    }
}