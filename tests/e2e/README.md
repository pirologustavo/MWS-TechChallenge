# BDD distribuído

O cenário Given/When/Then executa as cinco APIs, bancos SQL isolados, MongoDB e RabbitMQ reais. Autenticação e validações HTTP permanecem habilitadas. Somente o Mercado Pago externo usa um servidor de contrato local. Um segundo cenário verifica a devolução assíncrona do estoque na exclusão.

Na raiz do repositório:

```bash
docker compose -p mws-bdd -f tests/e2e/compose.yml up -d --build --wait
docker compose -p mws-bdd -f tests/e2e/compose.yml run --rm runner
docker compose -p mws-bdd -f tests/e2e/compose.yml down -v
```

O ambiente é isolado, não publica portas e usa apenas dados sintéticos. Remover esse projeto de testes não remove os volumes do ambiente de desenvolvimento. O teste de aplicação em PHPUnit continua disponível na API OS com `--group=bdd`.
