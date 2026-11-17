# Sistema de Gestão de Oficina Mecânica — Monólito

### MVP - Tech Challenge

Interface PHP para cadastro de clientes, veículos, funcionários, estoque e acompanhamento das ordens de serviço, da abertura até a entrega.

### Arquitetura e Tecnologias

- **Linguagem:** PHP 8.4 e Apache.
- **Comunicação:** APIs REST de Cliente, Veículo, Funcionário, Estoque e OS.
- **Autenticação:** token Sanctum obtido na API Funcionário.
- **Orquestração:** Docker Compose e Kubernetes.
- **Observabilidade:** Datadog APM, logs e correlação.

### Ecossistema de Microsserviços e Front-end

O Monólito é o frontend do ecossistema. Dados de domínio pertencem às APIs; consultas e alterações passam pelos respectivos endpoints. A Saga e o pagamento pertencem à API OS.

## Como executar o projeto

### Opção 1: Ambiente Local (Docker Compose)

Na raiz do repositório de integração, copie `docker-compose.env.example` para `.env`, configure os segredos e execute:

```bash
docker compose up -d --build
./setup.sh
```

Acesse `http://localhost:8080`. Configure o usuário inicial no serviço Funcionário e substitua as credenciais de desenvolvimento antes de expor o ambiente.

### Opção 2: Ambiente Clusterizado (Kubernetes)

Use `python3 scripts/bootstrap-kubernetes.py` na raiz, após selecionar o contexto correto. O script provisiona as dependências e os Secrets sem imprimi-los. O deployment do frontend usa o mesmo `OS_APPROVAL_SECRET` da API OS para os links de orçamento.

## Testes Automatizados

Os testes das regras de domínio pertencem às APIs. O cenário distribuído em `tests/e2e` exercita o fluxo real entre os serviços. Para construir o frontend: `docker build -t mws-monolito:local Monolito` a partir da raiz.

## Documentação e APIs

Consulte o [guia do ecossistema](../README.md), a [estratégia de comunicação](../docs/estrategia-comunicacao-microsservicos.md) e as collections Postman disponíveis em cada API.

## Observabilidade

Healthcheck em `/healthz.php`. Configure `DD_SERVICE=monolito`, `DD_ENV` e `DD_AGENT_HOST`. Datadog Agent, dashboard e monitores são gerenciados na raiz do repositório.

## CI/CD

O workflow `cd-monolito.yml` aguarda CI aprovado na main, constrói o frontend no SHA aprovado, publica no ECR e aguarda o rollout no EKS. Configure as credenciais AWS válidas, incluindo token de sessão quando necessário.

## Autor

- Gustavo Pirolo — Cientista da Computação & Trainee Software Development.
- RM371637.
