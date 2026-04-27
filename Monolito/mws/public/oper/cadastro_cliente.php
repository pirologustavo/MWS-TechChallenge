<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Cliente\Application\ClienteServiceInterface;
use App\Seguranca\Application\AuthService;
use App\Cliente\Application\ClienteService;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$mensagemErro = "";
$mensagemSucesso = "";

$clienteService = new ClienteService();
$estados = $clienteService::ESTADOS;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome_cliente'] ?? null;
    $email = $_POST['email_cliente'] ?? null;
    $cpf = $_POST['cpf_cliente'] ?? null;
    $endereco = $_POST['endereco_cliente'] ?? null;
    $numero = $_POST['numero_endereco_cliente'] ?? null;
    $estado = $_POST['estado_cliente'] ?? null;
    $cidade = $_POST['cidade_cliente'] ?? null;
    $cep = $_POST['cep_cliente'] ?? null;
    $telefone = $_POST['telefone_cliente'] ?? null;

    $cliente = [
        'nome' => $nome,
        'email' => $email,
        'cpf' => $cpf,
        'endereco' => $endereco . ", $numero",
        'estado' => $estado,
        'cidade' => $cidade,
        'cep' => $cep,
        'telefone' => $telefone
    ];

    function processarCadastro(ClienteServiceInterface $clienteService, array $cliente) {
        $clienteService->salvar($cliente);
    }

    try {
        processarCadastro($clienteService, $cliente);

        $mensagemSucesso = "Cliente cadastrado com sucesso!";
    } catch (Exception $ex) {
        $mensagemErro = $ex->getMessage();
    }
}

require '../header.php';
?>

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


<h1>Cadastro Cliente</h1>

<form method="POST">
    <label for="nome"><strong style="color:red">*</strong>Nome Completo</label>
    <input id="nome" name="nome_cliente" required>

    <label for="email"><strong style="color:red">*</strong>E-mail</label>
    <input id="email" name="email_cliente" required>

    <label for="cpf"><strong style="color:red">*</strong>CPF</label>
    <input id="cpf" name="cpf_cliente" required>

    <label for="cep"><strong style="color:red">*</strong>CEP</label>
    <input id="cep" name="cep_cliente" required>

    <label for="endereco"><strong style="color:red">*</strong>Endereço</label>
    <input id="endereco" name="endereco_cliente" required>

    <label for="numero">Número</label>
    <input id="numero" name="numero_endereco_cliente">

    <label for="estado"><strong style="color:red">*</strong>Estado</label>
    <select id="estado" name="estado_cliente" required>
        <option value="">Selecione...</option>
        <?php foreach ($estados as $estado => $sigla): ?>
            <option value="<?php echo $sigla; ?>"><?php echo $estado; ?></option>
        <?php endforeach; ?>
    </select>


    <label for="cidade"><strong style="color:red">*</strong>Cidade</label>
    <input id="cidade" name="cidade_cliente" required>

    <label for="telefone"><strong style="color:red">*</strong>Telefone</label>
    <input id="telefone" name="telefone_cliente" required>

    <button>Cadastrar</button>
</form>

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
