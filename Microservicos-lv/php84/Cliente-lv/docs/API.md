# Contrato HTTP

Importe `api.postman_collection.json` no Postman. Configure base_url e token emitido pelo serviço Funcionário; endpoints internos exigem internal_token. IDs e exemplos precisam corresponder a registros existentes. Webhooks exigem assinatura HMAC produzida pelo Mercado Pago; não envie status de pagamento pelo navegador.

| Método | Rota | Acesso |
|---|---|---|
| POST | `/api/clientes` | Bearer |
| GET | `/api/clientes` | Bearer |
| PUT | `/api/clientes/atualizarCliente/{id}` | Bearer |
| GET | `/api/clientes/buscar` | Bearer |
| GET | `/api/clientes/buscarPorId/{id}` | Bearer |
| POST | `/api/clientes/deletarCliente/{id}` | Bearer |
| GET | `/api/clientes/{id}/auditoria` | Bearer |
| GET | `/api/internal/v1/clientes` | interno |
| GET | `/api/ping` | público |
