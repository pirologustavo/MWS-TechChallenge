#!/usr/bin/env python3
"""Bootstrap the Docker Desktop development cluster without exposing secret values."""
import argparse
import base64
import json
from pathlib import Path
import secrets
import subprocess
import sys

import yaml

ROOT = Path(__file__).resolve().parents[1]
SERVICES = ['cliente', 'veiculo', 'estoque', 'funcionario', 'os']
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--context', default='docker-desktop')
parser.add_argument('--image-prefix', default='mwssandbox')
parser.add_argument('--tag', default='latest')
args = parser.parse_args()
if args.context != 'docker-desktop':
    sys.exit('Este bootstrap usa o MySQL local. Para EKS, provisione databases no RDS e os Secrets antes do CD.')


def kubectl(*command, stdin=None, quiet=False):
    result = subprocess.run(['kubectl', '--context', args.context, '--namespace', 'default', *command],
                            input=stdin, capture_output=True, text=True)
    if result.returncode:
        sys.exit('kubectl falhou: ' + ' '.join(command[:3]) + '\n' + result.stderr)
    if not quiet and result.stdout:
        print(result.stdout.strip(), flush=True)
    return result.stdout


def apply(resource):
    return kubectl('apply', '-f', '-', stdin=json.dumps(resource), quiet=True)


def secret(name, defaults):
    result = subprocess.run(['kubectl', '--context', args.context, '--namespace', 'default', 'get', 'secret', name, '-o', 'json'], capture_output=True, text=True)
    if result.returncode == 0:
        current = json.loads(result.stdout)
        defaults.update({key: base64.b64decode(value).decode() for key, value in current.get('data', {}).items()})
    apply({'apiVersion': 'v1', 'kind': 'Secret', 'metadata': {'name': name}, 'type': 'Opaque', 'stringData': defaults})
    print('Secret configurado: ' + name, flush=True)
    return defaults


shared = secrets.token_hex(32)
rabbit = secret('mws-rabbitmq-secret', {'username': 'mws', 'password': secrets.token_hex(24)})
mongo = secret('mws-mongodb-secret', {'username': 'root', 'password': secrets.token_hex(24)})
credentials = {}
for service in SERVICES:
    values = {'DB_USERNAME': service+'_app', 'DB_PASSWORD': secrets.token_hex(24),
              'APP_KEY': 'base64:'+base64.b64encode(secrets.token_bytes(32)).decode(), 'INTERNAL_API_TOKEN': shared}
    if service == 'os':
        values['OS_APPROVAL_SECRET'] = secrets.token_hex(32)
    if service == 'cliente':
        values.update({'MONGODB_URI': f'mongodb://{mongo["username"]}:{mongo["password"]}@mongodb:27017/?authSource=admin', 'MONGODB_DATABASE': 'cliente_audit'})
    credentials[service] = secret('mws-'+service+'-service-secret', values)
# Reuse the existing auth token if any Secret had already been provisioned.
shared = credentials['funcionario']['INTERNAL_API_TOKEN']
for service, values in credentials.items():
    values['INTERNAL_API_TOKEN'] = shared
    apply({'apiVersion':'v1','kind':'Secret','metadata':{'name':'mws-'+service+'-service-secret'},'type':'Opaque','stringData':values})

# Use the password already present inside the local MySQL pod, never print SQL/secrets.
pods = json.loads(kubectl('get', 'pods', '-l', 'app=mysql', '-o', 'json', quiet=True))['items']
if not pods:
    sys.exit('É necessário o MySQL local existente com label app=mysql.')
mysql_pod = pods[0]['metadata']['name']
sql = ''
for service, values in credentials.items():
    password = values['DB_PASSWORD']
    if not all(c.isalnum() or c in '_-' for c in password):
        sys.exit('DB_PASSWORD local deve usar letras, números, underscore ou hífen.')
    sql += f"CREATE DATABASE IF NOT EXISTS {service}_db; CREATE USER IF NOT EXISTS '{service}_app'@'%' IDENTIFIED BY '{password}'; ALTER USER '{service}_app'@'%' IDENTIFIED BY '{password}'; GRANT ALL ON {service}_db.* TO '{service}_app'@'%';\n"
result = subprocess.run(['kubectl','--context',args.context,'exec','-i',mysql_pod,'--','sh','-c','exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"'],input=sql,text=True,capture_output=True)
if result.returncode:
    sys.exit('Falha ao provisionar databases locais. Verifique acesso administrativo ao MySQL.')
print('Cinco databases e usuários exclusivos provisionados.', flush=True)
config = next(yaml.safe_load_all((ROOT/'k8s/mws-configmap.yaml').read_text()))
config['data'].update({'DB_HOST':'mws-db','APP_ENV':'production','APP_DEBUG':'false','CACHE_STORE':'array','SESSION_DRIVER':'file','LOG_CHANNEL':'stderr','FUNCIONARIO_API_URL':'http://funcionario-lv','CLIENTE_API_URL':'http://cliente-lv','VEICULO_API_URL':'http://veiculo-lv','ESTOQUE_API_URL':'http://estoque-lv'})
apply(config)
for file in ['rabbitmq.yaml','mongodb.yaml','mailhog.yaml']:
    kubectl('apply','-f',str(ROOT/'k8s'/file))
for service in SERVICES:
    for resource in yaml.safe_load_all((ROOT/'k8s'/f'{service}-api.yaml').read_text()):
        if resource['kind'] == 'Deployment':
            spec=resource['spec']['template']['spec']
            for container in spec.get('containers',[])+spec.get('initContainers',[]):
                container['image']=f'{args.image_prefix}-{service}-lv-api:{args.tag}'
                container['imagePullPolicy']='IfNotPresent'
        apply(resource)
for file, service in [('os-saga-worker.yaml','os'),('estoque-saga-worker.yaml','estoque'),('monolito.yaml','monolito')]:
    for resource in yaml.safe_load_all((ROOT/'k8s'/file).read_text()):
        if resource['kind'] == 'Deployment':
            for container in resource['spec']['template']['spec']['containers']:
                container['image']=f'{args.image_prefix}-{service}-lv-api:{args.tag}' if service!='monolito' else f'{args.image_prefix}-monolito:{args.tag}'
                container['imagePullPolicy']='IfNotPresent'
        apply(resource)
print('Recursos aplicados. Acompanhe: kubectl get pods; kubectl rollout status deployment/os-lv', flush=True)
