# Estratégia de comunicação entre microsserviços

## Objetivo e diagnóstico

Cada serviço acessa somente os dados do seu domínio. Os joins cruzados e consultas diretas foram substituídos por APIs REST internas: `Os-lv` valida referências por ID e usa snapshots para leituras históricas, `Veiculo-lv` valida e enriquece via Cliente, Estoque arquiva itens sem consultar OS, e o Monólito carrega a aprovação pela API assinada de `Os-lv`. O Compose provisiona schemas e usuários separados. `mws_db` continua como origem para o backfill.

## Decisão

- Usar REST síncrono para validações e consultas pontuais necessárias para concluir uma operação do usuário.
- O serviço dono do processo de negócio orquestra as chamadas. Para criar ou atualizar uma OS, `Os-lv` valida cliente, veículo, funcionário e itens no Estoque pelas APIs dos respectivos donos e então persiste somente suas tabelas locais.
- Em leituras compostas, `Os-lv` usa snapshots gravados na abertura/atualização para preservar histórico. `Veiculo-lv` consulta Cliente em lote para apresentação. Nunca montar SQL ou transações atravessando serviços.
- Persistir referências (`clientid`, `carid`, `funcid`, `estoqid`) como IDs, sem chaves estrangeiras entre bancos. Para preservar o histórico, copiar para a OS os dados relevantes no momento da operação: nome/contato do cliente, identificação do veículo e descrição/preço do item. Os snapshots da OS não mudam quando o cadastro ou estoque mudar.
- Cada microsserviço tem schema e credenciais próprios no Compose. `Funcionario-lv` é dono dos usuários e tokens; os demais validam bearer tokens por um endpoint interno. Migrations de identidade repetidas foram desativadas nos outros serviços. Referências entre domínios são IDs sem chaves estrangeiras cruzadas.
- O link público de aprovação de orçamento é assinado com HMAC SHA-256. A mesma chave `OS_APPROVAL_SECRET` é compartilhada entre OS e Monólito; o Monólito lê o detalhe pela API assinada do serviço de OS.

## Responsabilidades e contratos

| Produtor | Evento / rota | Consumidor | Efeito |
|---|---|---|---|
| `Os-lv` | `os.stock.adjustment.requested` | `Estoque-lv` | Reservar, consumir ou devolver saldo em transação local idempotente |
| `Estoque-lv` | `stock.adjustment.succeeded` / `stock.adjustment.failed` | `Os-lv` | Concluir a Saga ou compensar o estado local da OS |
| `Os-lv` | `os.status.changed`, `os.saga.#` | fila de integração / observabilidade | Propagar mudanças de ciclo de vida sem acoplar o serviço de OS |

As mensagens são JSON versionáveis com `event_id`, `event_type`, `saga_id`, `operation_id`, `osid`, `correlation_id` e `occurred_at`. REST continua sendo usado para validações e consultas pontuais; nenhuma mudança de estoque ligada ao fluxo de OS é feita por chamada REST síncrona. Cada serviço grava a mensagem em sua própria `integration_outbox` junto com a alteração de domínio e mantém `integration_inbox` para deduplicação. A publicação e os consumidores podem repetir mensagens sem repetir efeitos de negócio.

## Saga coreografada de OS

1. `Os-lv` valida referências por REST, persiste OS/itens no banco próprio e, na mesma transação, cria `saga_operations` e grava `os.stock.adjustment.requested` na outbox.
2. O worker de OS publica a outbox no exchange durável `mws.saga`. O worker de Estoque recebe o evento, bloqueia e valida os itens na sua transação local, registra a chave de idempotência e grava um evento de resultado na outbox do Estoque.
3. `Os-lv` consome o resultado. No sucesso, marca a Saga como concluída e libera transições do fluxo. Na rejeição, compensa a ação: criação vira cancelamento auditável; edição/análise/cancelamento restauram snapshot; exclusão só acontece após confirmação de devolução.
4. Mensagens transitórias recebem até cinco tentativas com espera e dead-letter. Eventos inválidos vão diretamente para dead-letter. `GET /api/os/{id}/saga` consulta o estado. O monitor `mws.saga.stuck` sinaliza operações pendentes por mais de cinco minutos.

