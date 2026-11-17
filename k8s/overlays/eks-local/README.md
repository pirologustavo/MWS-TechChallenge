# Armazenamento local no EKS do laboratório

MongoDB e RabbitMQ usam diretórios nos discos dos nós através de PersistentVolumes
`local`, sem driver EBS, add-ons ou alterações IAM. A classe `mws-local` usa
`kubernetes.io/no-provisioner`, `WaitForFirstConsumer` e retenção `Retain`.
Ela não é a classe padrão do cluster.

## Aplicação

Pré-requisitos: Python 3, kubectl configurado, nós Ready e permissão Kubernetes
para criar Jobs, PVs, PVCs, StorageClasses, Services e StatefulSets. A política do
cluster precisa permitir o Job de preparação com hostPath e usuário root. O Job
monta somente o diretório do serviço e não recebe token da service account.
Os Secrets `mws-mongodb-secret` e `mws-rabbitmq-secret` devem existir.

Na raiz do repositório de integração:

```bash
python3 scripts/configure-local-storage.py --context mws-eks
```

O script seleciona nós Ready, prepara `/var/lib/mws/mongodb` e
`/var/lib/mws/rabbitmq`, cria PVs reservados para os PVCs de cada serviço e aplica
o overlay. Para escolher os nós na primeira execução:

```bash
python3 scripts/configure-local-storage.py --context mws-eks \
  --mongodb-node '<hostname-do-no>' \
  --rabbitmq-node '<hostname-do-no>'
```

Cada PV usa `nodeAffinity` para que o scheduler mantenha o pod no nó dos dados.
Execuções posteriores reutilizam os nós registrados nos PVs. O script interrompe
se houver outro volume/classe, se o nó original estiver indisponível ou se houver
tentativa de trocar o nó. Não remove PVCs, PVs nem diretórios. PVCs pendentes sem
classe recebem `mws-local`; somente os controladores StatefulSet antigos são
recriados com `--cascade=orphan` para atualizar o template imutável, preservando
pods e PVCs. Os Jobs de preparação são removidos automaticamente após 5 minutos.
O overlay remove o `fsGroup` do RabbitMQ: a imagem já gerencia a propriedade dos
arquivos e a alteração recursiva de permissões pelo kubelet tornaria o arquivo
`.erlang.cookie` acessível ao grupo, impedindo a inicialização após um reinício.
A preparação mantém esse arquivo com modo `0600`.

Para atualizações, reutilize o script. Não reaplique diretamente
`k8s/mongodb.yaml` ou `k8s/rabbitmq.yaml` neste ambiente: esses manifests base
usam a classe padrão do cluster. Para inspecionar o overlay:

```bash
kubectl kustomize k8s/overlays/eks-local \
  --load-restrictor=LoadRestrictionsNone
```

A opção permite ao overlay ler os dois manifests base deste repositório.

## Validação

```bash
kubectl --context mws-eks get pv
kubectl --context mws-eks -n default get pvc
kubectl --context mws-eks -n default get pods -o wide
kubectl --context mws-eks -n default rollout status statefulset/mongodb
kubectl --context mws-eks -n default rollout status statefulset/rabbitmq
```

Os PVCs `data-mongodb-0` (5Gi) e `data-rabbitmq-0` (2Gi) devem estar `Bound`;
os dois pods devem ficar `1/1 Running`. Os workers da Saga reconectam ao broker.

## Persistência e recuperação

A recriação de um pod preserva documentos, credenciais e mensagens persistentes
no mesmo nó. O disco local não replica os dados. Se o nó for perdido, substituído
ou seu disco for apagado, restaure um backup em outro nó e faça uma migração
planejada dos volumes; o script não troca a afinidade automaticamente.
Não destrua o node group antes de fazer backup do MongoDB e dos dados necessários
do RabbitMQ. `Retain` protege contra limpeza automática ao excluir um PVC, mas
não protege contra perda do disco. Não exclua PVs/PVCs como forma de reiniciar.

As capacidades de 5Gi/2Gi são usadas no vínculo dos PVCs, sem quota de diretório;
monitore também o espaço livre no disco do nó. Esta configuração é destinada à
apresentação no laboratório, sem alta disponibilidade. As APIs continuam usando
seus próprios databases/usuários no RDS; o MongoDB permanece exclusivo do Cliente.

Referência: [volumes locais do Kubernetes](https://kubernetes.io/docs/concepts/storage/volumes/#local).
