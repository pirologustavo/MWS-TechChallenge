# Terraform - Infraestrutura AWS

## Visão Geral

Este diretório contém os arquivos Terraform responsáveis pela definição e provisionamento da infraestrutura em nuvem utilizada pelo projeto de microserviços.

A infraestrutura foi desenvolvida utilizando o conceito de **Infrastructure as Code (IaC)**, permitindo que os recursos sejam definidos através de código, facilitando a reprodução de ambientes, manutenção e controle das alterações realizadas.

---

# Pré-requisitos

Antes de executar o Terraform, certifique-se de possuir:

- Terraform instalado;
- AWS CLI configurada;
- Credenciais AWS com permissões necessárias;
- kubectl instalado para interação com o cluster Kubernetes.

Verificar versões:

```bash
terraform -version

aws --version

kubectl version --client

# Tecnologias Utilizadas

- Terraform
- AWS Provider
- Amazon EKS
- Amazon RDS
- Kubernetes

---

# Estrutura dos Arquivos

```text
terraform/
├── main.tf
│   └── Configurações principais do Terraform e definição do provider AWS.
│
├── eks.tf
│   └── Definição e configuração do cluster Kubernetes utilizando Amazon EKS.
│
├── rds.tf
│   └── Configuração da instância de banco de dados utilizando Amazon RDS.
│
├── variables.tf
│   └── Declaração das variáveis utilizadas na infraestrutura.
│
├── outputs.tf
│   └── Informações retornadas após o provisionamento dos recursos.
│
└── terraform.tfstate
    └── Arquivo gerado automaticamente pelo Terraform para controle do estado da infraestrutura.
```

> ⚠️ Arquivos de estado (`terraform.tfstate` e `terraform.tfstate.backup`) não devem ser versionados no Git, pois podem conter informações sensíveis sobre a infraestrutura.

---

# Recursos Provisionados

## Amazon EKS

O **Amazon Elastic Kubernetes Service (EKS)** é utilizado como ambiente gerenciado para execução dos containers da aplicação.

Responsável por:

- Gerenciamento do cluster Kubernetes;
- Execução dos workloads da aplicação;
- Orquestração dos containers;
- Integração com os manifests Kubernetes do projeto.

---

## Amazon RDS

O **Amazon Relational Database Service (RDS)** é utilizado como serviço gerenciado de banco de dados.

Responsável por:

- Armazenamento persistente dos dados da aplicação;
- Gerenciamento da instância do banco de dados;
- Backup e manutenção automatizada;
- Alta disponibilidade do serviço.

---

# Fluxo da Infraestrutura

O fluxo de provisionamento da aplicação segue a seguinte arquitetura:

```
Terraform
    |
    ├── Amazon EKS
    |
    └── Amazon RDS
            |
            ↓
     Kubernetes Cluster
            |
            ↓
    Containers dos Microserviços
```

---

# Comandos Terraform

## Inicialização

Instala os providers necessários e prepara o ambiente Terraform.

```bash
terraform init
```

---

## Validação

Realiza a validação da sintaxe e consistência dos arquivos Terraform.

```bash
terraform validate
```

---

## Planejamento

Exibe quais recursos serão criados, alterados ou removidos antes da aplicação.

```bash
terraform plan
```

---

## Aplicação

Provisiona ou atualiza a infraestrutura definida nos arquivos Terraform.

```bash
terraform apply
```

---

## Destruição

Remove os recursos provisionados pelo Terraform.

```bash
terraform destroy
```

> Utilize este comando com cuidado, pois remove recursos da infraestrutura AWS.

---

# Boas Práticas Aplicadas

- Infraestrutura definida utilizando o conceito de Infrastructure as Code (IaC);
- Recursos separados por responsabilidade;
- Provisionamento automatizado através do Terraform;
- Controle de mudanças através do histórico de código;
- Arquivos sensíveis removidos do versionamento Git;
- Separação entre código da aplicação e infraestrutura;
- Utilização de serviços gerenciados AWS para maior confiabilidade.

---

# Segurança

Para evitar exposição de informações sensíveis:

- Arquivos `terraform.tfstate` não são versionados;
- Credenciais AWS não são armazenadas no código;
- Variáveis sensíveis devem ser armazenadas através de mecanismos seguros;
- O acesso aos recursos AWS deve seguir o princípio do menor privilégio.

---

# Observações

A infraestrutura criada pelo Terraform fornece a base necessária para execução dos microserviços em ambiente Kubernetes.

O código da aplicação, manifests Kubernetes, ConfigMaps, Secrets e pipelines de CI/CD são mantidos separadamente, permitindo maior organização e facilidade de manutenção.