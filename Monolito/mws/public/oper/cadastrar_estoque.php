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

$mensagemErro = "";
$mensagemSucesso = "";

$estoqueService = new EstoqueService();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item = $_POST['item'];
    $tipo = $_POST['tipo'];
    $quantidadeAtual = $_POST['qtd_atual'];
    $quantidadeMinima = $_POST['qtd_min'];
    $custo = $_POST['valor_custo'];
    $preco = $_POST['valor_venda'];

    $estoque = [
        "descricao" => $item,
        "tipo" => $tipo,
        "quantidade_atual" => $quantidadeAtual,
        "quantidade_minima" => $quantidadeMinima,
        "valor_custo" => $custo,
        "valor_venda" => $preco
    ];



    function salvarEstoque(EstoqueServiceInterface $estoqueService, $estoque) {
        $estoqueService->salvar($estoque);
    }

    try {
        salvarEstoque($estoqueService, $estoque);

        $mensagemSucesso = "Estoque cadastrado com sucesso!";
    } catch (Exception $e) {
        $mensagemErro = $e->getMessage();
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

<form method="POST">
    <label for="item">Item: </label>
    <input name="item" id="item">


    <label for="tipo">Tipo:</label>
    <select name="tipo" id="tipo" required onchange="toggleCamposEstoque()">
        <option value="">Selecione...</option>
        <option value="peca">Peça (Produto Físico)</option>
        <option value="servico">Serviço (Mão de Obra)</option>
    </select>

    <label for="qtd_atual" class="campo-estoque">Qtd. Atual:</label>
    <input name="qtd_atual" id="qtd_atual" type="number" min="0" value="0" class="campo-estoque">
    <label for="qtd_min" class="campo-estoque">Qtd. Mínima:</label>
    <input name="qtd_min" id="qtd_min" type="number" min="0" value="0" class="campo-estoque">


    <label for="custo">Valor custo: </label>
    <input name="valor_custo" id="custo" type="number" step="0.01" min="0">

    <label for="preco">Preço </label>
    <input name="valor_venda" id="preco" type="number" step="0.01" min="0" required>

    <button type="submit" style="background: green; color: white; border: none; padding: 10px;">Salvar item</button>
    <a href="estoque.php">Voltar para o estoque</a>
</form>

<script>
    function toggleCamposEstoque() {
        const tipo = document.getElementById('tipo').value;
        const campos = document.getElementsByClassName('campo-estoque');
        const displayStatus = (tipo === 'servico') ? 'none' : 'inline-block';
        for (let i = 0; i < campos.length; i++) {
            campos[i].style.display = displayStatus;
        }
    }
</script>