A coreografia foi escolhida porque OS e Estoque são donos de bancos separados e ambos participam com eventos explícitos. Não há coordenador central fazendo chamadas distribuídas: cada participante reage ao evento anterior e publica o próximo passo. A outbox evita perder a publicação após commit; a inbox e a operação idempotente evitam efeitos duplicados. O rollback é uma compensação de negócio, não uma transação ACID distribuída: em caso de falha não transitória, cada serviço preserva a consistência do próprio banco e publica o resultado para desfazer a etapa anterior.

Para subir localmente, `docker compose up -d --build` inicia RabbitMQ, APIs, publishers e consumers. O painel local do broker fica em `http://localhost:15672`. No Kubernetes, aplique o Secret `mws-rabbitmq-secret`, `rabbitmq.yaml` e os Deployments `estoque-saga-worker.yaml` e `os-saga-worker.yaml` antes de atualizar as APIs. Os workers executam as migrations de seu serviço antes de consumir mensagens.

## Segurança e resiliência

- Usar DNS interno do Compose/Kubernetes e URLs configuradas por serviço (`CLIENTE_API_URL`, `VEICULO_API_URL`, `FUNCIONARIO_API_URL`, `ESTOQUE_API_URL`); não codificar hosts/portas nos controllers.
- Autenticação serviço-a-serviço com credencial/escopo próprio (token OAuth2 client credentials ou mecanismo equivalente), sobre TLS fora da rede local. Não compartilhar tokens de sessão do usuário entre serviços.
- Timeout de conexão curto e timeout total limitado. Retry apenas para falhas transitórias e operações idempotentes, com backoff e jitter; nunca repetir automaticamente comandos de estoque sem chave de idempotência.
- Circuit breaker e logs estruturados com correlation ID propagado em todas as chamadas. Não registrar CPF, e-mail, token ou payload sensível integralmente.
- Aplicar autorização e validação de entrada no serviço dono, mesmo quando o chamador já validou.

## Evolução

1. Contratos internos de consulta, validação de token e ajuste idempotente estão implementados e descritos em OpenAPI.
2. O Compose provisiona um schema e usuário por serviço. O Kubernetes usa ConfigMap com DNS interno e Secrets individuais por API.
3. Antes de mudar tráfego, aplicar migrations nos schemas vazios, fazer backup, executar backfill e reconciliar contagens. O script local copia dados e preenche snapshots históricos.
4. Em Kubernetes/RDS, provisionar os schemas e usuários via SQL e executar um backfill equivalente antes de atualizar deployments.
5. Quando houver necessidade de desacoplar disponibilidade ou propagar mudanças, introduzir eventos com outbox e consumidores idempotentes.

## Débitos técnicos identificados

- `OrdemServicoController` ainda contém regras de negócio e composição de respostas; a extração de DTOs e de adaptadores HTTP é uma melhoria posterior.
- O token interno compartilhado simplifica o ambiente atual, mas em produção deve ser substituído por credenciais individuais/escopos ou mTLS e transportado somente em rede TLS privada.
- Circuit breaker e retry com backoff/jitter ainda não foram implementados; chamadas GET têm timeouts limitados e comandos de estoque são idempotentes.

## Critérios de conclusão

- Nenhum serviço faz join, subconsulta, validação `exists` ou migration em tabelas de outro domínio.
- Credenciais e schemas isolados por serviço, sem leitura ou escrita cruzada.
- Contratos OpenAPI versionados, autenticação interna, timeout, correlation ID e política de erro implementados.
- Fluxos de indisponibilidade e repetição de comandos de estoque têm comportamento definido e idempotente.
- Backfill de dados e referências antigos reconciliados antes de desligar o acesso ao schema compartilhado.

## Migração inicial de dados

O `setup.sh` executa migrations e chama `migrate-service-data.sh` quando encontra tabelas legadas em `mws_db`. O backfill recusa destinos não vazios, copia IDs e dados de autenticação para `funcionario_db`, e preenche snapshots na OS. Faça backup e pare escritas antes da execução. Em Kubernetes/RDS, provisionar previamente schemas e usuários e executar um backfill equivalente antes de alterar os deployments.
