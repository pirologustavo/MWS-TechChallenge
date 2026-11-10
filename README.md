# Sistema de Gestão de Oficina Mecânica (Microserviços)
### MVP - Tech Challenge
![Arquitetura do Sistema - C4 Nível 2](./docs/C4-2.png)
Sistema projetado para a solução operacional de oficinas mecânicas, focando na rastreabilidade total do ciclo de vida de uma manutenção, desde a entrada do veículo até a entrega final com disparos automáticos de e-mail.

### Arquitetura e Tecnologias
A solução foi decomposta em contêineres independentes para garantir alta coesão e isolamento de domínios (Bounded Contexts).
* **Linguagem:** PHP 8.4 (Laravel Framework nas APIs) e PHP Puro (Front-end)
* **Banco de Dados:** MySQL 8.0 (Persistência) e SQLite (Testes)
* **Orquestração:** Docker, Docker Compose e Kubernetes (K8s)
* **Mensageria & Notificações:** SMTP interceptado via MailHog
* **Padrões:** DDD (Domain Driven Design), Arquitetura Hexagonal, Clean Code.

### Ecossistema de Microsserviços e Front-end: 
- **Monólito (Front-end):** Interface de usuário centralizada em PHP que consome o ecossistema de APIs via JWT.
- **Cliente-lv:** Gestão de cadastro de clientes.
- **Veiculo-lv:** Gestão de frota e vínculos com clientes.
- **Funcionario-lv:** Gestão de colaboradores e autenticação JWT.
- **Estoque-lv:** Controle de peças e insumos.
- **Os-lv:** Core Domain - Gestão de Ordens de Serviço, transições de status e disparos de e-mail ao cliente.

## Saga das ordens de serviço

As operações distribuídas de OS e estoque usam uma Saga síncrona entre os
serviços `Os-lv` e `Estoque-lv`. Cada serviço mantém transações somente no seu
banco: `Os-lv` persiste o agregado da OS e envia um ajuste versionado para o
endpoint interno de estoque; `Estoque-lv` valida e aplica o ajuste em uma
transação local, protegido por `Idempotency-Key`. O correlation ID acompanha
as chamadas e os logs de falha.

Quando uma etapa posterior falha, o serviço que iniciou a mudança executa a
ação compensatória inversa: falha ao baixar estoque remove OS, itens e histórico
locais; falha ao salvar edição/análise devolve o ajuste aplicado; cancelamento
ou exclusão devolve ao estoque as quantidades associadas à OS. Se a compensação
também falhar, o erro é registrado em nível crítico para reconciliação manual.
Isso é consistência eventual com compensação, não rollback ACID entre bancos.

Para este fluxo, a decisão é evoluir para coreografia quando houver um broker:
OS e Estoque são bounded contexts independentes, então cada serviço deve reagir
a eventos de negócio e publicar o resultado de sua etapa, sem um coordenador
central. Essa escolha reduz o acoplamento temporal e permite que os participantes
evoluam separadamente; o custo é exigir consumidores idempotentes, outbox,
observabilidade por correlation ID e reconciliação de Sagas paradas. O repositório
ainda não configura broker ou outbox: o transporte implementado é REST síncrono,
com `Os-lv` conduzindo a sequência e as compensações. Portanto, a implementação
atual oferece rollback local e compensações entre serviços, mas ainda não é uma
coreografia efetiva. Para concluir essa migração, é necessário provisionar a
mensageria e substituir as chamadas de comando por eventos persistidos em outbox
e consumidos independentemente pelos serviços.

## Como executar o projeto
### Opção 1: Ambiente Local (Docker Compose)
Copie `docker-compose.env.example` para `.env`, substitua os segredos e mantenha `MYSQL_ROOT_PASSWORD` compatível com o volume MySQL existente. Depois execute na raiz: <br>
`docker-compose up -d`
  
### Configurar os Bancos de Dados e Seeders
O setup cria os schemas isolados, executa as migrations e, quando há dados legados em `mws_db`, copia os dados preservando IDs e snapshots. Faça backup e pare gravações no banco legado antes do backfill; o script recusa destinos não vazios. Em ambiente novo, cria o usuário inicial. Execute: <br>
`chmod +x setup.sh` <br>
`./setup.sh` <br>

## Como executar o projeto
### Opção 2: Ambiente Clusterizado (Kubernetes)
Para deploy no cluster Kubernetes (Minikube ou EKS), provisione primeiro um schema e usuário por serviço no MySQL/RDS, preencha e aplique `k8s/mws-service-secrets.yaml.example` com segredos reais e ajuste o `DB_HOST` em `k8s/mws-configmap.yaml`. Faça backup e backfill dos dados antes de liberar tráfego. Depois aplique os manifests da pasta k8s/ <br>
`kubectl apply -f k8s/` <br>

Para acessar o painel de interceptação de e-mails de desenvolvimento, redirecione a porta do MailHog:
`kubectl port-forward svc/mailhog 8025:8025`
  
### Credenciais de acesso
Para testar os endpoints que exigem autenticação: <br>
`Usuário: admin` <br>
`Senha: admin123` <br>

## Testes Automatizados
Os testes usam SQLite em memória para não acessar os bancos dos serviços. Execute todos com:

```bash
for service in cliente-lv-api veiculo-lv-api estoque-lv-api funcionario-lv-api os-lv-api; do
  docker compose exec -T -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= "$service" php artisan test || break
done
```

Para testar apenas um serviço, use o mesmo comando substituindo `$service` pelo nome do container.

O CI executa os testes dos cinco microsserviços com PHP 8.4 e PCOV, publica um
relatório Clover por serviço e roda Pint e PHPStan nível 1 como verificações de
qualidade. O cenário BDD do ciclo de OS está em
`Microservicos-lv/php84/Os-lv/tests/Feature/ordem-servico.feature` e sua execução
está em `OrdemServicoControllerTest`.

## Documentação e APIs
A documentação completa, incluindo o Event Storming, Mapa de Contexto e Linguagem Ubíqua, pode ser acessada através do link abaixo:
[Estratégia de comunicação entre microsserviços](./docs/estrategia-comunicacao-microsservicos.md)
[Acesse a Documentação no Notion](https://www.notion.so/TECH-CHALLENGE-338b36cb511a80cb9c12d5c70c5682c7?source=copy_link)
[Endpoints (Postman)] (https://gustavo-5520387.postman.co/workspace/Gustavo's-
Workspace~e01e23b1-b0c9-4148-8bea-f931ea6d3628/collection/45952571-cfa04a96-
15fd-4662-b65d-0e6b27d75d80?action=share&creator=45952571)

## Segurança e Vulnerabilidades
Realizamos scans de segurança utilizando o composer audit
  - Status Atual: 0 vulnerabilidades críticas encontradas no código de produção

## CI/CD

O projeto possui pipeline automatizado utilizando GitHub Actions.

Fluxo:

1. Pull Request / Push na branch main
2. Execução dos testes automatizados
3. Build das imagens Docker
4. Publicação no Amazon ECR
5. Deploy automático no Amazon EKS via kubectl set image

Tecnologias:

- GitHub Actions
- Docker
- Amazon ECR
- Amazon EKS

## Autor
  - Gustavo Pirolo - Cientista da Computação & Trainee Software Development
  - Apelido do Servidor: Gustavo Pirolo - RM371637 
