# Contrato HTTP

Importe `api.postman_collection.json` no Postman. Configure base_url e token emitido pelo serviço Funcionário; endpoints internos exigem internal_token. IDs e exemplos precisam corresponder a registros existentes. Webhooks exigem assinatura HMAC produzida pelo Mercado Pago; não envie status de pagamento pelo navegador.

| Método | Rota | Acesso |
|---|---|---|
| POST | `/api/os/analiseOs/{id}` | Bearer |
| POST | `/api/os/aprovarOs/{id}` | Bearer |
| POST | `/api/os/aprovarOsUsuario/{id}` | público |
| PUT | `/api/os/atualizarOs/{id}` | Bearer |
| POST | `/api/os/atualizarOs/{id}` | Bearer |
| GET | `/api/os/buscarPorId/{id}` | Bearer |
| GET | `/api/os/cliente/os/{cpf}` | público |
| POST | `/api/os/deletarOs/{id}` | Bearer |
| POST | `/api/os/emDiagnostico/{id}` | Bearer |
| POST | `/api/os/entregar/{id}` | Bearer |
| POST | `/api/os/finalizarOs/{id}` | Bearer |
| GET | `/api/os/listar` | Bearer |
| GET | `/api/os/listarAguardandoAprovacao` | Bearer |
| POST | `/api/os/listarExecucao` | Bearer |
| GET | `/api/os/listarRecebidos` | Bearer |
| GET | `/api/os/publico/{id}` | público |
| POST | `/api/os/reprovarOsUsuario/{id}` | público |
| POST | `/api/os/salvar` | Bearer |
| GET | `/api/os/{id}/saga` | Bearer |
| POST | `/api/os/{osid}/checkout` | Bearer |
| POST | `/api/os/{osid}/pagamento/estornar` | Bearer |
| POST | `/api/os/{osid}/pagamento/verificar` | Bearer |
| POST | `/api/payments/webhook` | público |
| GET | `/api/ping` | público |
