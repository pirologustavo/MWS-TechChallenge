# Kubernetes - Orquestração dos Microserviços

## Visão Geral

Este diretório contém os manifests Kubernetes responsáveis pela configuração e execução dos microserviços da aplicação.

A utilização do Kubernetes permite realizar a orquestração dos containers, gerenciamento dos deployments, exposição dos serviços e configuração dos recursos necessários para execução da aplicação em ambiente cloud.

A infraestrutura base do cluster Kubernetes é provisionada utilizando Terraform através do Amazon EKS.

---

# Tecnologias Utilizadas

- Kubernetes
- Amazon EKS
- Docker
- Terraform
- ConfigMaps
- Secrets

---

# Responsabilidades

O Kubernetes é responsável por:

- Gerenciamento dos containers da aplicação;
- Escalonamento dos serviços;
- Disponibilidade dos pods;
- Comunicação entre serviços;
- Configuração de variáveis de ambiente;
- Gerenciamento de recursos da aplicação.

---

# Estrutura dos Arquivos

```text
k8s/
│
├── namespace.yaml
│   └── Definição do namespace utilizado pela aplicação.
│
├── configmap.yaml
│   └── Configurações não sensíveis da aplicação.
│
├── secret.yaml
│   └── Armazenamento de informações sensíveis da aplicação.
│
├── deployment.yaml
│   └── Definição dos deployments e configuração dos containers.
│
├── service.yaml
│   └── Exposição dos serviços dentro do cluster.
│
└── ingress.yaml
    └── Regras de acesso externo à aplicação (quando utilizado).
```

---

# Recursos Kubernetes

## Namespace

O namespace é utilizado para organizar e isolar os recursos da aplicação dentro do cluster Kubernetes.

Benefícios:

- Organização dos recursos;
- Separação entre ambientes;
- Facilidade de gerenciamento.

---

## Deployments

Os deployments são responsáveis pela criação e gerenciamento dos pods da aplicação.

Responsáveis por:

- Definição das imagens Docker utilizadas;
- Quantidade de réplicas;
- Estratégia de atualização dos containers;
- Recuperação automática de pods.

Exemplo de fluxo:

```
Deployment
    |
    ↓
ReplicaSet
    |
    ↓
Pods
    |
    ↓
Containers
```

---

## Services

Os Services permitem a comunicação entre os componentes da aplicação dentro do cluster.

Responsáveis por:

- Descoberta dos serviços;
- Balanceamento interno;
- Comunicação entre pods.

Tipos utilizados:

- ClusterIP;
- NodePort;
- LoadBalancer.

---

## ConfigMaps

Os ConfigMaps armazenam configurações da aplicação que não possuem informações sensíveis.

Exemplos:

- Ambiente da aplicação;
- URLs internas;
- Configurações gerais.

As informações são injetadas nos containers através de variáveis de ambiente ou arquivos de configuração.

---

## Secrets

Os Secrets armazenam informações sensíveis utilizadas pela aplicação.

Exemplos:

- Senhas;
- Tokens;
- Chaves de acesso;
- Credenciais de banco de dados.

Boas práticas aplicadas:

- Não armazenar valores sensíveis diretamente no código;
- Separar configurações sensíveis das imagens Docker;
- Controlar acesso aos recursos Kubernetes.

---

# Fluxo de Execução

A arquitetura segue o fluxo:

```
Terraform
    |
    ↓
Amazon EKS
    |
    ↓
Kubernetes Cluster
    |
    ↓
Deployments
    |
    ↓
Pods
    |
    ↓
Containers Docker
    |
    ↓
Aplicação Laravel
```

---

# Deploy da Aplicação

## Configurar acesso ao cluster

Após a criação do cluster EKS:

```bash
aws eks update-kubeconfig \
--region <regiao> \
--name <nome-do-cluster>
```

Validar conexão:

```bash
kubectl get nodes
```

---

# Aplicação dos Manifests

Aplicar todos os recursos Kubernetes:

```bash
kubectl apply -f k8s/
```

Ou aplicar individualmente:

```bash
kubectl apply -f namespace.yaml

kubectl apply -f configmap.yaml

kubectl apply -f secret.yaml

kubectl apply -f deployment.yaml

kubectl apply -f service.yaml
```

---

# Verificação dos Recursos

## Listar pods

```bash
kubectl get pods
```

---

## Listar serviços

```bash
kubectl get services
```

---

## Visualizar detalhes de um pod

```bash
kubectl describe pod <nome-do-pod>
```

---

## Visualizar logs

```bash
kubectl logs <nome-do-pod>
```

---

# Atualização da Aplicação

Quando uma nova imagem Docker é criada:

Atualizar a imagem do deployment:

```bash
kubectl set image deployment/<nome> \
<container>=<nova-imagem>
```

Verificar atualização:

```bash
kubectl rollout status deployment/<nome>
```

---

# Rollback

Caso uma atualização apresente problemas:

```bash
kubectl rollout undo deployment/<nome>
```

O Kubernetes retorna para a versão anterior do deployment.

---

# Boas Práticas Aplicadas

- Infraestrutura separada da aplicação;
- Uso de containers Docker;
- Configurações externas através de ConfigMaps;
- Informações sensíveis utilizando Secrets;
- Deploy automatizado através de manifests;
- Organização dos recursos por responsabilidade;
- Uso de Kubernetes para gerenciamento dos serviços.

---

# Integração com CI/CD

O pipeline de CI/CD é responsável por validar alterações no código da aplicação.

Fluxo esperado:

```
Commit
  |
  ↓
GitHub Actions
  |
  ↓
Build Docker Image
  |
  ↓
Push Image Registry
  |
  ↓
Deploy Kubernetes
  |
  ↓
Aplicação Atualizada
```

---

# Observações

O Kubernetes é responsável somente pela camada de execução e gerenciamento dos containers.

A criação da infraestrutura AWS (EKS, RDS e recursos relacionados) é realizada através do Terraform.

A aplicação Laravel, infraestrutura e pipeline CI/CD possuem responsabilidades separadas para facilitar manutenção, escalabilidade e evolução do projeto.