<?php

namespace App\Interface;

interface OsStatusInterface
{
    const RECEBIDA = ['Recebida', 1];
    const EM_DIAGNOSTICO = ['Em diagnóstico', 2];
    const AGUARDANDO_APROVACAO = ['Aguardando Aprovação', 3];
    const EM_EXECUCAO = ['Em Execução', 4];
    const FINALIZADO = ['Finalizado', 5];
    const ENTREGUE = ['Entregue', 6];
}
