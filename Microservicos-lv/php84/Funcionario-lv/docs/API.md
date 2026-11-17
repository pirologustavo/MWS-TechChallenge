# Contrato HTTP

Importe `api.postman_collection.json` no Postman. Configure base_url e token emitido pelo serviço Funcionário; endpoints internos exigem internal_token. IDs e exemplos precisam corresponder a registros existentes. Webhooks exigem assinatura HMAC produzida pelo Mercado Pago; não envie status de pagamento pelo navegador.

| Método | Rota | Acesso |
|---|---|---|
| GET | `/api/funcionario` | Bearer |
| GET | `/api/funcionario/buscarMecanico` | Bearer |
| GET | `/api/internal/v1/funcionarios` | interno |
| GET | `/api/internal/v1/token` | interno |
| POST | `/api/login` | público |
| GET | `/api/ping` | público |
