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

| Consumidor | Dono consultado | Uso | Contrato necessário |
|---|---|---|---|
| `Os-lv` | Cliente | validar cliente, obter contato e nome | buscar por ID/CPF; resposta mínima com ID, nome, e-mail e estado |
| `Os-lv` | Veículo | validar veículo e vínculo com cliente; obter placa/modelo | buscar por ID; resposta mínima com ID, `clientid`, placa e modelo |
| `Os-lv` | Funcionário | validar mecânico | buscar funcionário por ID e confirmar que pode atuar como mecânico |
| `Os-lv` | Estoque | validar itens e disponibilidade; futuramente reservar/baixar/restaurar quantidades | consulta em lote por IDs; comandos explícitos de reserva, consumo e estorno |
| `Veiculo-lv` | Cliente | validar `clientid` no cadastro/alteração e enriquecer tela | buscar cliente por ID; listar veículos não deve depender de acesso ao banco de Cliente |
| `Estoque-lv` | OS | preservar itens usados em histórico | sem consulta reversa à OS; itens são arquivados com `ativo=false`, permanecem consultáveis pela OS para histórico e deixam de aparecer em novas buscas |

Contratos internos são versionados sob `/api/internal/v1`, documentados em OpenAPI e retornam DTOs estáveis. Ajuste de estoque usa `POST /api/v1/stock-adjustments` com `Idempotency-Key`. Consultas em lote aceitam até 200 IDs para evitar uma chamada por linha.

## Fluxo recomendado para abrir OS

1. `Os-lv` valida a entrada localmente e consulta Cliente, Veículo e Funcionário por seus endpoints internos.
2. Confirma que o veículo pertence ao cliente informado e que o funcionário está habilitado.
3. Consulta Estoque em lote, confere IDs e disponibilidade, e registra snapshots de descrição e preço recebidos.
4. Grava OS, itens, snapshots e histórico em uma transação somente do banco de `Os-lv`.
5. `Os-lv` grava a OS e solicita a baixa de estoque pelo endpoint interno idempotente. Se a baixa falhar, remove a OS e seus itens locais como compensação. Estoque valida o saldo e aplica o ajuste em transação local.

Para listagens, retornar os dados locais primeiro e enriquecer em lote. Se um serviço de cadastro estiver indisponível, manter as informações históricas da OS; para campos atuais não disponíveis, sinalizar ausência parcial e registrar a falha. Não derrubar todo o fluxo de OS por falha de enriquecimento de tela.

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
