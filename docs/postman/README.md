# Collections Postman para apresentação

O pacote contém uma collection para cada uma das cinco APIs e um ambiente compartilhado. As URLs, métodos, autenticação e corpos foram conferidos com as rotas/controllers do projeto. Os exemplos de resposta são ilustrativos; não são evidências de chamadas ao ambiente.

## Importar e configurar

1. Extraia `mws-postman-apresentacao.zip` e importe os cinco arquivos `*.postman_collection.json` no Postman.
2. Importe `mws-apresentacao.postman_environment.json` e selecione **MWS — Apresentação** no seletor de ambiente.
3. Preencha `usr` e `password` com um usuário existente da API Funcionário. Os segredos são distribuídos em branco. O usuário de desenvolvimento criado pelo seeder é `admin`; use a senha configurada no seu ambiente.
4. Confira as cinco URLs da tabela abaixo. Execute **Login — capturar token compartilhado** em qualquer collection. O script salva `token` no ambiente, reutilizado nas cinco APIs.

| Collection | Variável da URL | Docker Compose / port-forward |
|---|---|---|
| Funcionário | `funcionario_base_url` | `http://localhost:8004` |
| Cliente | `cliente_base_url` | `http://localhost:8001` |
| Veículo | `veiculo_base_url` | `http://localhost:8002` |
| Estoque | `estoque_base_url` | `http://localhost:8003` |
| OS | `os_base_url` | `http://localhost:8005` |

As URLs são independentes, evitando colisão da antiga variável genérica `base_url`. O login usa `usr` e `password`, e o Bearer é um token **Sanctum**. As collections também possuem variáveis de fallback: sem ambiente selecionado, valores capturados ficam apenas na própria collection. Para compartilhar token e IDs, selecione sempre o ambiente fornecido.

Para o Compose, o ecossistema deve estar iniciado e com migrations e usuário inicial configurados conforme o README principal. A Saga depende dos workers OS/Estoque e RabbitMQ; orçamento por e-mail também depende do worker da fila database. A auditoria exige MongoDB disponível.

## Usar com EKS

Depois de as aplicações estarem implantadas e prontas, execute cada comando em um terminal separado:

```bash
kubectl --context mws-eks -n default port-forward service/funcionario-lv 8004:80
kubectl --context mws-eks -n default port-forward service/cliente-lv 8001:80
kubectl --context mws-eks -n default port-forward service/veiculo-lv 8002:80
kubectl --context mws-eks -n default port-forward service/estoque-lv 8003:80
kubectl --context mws-eks -n default port-forward service/os-lv 8005:80
```

Os comandos usam as mesmas URLs locais do ambiente Postman. Outra opção é preencher cada URL com o endpoint real da respectiva API. O endereço do frontend Monólito não substitui automaticamente os endpoints das APIs.

## Roteiro principal, em ordem

Envie as requests manualmente. Não execute todas as pastas no Runner: rejeição, exclusão, estorno e ajustes manuais são caminhos alternativos e alteram o estado usado pelo fluxo principal.

1. **Funcionário:** faça login, liste os colaboradores e execute **Buscar mecânico — capturar funcionario_id**. O primeiro mecânico encontrado é salvo; troque o filtro `q` ou escolha outro ID se necessário. A API atual não possui endpoints de cadastro/edição/exclusão de funcionários.
2. **Cliente:** execute **Cadastrar cliente — capturar cliente_id**. E-mail e CPF sintéticos são gerados a cada envio para evitar duplicidade. Liste, busque e atualize o cliente. Consulte a auditoria para mostrar o MongoDB.
3. **Veículo:** execute **Cadastrar veículo — capturar veiculo_id**. A request utiliza `cliente_id` e gera uma placa no formato aceito. Mostre a consulta com nome do cliente, obtido via REST.
4. **Estoque:** execute **Cadastrar peça — capturar estoque_id**. A peça inicia com saldo 10 e preço 50. Consulte o saldo; deixe ajustes manuais e arquivamento para uma demonstração separada.
5. **OS:** execute **Abrir OS — capturar os_id**. A resposta é **201** com `saga_status=PENDENTE`. Ela confirma a abertura, enquanto a reserva segue por RabbitMQ.
6. Execute **Consultar Saga — aguardar COMPLETED** até obter `COMPLETED` ou `CONCLUIDA`. `PENDING` e `CANCELLING` ainda não permitem avanço. `COMPENSATED`/`COMPENSADA` encerram esse caminho com falha compensada. Mostre a redução de saldo na API Estoque.
7. Execute **Iniciar diagnóstico**, depois **Registrar diagnóstico e orçamento**. Caso a análise retorne **202**, consulte novamente a Saga antes de continuar.
8. Execute **Aprovar orçamento pelo atendente**, **Listar fila de execução** e **Finalizar reparos**. A consulta da fila usa **POST**, conforme a rota atual.
9. Para demonstrar pagamento, execute **Criar ou consultar checkout Mercado Pago**, abra a URL capturada em `checkout_url` e realize um pagamento de teste. Informe o ID real retornado pelo provedor em `payment_id` e execute **Verificar pagamento no provedor** até o resultado ser `approved`.
10. Execute **Entregar veículo** e **Consultar resultado final da OS**. A entrega exige reparos finalizados e pagamento aprovado.

