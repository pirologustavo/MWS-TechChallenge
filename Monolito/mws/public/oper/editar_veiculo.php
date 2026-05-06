<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Cliente\Application\ClienteService;
use App\Seguranca\Application\AuthService;
use App\Veiculo\Application\VeiculoService;
use App\Veiculo\Application\VeiculoServiceInterface;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$veiculoService = new VeiculoService();
$idParaEditar = $_GET['carid'] ?? null;

$mensagemErro = "";
$mensagemSucesso = "";

if (isset($_GET['sucesso'])) {
    $mensagemSucesso = "Informações atualizadas com sucesso!";
}

function listarTodos(VeiculoServiceInterface $veiculoService)
{
    return $veiculoService->listarTodos();
}

try {
    if ($idParaEditar) {
        $veiculoParaEditar = $veiculoService->buscarPorId($idParaEditar);
    }

    if (!$idParaEditar) {
        $relatorio = listarTodos($veiculoService);
    }
} catch (Exception $e) {
    $mensagemErro = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST["acao"];

    if ($acao == 'editar') {
        $modelo = $_POST['modelo'] ?? null;
        $marca = $_POST['marca'] ?? null;
        $placa = $_POST['placa'] ?? null;
        $ano = $_POST['ano'] ?? null;
        $cliente = $_POST['clientid'] ?? null;

        $veiculo = [
                'modelo' => $modelo,
                'marca' => $marca,
                'placa' => $placa,
                'ano' => $ano,
                'clientid' => $cliente
        ];

        function processarCadastro(VeiculoServiceInterface $veiculoService, $id, $veiculo)
        {
            $veiculoService->atualizarVeiculo($id, $veiculo);
        }

        try {
            processarCadastro($veiculoService, $idParaEditar, $veiculo);
            header ("Location: editar_veiculo.php?carid=" . $idParaEditar . "&sucesso=1");
            exit;
        } catch (Exception $e) {
            $mensagemErro = $e->getMessage();
        }
    }

    if ($acao == 'deletar') {
        function veiculoEstoque(VeiculoServiceInterface $veiculoService, $id) {
            $veiculoService->veiculoEstoque($id);
        }

        try {
            veiculoEstoque($veiculoService, $idParaEditar);

            header("Location: editar_veiculo.php");
            exit;
        } catch (Exception $e) {
            $mensagemErro = $e->getMessage();
        }
    }
}

require '../header.php';
?>

<?php if(!$idParaEditar): ?>

<h2>Gerenciar Veiculos</h2>
<table border="1" style="width:100%; text-align:left; border-collapse: collapse;">
    <thead>
    <tr style="background-color: #f2f2f2;">
        <th>Placa</th>
        <th>Modelo</th>
        <th>Marca</th>
        <th>Proprietário</th>
        <th>Ações</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($relatorio as $veiculo):?>
        <tr>
            <td><?= $veiculo->placa ?></td>
            <td><?= $veiculo->modelo ?></td>
            <td><?= $veiculo->marca ?></td>
            <td><?= $veiculo->nome ?></td>
            <td style="padding: 10px">
                <a href="editar_veiculo.php?carid=<?= $veiculo->carid ?>"
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
    <h2>Editando Veículo: <?= $veiculoParaEditar->placa ?></h2>
    <form method="POST" style="max-width: 400px; border: 1px solid #ccc; padding: 20px;">
        <input type="hidden" name="carid" value="<?= $veiculoParaEditar->carid ?>">

        <p>
            <label>Modelo:</label><br>
            <input type="text" name="modelo" value="<?= $veiculoParaEditar->modelo ?>" style="width: 100%">
        </p>

        <p>
            <label>Marca:</label><br>
            <input type="text" name="marca" value="<?= $veiculoParaEditar->marca ?>" style="width: 100%">
        </p>

        <p>
            <label>Placa:</label><br>
            <input type="text" name="placa" value="<?= $veiculoParaEditar->placa ?>" style="width: 100%">
        </p>

        <p>
            <label>ano:</label><br>
            <input type="text" name="ano" value="<?= $veiculoParaEditar->ano ?>" style="width: 100%" id="cep">
        </p>

        <p>
            <label>Proprietário:</label><br>
            <input type="text" name="endereco" value="<?= $veiculoParaEditar->nome ?>" style="width: 100%" id="endereco">
        </p>

        <button type="submit" name="acao" value="editar" style="background: green; color: white; border: none; padding: 10px;">Salvar Alterações</button>
        <button type="submit" name="acao" value="deletar"
                style="background: red; color: white;"
                onclick="return confirm('Tem certeza que deseja excluir este item?')">
            Deletar veiculo
        </button>
        <a href="editar_veiculo.php">Voltar para a lista</a>
    </form>
<?php endif; ?>