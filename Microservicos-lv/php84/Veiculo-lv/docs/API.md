# Contrato HTTP

Importe `api.postman_collection.json` no Postman. Configure base_url e token emitido pelo serviço Funcionário; endpoints internos exigem internal_token. IDs e exemplos precisam corresponder a registros existentes. Webhooks exigem assinatura HMAC produzida pelo Mercado Pago; não envie status de pagamento pelo navegador.

| Método | Rota | Acesso |
|---|---|---|
| GET | `/api/internal/v1/veiculos` | interno |
| GET | `/api/ping` | público |
| PUT | `/api/veiculos/atualizarVeiculo/{id}` | Bearer |
| GET | `/api/veiculos/buscarPorId/{id}` | Bearer |
| GET | `/api/veiculos/buscarVeiculoCliente/{id}` | Bearer |
| POST | `/api/veiculos/deletarVeiculo/{id}` | Bearer |
| GET | `/api/veiculos/listar` | Bearer |
| POST | `/api/veiculos/salvar` | Bearer |
