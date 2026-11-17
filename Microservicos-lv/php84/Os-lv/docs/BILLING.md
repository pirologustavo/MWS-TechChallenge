# Orçamento, execução e pagamento

As responsabilidades de Billing e Execução estão no serviço OS por decisão do projeto (sem API Execucao-lv). Há cinco microsserviços independentes; não há um sexto Billing separado. Se a avaliação exigir literalmente processos independentes para esses dois domínios, essa distribuição precisa ser revista; não equivale a dois deploys distintos.

Fluxo: abertura → reserva assíncrona de estoque → diagnóstico → orçamento → aprovação → execução → finalização → pagamento confirmado → entrega. O histórico e eventos pertencem ao banco OS. A fila operacional é consultada em `/api/os/listarExecucao`; finalizar gera `OSStatusChanged` na outbox. A entrega exige pagamento aprovado. Orçamento com cobrança iniciada fica imutável.

## Mercado Pago

Integração **Checkout Pro por Preferences**, não Orders: POST `/checkout/preferences`, GET `/v1/payments/{id}`, POST `/v1/payments/{id}/refunds`. Consulte [Preferences](https://www.mercadopago.com.br/developers/pt/reference/online-payments/checkout-pro-preferences/overview) e [notificações](https://www.mercadopago.com.br/developers/pt/docs/checkout-pro-preferences/additional-settings/optional-notifications).

1. Configure MERCADOPAGO_ACCESS_TOKEN e MERCADOPAGO_WEBHOOK_SECRET no secret do serviço OS.
2. Na aplicação Mercado Pago configure Webhook de pagamentos para a URL HTTPS pública `/api/payments/webhook`. Deve alcançar a API OS; localhost não é acessível pelo provedor.
3. Crie checkout autenticado em POST `/api/os/{osid}/checkout`; use checkout_url retornada para pagamento.
4. Webhook valida HMAC e consulta o provedor. ID, referência, moeda e valor devem corresponder à cobrança persistida. Retorno do navegador não confirma pagamento.
5. `billing:reconcile` recupera notificações perdidas e preferência cujo resultado ficou incerto. Falhas têm backoff e last_error. Não cria outra preferência enquanto o resultado externo permanece incerto.
6. Cancelamento concluído ou exclusão da OS provoca estorno automático de pagamento aprovado; X-Idempotency-Key é reutilizada nas tentativas. Checkout permanece como registro após exclusão da OS.

## E-mail e recuperação

O orçamento é persistido na fila database dentro da transação que conclui a análise/Saga. O worker executa `queue:work database` com dez tentativas e intervalo de 60 segundos. Se as dez tentativas do orçamento falharem enquanto a OS ainda aguarda aprovação, o callback de falha cancela a OS e solicita devolução assíncrona do estoque. Se a OS já avançou, não desfaz aprovação ou pagamento. E-mail tem semântica de pelo menos uma entrega e pode se repetir se o processo cair após envio. Configurar MAIL_* e MONOLITO_URL; aprovação pública exige assinatura tanto no Monólito quanto na API.

## Consistência e limites

Saga coreografada entre OS e Estoque, com transações locais, outbox e inbox. Falha transitória deixa a operação pendente para retentativa; rejeição de estoque cancela a abertura ou restaura snapshot. Etapas locais usam transações. Não existe rollback ACID global: efeitos externos precisam de compensação ou retomada. Não se estorna antes da confirmação da compensação de estoque. Homologação com credenciais de teste Mercado Pago continua sendo uma verificação externa; testes locais simulam o provedor.
