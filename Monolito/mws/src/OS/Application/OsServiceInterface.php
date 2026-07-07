<?php

namespace App\OS\Application;

interface OsServiceInterface
{
    const RECEBIDA = ['Recebida', 1];
    const EM_DIAGNOSTICO = ['Em diagnóstico', 2];
    const AGUARDANDO_APROVACAO = ['Aguardando Aprovação', 3];
    const EM_EXECUCAO = ['Em Execução', 4];
    const FINALIZADO = ['Finalizado', 5];
    const ENTREGUE = ['Entregue', 6];
    public function salvar($os);

    public function listarTodos();

    public function atualizarOs($id, $os);

    public function deletarOs($id);

    public function listarRecebidos();

    public function analiseOs($id, $os);

    public function listarAguardandoAprovacao();

    public function aprovarOs($id);

    public function entregarOs($id);

    public function listarEmExecucao();

    public function aprovarOsUsuario($id);

    public function reprovarOsUsuario($id);
}