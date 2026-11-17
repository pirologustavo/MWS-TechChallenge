# Contrato HTTP — Os-lv

## Apresentação no Postman

Importe [a collection desta API](api.postman_collection.json). Ela contém exemplos de sucesso/erro, descrições, scripts de validação e captura de IDs. Os exemplos são ilustrativos e não são respostas coletadas do ambiente.

Para apresentar as cinco APIs em sequência, importe também `mws-apresentacao.postman_environment.json` do pacote de apresentação do repositório de integração e selecione **MWS — Apresentação**. A URL deste serviço é `{{os_base_url}}`; ajuste-a para Docker Compose, port-forward ou endpoint real. As cinco URLs são independentes.

Execute **Login — capturar token compartilhado**, com `usr` e `password` de um usuário existente da API Funcionário. O token **Sanctum** é salvo como `token` no ambiente. Os segredos são distribuídos em branco. Sem ambiente selecionado, capturas ficam restritas à própria collection.

A API Funcionário deve estar acessível para validar o Bearer das demais APIs. Endpoints internos exigem `internal_token`, enviado em `X-Internal-Token`. IDs de referência são capturados nas collections de Cliente, Veículo, Funcionário e Estoque antes de abrir uma OS.

Abertura retorna 201 com Saga pendente. Consulte seu estado até COMPLETED/CONCLUIDA antes de avançar; análise/edição/exclusão podem responder 202. Aprovação pública exige HMAC, calculado pelo script com `os_approval_secret` ou fornecido em `approval_signature`. Entrega exige finalização e pagamento aprovado pelo provedor. Não execute rejeição, estorno ou exclusão no mesmo fluxo que pretende demonstrar como entregue.

[Collection complementar de pagamentos](payments.postman_collection.json). Webhook exige HMAC e um payment_id real; os scripts usam Web Crypto, sem bibliotecas externas.

## Rotas de negócio

Os nomes dos parâmetros abaixo indicam as variáveis da apresentação; o servidor recebe seus valores numéricos nos caminhos. Healthcheck público em `/api/ping`; o healthcheck Laravel `/up` é operacional e não faz parte deste contrato de negócio.

| Método | Rota | Acesso |
|---|---|---|
| GET | `/api/ping` | Público |
| POST | `/api/os/salvar` | Bearer |
| GET | `/api/os/{os_id}/saga` | Bearer |
| GET | `/api/os/buscarPorId/{os_id}` | Bearer |
| GET | `/api/os/listarRecebidos` | Bearer |
| POST | `/api/os/emDiagnostico/{os_id}` | Bearer |
| POST | `/api/os/analiseOs/{os_id}` | Bearer |
| GET | `/api/os/listarAguardandoAprovacao` | Bearer |
| POST | `/api/os/aprovarOs/{os_id}` | Bearer |
| POST | `/api/os/listarExecucao` | Bearer |
| POST | `/api/os/finalizarOs/{os_id}` | Bearer |
| POST | `/api/os/{os_id}/checkout` | Bearer |
| POST | `/api/os/{os_id}/pagamento/verificar` | Bearer |
| POST | `/api/os/entregar/{os_id}` | Bearer |
| GET | `/api/os/listar` | Bearer |
| GET | `/api/os/cliente/os/{cliente_cpf}` | Público |
| PUT | `/api/os/atualizarOs/{os_id}` | Bearer |
| POST | `/api/os/atualizarOs/{os_id}` | Bearer |
| GET | `/api/os/publico/{os_id}` | HMAC de aprovação |
| POST | `/api/os/aprovarOsUsuario/{os_id}` | HMAC de aprovação |
| POST | `/api/os/reprovarOsUsuario/{os_id}` | HMAC de aprovação |
| POST | `/api/payments/webhook` | HMAC do webhook |
| POST | `/api/os/{os_id}/pagamento/estornar` | Bearer |
| POST | `/api/os/deletarOs/{os_id}` | Bearer |
