# Contrato HTTP — Funcionario-lv

## Apresentação no Postman

Importe [a collection desta API](api.postman_collection.json). Ela contém exemplos de sucesso/erro, descrições, scripts de validação e captura de IDs. Os exemplos são ilustrativos e não são respostas coletadas do ambiente.

Para apresentar as cinco APIs em sequência, importe também `mws-apresentacao.postman_environment.json` do pacote de apresentação do repositório de integração e selecione **MWS — Apresentação**. A URL deste serviço é `{{funcionario_base_url}}`; ajuste-a para Docker Compose, port-forward ou endpoint real. As cinco URLs são independentes.

Execute **Login — capturar token compartilhado**, com `usr` e `password` de um usuário existente da API Funcionário. O token **Sanctum** é salvo como `token` no ambiente. Os segredos são distribuídos em branco. Sem ambiente selecionado, capturas ficam restritas à própria collection.

A API Funcionário deve estar acessível para validar o Bearer das demais APIs. Endpoints internos exigem `internal_token`, enviado em `X-Internal-Token`. IDs de referência são capturados nas collections de Cliente, Veículo, Funcionário e Estoque antes de abrir uma OS.

A API atual lista colaboradores e mecânicos; não expõe endpoints de cadastro, edição ou exclusão. Buscar mecânico captura `funcionario_id` para o fluxo da OS. O login inválido retorna 411 na implementação atual.

## Rotas de negócio

Os nomes dos parâmetros abaixo indicam as variáveis da apresentação; o servidor recebe seus valores numéricos nos caminhos. Healthcheck público em `/api/ping`; o healthcheck Laravel `/up` é operacional e não faz parte deste contrato de negócio.

| Método | Rota | Acesso |
|---|---|---|
| POST | `/api/login` | Público |
| GET | `/api/ping` | Público |
| GET | `/api/funcionario` | Bearer |
| GET | `/api/funcionario/buscarMecanico` | Bearer |
| GET | `/api/internal/v1/token` | Bearer + X-Internal-Token |
| GET | `/api/internal/v1/funcionarios` | X-Internal-Token |
