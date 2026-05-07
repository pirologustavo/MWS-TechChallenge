# Sistema de Gestão de Oficina Mecânica (Microserviços)
## MVP - Tech Challenge Fase 1
Sistema projeto para a solução operacional de oficinas mecânicas, focando na rastreabilidade total do ciclo de vida de uma manutenção, desde a entrada do veículo até a entrega final.

### Arquitetura e Tecnologias
A solução foi decomposta em contêineres independentes para garantir alta coesão e isolamento de domínios (Bounded Contexts).
* **Linguagem:** PHP 8.4 (Laravel Framework) <br>
* **Banco de Dados:** MySQL 8.0 (Persistência) e SQLite (Testes) <br>
* **Orquestração:** Docker e Docker Compose <br>
* **Padrões:** DDD (Domain Drive Design), Arquitetura Hexagonal, Clean Code. <br>

### Microsserviços: 
- Cliente-lv: Gestão de cadastro de clientes.
- Veiculo-lv: Gestão de frota e vínculos com clientes.
- Funcionario-lv: Gestão de colaboradores e autenticação.
- Estoque-lv: Controle de peças e insumos.
- Os-lv: Core Domain - Gestão de Ordens de Serviço e transições de status.

## Como executar o projeto
### Subir os contêineres
Na raiz do projeto execute: <br>
`docker-compose up -d`
  
### Configurar os Bancos de Dados e Seeders
Foi criado um script de setup automatizado que executa todas as migrations e cria um usuário administrador padrão execute: <br>
`chmod +x setup.sh` <br>
`./setup.sh` <br>
  
### Credenciais de acesso
Para testar os endpoints que exigem autenticação: <br>
`Usuário: admin` <br
`Senha: admin123` <br>

## Testes Automatizados
Garantimos uma cobertura de testes superior a 80% em todos os domínios. Para rodar os testes de um módulo específico: <br>
`docker exec -it cliente-lv-api php artisan test` <br>
`docker exec -it estoque-lv-api php artisan test `<br>
`docker exec -it funcionario-lv-api php artisan test` <br>
`docker exec -it os-lv-api php artisan test` <br>
`docker exec -it veiculo-lv-api php artisan test` <br>

## Documentação de Domínio (DDD)
A documentação completa, incluindo o Event Storming, Mapa de Contexto e Linguagem Ubíqua, pode ser acessada através do link abaixo: <br>
`https://www.notion.so/TECH-CHALLENGE-338b36cb511a80cb9c12d5c70c5682c7?source=copy_link` <br>

## Segurança e Vulnerabilidades
Realizamos scans de segurança utilizando o composer audit
  - Status Atual: 0 vulnerabilidades críticas encontradas no código de produção

Autor
  - Gustavo Pirolo - Cientista da Computação & Trainee Software Development
  - Apelido do Servidor: Gustavo Pirolo - RM371637 
