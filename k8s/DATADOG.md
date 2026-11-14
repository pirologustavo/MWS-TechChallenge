# Configurar Datadog no Kubernetes

Este guia instala o Agent em Kubernetes e envia APM, logs e métricas DogStatsD.
Use Bash/WSL nos comandos abaixo. O arquivo de valores versionado está
preparado para desenvolvimento com Docker Desktop; em EKS, ajuste o nome do
cluster e a configuração do Kubelet conforme a seção abaixo.

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

### Docker Desktop e acesso ao Kubelet

No Docker Desktop, os nós Kubernetes e o Kubelet rodam dentro da VM Linux do
Docker Desktop. O endereço anunciado em `status.hostIP` pode não ser roteável
do pod do Agent até o Kubelet. Isso causa mensagens como `impossible to reach
Kubelet with HTTP` e deixa checks `kubelet`/`orchestrator_pod` em erro, mesmo
quando o pod do Agent está `Running` e a API key é válida. O log recebido para
este projeto apresentou essa condição.

O values file aplica duas configurações locais. `datadog.kubelet.tlsVerify:
false` permite a conexão HTTPS quando o certificado do Kubelet local é
autossinado ou não contém o IP anunciado pelo nó. Essa é a causa mais comum
documentada para falha de handshake TLS do Agent, e os logs deste projeto
também sugerem conferir essa opção. O chart oficial recomenda o mesmo ajuste
para clusters locais como kind/minikube. **Essa opção desativa a validação do
certificado e deve ficar restrita ao Docker Desktop de desenvolvimento; não a
use em EKS/produção.**

`datadog.kubelet.useApiServer: true` faz o Agent consultar a lista de pods pelo
API Server em vez do endpoint de listagem do Kubelet. Requer Agent 7.65 ou
superior. Isso ajuda na descoberta, mas **não redireciona todas as chamadas do
Kubelet**: a documentação do chart limita essa opção à lista de pods. Se o
Agent continuar reportando falha no check `kubelet` ou em endpoints de
estatísticas após o rollout, o problema restante é conectividade/endpoint do
Kubelet dentro da VM Docker Desktop; o fallback do API Server não fornece essas
estatísticas.

O arquivo também define `DD_HOSTNAME` com `spec.nodeName`, para evitar o erro
separado de autodetecção do hostname observado anteriormente. Essa configuração
de hostname não corrige conectividade com o Kubelet.

Para EKS ou outro cluster em que o Agent alcança diretamente o IP do nó e seu
Kubelet, remova `datadog.kubelet.tlsVerify: false` (o padrão é `true`) e use
`datadog.kubelet.useApiServer: false` (padrão do chart), salvo se quiser a
consulta de lista de pods pelo API Server.

```bash
helm repo add datadog https://helm.datadoghq.com
helm repo update
helm upgrade --install datadog-agent datadog/datadog \
  --namespace default \
  --values k8s/datadog-values.yaml.example \
  --wait \
  --timeout 10m
```

As opções seguem o [guia oficial de instalação do Agent com Helm](https://docs.datadoghq.com/containers/kubernetes/installation/).

Confirme que os pods do Agent estão prontos:

```bash
helm status datadog-agent --namespace default
kubectl get pods --all-namespaces -l app.kubernetes.io/component=agent
```

Para validar a saúde do Agent, escolha um pod retornado pelo comando acima:

```bash
kubectl exec -n <NAMESPACE> <AGENT_POD> -c agent -- agent status
```

No Docker Desktop, confirme também que a opção chegou ao pod e verifique os
checks Kubelet no status:

```bash
kubectl get daemonset datadog-agent -n default \
  -o jsonpath='{.spec.template.spec.containers[?(@.name=="agent")].env[?(@.name=="DD_KUBELET_USE_API_SERVER")].value}{"\n"}'
kubectl get daemonset datadog-agent -n default \
  -o jsonpath='{.spec.template.spec.containers[?(@.name=="agent")].env[?(@.name=="DD_KUBELET_TLS_VERIFY")].value}{"\n"}'
POD="$(kubectl get pods -n default -l app.kubernetes.io/component=agent \
  -o jsonpath='{.items[0].metadata.name}')"
kubectl exec -n default "$POD" -c agent -- agent status
```

O primeiro comando deve imprimir `true`. Confirme também no DaemonSet que
`DD_KUBELET_TLS_VERIFY` está como `false`. `agent status` mostra se o check
`kubelet` ficou saudável. Se continuar em erro, diferencie falha de TLS de
falha de rota/endpoint consultando os logs do container `agent`; TLS desativado
não corrige endereço ou porta inacessíveis. Em Docker Desktop, trate métricas
de CPU/memória do host e dos
containers como telemetria do runtime/host local; para telemetria Kubernetes
completa do Kubelet, valide em um cluster em que o Agent tenha conectividade
com os endpoints do nó (por exemplo, EKS). Isso é uma limitação do ambiente
local, não uma falha de autenticação Datadog.

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
