<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use App\Estoque\Application\EstoqueService;
use App\Estoque\Application\EstoqueServiceInterface;
use App\Seguranca\Application\AuthService;

if (!AuthService::authenticate()) {
    header("Location: /index.php");
    exit;
}

$estoqueService = new EstoqueService();
$idParaEditar = $_GET['estoqid'] ?? null;

$mensagemErro = "";
$mensagemSucesso = "";

if (isset($_GET['sucesso'])) {
    $mensagemSucesso = "Informações atualizadas com sucesso!";
}

function listarTodos(EstoqueServiceInterface $estoqueService)
{
    return $estoqueService->listarTodos();
}

try {
    if ($idParaEditar) {
        $estoqueParaEditar = $estoqueService->buscarPorId($idParaEditar);
    }

    if (!$idParaEditar) {
        $relatorio = listarTodos($estoqueService);
    }
} catch (Exception $e) {
    $mensagemErro = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descricao = $_POST['descricao'] ?? null;
    $tipo = $_POST['tipo'] ?? null;
    $qtdAtual = $_POST['qtd_atual'] ?? null;
    $ano = $_POST['ano'] ?? null;
    $preco = $_POST['preco'] ?? null;

    $estoque = [
        'descricao' => $descricao,
        'tipo' => $tipo,
        'quantidade_atual' => $qtdAtual,
        'valor_venda' => $preco
    ];

    function processarCadastro(EstoqueServiceInterface $estoqueService, $id, $estoque)
    {
        $estoqueService->atualizarEstoque($id, $estoque);
    }

    try {
        processarCadastro($estoqueService, $idParaEditar, $estoque);
        header ("Location: estoque.php?estoqid=" . $idParaEditar . "&sucesso=1");
        exit;
    } catch (Exception $e) {
        $mensagemErro = $e->getMessage();
    }
}

require '../header.php';
?>

<?php if(!$idParaEditar): ?>

    <h2>Gerenciar Estoque</h2>
    <table border="1" style="width:100%; text-align:left; border-collapse: collapse;">
        <thead>
        <tr style="background-color: #f2f2f2;">
            <th>Item</th>
            <th>Tipo</th>
            <th>Quantidade atual</th>
            <th>Quantidade Minima</th>
            <th>Preço</th>
            <th>Ação</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($relatorio as $estoque): ?>
            <tr>
                <td><?= htmlspecialchars($estoque->descricao) ?></td>
                <td><?= ucfirst($estoque->tipo) ?></td>
                <td style="color: <?= $estoque->alerta ? 'red' : 'black' ?>; font-weight: <?= $estoque->alerta ? 'bold' : 'normal' ?>;">
                    <?= $estoque->quantidade_atual ?>
                </td>
                <td><?=$estoque->quantidade_minima ?></td>
                <td>R$ <?= number_format($estoque->valor_venda, 2, ',', '.') ?></td>
                <td style="padding: 10px">
                    <a href="estoque.php?estoqid=<?= $estoque->estoqid ?>"
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
    <h2>Editando Estoque: <?= $estoqueParaEditar->descricao ?></h2>
    <form method="POST" style="max-width: 400px; border: 1px solid #ccc; padding: 20px;">
        <input type="hidden" name="estoque" value="<?= $estoqueParaEditar->estoqid ?>">

        <p>
            <label>Descrição:</label><br>
            <input type="text" name="descricao" value="<?= $estoqueParaEditar->descricao ?>" style="width: 100%">
        </p>

        <p>
            <label>Tipo:</label><br>
            <input type="text" name="tipo" value="<?= $estoqueParaEditar->tipo ?>" style="width: 100%">
        </p>

        <p>
            <label>Quantidade:</label><br>
            <input type="text" name="qtd_atual" value="<?= $estoqueParaEditar->quantidade_atual ?>" style="width: 100%">
        </p>

        <p>
            <label>Preço:</label><br>
            <input type="text" name="preco" value="<?= $estoqueParaEditar->valor_venda ?>" style="width: 100%" id="cep">
        </p>

        <button type="submit" style="background: green; color: white; border: none; padding: 10px;">Salvar Alterações</button>
        <a href="estoque.php">Voltar para o estoque</a>
    </form>
<?php endif; ?>