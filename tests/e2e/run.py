"""Given/When/Then BDD against real APIs, MySQL, MongoDB and RabbitMQ."""
import json
import time
import urllib.request


def request(service, path, method='GET', data=None, token=None):
    headers = {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Correlation-ID': 'bdd-distributed'}
    if token:
        headers['Authorization'] = 'Bearer ' + token
    req = urllib.request.Request('http://' + service + '/api/' + path, method=method, headers=headers,
                                 data=None if data is None else json.dumps(data).encode())
    with urllib.request.urlopen(req, timeout=20) as response:
        return json.load(response)


def wait_saga(osid, token):
    for _ in range(90):
        state = request('os', f'os/{osid}/saga', token=token)['saga_status']
        if state in ['COMPLETED', 'CONCLUIDA']:
            return
        if state in ['COMPENSATED', 'COMPENSADA']:
            raise AssertionError('Unexpected compensation')
        time.sleep(1)
    raise AssertionError('Saga did not complete')


# Given: real customer, vehicle, employee and stock, using authenticated REST.
token = request('funcionario', 'login', 'POST', {'usr': 'admin', 'password': 'admin123'})['token']
nonce = str(time.time_ns())[-11:]
customer = request('cliente', 'clientes', 'POST', {'nome': 'Cliente BDD', 'cpf': nonce, 'email': nonce+'@example.test',
    'cep': '01001000', 'endereco': 'Rua BDD', 'estado': 'SP', 'cidade': 'São Paulo', 'telefone': '11999999999'}, token)['data']
car = request('veiculo', 'veiculos/salvar', 'POST', {'modelo': 'BDD', 'marca': 'Teste', 'placa': 'BDD'+nonce[-4:],
    'ano': 2026, 'clientid': customer['clientid']}, token)['data']
stock = request('estoque', 'estoque/salvar', 'POST', {'descricao': 'Peça BDD', 'tipo': 'peca',
    'quantidade_atual': 10, 'quantidade_minima': 1, 'valor_custo': 100, 'valor_venda': 250}, token)['data']
mechanic = request('funcionario', 'funcionario/buscarMecanico', token=token)[0]['funcid']
items = [{'id': stock['estoqid'], 'qtd': 1, 'preco': 250}]

# When: open order; the real RabbitMQ workers reserve stock.
opened = request('os', 'os/salvar', 'POST', {'clientid': customer['clientid'], 'carid': car['carid'], 'funcid': mechanic, 'itens': items}, token)
osid = opened['osid']
wait_saga(osid, token)
assert request('estoque', f'estoque/buscarPorId/{stock["estoqid"]}', token=token)['quantidade_atual'] == 9
request('os', f'os/emDiagnostico/{osid}', 'POST', {}, token)
request('os', f'os/analiseOs/{osid}', 'POST', {'analise': 'Troca da peça', 'itens': items}, token)
request('os', f'os/aprovarOs/{osid}', 'POST', {}, token)
request('os', f'os/finalizarOs/{osid}', 'POST', {}, token)
request('os', f'os/{osid}/checkout', 'POST', {}, token)
request('os', f'os/{osid}/pagamento/verificar', 'POST', {'payment_id': '123'}, token)
request('os', f'os/entregar/{osid}', 'POST', {}, token)

# Then: order delivered, historical item preserved and MongoDB audit persisted.
result = request('os', f'os/buscarPorId/{osid}', token=token)
assert result['os']['status_atual'] == 'Entregue'
assert len(result['itens']) == 1
assert request('cliente', f'clientes/{customer["clientid"]}/auditoria', token=token)

# A second order exercises compensating inventory return.
second = request('os', 'os/salvar', 'POST', {'clientid': customer['clientid'], 'carid': car['carid'], 'funcid': mechanic, 'itens': items}, token)['osid']
wait_saga(second, token)
# Use authenticated deletion to exercise a real compensating inventory return.
request('os', f'os/deletarOs/{second}', 'POST', {}, token)
wait_saga(second, token)
assert request('estoque', f'estoque/buscarPorId/{stock["estoqid"]}', token=token)['quantidade_atual'] == 9
print('PASS: distributed lifecycle, persisted audit and compensating stock return')
