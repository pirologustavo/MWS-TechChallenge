<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Cliente\Application\ClienteService;
use App\Cliente\Application\ClienteServiceInterface;
use App\Seguranca\Application\AuthService;
use App\Veiculo\Application\VeiculoService;
use App\Veiculo\Application\VeiculoServiceInterface;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$clienteService = new ClienteService();
$estados = $clienteService::ESTADOS;
$idParaEditar = $_GET['clientid'] ?? null;

$mensagemErro = "";
$mensagemSucesso = "";

if (isset($_GET['sucesso'])) {
    $mensagemSucesso = "Informações atualizadas com sucesso!";
}

function listarTodos(ClienteServiceInterface $clienteService)
{
    return $clienteService->listarTodos();
}

try {
    if($idParaEditar){
        $clienteParaEditar = $clienteService->buscarPorId($idParaEditar);
    }

    if (!$idParaEditar) {
        $relatorio = listarTodos($clienteService);
    }
} catch (Exception $e) {
    $mensagem = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'] ?? null;
    $email = $_POST['email'] ?? null;
    $cpf = $_POST['cpf'] ?? null;
    $endereco = $_POST['endereco'] ?? null;
    $estado = $_POST['estado'] ?? null;
    $cidade = $_POST['cidade'] ?? null;
    $cep = $_POST['cep'] ?? null;
    $telefone = $_POST['telefone'] ?? null;

    $cliente = [
        'nome' => $nome,
        'email' => $email,
        'cpf' => $cpf,
        'endereco' => $endereco,
        'estado' => $estado,
        'cidade' => $cidade,
        'cep' => $cep,
        'telefone' => $telefone
    ];

    function processarCadastro(ClienteServiceInterface $clienteService, $id, array $cliente)
    {
        $clienteService->atualizarCliente($id, $cliente);
    }

    try {
        processarCadastro($clienteService, $idParaEditar, $cliente);

        header("Location: editar_cliente.php?clientid=" . $idParaEditar . "&sucesso=1");
        exit;
    } catch (Exception $ex) {
        $mensagemErro = $ex->getMessage();
    }
}

require '../header.php';
?>

<?php if(!$idParaEditar): ?>

<h2>Gerenciar Clientes</h2>
<table border="1" style="width:100%; text-align:left; border-collapse: collapse;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th>ID</th>
        <th>Nome</th>
        <th>CPF</th>
        <th>Ações</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($relatorio as $cliente): ?>
        <tr>
            <td><?= $cliente->clientid ?></td>
            <td><?= $cliente->nome ?></td>
            <td><?= $cliente->cpf ?></td>
            <td style="padding: 10px">
                <a href="editar_cliente.php?clientid=<?= $cliente->clientid ?>"
                   style="padding: 5px 10px; background: orange; color: white; text-decoration: none; border-radius: 3px;">
                    Editar
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php else: ?>
    <?php if ($mensagemErro): ?>
        <div style="background: #ffcccc; color: #990000; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Erro:</strong> <?php  echo $mensagemErro; ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagemSucesso): ?>
        <div style="background: #ccffcc; color: #006600; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Sucesso:</strong> <?php  echo $mensagemSucesso; ?>
        </div>
    <?php endif; ?>
    <h2>Editando Cliente: <?= $clienteParaEditar->nome ?></h2>
    <form method="POST" style="max-width: 400px; border: 1px solid #ccc; padding: 20px;">
        <input type="hidden" name="clientid" value="<?= $clienteParaEditar->clientid ?>">

        <p>
            <label>Nome:</label><br>
            <input type="text" name="nome" value="<?= $clienteParaEditar->nome ?>" style="width: 100%">
        </p>

        <p>
            <label>E-mail:</label><br>
            <input type="text" name="email" value="<?= $clienteParaEditar->email ?>" style="width: 100%">
        </p>

        <p>
            <label>CPF:</label><br>
            <input type="text" name="cpf" value="<?= $clienteParaEditar->cpf ?>" style="width: 100%">
        </p>

        <p>
            <label>CEP:</label><br>
            <input type="text" name="cep" value="<?= $clienteParaEditar->cep ?>" style="width: 100%" id="cep">
        </p>

        <p>
            <label>Endereço:</label><br>
            <input type="text" name="endereco" value="<?= $clienteParaEditar->endereco ?>" style="width: 100%" id="endereco">
        </p>

        <p>
            <label>Cidade:</label><br>
            <input type="text" name="cidade" value="<?= $clienteParaEditar->cidade ?>" style="width: 100%" id="cidade">
        </p>

        <label for="estado">Estado</label><br>
        <select id="estado" name="estado" style="width: 100%">
            <option value="">Selecione um estado</option>
            <?php foreach ($estados as $nomeEstado => $sigla): ?>
                <option value="<?= $sigla; ?>"
                    <?= ($sigla === $clienteParaEditar->estado) ? 'selected' : ''; ?>>
                    <?= $nomeEstado; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <p>
            <label>Telefone:</label><br>
            <input type="text" name="telefone" value="<?= $clienteParaEditar->telefone ?>" style="width: 100%">
        </p>

        <button type="submit" style="background: green; color: white; border: none; padding: 10px;">Salvar Alterações</button>
        <a href="editar_cliente.php">Voltar para a lista</a>
    </form>
<?php endif; ?>

<script>
    document.getElementById('cep').addEventListener('blur', function() {
        let cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(res => res.json())
                .then(dados => {
                    if (!dados.erro) {
                        if(document.getElementById('endereco')) document.getElementById('endereco').value = dados.logradouro;
                        if(document.getElementById('cidade'))   document.getElementById('cidade').value = dados.localidade;

                        let campoEstado = document.getElementById('estado');
                        if(campoEstado) campoEstado.value = dados.uf;

                    } else {
                        alert("CEP não encontrado.");
                    }
                })
                .catch(error => console.error('Erro na requisição:', error));
        }
    });
</script>

