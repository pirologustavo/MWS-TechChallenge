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

## Como executar o projeto
### Opção 1: Ambiente Local (Docker Compose)
Na raiz do projeto execute: <br>
`docker-compose up -d`
  
### Configurar os Bancos de Dados e Seeders
Foi criado um script de setup automatizado que executa todas as migrations e cria um usuário administrador padrão execute: <br>
`chmod +x setup.sh` <br>
`./setup.sh` <br>

## Como executar o projeto
### Opção 2: Ambiente Clusterizado (Kubernetes)
Para deploy no cluster Kubernetes (Minikube ou EKS), aplique os manifests da pasta k8s/ <br>
`kubectl apply -f k8s/` <br>

Para acessar o painel de interceptação de e-mails de desenvolvimento, redirecione a porta do MailHog:
`kubectl port-forward svc/mailhog 8025:8025`
  
### Credenciais de acesso
Para testar os endpoints que exigem autenticação: <br>
`Usuário: admin` <br>
`Senha: admin123` <br>

## Testes Automatizados
Garantimos uma cobertura de testes superior a 80% em todos os domínios. Para rodar os testes de um módulo específico: <br>
`docker exec -it cliente-lv-api php artisan test` <br>
`docker exec -it estoque-lv-api php artisan test `<br>
`docker exec -it funcionario-lv-api php artisan test` <br>
`docker exec -it os-lv-api php artisan test` <br>
`docker exec -it veiculo-lv-api php artisan test` <br>

## Documentação e APIs
A documentação completa, incluindo o Event Storming, Mapa de Contexto e Linguagem Ubíqua, pode ser acessada através do link abaixo:
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
