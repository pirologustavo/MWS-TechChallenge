#!/usr/bin/env python3
"""Provisiona PVs locais de laboratório; não remove PVCs, PVs ou diretórios."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[1]
SERVICES = {"mongodb": ("5Gi", 999, 999), "rabbitmq": ("2Gi", 100, 101)}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--context", required=True)
    parser.add_argument("--mongodb-node", help="Hostname do nó para um volume novo")
    parser.add_argument("--rabbitmq-node", help="Hostname do nó para um volume novo")
    args = parser.parse_args()
    base = ["kubectl", "--context", args.context, "--request-timeout=30s"]

    def kubectl(*cmd, document=None):
        result = subprocess.run(base + list(cmd), check=True, text=True,
                                input=None if document is None else json.dumps(document),
                                capture_output=True)
        return result.stdout

    def get(kind, name=None, namespace=None):
        cmd = ["get", kind]
        if name:
            cmd += [name, "--ignore-not-found"]
        if namespace:
            cmd += ["-n", namespace]
        raw = kubectl(*cmd, "-o", "json")
        return json.loads(raw) if raw.strip() else None

    def apply(document):
        print(kubectl("apply", "-f", "-", document=document).strip(), flush=True)

    # Valida o overlay antes de alterar qualquer recurso do cluster.
    rendered = kubectl("kustomize", str(ROOT / "k8s/overlays/eks-local"),
                       "--load-restrictor=LoadRestrictionsNone")

    nodes = [n["metadata"]["labels"]["kubernetes.io/hostname"]
             for n in get("nodes")["items"]
             if not n["spec"].get("unschedulable") and any(
                 c["type"] == "Ready" and c["status"] == "True"
                 for c in n["status"].get("conditions", []))]
    if not nodes:
        raise SystemExit("Nenhum nó Ready disponível.")
    nodes.sort()
    plans = []
    for index, (service, (capacity, uid, gid)) in enumerate(SERVICES.items()):
        pv_name, claim = f"mws-local-{service}", f"data-{service}-0"
        pv = get("pv", pv_name)
        pvc = get("pvc", claim, "default")
        if pvc and pvc["spec"].get("storageClassName") not in (None, "mws-local"):
            raise SystemExit(f"{claim}: outra StorageClass; migração manual necessária.")
        if pvc and pvc["spec"].get("volumeName") not in (None, "", pv_name):
            raise SystemExit(f"{claim}: já vinculado a outro PV; operação interrompida.")
        selected = getattr(args, f"{service}_node")
        if pv:
            spec = pv["spec"]
            if (spec.get("local", {}).get("path") != f"/var/lib/mws/{service}"
                    or spec.get("storageClassName") != "mws-local"
                    or spec.get("claimRef", {}).get("name") != claim
                    or spec.get("claimRef", {}).get("namespace") != "default"):
                raise SystemExit(f"{pv_name}: configuração inesperada; revisar manualmente.")
            node = spec["nodeAffinity"]["required"]["nodeSelectorTerms"][0]["matchExpressions"][0]["values"][0]
            if selected and selected != node:
                raise SystemExit(f"{pv_name}: não é possível trocar o nó sem migrar os dados.")
        else:
            node = selected or nodes[index % len(nodes)]
        if node not in nodes:
            raise SystemExit(f"Nó {node} indisponível; restaure o nó ou migre os dados.")
        plans.append((service, capacity, uid, gid, pv_name, claim, node, pvc, pv))

    # Preparação restrita ao diretório de cada serviço, sem acesso a Secrets ou IAM.
    for service, capacity, uid, gid, pv_name, claim, node, pvc, pv in plans:
        prepare = f"chown {uid}:{gid} /volume; chmod 0770 /volume"
        if service == "rabbitmq":
            prepare += "; if [ -f /volume/.erlang.cookie ]; then chmod 0600 /volume/.erlang.cookie; fi"
        job = {"apiVersion": "batch/v1", "kind": "Job", "metadata": {
            "generateName": f"mws-local-prepare-{service}-", "namespace": "default"},
            "spec": {"ttlSecondsAfterFinished": 300, "backoffLimit": 1, "template": {"spec": {
                "restartPolicy": "Never", "automountServiceAccountToken": False,
                "nodeSelector": {"kubernetes.io/hostname": node},
                "containers": [{"name": "prepare", "image": "busybox:1.37",
                    "command": ["sh", "-ec", prepare],
                    "securityContext": {"runAsUser": 0, "allowPrivilegeEscalation": False},
                    "resources": {"requests": {"cpu": "10m", "memory": "16Mi"},
                                  "limits": {"cpu": "100m", "memory": "32Mi"}},
                    "volumeMounts": [{"name": "data", "mountPath": "/volume"}]}],
                "volumes": [{"name": "data", "hostPath": {
                    "path": f"/var/lib/mws/{service}", "type": "DirectoryOrCreate"}}]}}}}
        created = json.loads(kubectl("create", "-f", "-", "-o", "json", document=job))
        job_name = created["metadata"]["name"]
        print(f"Preparando {service} no nó {node} ({job_name})", flush=True)
        kubectl("wait", "-n", "default", "--for=condition=complete", f"job/{job_name}", "--timeout=180s")

    kubectl("apply", "-f", str(ROOT / "k8s/overlays/eks-local/storageclass.yaml"))
    for service, capacity, uid, gid, pv_name, claim, node, pvc, pv in plans:
        if not pv:
            apply({"apiVersion": "v1", "kind": "PersistentVolume", "metadata": {"name": pv_name},
                   "spec": {"capacity": {"storage": capacity}, "volumeMode": "Filesystem",
                       "accessModes": ["ReadWriteOnce"], "persistentVolumeReclaimPolicy": "Retain",
                       "storageClassName": "mws-local", "local": {"path": f"/var/lib/mws/{service}"},
                       "claimRef": {"namespace": "default", "name": claim},
                       "nodeAffinity": {"required": {"nodeSelectorTerms": [{"matchExpressions": [{
                           "key": "kubernetes.io/hostname", "operator": "In", "values": [node]}]}]}}}})
        if pvc and pvc["spec"].get("storageClassName") is None:
            kubectl("patch", "pvc", claim, "-n", "default", "--type=merge", "-p",
                    json.dumps({"spec": {"storageClassName": "mws-local"}}))
        sts = get("statefulset", service, "default")
        if sts and sts["spec"]["volumeClaimTemplates"][0]["spec"].get("storageClassName") != "mws-local":
            # Campo imutável: recria somente o controlador, preservando pods e PVCs.
            kubectl("delete", "statefulset", service, "-n", "default", "--cascade=orphan")
    result = subprocess.run(base + ["apply", "-f", "-"], input=rendered,
                            text=True, capture_output=True, check=True)
    print(result.stdout, flush=True)
    for service in SERVICES:
        print(kubectl("rollout", "status", f"statefulset/{service}", "-n", "default", "--timeout=300s"), flush=True)
    print(kubectl("get", "pvc", "-n", "default"), flush=True)


if __name__ == "__main__":
    try:
        main()
    except subprocess.CalledProcessError as exc:
        raise SystemExit(f"Comando falhou: {exc.stderr or exc.stdout}") from exc