Sem configuração Mercado Pago válida no servidor e um pagamento de teste confirmado, apresente até a finalização dos reparos e mostre os exemplos salvos de pagamento/entrega como ilustrativos. Enviar `status=approved` no corpo não aprova pagamento; a API consulta o provedor.

## Variáveis capturadas

| Variável | Request que a preenche |
|---|---|
| `token` | Login |
| `funcionario_id` | Buscar mecânico |
| `cliente_id`, `cliente_cpf`, `cliente_email` | Cadastrar cliente |
| `veiculo_id`, `veiculo_placa` | Cadastrar veículo |
| `estoque_id` | Cadastrar peça |
| `os_id`, `os_saga_status` | Abrir OS / consultar Saga |
| `checkout_id`, `checkout_url` | Criar ou consultar checkout |

`payment_id` é informado a partir do pagamento efetivo no Mercado Pago, não é fabricado pelo roteiro.

## Contratos internos e idempotência

Preencha `internal_token` com o mesmo `INTERNAL_API_TOKEN` configurado nos serviços para executar as pastas de contratos internos. As requests incluem `X-Internal-Token`. A validação interna de token na API Funcionário exige também Bearer.

Ajustes de estoque exigem `Idempotency-Key`; os scripts geram uma chave por operação e a conservam para reenviar a mesma request sem repetir a alteração. As variáveis são `ajuste_key`, `ajuste_interno_key`, `baixa_key` e `estorno_key`. Para uma operação nova, limpe a chave correspondente; não reutilize chave com payload diferente. Criar uma nova peça limpa as chaves da demonstração anterior.

- `/api/v1/stock-adjustments` e `/api/internal/v1/stock-adjustments`: corpo `{"items":[{"id":1,"qtd":1}]}`.
- `/api/estoque/alterarEstoque` e `/api/estoque/estornarItens`: ARRAY na raiz, `[{"id":1,"qtd":1}]`.
- Quantidade positiva em `stock-adjustments` baixa saldo; negativa devolve. A rota de estorno converte a quantidade positiva recebida em devolução.

O fluxo de estoque vinculado à OS usa eventos; não é necessário chamar ajustes HTTP manualmente durante esse fluxo.

## Assinaturas, rejeição e compensação

A pasta de link assinado é uma alternativa à aprovação pelo atendente. Para gerar a assinatura automaticamente, preencha `os_approval_secret` somente no seu ambiente local do Postman, com o mesmo `OS_APPROVAL_SECRET` da API OS. Outra opção é preencher `approval_signature` com uma assinatura válida já recebida no link.

O HMAC SHA-256 usa o ID da OS como mensagem. A visualização pública envia `signature` na query; aprovação/reprovação enviam `X-Approval-Signature`. Os scripts usam Web Crypto, disponível no Postman atual, sem bibliotecas externas.

Para demonstrar compensação, abra outra OS e aguarde a reserva. Reprove pelo link assinado, consulte a Saga e confira a devolução no Estoque. Para demonstrar exclusão, use uma OS separada: com reserva ativa, a API retorna **202** e só exclui após a devolução confirmada; uma OS já cancelada pode responder **200**.

## Webhook e estorno opcionais

O webhook exige `payment_id` numérico e válido no provedor. Preencha `mercadopago_webhook_secret` localmente para o script calcular os headers `x-signature` e `x-request-id`, ou informe os valores originais em `webhook_signature` e `request_id`. O contrato assinado é `id:<payment_id>;request-id:<request_id>;ts:<timestamp>;`.

O webhook consulta o Mercado Pago; não é uma simulação de pagamento por corpo HTTP. O estorno também chama o provedor: use pagamento de teste de uma OS separada do fluxo de entrega. A collection complementar `os-pagamentos.postman_collection.json` contém apenas acesso, etapas de pagamento/entrega e webhook/estorno.

## Como interpretar erros durante a apresentação

| HTTP | Interpretação no contrato atual |
|---|---|
| 400 | Idempotency-Key ausente/inválida no ajuste de estoque |
| 401 | Bearer ou segredo interno ausente/inválido; assinatura inválida do webhook |
| 403 | Assinatura do link de orçamento ausente/inválida |
| 404 | Recurso não encontrado |
| 409 | Saga pendente, saldo insuficiente, OS encerrada ou regra de pagamento/entrega |
| 411 | Credenciais inválidas no login, conforme implementação atual |
| 422 | Campos ou referências inválidos; validação de valor/moeda/referência do pagamento |
| 503 | Dependência indisponível, auditoria MongoDB indisponível ou Mercado Pago não configurado |

Os scripts conferem o status esperado e capturam variáveis em respostas bem-sucedidas. Os exemplos de erros podem ser inspecionados na lista de respostas salvas de cada request, sem provocar falhas no ambiente.
