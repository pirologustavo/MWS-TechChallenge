# Configurar Datadog no Kubernetes

Este guia instala o Agent no cluster EKS e envia APM, logs, métricas DogStatsD
e métricas de CPU/memória. Use Bash/WSL nos comandos abaixo.

## 1. Prepare as chaves

No Datadog, crie uma API key em **Organization Settings → API Keys**. Crie uma
Application key em **Organization Settings → Application Keys** com os escopos
`dashboards_write` e `monitors_write`; o usuário proprietário também precisa
ter as permissões correspondentes. A API key permite ao Agent enviar telemetria;
a Application key permite ao script criar o dashboard e os monitores. Veja a
[documentação de API/Application keys](https://docs.datadoghq.com/account_management/api-app-keys/)
e as permissões da [API de dashboards](https://docs.datadoghq.com/api/latest/dashboards/create-a-new-dashboard/)
e da [API de monitores](https://docs.datadoghq.com/api/latest/monitors/create-a-monitor/).

O arquivo `.env` da raiz, que já está ignorado pelo Git, contém os campos
`DD_API_KEY`, `DD_APP_KEY` e `DD_SITE`. Preencha os dois segredos localmente e
defina `DD_SITE` conforme a região da sua organização, por exemplo
`datadoghq.com` ou `datadoghq.eu`. Não cole as chaves em issues, commits ou
mensagens.

## 2. Aponte kubectl para o cluster

Configure o contexto EKS e confira antes de criar recursos:

```bash
aws eks update-kubeconfig --region <REGIAO> --name <NOME_DO_CLUSTER>
kubectl config current-context
kubectl get nodes
```

Edite `k8s/datadog-values.yaml.example` e ajuste `clusterName` para o nome real
do cluster. O site deve corresponder ao valor `DD_SITE` do `.env`.

## 3. Crie o Secret do Agent

O comando abaixo lê a API key do `.env` sem mostrá-la no terminal e cria ou
atualiza o Secret `datadog-secret` no namespace `default`:

```bash
DD_API_KEY="$(sed -n 's/^DD_API_KEY=//p' .env)"
kubectl create secret generic datadog-secret \
  --namespace default \
  --from-file=api-key=<(printf '%s' "$DD_API_KEY") \
  --dry-run=client -o yaml | kubectl apply -f -
unset DD_API_KEY
```

O Agent usa esse Secret por meio de `datadog.apiKeyExistingSecret`. Restrinja
quem pode ler Secrets no cluster e habilite encryption at rest no etcd conforme
a política do cluster.

## 4. Instale o Datadog Agent

Se você já executou o comando gerado pelo assistente de instalação do Datadog,
não instale outro Agent por Helm. Esse comando pode ter usado outro namespace ou
o Datadog Operator; mantenha o método e namespace que ele configurou e siga
direto para as verificações abaixo. Use o Helm a seguir somente se ainda não
houver Agent no cluster.

O values file habilita APM na porta 8126, DogStatsD na 8125/UDP, coleta de logs,
processos e estado do Kubernetes:

No Docker Desktop, o kubelet/runtime pode não permitir a autodetecção do
hostname. Por isso o values file define `DD_HOSTNAME` com o nome do nó via
Downward API (`spec.nodeName`), evitando que o Agent encerre com erro de
hostname.

```bash
helm repo add datadog https://helm.datadoghq.com
helm repo update
helm upgrade --install datadog-agent datadog/datadog \
  --namespace default \
  --values k8s/datadog-values.yaml.example \
  --wait
```

As opções seguem o [guia oficial de instalação do Agent com Helm](https://docs.datadoghq.com/containers/kubernetes/installation/).

Confirme que os pods do Agent estão prontos:

```bash
helm status datadog-agent --namespace default
kubectl get pods --all-namespaces -l app.kubernetes.io/component=agent
```

Para validar a saúde do Agent, escolha um pod retornado pelo comando acima:

```bash
kubectl exec -n <NAMESPACE> <AGENT_POD> -- agent status
```

## 5. Reinicie as aplicações instrumentadas

As imagens PHP incluem o tracer Datadog. Construa e publique as novas imagens
antes do rollout. Confira as referências atuais para não aplicar uma imagem
antiga ou de outro registry:

```bash
kubectl get deployments -o custom-columns=NAME:.metadata.name,IMAGE:.spec.template.spec.containers[0].image
```

Atualize `image:` nos manifests em `k8s/` para apontar às imagens recém-
publicadas (ou deixe seu pipeline atualizar as imagens). Aplique-os somente
depois de conferir que ConfigMaps e Secrets de banco estão corretos:

```bash
kubectl apply -f k8s/cliente-api.yaml -f k8s/veiculo-api.yaml \
  -f k8s/estoque-api.yaml -f k8s/funcionario-api.yaml \
  -f k8s/os-api.yaml -f k8s/monolito.yaml
kubectl rollout restart deployment/cliente-lv deployment/veiculo-lv \
  deployment/estoque-lv deployment/funcionario-lv deployment/os-lv \
  deployment/monolito-deployment
```

Os manifests configuram `DD_AGENT_HOST` com o IP do node, probes `/up` (Laravel)
ou `/healthz.php` (Monólito), logs JSON e recursos de CPU/memória.

As imagens precisam ser reconstruídas/publicadas após a inclusão do tracer nos
Dockerfiles. O assistente do Datadog instala o Agent; ele não substitui o tracer
PHP destas imagens. Não habilite uma segunda instrumentação automática (SSI)
para os mesmos pods que já carregam o tracer na imagem.

## 6. Confirme que há telemetria

Depois do rollout, gere uma requisição de teste para a API de OS:

```bash
kubectl port-forward service/os-lv 8005:80
```

Em outro terminal:

```bash
curl -i http://127.0.0.1:8005/api/ping
```

Confirme no Datadog que aparecem o cluster na página **Infrastructure →
Containers**, o serviço `os-lv` em **APM → Services** e os logs JSON do serviço
em **Logs**. A descoberta pode levar alguns minutos. Se os Agent pods estiverem
prontos, mas `os-lv` não aparecer no APM, confirme que a imagem implantada contém
o tracer e que os pods foram reiniciados.

## 7. Crie dashboard e monitores

Com as chaves preenchidas no `.env`, rode na raiz do repositório:

```bash
python3 observability/provision-datadog.py
```

O script lê as chaves sem imprimi-las e cria o dashboard e três monitores. Rode-o
uma vez por organização. Depois, associe os monitores ao canal de notificação
da equipe dentro do Datadog. Métricas começam a aparecer após Agent e aplicações
estarem ativos; os tempos por status usam o campo `status_changed_at` da OS.
